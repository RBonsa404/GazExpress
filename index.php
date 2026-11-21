<?php
/**
 * GazExpress — contrôleur frontal.
 * Toutes les requêtes passent ici (voir .htaccess) puis sont routées
 * vers le contrôleur et l'action correspondants.
 */

declare(strict_types=1);

require __DIR__ . '/config/config.php';
require APP_PATH . '/core/helpers.php';
require APP_PATH . '/core/Database.php';
require APP_PATH . '/core/Model.php';
require APP_PATH . '/core/Controller.php';
require APP_PATH . '/core/Auth.php';
require APP_PATH . '/core/Panier.php';
require APP_PATH . '/core/Router.php';

spl_autoload_register(function (string $classe): void {
    foreach (['models', 'controllers'] as $dossier) {
        $fichier = APP_PATH . "/$dossier/$classe.php";
        if (is_file($fichier)) {
            require $fichier;
            return;
        }
    }
});

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_URL . '/',
    'secure'   => EN_HTTPS,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('GAZEXPRESS_SID');
session_start();

$router = new Router();

// Pages publiques
$router->get('',                 'AccueilController@index');
$router->get('catalogue',        'CatalogueController@index');

// Authentification
$router->get('connexion',        'AuthController@connexion');
$router->post('connexion',       'AuthController@seConnecter');
$router->get('inscription',      'AuthController@inscription');
$router->post('inscription',     'AuthController@sInscrire');
$router->post('deconnexion',     'AuthController@deconnexion');

// Panier & commande
$router->post('panier/ajouter',  'PanierController@ajouter');
$router->post('panier/modifier', 'PanierController@modifier');
$router->post('panier/vider',    'PanierController@vider');
$router->post('panier/valider',  'PanierController@valider');

// Espace client
$router->get('commandes',              'CommandeController@index');
$router->get('commandes/suivi',        'CommandeController@suivi');
$router->post('commandes/annuler',     'CommandeController@annuler');
$router->post('commandes/avis',        'CommandeController@avis');
$router->post('commandes/reclamation', 'CommandeController@reclamation');

// Espace vendeur
$router->get('vendeur',                 'VendeurController@index');
$router->post('vendeur/boutique',       'VendeurController@basculerBoutique');
$router->post('vendeur/stock',          'VendeurController@majStock');
$router->post('vendeur/stock/ajouter',  'VendeurController@ajouterProduit');
$router->post('vendeur/commande',       'VendeurController@traiterCommande');

// Espace livreur
$router->get('livreur',                 'LivreurController@index');
$router->post('livreur/disponibilite',  'LivreurController@basculerDisponibilite');
$router->post('livreur/accepter',       'LivreurController@accepter');
$router->post('livreur/recuperer',      'LivreurController@recuperer');
$router->post('livreur/livrer',         'LivreurController@livrer');

// Administration
$router->get('admin',                   'AdminController@index');
$router->post('admin/validation',       'AdminController@validation');
$router->post('admin/utilisateur',      'AdminController@statutUtilisateur');
$router->post('admin/reclamation',      'AdminController@traiterReclamation');
$router->post('admin/commande',         'AdminController@annulerCommande');

// API JSON (AJAX)
$router->get('api/notifications',        'ApiController@notifications');
$router->post('api/notifications/lues',  'ApiController@marquerLues');
$router->get('api/commande-statut',      'ApiController@statutCommande');
$router->get('api/vendeur-commandes',    'ApiController@commandesVendeur');

$router->dispatch($_GET['url'] ?? '', $_SERVER['REQUEST_METHOD']);
