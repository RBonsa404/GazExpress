<?php
/**
 * Fonctions utilitaires partagées par les contrôleurs et les vues.
 */

/** Échappement HTML (protection XSS). */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $chemin = ''): string
{
    return BASE_URL . '/' . ltrim($chemin, '/');
}

function asset(string $chemin): string
{
    $fichier = ROOT_PATH . '/public/' . ltrim($chemin, '/');
    $version = is_file($fichier) ? filemtime($fichier) : 1;
    return BASE_URL . '/public/' . ltrim($chemin, '/') . '?v=' . $version;
}

function redirect(string $chemin): void
{
    header('Location: ' . (str_starts_with($chemin, 'http') || str_starts_with($chemin, '/') ? $chemin : url($chemin)));
    exit;
}

/** Retour à la page précédente (même origine uniquement). */
function redirect_back(string $defaut = '', string $ancre = ''): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $hote = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $hote, PHP_URL_HOST)) {
        $cible = strtok($ref, '#');
    } else {
        $cible = url($defaut);
    }
    header('Location: ' . $cible . $ancre);
    exit;
}

/* ---------- Messages flash ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $liste = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $liste;
}

/** Conserve les saisies d'un formulaire après une erreur de validation. */
function old(string $cle, string $defaut = ''): string
{
    return e($_SESSION['old'][$cle] ?? $defaut);
}

/* ---------- Jeton CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verifier_csrf(): void
{
    $jeton = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($jeton) || !hash_equals(csrf_token(), $jeton)) {
        http_response_code(403);
        exit('Session expirée ou requête invalide. Rechargez la page et réessayez.');
    }
}

/* ---------- Formatage ---------- */

function fcfa($montant): string
{
    return number_format((float) $montant, 0, ',', "\u{202F}") . ' FCFA';
}

function date_fr(?string $date, bool $avecHeure = true): string
{
    if (!$date) {
        return '—';
    }
    $mois = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($date);
    $txt = date('j', $t) . ' ' . $mois[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $avecHeure ? $txt . ' à ' . date('H:i', $t) : $txt;
}

function heure(?string $time): string
{
    return $time ? substr($time, 0, 5) : '';
}

function capacite($kg): string
{
    return rtrim(rtrim(number_format((float) $kg, 1, '.', ''), '0'), '.') . ' kg';
}

/* ---------- Règles métier d'affichage ---------- */

/** État d'un stock calculé à partir de la quantité (jamais stocké en base). */
function etat_stock(int $quantite): array
{
    if ($quantite <= 0) {
        return ['cle' => 'indisponible', 'libelle' => 'Indisponible'];
    }
    if ($quantite < SEUIL_STOCK_LIMITE) {
        return ['cle' => 'limite', 'libelle' => 'Stock limité'];
    }
    return ['cle' => 'disponible', 'libelle' => 'Disponible'];
}

function statuts_commande(): array
{
    return [
        'en_attente'     => ['libelle' => 'En attente',     'classe' => 'limite',       'icone' => 'fa-clock'],
        'acceptee'       => ['libelle' => 'Acceptée',       'classe' => 'disponible',   'icone' => 'fa-check'],
        'en_preparation' => ['libelle' => 'En préparation', 'classe' => 'limite',       'icone' => 'fa-box-open'],
        'en_livraison'   => ['libelle' => 'En livraison',   'classe' => 'info',         'icone' => 'fa-motorcycle'],
        'livree'         => ['libelle' => 'Livrée',         'classe' => 'disponible',   'icone' => 'fa-house-circle-check'],
        'annulee'        => ['libelle' => 'Annulée',        'classe' => 'indisponible', 'icone' => 'fa-xmark'],
    ];
}

function statut_commande(string $cle): array
{
    return statuts_commande()[$cle] ?? ['libelle' => $cle, 'classe' => 'neutre', 'icone' => 'fa-circle'];
}

function modes_paiement(): array
{
    return [
        'orange_money' => 'Orange Money Burkina',
        'moov_money'   => 'Moov Money',
        'especes'      => 'Espèces à la livraison / au retrait',
    ];
}

function statut_paiement(string $cle): string
{
    return ['en_attente' => 'À régler', 'valide' => 'Validé', 'rembourse' => 'Remboursé', 'echoue' => 'Non abouti'][$cle] ?? $cle;
}

/** Distance à vol d'oiseau en kilomètres (formule de Haversine). */
function distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
}

function initiales(string $prenom, string $nom): string
{
    return mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
}
