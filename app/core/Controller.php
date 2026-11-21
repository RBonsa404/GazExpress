<?php
/**
 * Contrôleur de base : rendu des vues dans le gabarit commun et réponses JSON.
 */
class Controller
{
    public function render(string $vue, array $donnees = [], string $gabarit = 'layout'): void
    {
        $donnees['page'] = $donnees['page'] ?? '';
        extract($donnees, EXTR_SKIP);

        ob_start();
        require APP_PATH . "/views/$vue.php";
        $contenu = ob_get_clean();

        require APP_PATH . "/views/partials/$gabarit.php";
    }

    protected function json($donnees, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    }

    protected function entier(string $cle, ?array $source = null): int
    {
        $source = $source ?? $_POST;
        return (int) filter_var($source[$cle] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
    }

    protected function texte(string $cle, int $max = 255): string
    {
        return mb_substr(trim((string) ($_POST[$cle] ?? '')), 0, $max);
    }
}
