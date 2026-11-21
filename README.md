# GazExpress

Plateforme web de commande et de livraison de gaz domestique à Ouagadougou (PHP 8 / MySQL).
Projet académique — marketplace reliant clients, vendeurs et livreurs, supervisée par un administrateur.

## Installation (XAMPP)

1. Placer le dossier dans `C:\xampp\htdocs\GazExpress`.
2. Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.
3. Ouvrir phpMyAdmin (http://localhost/phpmyadmin) → **Nouvelle base de données** `gazexpress`, interclassement `utf8mb4_unicode_ci`.
4. Sélectionner cette base → onglet **Importer** → choisir `database/gazexpress.sql` → **Exécuter**.
   Le script crée 14 tables, 1 vue et un jeu de démonstration. Il peut être relancé pour remettre les données à zéro.
5. Ouvrir http://localhost/GazExpress

Paramètres de connexion : `config/env.example.php` est utilisé tel quel en local (`root` sans mot de passe).
Pour d'autres identifiants, le copier en `config/env.php` (fichier non versionné) et l'adapter.
Le module Apache `mod_rewrite` doit être actif (c'est le cas par défaut dans XAMPP).

**Mise en ligne sur o2switch** : voir [DEPLOIEMENT.md](DEPLOIEMENT.md).

## Comptes de démonstration

Mot de passe commun : `Gaz@2026` (boutons de connexion rapide sur la page Connexion, désactivables avec `comptes_demo` dans `config/env.php`).

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

1. **Client** : Trouver un dépôt → « Commander » sur un dépôt → Ajouter → Panier → Valider la commande.
2. **Vendeur** : Espace Vendeur → Accepter (le stock baisse) → Passer en préparation.
3. **Livreur** (adama) : Espace livreur → Accepter la course → Bouteille récupérée → Bouteille remise au client.
4. **Client** : Suivi de commande → la progression se met à jour seule → noter le dépôt et le livreur.
5. **Admin** : validations, suspension de comptes, supervision des commandes, réclamations.

## Organisation

```
index.php            contrôleur frontal + table des routes
.htaccess            réécriture d'URL, HTTPS, accès interdit à app/, config/, database/, docs/
config/              config.php (constantes), env.example.php (modèle des identifiants serveur)
app/core/            Router, Database (PDO), Model, Controller, Auth, Panier, helpers
app/models/          Utilisateur, Vendeur, Livreur, Quartier, Produit, Stock, Commande,
                     Livraison, Avis, Notification, Reclamation
app/controllers/     Accueil, Catalogue, Panier, Commande, Vendeur, Livreur, Admin, Auth, Api
app/views/           gabarit, tiroir panier, écrans
public/              css/app.css, js/app.js, js/carte.js,
                     img/icones.svg (jeu d'icônes), img/produits/ (illustrations des bouteilles)
database/            gazexpress.sql
docs/                cahier des charges (.docx), diagramme de classes (.drawio + .png),
                     maquette de référence, archives, sources/ (scripts de génération)
                     (documents de travail : ni publiés ni liés depuis le site)
```

## Visuels

Les icônes (`public/img/icones.svg`) et les illustrations de bouteilles (`public/img/produits/*.svg`)
ont été dessinées pour le projet : aucune licence tierce. Les couleurs des bouteilles servent à distinguer
les marques et ne reproduisent pas leurs logos ; elles peuvent être remplacées par des photos du stock réel
(même nom de fichier, ou adapter `visuel_produit()` dans `app/core/helpers.php`).

## Sécurité mise en œuvre

Mots de passe hachés (`password_hash`), requêtes préparées PDO, échappement `htmlspecialchars`,
jeton CSRF sur tous les formulaires POST, cookie de session HttpOnly/SameSite, régénération de session
à la connexion, limitation des tentatives, contrôle du rôle et du statut du compte à chaque action,
transactions pour la création, l'acceptation et l'annulation des commandes.

## Ouvrir le diagramme de classes

`docs/diagramme_classes.drawio` s'ouvre sur https://app.diagrams.net (Fichier → Ouvrir depuis → Appareil)
ou avec l'extension draw.io de VS Code.
