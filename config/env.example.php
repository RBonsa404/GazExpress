<?php
/**
 * Modèle de configuration propre à chaque serveur.
 *
 * En production (o2switch) : copier ce fichier en « config/env.php » puis
 * renseigner les identifiants créés dans cPanel > Bases de données MySQL.
 * « env.php » n'est pas versionné : les mots de passe ne quittent pas le serveur.
 *
 * En local (XAMPP), si « env.php » est absent, ce modèle est utilisé tel quel.
 */
return [
    // Base de données MySQL / MariaDB
    'db_host' => '127.0.0.1',       // o2switch : localhost
    'db_port' => '3306',
    'db_name' => 'gazexpress',      // o2switch : préfixe cPanel, ex. moncompte_gazexpress
    'db_user' => 'root',            // o2switch : ex. moncompte_gazuser
    'db_pass' => '',

    // false en production : les erreurs PHP sont journalisées, jamais affichées
    'debug'   => true,

    // Boutons de connexion rapide aux comptes de démonstration (page Connexion).
    // À mettre à false dès que le site sert de vrais clients.
    'comptes_demo' => true,

    // Coordonnées affichées sur le site
    'telephone' => '+226 25 00 00 00',
    'email'     => 'contact@gazexpress.bf',
];
