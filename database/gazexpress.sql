-- =====================================================================
--  GazExpress — Base de données MySQL / MariaDB
--  Plateforme de commande et de livraison de gaz domestique
--  Ville pilote : Ouagadougou (Burkina Faso)
--  Moteur InnoDB, jeu de caractères utf8mb4
--  Mot de passe de tous les comptes de démonstration : Gaz@2026
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- Le script s'importe dans la base sélectionnée (phpMyAdmin : choisir la base, puis Importer).
-- Il ne crée pas la base lui-même : sur un hébergement mutualisé (o2switch, cPanel),
-- la base est créée dans cPanel et son nom porte le préfixe du compte.
-- Relancer le script remet les données de démonstration à zéro.

DROP VIEW IF EXISTS v_synthese_vendeurs;
DROP TABLE IF EXISTS reclamations, historique_statuts, notifications, avis, paiements, livraisons,
    details_commande, commandes, stocks, produits, livreurs, vendeurs, utilisateurs, quartiers;

-- ---------------------------------------------------------------------
-- Référentiel des quartiers desservis (frais de livraison par zone)
-- ---------------------------------------------------------------------
CREATE TABLE quartiers (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(80)   NOT NULL,
    latitude         DECIMAL(10,7) NOT NULL,
    longitude        DECIMAL(10,7) NOT NULL,
    frais_livraison  INT UNSIGNED  NOT NULL DEFAULT 500,
    actif            TINYINT(1)    NOT NULL DEFAULT 1,
    UNIQUE KEY uq_quartier_nom (nom)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Comptes de tous les acteurs (le rôle porte le type d'acteur)
-- ---------------------------------------------------------------------
CREATE TABLE utilisateurs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(60)  NOT NULL,
    prenom         VARCHAR(60)  NOT NULL,
    telephone      VARCHAR(20)  NOT NULL,
    email          VARCHAR(120) NOT NULL,
    mot_de_passe   VARCHAR(255) NOT NULL,
    role           ENUM('client','vendeur','livreur','admin') NOT NULL DEFAULT 'client',
    statut         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
    quartier_id    INT UNSIGNED NULL,
    adresse        VARCHAR(255) NULL,
    date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL,
    UNIQUE KEY uq_utilisateur_email (email),
    UNIQUE KEY uq_utilisateur_tel (telephone),
    KEY idx_utilisateur_role (role),
    CONSTRAINT fk_utilisateur_quartier FOREIGN KEY (quartier_id)
        REFERENCES quartiers(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Profil commercial du vendeur (boutique / dépôt / station)
-- ---------------------------------------------------------------------
CREATE TABLE vendeurs (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id       INT UNSIGNED  NOT NULL,
    nom_commerce         VARCHAR(120)  NOT NULL,
    quartier_id          INT UNSIGNED  NOT NULL,
    adresse              VARCHAR(255)  NOT NULL,
    telephone            VARCHAR(20)   NOT NULL,
    latitude             DECIMAL(10,7) NOT NULL,
    longitude            DECIMAL(10,7) NOT NULL,
    heure_ouverture      TIME          NOT NULL DEFAULT '07:00:00',
    heure_fermeture      TIME          NOT NULL DEFAULT '20:00:00',
    est_ouvert           TINYINT(1)    NOT NULL DEFAULT 1,
    numero_autorisation  VARCHAR(60)   NULL,
    statut_validation    ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    date_validation      DATETIME      NULL,
    UNIQUE KEY uq_vendeur_utilisateur (utilisateur_id),
    KEY idx_vendeur_validation (statut_validation),
    CONSTRAINT fk_vendeur_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vendeur_quartier FOREIGN KEY (quartier_id)
        REFERENCES quartiers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Profil du livreur (engin, pièces, disponibilité)
-- ---------------------------------------------------------------------
CREATE TABLE livreurs (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id     INT UNSIGNED NOT NULL,
    type_engin         ENUM('moto','tricycle','velo') NOT NULL DEFAULT 'moto',
    immatriculation    VARCHAR(30)  NULL,
    numero_piece       VARCHAR(40)  NOT NULL,
    disponible         TINYINT(1)   NOT NULL DEFAULT 0,
    statut_validation  ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    date_validation    DATETIME     NULL,
    UNIQUE KEY uq_livreur_utilisateur (utilisateur_id),
    CONSTRAINT fk_livreur_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Catalogue des types de bouteilles
-- ---------------------------------------------------------------------
CREATE TABLE produits (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    designation     VARCHAR(120) NOT NULL,
    marque          VARCHAR(40)  NOT NULL,
    capacite_kg     DECIMAL(4,1) NOT NULL,
    description     VARCHAR(255) NULL,
    prix_reference  INT UNSIGNED NOT NULL,
    actif           TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_produit_marque_capacite (marque, capacite_kg)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Stock et prix d'un produit chez un vendeur
-- L'état (disponible / limité / indisponible) est calculé, jamais stocké
-- ---------------------------------------------------------------------
CREATE TABLE stocks (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendeur_id        INT UNSIGNED NOT NULL,
    produit_id        INT UNSIGNED NOT NULL,
    quantite          INT UNSIGNED NOT NULL DEFAULT 0,
    prix              INT UNSIGNED NOT NULL,
    date_mise_a_jour  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_vendeur_produit (vendeur_id, produit_id),
    CONSTRAINT fk_stock_vendeur FOREIGN KEY (vendeur_id)
        REFERENCES vendeurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stock_produit FOREIGN KEY (produit_id)
        REFERENCES produits(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Commandes
-- ---------------------------------------------------------------------
CREATE TABLE commandes (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference          VARCHAR(12)  NOT NULL,
    client_id          INT UNSIGNED NOT NULL,
    vendeur_id         INT UNSIGNED NOT NULL,
    date_commande      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant_articles   INT UNSIGNED NOT NULL,
    frais_livraison    INT UNSIGNED NOT NULL DEFAULT 0,
    montant_total      INT UNSIGNED NOT NULL,
    statut             ENUM('en_attente','acceptee','en_preparation','en_livraison','livree','annulee') NOT NULL DEFAULT 'en_attente',
    mode_recuperation  ENUM('livraison','retrait') NOT NULL,
    motif_annulation   VARCHAR(255) NULL,
    date_cloture       DATETIME     NULL,
    UNIQUE KEY uq_commande_reference (reference),
    KEY idx_commande_statut (statut),
    KEY idx_commande_date (date_commande),
    CONSTRAINT fk_commande_client FOREIGN KEY (client_id)
        REFERENCES utilisateurs(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_commande_vendeur FOREIGN KEY (vendeur_id)
        REFERENCES vendeurs(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE details_commande (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id    INT UNSIGNED NOT NULL,
    produit_id     INT UNSIGNED NOT NULL,
    quantite       INT UNSIGNED NOT NULL,
    prix_unitaire  INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_detail_commande_produit (commande_id, produit_id),
    CONSTRAINT fk_detail_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_produit FOREIGN KEY (produit_id)
        REFERENCES produits(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Livraison (absente pour une commande en retrait sur place)
-- ---------------------------------------------------------------------
CREATE TABLE livraisons (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id        INT UNSIGNED  NOT NULL,
    livreur_id         INT UNSIGNED  NULL,
    quartier_id        INT UNSIGNED  NOT NULL,
    adresse_livraison  VARCHAR(255)  NOT NULL,
    latitude           DECIMAL(10,7) NULL,
    longitude          DECIMAL(10,7) NULL,
    distance_km        DECIMAL(5,1)  NULL,
    frais              INT UNSIGNED  NOT NULL,
    statut             ENUM('disponible','acceptee','en_route','livree','annulee') NOT NULL DEFAULT 'disponible',
    note_livreur       TINYINT UNSIGNED NULL,
    date_prise         DATETIME      NULL,
    date_livraison     DATETIME      NULL,
    UNIQUE KEY uq_livraison_commande (commande_id),
    KEY idx_livraison_statut (statut),
    CONSTRAINT fk_livraison_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_livraison_livreur FOREIGN KEY (livreur_id)
        REFERENCES livreurs(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_livraison_quartier FOREIGN KEY (quartier_id)
        REFERENCES quartiers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_note_livreur CHECK (note_livreur IS NULL OR note_livreur BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Paiement (espèces à la livraison ou Mobile Money)
-- ---------------------------------------------------------------------
CREATE TABLE paiements (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id            INT UNSIGNED NOT NULL,
    montant                INT UNSIGNED NOT NULL,
    mode_paiement          ENUM('especes','orange_money','moov_money') NOT NULL,
    statut                 ENUM('en_attente','valide','rembourse','echoue') NOT NULL DEFAULT 'en_attente',
    reference_transaction  VARCHAR(40)  NULL,
    date_paiement          DATETIME     NULL,
    UNIQUE KEY uq_paiement_commande (commande_id),
    CONSTRAINT fk_paiement_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Avis d'un client sur un vendeur (un avis par commande livrée)
-- ---------------------------------------------------------------------
CREATE TABLE avis (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id    INT UNSIGNED NOT NULL,
    vendeur_id   INT UNSIGNED NOT NULL,
    commande_id  INT UNSIGNED NOT NULL,
    note         TINYINT UNSIGNED NOT NULL,
    commentaire  VARCHAR(500) NULL,
    date_avis    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avis_commande (commande_id),
    CONSTRAINT fk_avis_client FOREIGN KEY (client_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avis_vendeur FOREIGN KEY (vendeur_id)
        REFERENCES vendeurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avis_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Notifications in-app (rafraîchies en AJAX)
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT UNSIGNED NOT NULL,
    commande_id     INT UNSIGNED NULL,
    message         VARCHAR(255) NOT NULL,
    lien            VARCHAR(120) NULL,
    est_lue         TINYINT(1)   NOT NULL DEFAULT 0,
    date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_user (utilisateur_id, est_lue),
    CONSTRAINT fk_notification_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notification_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Traçabilité des changements de statut d'une commande
-- ---------------------------------------------------------------------
CREATE TABLE historique_statuts (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id      INT UNSIGNED NOT NULL,
    ancien_statut    VARCHAR(20)  NULL,
    nouveau_statut   VARCHAR(20)  NOT NULL,
    auteur_id        INT UNSIGNED NULL,
    date_changement  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_historique_commande (commande_id),
    CONSTRAINT fk_historique_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_historique_auteur FOREIGN KEY (auteur_id)
        REFERENCES utilisateurs(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Réclamations des clients, traitées par l'administrateur
-- ---------------------------------------------------------------------
CREATE TABLE reclamations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id     INT UNSIGNED NOT NULL,
    client_id       INT UNSIGNED NOT NULL,
    objet           VARCHAR(120) NOT NULL,
    message         TEXT         NOT NULL,
    statut          ENUM('ouverte','traitee') NOT NULL DEFAULT 'ouverte',
    reponse         TEXT         NULL,
    date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME     NULL,
    CONSTRAINT fk_reclamation_commande FOREIGN KEY (commande_id)
        REFERENCES commandes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reclamation_client FOREIGN KEY (client_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  Vue utile pour l'administration : synthèse par vendeur
-- =====================================================================
CREATE VIEW v_synthese_vendeurs AS
SELECT v.id,
       v.nom_commerce,
       q.nom                                         AS quartier,
       v.statut_validation,
       COALESCE(SUM(s.quantite), 0)                  AS bouteilles_en_stock,
       (SELECT COUNT(*) FROM commandes c WHERE c.vendeur_id = v.id)                         AS nb_commandes,
       (SELECT COALESCE(SUM(c.montant_articles),0) FROM commandes c
         WHERE c.vendeur_id = v.id AND c.statut = 'livree')                                  AS chiffre_affaires,
       (SELECT ROUND(AVG(a.note),1) FROM avis a WHERE a.vendeur_id = v.id)                   AS note_moyenne
FROM vendeurs v
JOIN quartiers q ON q.id = v.quartier_id
LEFT JOIN stocks s ON s.vendeur_id = v.id
GROUP BY v.id, v.nom_commerce, q.nom, v.statut_validation;

-- =====================================================================
--  JEU DE DONNÉES DE DÉMONSTRATION
-- =====================================================================

INSERT INTO quartiers (id, nom, latitude, longitude, frais_livraison) VALUES
(1,  'Ouaga 2000',      12.3060000, -1.5080000, 500),
(2,  '1200 Logements',  12.3790000, -1.4890000, 500),
(3,  'Patte d''Oie',    12.3365000, -1.5375000, 500),
(4,  'Koulouba',        12.3722000, -1.5165000, 500),
(5,  'Pissy',           12.3296000, -1.5645000, 700),
(6,  'Zone du Bois',    12.3805000, -1.4805000, 500),
(7,  'Cité Azimo',      12.3688000, -1.4930000, 500),
(8,  'Gounghin',        12.3612000, -1.5455000, 600),
(9,  'Tampouy',         12.4020000, -1.5555000, 800),
(10, 'Dassasgho',       12.3765000, -1.4605000, 700);

-- Mot de passe commun : Gaz@2026
INSERT INTO utilisateurs (id, nom, prenom, telephone, email, mot_de_passe, role, statut, quartier_id, adresse, date_creation) VALUES
(1,  'SIDIBE',     'Ramatou',   '+22670000001', 'admin@gazexpress.bf',          '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'admin',   'actif', 1, 'Siège GazExpress, Ouaga 2000', '2026-09-01 08:00:00'),
(2,  'OUEDRAOGO',  'Awa',       '+22670112233', 'awa.ouedraogo@gmail.com',      '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'client',  'actif', 1, 'Secteur 53, près du Monument des Martyrs', '2026-10-02 10:15:00'),
(3,  'KABORE',     'Issouf',    '+22676223344', 'issouf.kabore@yahoo.fr',       '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'client',  'actif', 7, 'Cité Azimo, rue 12.45', '2026-10-05 17:40:00'),
(4,  'TRAORE',     'Mariam',    '+22678334455', 'mariam.traore@gmail.com',      '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'client',  'actif', 6, 'Zone du Bois, villa 214', '2026-10-11 09:05:00'),
(5,  'ZONGO',      'Boukary',   '+22625373700', 'sodigaz.ouaga2000@gazexpress.bf', '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'vendeur', 'actif', 1, NULL, '2026-09-10 08:30:00'),
(6,  'COMPAORE',   'Salif',     '+22625304050', 'oryx.koulouba@gazexpress.bf',  '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'vendeur', 'actif', 4, NULL, '2026-09-12 11:00:00'),
(7,  'SAWADOGO',   'Aminata',   '+22625431020', 'total.pattedoie@gazexpress.bf', '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'vendeur', 'actif', 3, NULL, '2026-09-15 14:20:00'),
(8,  'NIKIEMA',    'Paul',      '+22625368899', 'gaz.1200logts@gazexpress.bf',  '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'vendeur', 'actif', 2, NULL, '2026-09-20 09:45:00'),
(9,  'ILBOUDO',    'Rasmané',   '+22670998877', 'pissy.gaz@gmail.com',          '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'vendeur', 'actif', 5, NULL, '2026-11-16 16:10:00'),
(10, 'YAMEOGO',    'Moussa',    '+22671556677', 'moussa.livreur@gazexpress.bf', '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'livreur', 'actif', 1, NULL, '2026-09-18 07:30:00'),
(11, 'OUATTARA',   'Adama',     '+22672667788', 'adama.livreur@gazexpress.bf',  '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'livreur', 'actif', 4, NULL, '2026-09-25 07:50:00'),
(12, 'BAMOGO',     'Hamidou',   '+22673778899', 'hamidou.bamogo@gmail.com',     '$2y$10$brhw8AV26Y2vtqbDO5UgBuLyPavuEOS2Bf8rE9LlDESreN2r6wAmu', 'livreur', 'actif', 9, NULL, '2026-11-17 12:00:00');

INSERT INTO vendeurs (id, utilisateur_id, nom_commerce, quartier_id, adresse, telephone, latitude, longitude, heure_ouverture, heure_fermeture, est_ouvert, numero_autorisation, statut_validation, date_validation) VALUES
(1, 5, 'Dépôt Central SODIGAZ Ouaga 2000', 1, 'Avenue Moctar Ouédraogo, Ouaga 2000',          '+22625373700', 12.3092000, -1.5046000, '06:00:00', '22:00:00', 1, 'AUT-MCIA-2026-0141', 'valide', '2026-09-11 10:00:00'),
(2, 6, 'Relais ORYX Koulouba',             4, 'Près du Lycée Philippe Zinda Kaboré, Koulouba', '+22625304050', 12.3738000, -1.5186000, '07:00:00', '20:00:00', 1, 'AUT-MCIA-2026-0157', 'valide', '2026-09-13 09:30:00'),
(3, 7, 'Station TOTAL Patte d''Oie',       3, 'Boulevard Tansoba Amadou, Patte d''Oie',       '+22625431020', 12.3352000, -1.5398000, '06:30:00', '21:30:00', 1, 'AUT-MCIA-2026-0163', 'valide', '2026-09-16 15:00:00'),
(4, 8, 'Boutique Gaz 1200 Logements',      2, 'Avenue de l''Europe, 1200 Logements',          '+22625368899', 12.3778000, -1.4912000, '07:00:00', '21:00:00', 1, 'AUT-MCIA-2026-0172', 'valide', '2026-09-21 11:15:00'),
(5, 9, 'Gaz Service Pissy',                5, 'Rond-point de Pissy, face à la pharmacie',     '+22670998877', 12.3301000, -1.5662000, '07:00:00', '19:00:00', 0, 'AUT-MCIA-2026-0209', 'en_attente', NULL);

INSERT INTO livreurs (id, utilisateur_id, type_engin, immatriculation, numero_piece, disponible, statut_validation, date_validation) VALUES
(1, 10, 'moto',     '11 KK 4521', 'B11223344', 1, 'valide',     '2026-09-19 09:00:00'),
(2, 11, 'tricycle', '11 LL 0987', 'B55667788', 1, 'valide',     '2026-09-26 10:00:00'),
(3, 12, 'moto',     '11 MM 3210', 'B99001122', 0, 'en_attente', NULL);

INSERT INTO produits (id, designation, marque, capacite_kg, description, prix_reference) VALUES
(1, 'Bouteille Gaz SODIGAZ 6 kg',    'SODIGAZ', 6.0,  'Recharge butane, échange contre une bouteille vide.', 3000),
(2, 'Bouteille Gaz SODIGAZ 12,5 kg', 'SODIGAZ', 12.5, 'Recharge butane, échange contre une bouteille vide.', 6200),
(3, 'Bouteille Gaz TOTAL 6 kg',      'TOTAL',   6.0,  'Recharge butane, échange contre une bouteille vide.', 3200),
(4, 'Bouteille Gaz TOTAL 12,5 kg',   'TOTAL',   12.5, 'Recharge butane, échange contre une bouteille vide.', 6500),
(5, 'Bouteille Gaz ORYX 6 kg',       'ORYX',    6.0,  'Recharge butane, échange contre une bouteille vide.', 3150),
(6, 'Bouteille Gaz ORYX 12,5 kg',    'ORYX',    12.5, 'Recharge butane, échange contre une bouteille vide.', 6400);

INSERT INTO stocks (vendeur_id, produit_id, quantite, prix) VALUES
(1, 1, 18, 3000), (1, 2, 10, 6200), (1, 4, 3, 6500), (1, 3, 11, 3200),
(2, 5, 4, 3150),  (2, 6, 3, 6400),  (2, 1, 2, 3000),
(3, 3, 15, 3200), (3, 4, 9, 6500),  (3, 2, 6, 6200),
(4, 1, 7, 3000),  (4, 2, 0, 6200),  (4, 5, 8, 3150), (4, 6, 5, 6400),
(5, 1, 12, 3000), (5, 2, 6, 6200);

-- Commandes de démonstration (historique + commandes en cours)
INSERT INTO commandes (id, reference, client_id, vendeur_id, date_commande, montant_articles, frais_livraison, montant_total, statut, mode_recuperation, date_cloture) VALUES
(1, 'GX-8481', 2, 1, '2026-11-10 09:12:00', 6200, 500, 6700, 'livree',         'livraison', '2026-11-10 10:05:00'),
(2, 'GX-8485', 3, 4, '2026-11-14 18:30:00', 3150, 0,   3150, 'livree',         'retrait',   '2026-11-14 19:02:00'),
(3, 'GX-8488', 4, 3, '2026-11-15 12:00:00', 3200, 500, 3700, 'annulee',        'livraison', '2026-11-15 12:20:00'),
(4, 'GX-8492', 2, 1, '2026-11-17 14:32:00', 6200, 500, 6700, 'en_preparation', 'livraison', NULL),
(5, 'GX-8495', 3, 3, '2026-11-18 08:10:00', 3200, 500, 3700, 'acceptee',       'livraison', NULL),
(6, 'GX-8496', 4, 2, '2026-11-18 08:25:00', 12800, 500, 13300,'en_preparation','livraison', NULL),
(7, 'GX-8497', 4, 1, '2026-11-18 08:41:00', 3000, 0,   3000, 'en_attente',     'retrait',   NULL),
(8, 'GX-8498', 3, 1, '2026-11-18 08:55:00', 6500, 500, 7000, 'en_attente',     'livraison', NULL);

UPDATE commandes SET motif_annulation = 'Annulée par le client' WHERE id = 3;

INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire) VALUES
(1, 2, 1, 6200), (2, 5, 1, 3150), (3, 3, 1, 3200), (4, 2, 1, 6200),
(5, 3, 1, 3200), (6, 6, 2, 6400), (7, 1, 1, 3000), (8, 4, 1, 6500);

INSERT INTO livraisons (commande_id, livreur_id, quartier_id, adresse_livraison, latitude, longitude, distance_km, frais, statut, note_livreur, date_prise, date_livraison) VALUES
(1, 1, 1, 'Secteur 53, Ouaga 2000 (Près Monument des Martyrs)', 12.3060000, -1.5080000, 0.6, 500, 'livree', 5, '2026-11-10 09:40:00', '2026-11-10 10:05:00'),
(3, NULL, 6, 'Zone du Bois, villa 214', 12.3805000, -1.4805000, 7.5, 500, 'annulee', NULL, NULL, NULL),
(4, 1, 1, 'Secteur 53, Ouaga 2000 (Près Monument des Martyrs)', 12.3060000, -1.5080000, 0.6, 500, 'acceptee', NULL, '2026-11-18 08:50:00', NULL),
(5, NULL, 7, 'Cité Azimo, rue 12.45', 12.3688000, -1.4930000, 5.4, 500, 'disponible', NULL, NULL, NULL),
(6, NULL, 6, 'Zone du Bois, villa 214', 12.3805000, -1.4805000, 4.0, 500, 'disponible', NULL, NULL, NULL),
(8, NULL, 7, 'Cité Azimo, rue 12.45', 12.3688000, -1.4930000, 5.0, 500, 'disponible', NULL, NULL, NULL);

INSERT INTO paiements (commande_id, montant, mode_paiement, statut, reference_transaction, date_paiement) VALUES
(1, 6700,  'orange_money', 'valide',    'OM261110.0912.A4471', '2026-11-10 09:12:00'),
(2, 3150,  'especes',      'valide',    NULL,                  '2026-11-14 19:02:00'),
(3, 3700,  'especes',      'echoue',    NULL,                  NULL),
(4, 6700,  'orange_money', 'valide',    'OM261117.1432.B8812', '2026-11-17 14:32:00'),
(5, 3700,  'especes',      'en_attente', NULL,                 NULL),
(6, 13300, 'moov_money',   'valide',    'MM261118.0825.C1203', '2026-11-18 08:25:00'),
(7, 3000,  'especes',      'en_attente', NULL,                 NULL),
(8, 7000,  'especes',      'en_attente', NULL,                 NULL);

INSERT INTO avis (client_id, vendeur_id, commande_id, note, commentaire, date_avis) VALUES
(2, 1, 1, 5, 'Livraison rapide, bouteille bien scellée. Je recommande.', '2026-11-10 10:30:00'),
(3, 4, 2, 4, 'Bon accueil, retrait en moins de 10 minutes.',             '2026-11-14 19:20:00');

INSERT INTO historique_statuts (commande_id, ancien_statut, nouveau_statut, auteur_id, date_changement) VALUES
(1, NULL, 'en_attente', 2, '2026-11-10 09:12:00'), (1, 'en_attente', 'acceptee', 5, '2026-11-10 09:18:00'),
(1, 'acceptee', 'en_preparation', 5, '2026-11-10 09:25:00'), (1, 'en_preparation', 'en_livraison', 10, '2026-11-10 09:45:00'),
(1, 'en_livraison', 'livree', 10, '2026-11-10 10:05:00'),
(2, NULL, 'en_attente', 3, '2026-11-14 18:30:00'), (2, 'en_attente', 'acceptee', 8, '2026-11-14 18:35:00'),
(2, 'acceptee', 'livree', 8, '2026-11-14 19:02:00'),
(3, NULL, 'en_attente', 4, '2026-11-15 12:00:00'), (3, 'en_attente', 'annulee', 4, '2026-11-15 12:20:00'),
(4, NULL, 'en_attente', 2, '2026-11-17 14:32:00'), (4, 'en_attente', 'acceptee', 5, '2026-11-17 14:40:00'),
(4, 'acceptee', 'en_preparation', 5, '2026-11-18 08:30:00'),
(5, NULL, 'en_attente', 3, '2026-11-18 08:10:00'), (5, 'en_attente', 'acceptee', 7, '2026-11-18 08:15:00'),
(6, NULL, 'en_attente', 4, '2026-11-18 08:25:00'), (6, 'en_attente', 'acceptee', 6, '2026-11-18 08:28:00'),
(6, 'acceptee', 'en_preparation', 6, '2026-11-18 08:35:00'),
(7, NULL, 'en_attente', 4, '2026-11-18 08:41:00'),
(8, NULL, 'en_attente', 3, '2026-11-18 08:55:00');

INSERT INTO notifications (utilisateur_id, commande_id, message, lien, est_lue, date_creation) VALUES
(2, 4, 'Votre commande GX-8492 est en préparation chez Dépôt Central SODIGAZ Ouaga 2000.', 'commandes/suivi?id=4', 0, '2026-11-18 08:30:00'),
(5, 7, 'Nouvelle commande GX-8497 en attente de validation.', 'vendeur', 0, '2026-11-18 08:41:00'),
(5, 8, 'Nouvelle commande GX-8498 en attente de validation.', 'vendeur', 0, '2026-11-18 08:55:00'),
(1, NULL, 'Nouveau vendeur à valider : Gaz Service Pissy.', 'admin', 0, '2026-11-16 16:10:00'),
(1, NULL, 'Nouveau livreur à valider : Hamidou BAMOGO.', 'admin', 0, '2026-11-17 12:00:00');

INSERT INTO reclamations (commande_id, client_id, objet, message, statut, date_creation) VALUES
(3, 4, 'Commande annulée sans nouvelles', 'J''ai dû annuler car personne n''avait accepté ma commande après 20 minutes. Est-ce normal ?', 'ouverte', '2026-11-15 12:25:00');
