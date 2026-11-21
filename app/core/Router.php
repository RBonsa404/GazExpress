<?php
/**
 * Routeur minimal : associe un chemin + une méthode HTTP à « Controleur@action ».
 */
class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $chemin, string $cible): void
    {
        $this->routes['GET'][trim($chemin, '/')] = $cible;
    }

    public function post(string $chemin, string $cible): void
    {
        $this->routes['POST'][trim($chemin, '/')] = $cible;
    }

    public function dispatch(string $url, string $methode): void
    {
        $chemin  = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $methode = strtoupper($methode);

        if (!isset($this->routes[$methode][$chemin])) {
            http_response_code(404);
            (new Controller())->render('erreurs/404', ['titre' => 'Page introuvable']);
            return;
        }

        if ($methode === 'POST') {
            verifier_csrf();
        }

        [$classe, $action] = explode('@', $this->routes[$methode][$chemin]);

        try {
            (new $classe())->$action();
        } catch (PDOException $e) {
            error_log('[GazExpress] ' . $e->getMessage());
            http_response_code(500);
            (new Controller())->render('erreurs/500', [
                'titre'   => 'Service indisponible',
                'message' => APP_DEBUG
                    ? 'La base de données est inaccessible. Vérifiez que MySQL est démarré, que la base a été importée et que config/env.php est correct.'
                    : 'Le service est momentanément indisponible. Merci de réessayer dans quelques minutes.',
            ]);
        }
    }
}
