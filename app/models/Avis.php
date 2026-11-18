<?php
class Avis extends Model
{
    public function parCommande(int $commandeId): ?array
    {
        return $this->one('SELECT * FROM avis WHERE commande_id = ?', [$commandeId]);
    }

    public function publier(int $clientId, int $vendeurId, int $commandeId, int $note, string $commentaire): void
    {
        $this->run(
            'INSERT INTO avis (client_id, vendeur_id, commande_id, note, commentaire) VALUES (?, ?, ?, ?, ?)',
            [$clientId, $vendeurId, $commandeId, max(1, min(5, $note)), $commentaire !== '' ? $commentaire : null]
        );
    }

    public function parVendeur(int $vendeurId, int $limite = 5): array
    {
        return $this->all(
            'SELECT a.*, u.prenom, u.nom FROM avis a JOIN utilisateurs u ON u.id = a.client_id
             WHERE a.vendeur_id = ? ORDER BY a.date_avis DESC LIMIT ' . (int) $limite,
            [$vendeurId]
        );
    }
}
