<?php
class Vendeur extends Model
{
    private const SELECT = "SELECT v.*, q.nom AS quartier, u.nom AS gerant_nom, u.prenom AS gerant_prenom, u.email, u.statut AS statut_compte,
                (SELECT COALESCE(SUM(s.quantite),0) FROM stocks s WHERE s.vendeur_id = v.id) AS total_stock,
                (SELECT COUNT(*) FROM stocks s WHERE s.vendeur_id = v.id AND s.quantite >= " . SEUIL_STOCK_LIMITE . ") AS refs_disponibles,
                (SELECT ROUND(AVG(a.note),1) FROM avis a WHERE a.vendeur_id = v.id) AS note_moyenne,
                (SELECT COUNT(*) FROM avis a WHERE a.vendeur_id = v.id) AS nb_avis
            FROM vendeurs v
            JOIN quartiers q ON q.id = v.quartier_id
            JOIN utilisateurs u ON u.id = v.utilisateur_id";

    public function trouver(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE v.id = ?', [$id]);
    }

    public function parUtilisateur(int $utilisateurId): ?array
    {
        return $this->one(self::SELECT . ' WHERE v.utilisateur_id = ?', [$utilisateurId]);
    }

    /** Vendeurs visibles par les clients : validés et dont le compte est actif. */
    public function actifs(): array
    {
        return $this->all(self::SELECT . " WHERE v.statut_validation = 'valide' AND u.statut = 'actif' ORDER BY v.nom_commerce");
    }

    public function enAttente(): array
    {
        return $this->all(self::SELECT . " WHERE v.statut_validation = 'en_attente' ORDER BY u.date_creation");
    }

    public function tous(): array
    {
        return $this->all(self::SELECT . ' ORDER BY v.nom_commerce');
    }

    public function creer(int $utilisateurId, array $d): int
    {
        return $this->insert(
            'INSERT INTO vendeurs (utilisateur_id, nom_commerce, quartier_id, adresse, telephone, latitude, longitude,
                                   heure_ouverture, heure_fermeture, numero_autorisation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $utilisateurId, $d['nom_commerce'], $d['quartier_id'], $d['adresse_commerce'], $d['telephone'],
                $d['latitude'], $d['longitude'], $d['heure_ouverture'], $d['heure_fermeture'], $d['numero_autorisation'],
            ]
        );
    }

    public function basculerOuverture(int $id): void
    {
        $this->run('UPDATE vendeurs SET est_ouvert = 1 - est_ouvert WHERE id = ?', [$id]);
    }

    public function valider(int $id, string $decision): void
    {
        $this->run('UPDATE vendeurs SET statut_validation = ?, date_validation = NOW() WHERE id = ?', [$decision, $id]);
    }

    /** État global affiché sur la carte : disponible / limité / indisponible. */
    public static function etatGlobal(array $v): array
    {
        if ((int) $v['total_stock'] <= 0) {
            return etat_stock(0);
        }
        return (int) $v['refs_disponibles'] > 0 ? etat_stock(SEUIL_STOCK_LIMITE) : etat_stock(1);
    }

    public static function estOuvertMaintenant(array $v): bool
    {
        $h = date('H:i:s');
        return (bool) $v['est_ouvert'] && $h >= $v['heure_ouverture'] && $h <= $v['heure_fermeture'];
    }

    public function statistiques(int $id): array
    {
        $jour = $this->one(
            "SELECT COUNT(*) AS commandes_jour, COALESCE(SUM(montant_articles),0) AS ca_jour
             FROM commandes WHERE vendeur_id = ? AND DATE(date_commande) = CURDATE() AND statut <> 'annulee'",
            [$id]
        );
        $jour['bouteilles']   = (int) $this->value('SELECT COALESCE(SUM(quantite),0) FROM stocks WHERE vendeur_id = ?', [$id]);
        $jour['ca_total']     = (int) $this->value("SELECT COALESCE(SUM(montant_articles),0) FROM commandes WHERE vendeur_id = ? AND statut = 'livree'", [$id]);
        return $jour;
    }
}
