<?php
class Reclamation extends Model
{
    public function creer(int $commandeId, int $clientId, string $objet, string $message): int
    {
        return $this->insert(
            'INSERT INTO reclamations (commande_id, client_id, objet, message) VALUES (?, ?, ?, ?)',
            [$commandeId, $clientId, $objet, $message]
        );
    }

    public function parCommande(int $commandeId): array
    {
        return $this->all('SELECT * FROM reclamations WHERE commande_id = ? ORDER BY date_creation DESC', [$commandeId]);
    }

    public function toutes(): array
    {
        return $this->all(
            "SELECT r.*, c.reference, u.prenom, u.nom, u.telephone FROM reclamations r
             JOIN commandes c ON c.id = r.commande_id JOIN utilisateurs u ON u.id = r.client_id
             ORDER BY r.statut = 'ouverte' DESC, r.date_creation DESC"
        );
    }

    public function trouver(int $id): ?array
    {
        return $this->one('SELECT * FROM reclamations WHERE id = ?', [$id]);
    }

    public function traiter(int $id, string $reponse): void
    {
        $this->run("UPDATE reclamations SET statut = 'traitee', reponse = ?, date_traitement = NOW() WHERE id = ?", [$reponse, $id]);
    }

    public function ouvertes(): int
    {
        return (int) $this->value("SELECT COUNT(*) FROM reclamations WHERE statut = 'ouverte'");
    }
}
