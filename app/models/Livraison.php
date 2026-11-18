<?php
class Livraison extends Model
{
    private const SELECT = "SELECT l.*, c.reference, c.statut AS statut_commande, c.client_id, c.vendeur_id, c.montant_total,
                v.nom_commerce, v.adresse AS vendeur_adresse, v.telephone AS vendeur_tel, v.latitude AS v_lat, v.longitude AS v_lon,
                q.nom AS quartier, u.prenom AS client_prenom, u.nom AS client_nom, u.telephone AS client_tel,
                p.mode_paiement, p.statut AS statut_paiement
            FROM livraisons l
            JOIN commandes c ON c.id = l.commande_id
            JOIN vendeurs v ON v.id = c.vendeur_id
            JOIN quartiers q ON q.id = l.quartier_id
            JOIN utilisateurs u ON u.id = c.client_id
            LEFT JOIN paiements p ON p.commande_id = c.id";

    public function trouver(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE l.id = ?', [$id]);
    }

    /** Course en cours d'un livreur (une seule à la fois). */
    public function enCours(int $livreurId): ?array
    {
        return $this->one(self::SELECT . " WHERE l.livreur_id = ? AND l.statut IN ('acceptee','en_route') ORDER BY l.date_prise LIMIT 1", [$livreurId]);
    }

    /**
     * Courses que le vendeur a acceptées et qu'aucun livreur n'a encore prises,
     * triées par distance entre le livreur (son quartier) et le point de retrait.
     */
    public function disponibles(?float $lat, ?float $lon): array
    {
        $courses = $this->all(self::SELECT . " WHERE l.statut = 'disponible' AND l.livreur_id IS NULL
                AND c.statut IN ('acceptee','en_preparation') ORDER BY c.date_commande");
        foreach ($courses as &$c) {
            $c['distance_livreur'] = ($lat !== null) ? distance_km($lat, $lon, (float) $c['v_lat'], (float) $c['v_lon']) : null;
        }
        unset($c);
        $courses = array_values(array_filter($courses, fn ($c) => $c['distance_livreur'] === null || $c['distance_livreur'] <= RAYON_COURSES_KM));
        usort($courses, fn ($a, $b) => ($a['distance_livreur'] ?? 0) <=> ($b['distance_livreur'] ?? 0));
        return $courses;
    }

    public function historique(int $livreurId, int $limite = 10): array
    {
        return $this->all(self::SELECT . " WHERE l.livreur_id = ? AND l.statut = 'livree' ORDER BY l.date_livraison DESC LIMIT " . (int) $limite, [$livreurId]);
    }

    /** Prise de la course : mise à jour atomique pour éviter qu'un autre livreur la prenne. */
    public function accepter(int $id, int $livreurId): bool
    {
        return $this->run(
            "UPDATE livraisons l JOIN commandes c ON c.id = l.commande_id
             SET l.livreur_id = ?, l.statut = 'acceptee', l.date_prise = NOW()
             WHERE l.id = ? AND l.statut = 'disponible' AND l.livreur_id IS NULL AND c.statut IN ('acceptee','en_preparation')",
            [$livreurId, $id]
        ) === 1;
    }

    public function marquerEnRoute(int $id): void
    {
        $this->run("UPDATE livraisons SET statut = 'en_route' WHERE id = ?", [$id]);
    }

    public function marquerLivree(int $id): void
    {
        $this->run("UPDATE livraisons SET statut = 'livree', date_livraison = NOW() WHERE id = ?", [$id]);
    }

    public function noter(int $commandeId, int $note): void
    {
        $this->run("UPDATE livraisons SET note_livreur = ? WHERE commande_id = ? AND statut = 'livree' AND note_livreur IS NULL", [$note, $commandeId]);
    }
}
