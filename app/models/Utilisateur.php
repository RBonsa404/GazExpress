<?php
class Utilisateur extends Model
{
    public function trouver(?int $id): ?array
    {
        return $id ? $this->one('SELECT * FROM utilisateurs WHERE id = ?', [$id]) : null;
    }

    public function parIdentifiant(string $identifiant): ?array
    {
        return $this->one(
            'SELECT * FROM utilisateurs WHERE email = ? OR telephone = ? LIMIT 1',
            [mb_strtolower($identifiant), $identifiant]
        );
    }

    public function existe(string $email, string $telephone): bool
    {
        return (bool) $this->value('SELECT COUNT(*) FROM utilisateurs WHERE email = ? OR telephone = ?', [$email, $telephone]);
    }

    public function creer(array $d): int
    {
        return $this->insert(
            'INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, role, quartier_id, adresse)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                mb_strtoupper($d['nom']), $d['prenom'], $d['telephone'], mb_strtolower($d['email']),
                password_hash($d['mot_de_passe'], PASSWORD_DEFAULT), $d['role'],
                $d['quartier_id'] ?: null, $d['adresse'] ?: null,
            ]
        );
    }

    public function enregistrerConnexion(int $id): void
    {
        $this->run('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?', [$id]);
    }

    public function majMotDePasseSiNecessaire(int $id, string $clair, string $hash): void
    {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->run('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?', [password_hash($clair, PASSWORD_DEFAULT), $id]);
        }
    }

    public function changerStatut(int $id, string $statut): void
    {
        $this->run("UPDATE utilisateurs SET statut = ? WHERE id = ? AND role <> 'admin'", [$statut, $id]);
    }

    public function liste(string $role = '', string $recherche = ''): array
    {
        $sql = 'SELECT u.*, q.nom AS quartier FROM utilisateurs u LEFT JOIN quartiers q ON q.id = u.quartier_id WHERE 1';
        $p = [];
        if ($role !== '') {
            $sql .= ' AND u.role = ?';
            $p[] = $role;
        }
        if ($recherche !== '') {
            $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ?)';
            array_push($p, "%$recherche%", "%$recherche%", "%$recherche%", "%$recherche%");
        }
        return $this->all($sql . ' ORDER BY u.date_creation DESC', $p);
    }

    public function compterParRole(): array
    {
        $lignes = $this->all('SELECT role, COUNT(*) n FROM utilisateurs GROUP BY role');
        return array_column($lignes, 'n', 'role');
    }

    public function admins(): array
    {
        return $this->all("SELECT id FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'");
    }
}
