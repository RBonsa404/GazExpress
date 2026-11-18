# GazExpress

Plateforme web de commande et de livraison de gaz domestique à Ouagadougou (PHP 8 / MySQL).
Projet académique — marketplace reliant clients, vendeurs et livreurs, supervisée par un administrateur.

## Installation (XAMPP)

1. Placer le dossier dans `C:\xampp\htdocs\GazExpress`.
2. Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.
3. Ouvrir phpMyAdmin (http://localhost/phpmyadmin) → onglet **Importer** → choisir `database/gazexpress.sql` → **Exécuter**.
   Le script crée la base `gazexpress` (14 tables, 1 vue) avec un jeu de démonstration. Il peut être relancé pour remettre les données à zéro.
4. Ouvrir http://localhost/GazExpress

Paramètres de connexion : `config/config.php` (par défaut `root` sans mot de passe sur `127.0.0.1`).
Le module Apache `mod_rewrite` doit être actif (c'est le cas par défaut dans XAMPP).

## Comptes de démonstration

Mot de passe commun : `Gaz@2026` (boutons de connexion rapide sur la page Connexion).

| Rôle | Identifiant |
|---|---|
| Client | awa.ouedraogo@gmail.com |
| Client | issouf.kabore@yahoo.fr |
| Vendeur (Dépôt Central SODIGAZ Ouaga 2000) | sodigaz.ouaga2000@gazexpress.bf |
| Vendeur (Relais ORYX Koulouba) | oryx.koulouba@gazexpress.bf |
| Livreur (course en cours) | moussa.livreur@gazexpress.bf |
| Livreur (disponible) | adama.livreur@gazexpress.bf |
| Administrateur | admin@gazexpress.bf |

Un vendeur (Gaz Service Pissy) et un livreur (Hamidou BAMOGO) attendent une validation dans l'espace administrateur.

## Scénario de démonstration

1. **Client** : Carte & Vendeurs → « Voir le stock » d'une boutique → Ajouter → Panier → Confirmer.
2. **Vendeur** : Espace Vendeur → Accepter (le stock baisse) → Passer en préparation.
3. **Livreur** (adama) : Espace Livreur → Accepter la course → Colis récupéré → Confirmer la livraison.
4. **Client** : Suivi Commande → la progression se met à jour seule → noter le vendeur et le livreur.
5. **Admin** : validations, suspension de comptes, supervision des commandes, réclamations.

## Organisation

```
index.php            contrôleur frontal + table des routes
.htaccess            réécriture d'URL, accès interdit à app/, config/, database/, docs/
config/              configuration (BDD, seuils)
app/core/            Router, Database (PDO), Model, Controller, Auth, Panier, helpers
app/models/          Utilisateur, Vendeur, Livreur, Quartier, Produit, Stock, Commande,
                     Livraison, Avis, Notification, Reclamation
app/controllers/     Accueil, Catalogue, Panier, Commande, Vendeur, Livreur, Admin, Auth, Api, Charte
app/views/           gabarit, tiroir panier, écrans
public/              css/app.css (charte « Édition Bordeaux »), js/app.js, js/carte.js
database/            gazexpress.sql
docs/                cahier des charges (.docx), diagramme de classes (.drawio + .png),
                     maquettes, archives, sources/ (scripts de génération des documents)
```

## Sécurité mise en œuvre

Mots de passe hachés (`password_hash`), requêtes préparées PDO, échappement `htmlspecialchars`,
jeton CSRF sur tous les formulaires POST, cookie de session HttpOnly/SameSite, régénération de session
à la connexion, limitation des tentatives, contrôle du rôle et du statut du compte à chaque action,
transactions pour la création, l'acceptation et l'annulation des commandes.

## Ouvrir le diagramme de classes

`docs/diagramme_classes.drawio` s'ouvre sur https://app.diagrams.net (Fichier → Ouvrir depuis → Appareil)
ou avec l'extension draw.io de VS Code.
