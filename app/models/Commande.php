<?php
/**
 * Commande : création transactionnelle, cycle de vie des statuts,
 * gestion du stock réel et traçabilité.
 */
class Commande extends Model
{
    /** Transitions autorisées du cycle de vie. */
    public const TRANSITIONS = [
        'en_attente'     => ['acceptee', 'annulee'],
        'acceptee'       => ['en_preparation', 'en_livraison', 'livree', 'annulee'],
        'en_preparation' => ['en_livraison', 'livree', 'annulee'],
        'en_livraison'   => ['livree', 'annulee'],
        'livree'         => [],
        'annulee'        => [],
    ];

    private const SELECT = "SELECT c.*, v.nom_commerce, v.telephone AS vendeur_tel, v.adresse AS vendeur_adresse,
                v.latitude AS vendeur_lat, v.longitude AS vendeur_lon, v.utilisateur_id AS vendeur_user_id,
                qv.nom AS vendeur_quartier,
                u.nom AS client_nom, u.prenom AS client_prenom, u.telephone AS client_tel,
                p.mode_paiement, p.statut AS statut_paiement, p.reference_transaction,
                l.id AS livraison_id, l.statut AS statut_livraison, l.adresse_livraison, l.frais AS frais_livraison_l,
                l.distance_km, l.note_livreur, l.livreur_id, ql.nom AS quartier_livraison,
                lu.prenom AS livreur_prenom, lu.nom AS livreur_nom, lu.telephone AS livreur_tel
            FROM commandes c
            JOIN vendeurs v      ON v.id = c.vendeur_id
            JOIN quartiers qv    ON qv.id = v.quartier_id
            JOIN utilisateurs u  ON u.id = c.client_id
            LEFT JOIN paiements p  ON p.commande_id = c.id
            LEFT JOIN livraisons l ON l.commande_id = c.id
            LEFT JOIN quartiers ql ON ql.id = l.quartier_id
            LEFT JOIN livreurs lv  ON lv.id = l.livreur_id
            LEFT JOIN utilisateurs lu ON lu.id = lv.utilisateur_id";

    public function trouver(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE c.id = ?', [$id]);
    }

    public function details(int $commandeId): array
    {
        return $this->all(
            'SELECT d.*, p.designation, p.marque, p.capacite_kg
             FROM details_commande d JOIN produits p ON p.id = d.produit_id WHERE d.commande_id = ?',
            [$commandeId]
        );
    }

    /** Ajoute la liste des articles à chaque commande (affichage des listes). */
    public function avecDetails(array $commandes): array
    {
        if (!$commandes) {
            return [];
        }
        $ids = array_column($commandes, 'id');
        $marques = implode(',', array_fill(0, count($ids), '?'));
        $lignes = $this->all(
            "SELECT d.commande_id, d.quantite, d.prix_unitaire, p.marque, p.capacite_kg
             FROM details_commande d JOIN produits p ON p.id = d.produit_id WHERE d.commande_id IN ($marques)",
            $ids
        );
        $parCommande = [];
        foreach ($lignes as $l) {
            $parCommande[$l['commande_id']][] = $l;
        }
        foreach ($commandes as &$c) {
            $c['articles'] = $parCommande[$c['id']] ?? [];
            $c['resume'] = implode(', ', array_map(
                fn ($a) => $a['quantite'] . 'x ' . $a['marque'] . ' ' . capacite($a['capacite_kg']),
                $c['articles']
            ));
        }
        return $commandes;
    }

    public function parClient(int $clientId): array
    {
        return $this->avecDetails($this->all(self::SELECT . ' WHERE c.client_id = ? ORDER BY c.date_commande DESC', [$clientId]));
    }

    public function parVendeur(int $vendeurId, array $statuts = [], int $limite = 50): array
    {
        $sql = self::SELECT . ' WHERE c.vendeur_id = ?';
        $p = [$vendeurId];
        if ($statuts) {
            $sql .= ' AND c.statut IN (' . implode(',', array_fill(0, count($statuts), '?')) . ')';
            $p = array_merge($p, $statuts);
        }
        return $this->avecDetails($this->all($sql . ' ORDER BY c.date_commande DESC LIMIT ' . (int) $limite, $p));
    }

    public function toutes(string $statut = '', int $limite = 100): array
    {
        $sql = self::SELECT;
        $p = [];
        if ($statut !== '') {
            $sql .= ' WHERE c.statut = ?';
            $p[] = $statut;
        }
        return $this->avecDetails($this->all($sql . ' ORDER BY c.date_commande DESC LIMIT ' . (int) $limite, $p));
    }

    public function historique(int $commandeId): array
    {
        return $this->all(
            'SELECT h.*, u.prenom, u.nom, u.role FROM historique_statuts h
             LEFT JOIN utilisateurs u ON u.id = h.auteur_id WHERE h.commande_id = ? ORDER BY h.date_changement, h.id',
            [$commandeId]
        );
    }

