<?php
/**
 * Gestion de la session utilisateur et contrôle des rôles.
 */
class Auth
{
    public static function connecter(array $utilisateur): void
    {
        session_regenerate_id(true);
        $_SESSION['utilisateur'] = [
            'id'     => (int) $utilisateur['id'],
            'nom'    => $utilisateur['nom'],
            'prenom' => $utilisateur['prenom'],
            'email'  => $utilisateur['email'],
            'role'   => $utilisateur['role'],
        ];
    }

    public static function deconnecter(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?array
    {
        return $_SESSION['utilisateur'] ?? null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function est(string ...$roles): bool
    {
        return self::check() && in_array(self::role(), $roles, true);
    }

    /**
     * Exige un rôle donné. Vérifie aussi en base que le compte n'a pas été
     * suspendu entre-temps par l'administrateur.
     */
    public static function exiger(string ...$roles): array
    {
        if (!self::check()) {
            flash('info', 'Connectez-vous pour accéder à cet espace.');
            $_SESSION['apres_connexion'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect('connexion');
        }

        $compte = (new Utilisateur())->trouver(self::id());
        if (!$compte || $compte['statut'] !== 'actif') {
            self::deconnecter();
            session_start();
            flash('erreur', 'Votre compte est suspendu. Contactez l\'administration GazExpress.');
            redirect('connexion');
        }

        if ($roles && !in_array($compte['role'], $roles, true)) {
            http_response_code(403);
            flash('erreur', 'Cet espace est réservé aux comptes « ' . implode(' / ', $roles) . ' ».');
            redirect('');
        }
        return $compte;
    }
}
