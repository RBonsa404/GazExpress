<?php
class Notification extends Model
{
    public function envoyer(int $utilisateurId, string $message, ?string $lien = null, ?int $commandeId = null): void
    {
        $this->run(
            'INSERT INTO notifications (utilisateur_id, commande_id, message, lien) VALUES (?, ?, ?, ?)',
            [$utilisateurId, $commandeId, $message, $lien]
        );
    }

    public function recentes(int $utilisateurId, int $limite = 8): array
    {
        return $this->all(
            'SELECT id, message, lien, est_lue, date_creation FROM notifications
             WHERE utilisateur_id = ? ORDER BY date_creation DESC, id DESC LIMIT ' . (int) $limite,
            [$utilisateurId]
        );
    }

    public function nonLues(int $utilisateurId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND est_lue = 0', [$utilisateurId]);
    }

    public function marquerLues(int $utilisateurId): void
    {
        $this->run('UPDATE notifications SET est_lue = 1 WHERE utilisateur_id = ? AND est_lue = 0', [$utilisateurId]);
    }
}
