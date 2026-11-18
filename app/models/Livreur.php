<?php
class Livreur extends Model
{
    private const SELECT = 'SELECT l.*, u.nom, u.prenom, u.telephone, u.email, u.statut AS statut_compte, u.quartier_id,
                q.nom AS quartier, q.latitude AS q_lat, q.longitude AS q_lon, u.date_creation
            FROM livreurs l
            JOIN utilisateurs u ON u.id = l.utilisateur_id
            LEFT JOIN quartiers q ON q.id = u.quartier_id';

    public function parUtilisateur(int $utilisateurId): ?array
    {
        return $this->one(self::SELECT . ' WHERE l.utilisateur_id = ?', [$utilisateurId]);
    }

    public function trouver(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE l.id = ?', [$id]);
    }

    public function enAttente(): array
    {
        return $this->all(self::SELECT . " WHERE l.statut_validation = 'en_attente' ORDER BY u.date_creation");
    }

    public function creer(int $utilisateurId, array $d): int
    {
        return $this->insert(
            'INSERT INTO livreurs (utilisateur_id, type_engin, immatriculation, numero_piece) VALUES (?, ?, ?, ?)',
            [$utilisateurId, $d['type_engin'], $d['immatriculation'] ?: null, $d['numero_piece']]
        );
    }

    public function basculerDisponibilite(int $id): void
    {
        $this->run('UPDATE livreurs SET disponible = 1 - disponible WHERE id = ?', [$id]);
    }

    public function valider(int $id, string $decision): void
    {
        $this->run('UPDATE livreurs SET statut_validation = ?, date_validation = NOW() WHERE id = ?', [$decision, $id]);
    }

    public function statistiques(int $id): array
    {
        return $this->one(
            "SELECT
                (SELECT COUNT(*) FROM livraisons WHERE livreur_id = :a AND statut = 'livree') AS courses,
                (SELECT COUNT(*) FROM livraisons WHERE livreur_id = :b AND statut = 'livree' AND DATE(date_livraison) = CURDATE()) AS courses_jour,
                (SELECT COALESCE(SUM(frais),0) FROM livraisons WHERE livreur_id = :c AND statut = 'livree' AND DATE(date_livraison) = CURDATE()) AS gains_jour,
                (SELECT ROUND(AVG(note_livreur),1) FROM livraisons WHERE livreur_id = :d AND note_livreur IS NOT NULL) AS note",
            ['a' => $id, 'b' => $id, 'c' => $id, 'd' => $id]
        );
    }
}
