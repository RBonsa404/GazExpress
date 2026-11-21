# Mise en ligne sur o2switch

Hébergement mutualisé cPanel, PHP 8 et MySQL/MariaDB. Comptez 15 minutes environ.

## 1. Préparer les fichiers

Envoyer sur le serveur uniquement :

```
.htaccess
index.php
app/
config/        (sans env.php local : il sera créé sur le serveur)
public/
database/      (facultatif : sert seulement à l'import, l'accès web y est bloqué)
```

Ne pas envoyer `docs/` (cahier des charges, maquettes, diagrammes) ni `.git/`.
Si vous les envoyez quand même, le `.htaccess` en interdit l'accès.

Deux emplacements possibles :

- **Domaine ou sous-domaine dédié** (ex. `gazexpress.mondomaine.com`) : envoyer le contenu
  dans le dossier racine du domaine (souvent `public_html/` ou le dossier choisi à la création du sous-domaine).
- **Sous-dossier** (ex. `mondomaine.com/gazexpress`) : envoyer le contenu dans `public_html/gazexpress/`.

Aucun chemin n'est à modifier : l'adresse de base est calculée automatiquement.

Envoi : Gestionnaire de fichiers de cPanel (envoyer un .zip puis « Extraire »), ou FTP (FileZilla).

## 2. Version de PHP

cPanel > **Sélecteur de version PHP** (ou « MultiPHP Manager ») : choisir **PHP 8.1 ou plus récent**.
Extensions nécessaires (actives par défaut chez o2switch) : `pdo_mysql`, `mbstring`.

## 3. Base de données

1. cPanel > **Bases de données MySQL** :
   - créer une base, par exemple `moncompte_gazexpress` ;
   - créer un utilisateur, par exemple `moncompte_gazuser`, avec un mot de passe fort ;
   - ajouter l'utilisateur à la base avec **tous les privilèges**.
2. cPanel > **phpMyAdmin** : cliquer sur la base dans la colonne de gauche, onglet **Importer**,
   choisir `database/gazexpress.sql`, **Exécuter**.
   Le script crée les 14 tables, la vue et le jeu de démonstration (mot de passe des comptes : `Gaz@2026`).

## 4. Configuration

Dans le dossier `config/` du serveur, copier `env.example.php` en **`env.php`** puis l'éditer :

```php
'db_host' => 'localhost',
'db_port' => '3306',
'db_name' => 'moncompte_gazexpress',
'db_user' => 'moncompte_gazuser',
'db_pass' => 'le mot de passe choisi',
'debug'   => false,          // jamais true en production
'comptes_demo' => true,      // false dès que le site sert de vrais clients
```

`env.php` n'est pas versionné : les identifiants restent sur le serveur.

## 5. HTTPS

cPanel > **SSL/TLS Status** : lancer AutoSSL pour le domaine si le certificat n'est pas encore émis.
Le `.htaccess` redirige ensuite automatiquement tout le trafic vers `https://`,
et le cookie de session passe en mode sécurisé.

## 6. Vérifications

- La page d'accueil affiche la carte et les dépôts : la base est bien branchée.
- « Service momentanément indisponible » : vérifier `config/env.php` (nom de base et utilisateur avec le préfixe cPanel).
- Erreur 500 sans page : consulter cPanel > **Erreurs** (journal Apache) ou le fichier `error_log` du dossier.
- `https://votre-domaine/config/env.php` doit renvoyer **403 Interdit**.
- Connexion avec un compte de démonstration, ajout d'une bouteille au panier, validation d'une commande.

## Mettre à jour le site

Renvoyer les fichiers modifiés (sans écraser `config/env.php`).
Ne réimporter `database/gazexpress.sql` que pour remettre la démonstration à zéro : il efface les données.