    /**
     * Création d'une commande à partir du panier.
     * Le stock est vérifié (verrou FOR UPDATE) mais n'est décrémenté
     * qu'à l'acceptation par le vendeur, conformément au cahier des charges.
     *
     * @throws RuntimeException si une règle métier n'est pas respectée
     */
    public function creer(int $clientId, int $vendeurId, array $lignes, string $mode, ?array $quartier, string $adresse, string $modePaiement): int
    {
        $this->db->beginTransaction();
        try {
            $vendeur = $this->one(
                "SELECT v.*, u.statut FROM vendeurs v JOIN utilisateurs u ON u.id = v.utilisateur_id WHERE v.id = ?",
                [$vendeurId]
            );
            if (!$vendeur || $vendeur['statut_validation'] !== 'valide' || $vendeur['statut'] !== 'actif') {
                throw new RuntimeException('Ce vendeur n\'est plus disponible sur la plateforme.');
            }
            if (!$vendeur['est_ouvert']) {
                throw new RuntimeException('La boutique « ' . $vendeur['nom_commerce'] . ' » est actuellement fermée.');
            }

            $montant = 0;
            $articles = [];
            foreach ($lignes as $produitId => $qte) {
                $stock = $this->one(
                    'SELECT s.quantite, s.prix, p.designation FROM stocks s JOIN produits p ON p.id = s.produit_id
                     WHERE s.vendeur_id = ? AND s.produit_id = ? FOR UPDATE',
                    [$vendeurId, $produitId]
                );
                if (!$stock) {
                    throw new RuntimeException('Un produit de votre panier n\'est plus proposé par ce vendeur.');
                }
                if ($qte > (int) $stock['quantite']) {
                    throw new RuntimeException('Stock insuffisant pour « ' . $stock['designation'] . ' » (' . (int) $stock['quantite'] . ' disponible(s)).');
                }
                $montant += $qte * (int) $stock['prix'];
                $articles[] = [$produitId, $qte, (int) $stock['prix']];
            }

            $frais = $mode === 'livraison' ? (int) $quartier['frais_livraison'] : 0;
            $total = $montant + $frais;

            $id = $this->insert(
                'INSERT INTO commandes (reference, client_id, vendeur_id, montant_articles, frais_livraison, montant_total, mode_recuperation)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['TMP' . bin2hex(random_bytes(4)), $clientId, $vendeurId, $montant, $frais, $total, $mode]
            );
            $reference = 'GX-' . (8490 + $id);
            $this->run('UPDATE commandes SET reference = ? WHERE id = ?', [$reference, $id]);

            foreach ($articles as [$produitId, $qte, $prix]) {
                $this->run(
                    'INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)',
                    [$id, $produitId, $qte, $prix]
                );
            }

            if ($mode === 'livraison') {
                $distance = distance_km((float) $vendeur['latitude'], (float) $vendeur['longitude'], (float) $quartier['latitude'], (float) $quartier['longitude']);
                $this->run(
                    'INSERT INTO livraisons (commande_id, quartier_id, adresse_livraison, latitude, longitude, distance_km, frais)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$id, $quartier['id'], $adresse, $quartier['latitude'], $quartier['longitude'], $distance, $frais]
                );
            }

            // Mobile Money : paiement simulé et validé immédiatement dans cette version
            $mobile = in_array($modePaiement, ['orange_money', 'moov_money'], true);
            $this->run(
                'INSERT INTO paiements (commande_id, montant, mode_paiement, statut, reference_transaction, date_paiement) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $id, $total, $modePaiement, $mobile ? 'valide' : 'en_attente',
                    $mobile ? strtoupper(substr($modePaiement, 0, 1)) . 'M' . date('ymd.Hi') . '.' . strtoupper(bin2hex(random_bytes(2))) : null,
                    $mobile ? date('Y-m-d H:i:s') : null,
                ]
            );

            $this->tracer($id, null, 'en_attente', $clientId);
            (new Notification())->envoyer((int) $vendeur['utilisateur_id'], "Nouvelle commande $reference en attente de validation.", 'vendeur', $id);

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Acceptation par le vendeur : contrôle du stock réel puis décrément.
     */
    public function accepter(int $id, int $vendeurId, int $auteurId): void
    {
        $this->db->beginTransaction();
        try {
            $c = $this->one('SELECT * FROM commandes WHERE id = ? AND vendeur_id = ? FOR UPDATE', [$id, $vendeurId]);
            if (!$c || $c['statut'] !== 'en_attente') {
                throw new RuntimeException('Cette commande ne peut plus être acceptée.');
            }
            foreach ($this->details($id) as $d) {
                $q = (int) $this->value('SELECT quantite FROM stocks WHERE vendeur_id = ? AND produit_id = ? FOR UPDATE', [$vendeurId, $d['produit_id']]);
                if ($q < (int) $d['quantite']) {
                    throw new RuntimeException('Stock réel insuffisant pour « ' . $d['designation'] . ' ». Mettez votre stock à jour ou refusez la commande.');
                }
                $this->run('UPDATE stocks SET quantite = quantite - ? WHERE vendeur_id = ? AND produit_id = ?', [$d['quantite'], $vendeurId, $d['produit_id']]);
            }
            $this->changerStatutInterne($c, 'acceptee', $auteurId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Annulation (refus vendeur, annulation client ou administrateur).
     * Le stock est restitué si la commande avait déjà été acceptée.
     */
    public function annuler(int $id, int $auteurId, string $motif): void
    {
        $this->db->beginTransaction();
        try {
            $c = $this->one('SELECT * FROM commandes WHERE id = ? FOR UPDATE', [$id]);
            if (!$c || !in_array('annulee', self::TRANSITIONS[$c['statut']], true)) {
                throw new RuntimeException('Cette commande ne peut plus être annulée.');
            }
            if ($c['statut'] !== 'en_attente') {
                foreach ($this->details($id) as $d) {
                    $this->run('UPDATE stocks SET quantite = quantite + ? WHERE vendeur_id = ? AND produit_id = ?', [$d['quantite'], $c['vendeur_id'], $d['produit_id']]);
                }
            }
            $this->run('UPDATE commandes SET motif_annulation = ? WHERE id = ?', [$motif, $id]);
            $this->run("UPDATE livraisons SET statut = 'annulee' WHERE commande_id = ?", [$id]);
            $this->run("UPDATE paiements SET statut = IF(statut = 'valide', 'rembourse', 'echoue') WHERE commande_id = ?", [$id]);
            $this->changerStatutInterne($c, 'annulee', $auteurId, $motif);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Changement de statut simple, avec contrôle de la transition. */
    public function changerStatut(int $id, string $nouveau, int $auteurId): void
    {
        $c = $this->one('SELECT * FROM commandes WHERE id = ?', [$id]);
        if (!$c || !in_array($nouveau, self::TRANSITIONS[$c['statut']] ?? [], true)) {
            throw new RuntimeException('Changement de statut non autorisé.');
        }
        $this->changerStatutInterne($c, $nouveau, $auteurId);
    }

    private function changerStatutInterne(array $c, string $nouveau, int $auteurId, string $motif = ''): void
    {
        $cloture = in_array($nouveau, ['livree', 'annulee'], true) ? ', date_cloture = NOW()' : '';
        $this->run("UPDATE commandes SET statut = ?$cloture WHERE id = ?", [$nouveau, $c['id']]);

        if ($nouveau === 'livree') {
            $this->run("UPDATE paiements SET statut = 'valide', date_paiement = COALESCE(date_paiement, NOW()) WHERE commande_id = ?", [$c['id']]);
        }
        $this->tracer((int) $c['id'], $c['statut'], $nouveau, $auteurId);

        $vendeur = $this->value('SELECT nom_commerce FROM vendeurs WHERE id = ?', [$c['vendeur_id']]);
        $messages = [
            'acceptee'       => "Bonne nouvelle : $vendeur a accepté votre commande {$c['reference']}.",
            'en_preparation' => "Votre commande {$c['reference']} est en préparation chez $vendeur.",
            'en_livraison'   => "Le livreur est parti avec votre commande {$c['reference']}.",
            'livree'         => $c['mode_recuperation'] === 'retrait'
                                ? "Commande {$c['reference']} remise. Merci d'avoir choisi GazExpress !"
                                : "Commande {$c['reference']} livrée. Merci d'avoir choisi GazExpress !",
            'annulee'        => "Votre commande {$c['reference']} a été annulée" . ($motif ? " ($motif)." : '.'),
        ];
        if (isset($messages[$nouveau]) && (int) $auteurId !== (int) $c['client_id']) {
            (new Notification())->envoyer((int) $c['client_id'], $messages[$nouveau], 'commandes/suivi?id=' . $c['id'], (int) $c['id']);
        }
    }

    private function tracer(int $id, ?string $ancien, string $nouveau, int $auteurId): void
    {
        $this->run(
            'INSERT INTO historique_statuts (commande_id, ancien_statut, nouveau_statut, auteur_id) VALUES (?, ?, ?, ?)',
            [$id, $ancien, $nouveau, $auteurId]
        );
    }

    public function statistiquesGlobales(): array
    {
        return $this->one(
            "SELECT COUNT(*) AS total,
                    SUM(DATE(date_commande) = CURDATE()) AS jour,
                    SUM(statut IN ('en_attente','acceptee','en_preparation','en_livraison')) AS en_cours,
                    SUM(statut = 'livree') AS livrees,
                    SUM(statut = 'annulee') AS annulees,
                    COALESCE(SUM(CASE WHEN statut = 'livree' THEN montant_total END),0) AS volume,
                    COALESCE(SUM(CASE WHEN statut = 'livree' THEN montant_articles END),0) * 0.05 AS commission
             FROM commandes"
        );
    }

    /** Commandes par jour sur les 7 derniers jours (tableau de bord admin). */
    public function parJour(int $jours = 7): array
    {
        $lignes = $this->all(
            'SELECT DATE(date_commande) AS jour, COUNT(*) AS n FROM commandes
             WHERE date_commande >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(date_commande)',
            [$jours - 1]
        );
        $index = array_column($lignes, 'n', 'jour');
        $res = [];
        for ($i = $jours - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $res[$d] = (int) ($index[$d] ?? 0);
        }
        return $res;
    }
}
