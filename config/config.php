<?php
/**
 * Configuration générale de GazExpress.
 * Adapter les identifiants MySQL si votre installation XAMPP diffère.
 */

define('APP_NAME', 'GazExpress');
define('APP_CITY', 'Ouagadougou, Burkina Faso');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'gazexpress');
define('DB_USER', 'root');
define('DB_PASS', '');

// Seuil en dessous duquel un stock est affiché « Stock limité » (charte : moins de 5 bouteilles)
define('SEUIL_STOCK_LIMITE', 5);

// Rayon (km) dans lequel un livreur voit les courses disponibles
define('RAYON_COURSES_KM', 10);

// URL de base calculée automatiquement (ex. /GazExpress)
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $base);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

date_default_timezone_set('Africa/Ouagadougou');
