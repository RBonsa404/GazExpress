<?php
/**
 * Configuration générale de GazExpress.
 * Les réglages propres au serveur (base de données, mode debug) sont lus dans
 * config/env.php, ou à défaut dans config/env.example.php (valeurs XAMPP).
 */

$env = require (is_file(__DIR__ . '/env.php') ? __DIR__ . '/env.php' : __DIR__ . '/env.example.php');

define('APP_NAME', 'GazExpress');
define('APP_CITY', 'Ouagadougou, Burkina Faso');
define('APP_CITY_COURT', 'Ouagadougou');
define('APP_TELEPHONE', $env['telephone'] ?? '+226 25 00 00 00');
define('APP_EMAIL', $env['email'] ?? 'contact@gazexpress.bf');
define('APP_DEBUG', (bool) ($env['debug'] ?? false));
define('APP_DEMO', (bool) ($env['comptes_demo'] ?? false));

define('DB_HOST', $env['db_host'] ?? 'localhost');
define('DB_PORT', (string) ($env['db_port'] ?? '3306'));
define('DB_NAME', $env['db_name'] ?? 'gazexpress');
define('DB_USER', $env['db_user'] ?? 'root');
define('DB_PASS', $env['db_pass'] ?? '');
unset($env);

// Erreurs : affichées en développement, journalisées seulement en production
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// Seuil en dessous duquel un stock est affiché « Stock limité » (moins de 5 bouteilles)
define('SEUIL_STOCK_LIMITE', 5);

// Rayon (km) dans lequel un livreur voit les courses disponibles
define('RAYON_COURSES_KM', 10);

// URL de base calculée automatiquement : « /GazExpress » sous XAMPP, « » à la racine d'un domaine
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $base);
unset($base);

// Connexion chiffrée (HTTPS) : sert à sécuriser le cookie de session
define('EN_HTTPS', ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['SERVER_PORT'] ?? '') === '443');

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

date_default_timezone_set('Africa/Ouagadougou');
