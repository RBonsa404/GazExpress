// Génère le cahier des charges GazExpress (v3.0) au format Word
const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType, Table, TableRow, TableCell,
  WidthType, ShadingType, BorderStyle, ImageRun, PageBreak, Header, Footer, PageNumber,
  TableOfContents, LevelFormat, PageOrientation, VerticalAlign, TabStopType, TabStopPosition,
} = require('docx');

const DOCS = process.argv[2];
const OUT = process.argv[3];

const B = '58111A', ACC = '7A1824', ROSE = 'FDF2F4', ROSE_B = 'F6D5DB', NUIT = '0F172A', GRIS = '64748B', GRIS_L = 'E2E8F0', SURF = 'F8FAFC';
const FONT = 'Segoe UI';
const LARG = 9638; // largeur utile A4 avec marges de 2 cm (DXA)

// ---------- briques ----------
const t = (texte, o = {}) => new TextRun({ text: texte, font: FONT, ...o });
function p(contenu, o = {}) {
  const runs = (Array.isArray(contenu) ? contenu : [contenu]).map(c => typeof c === 'string' ? t(c) : c);
  return new Paragraph({ children: runs, spacing: { after: 120, line: 300 }, ...o });
}
const gras = (s, o = {}) => t(s, { bold: true, ...o });
const SAUTS = ['1. ', '4. ', '8. ', '10. ', '16. ', 'Annexe A'];
const h1 = s => new Paragraph({ heading: HeadingLevel.HEADING_1, children: [t(s)], pageBreakBefore: SAUTS.some(x => s.startsWith(x)) });
const h1Suite = s => new Paragraph({ heading: HeadingLevel.HEADING_1, children: [t(s)] });
const h2 = s => new Paragraph({ heading: HeadingLevel.HEADING_2, children: [t(s)] });
const h3 = s => new Paragraph({ heading: HeadingLevel.HEADING_3, children: [t(s)] });
const puce = (contenu, niveau = 0) => p(contenu, { numbering: { reference: 'puces', level: niveau }, spacing: { after: 60, line: 290 } });
const num = (contenu, ref = 'etapes') => p(contenu, { numbering: { reference: ref, level: 0 }, spacing: { after: 60, line: 290 } });
const vide = (apres = 0) => new Paragraph({ children: [], spacing: { after: apres } });
const titreLibre = (s, taille, couleur) => new Paragraph({ keepNext: true, spacing: { before: 160, after: 200 }, children: [t(s, { bold: true, size: taille, color: couleur })],
  border: taille > 30 ? { bottom: { style: BorderStyle.SINGLE, size: 8, color: ROSE_B, space: 6 } } : undefined });
const legende = s => new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 80, after: 240 }, children: [t(s, { italics: true, size: 18, color: GRIS })] });

const bord = { style: BorderStyle.SINGLE, size: 4, color: GRIS_L };
const bords = { top: bord, bottom: bord, left: bord, right: bord };

function cellule(contenu, largeur, o = {}) {
  const paras = (Array.isArray(contenu) ? contenu : [contenu]).map(c =>
    c instanceof Paragraph ? c : new Paragraph({
      spacing: { before: 40, after: 40, line: 270 },
      alignment: o.centre ? AlignmentType.CENTER : AlignmentType.LEFT,
      children: [typeof c === 'string' ? t(c, { size: 19, bold: !!o.gras, color: o.couleur }) : c],
    }));
  return new TableCell({
    children: paras, width: { size: largeur, type: WidthType.DXA }, borders: bords,
    verticalAlign: VerticalAlign.CENTER,
    margins: { top: 70, bottom: 70, left: 110, right: 110 },
    shading: o.fond ? { fill: o.fond, type: ShadingType.CLEAR, color: 'auto' } : undefined,
    columnSpan: o.span,
  });
}

/** Tableau avec en-tête bordeaux et lignes alternées rosé pâle. */
function tableau(entetes, lignes, largeurs, o = {}) {
  const total = largeurs.reduce((a, b) => a + b, 0);
  const rows = [new TableRow({
    tableHeader: true,
    children: entetes.map((e, i) => new TableCell({
      children: [new Paragraph({ spacing: { before: 40, after: 40 }, children: [t(e, { bold: true, color: 'FFFFFF', size: 19 })] })],
      width: { size: largeurs[i], type: WidthType.DXA }, borders: bords,
      margins: { top: 80, bottom: 80, left: 110, right: 110 },
      shading: { fill: B, type: ShadingType.CLEAR, color: 'auto' },
    })),
  })];
  lignes.forEach((l, k) => rows.push(new TableRow({
    cantSplit: true,
    children: l.map((c, i) => cellule(c, largeurs[i], { fond: k % 2 ? ROSE : undefined, gras: o.premiereGras && i === 0 })),
  })));
  return new Table({ width: { size: total, type: WidthType.DXA }, columnWidths: largeurs, rows });
}

/** Encadré d'information (fond rosé, filet bordeaux à gauche). */
function encadre(titre, lignes) {
  const children = [new Paragraph({ spacing: { after: 60 }, children: [t(titre, { bold: true, color: B })] })]
    .concat(lignes.map(l => new Paragraph({ spacing: { after: 40, line: 280 }, children: (Array.isArray(l) ? l : [l]).map(x => typeof x === 'string' ? t(x, { size: 20 }) : x) })));
  return new Table({
    width: { size: LARG, type: WidthType.DXA }, columnWidths: [LARG],
    rows: [new TableRow({ children: [new TableCell({
      children, width: { size: LARG, type: WidthType.DXA },
      shading: { fill: ROSE, type: ShadingType.CLEAR, color: 'auto' },
      margins: { top: 140, bottom: 140, left: 220, right: 220 },
      borders: { left: { style: BorderStyle.SINGLE, size: 24, color: B }, top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
    })] })],
  });
}

function image(fichier, largeurPx, o = {}) {
  const buf = fs.readFileSync(fichier);
  const w = buf.readUInt32BE(16), h = buf.readUInt32BE(20); // en-tête PNG
  const hauteur = Math.round(largeurPx * h / w);
  return new Paragraph({
    alignment: AlignmentType.CENTER, spacing: { before: 120, after: 40 }, keepNext: true,
    children: [new ImageRun({ type: 'png', data: buf, transformation: { width: largeurPx, height: hauteur },
      altText: { title: o.titre || 'Illustration', description: o.titre || 'Illustration', name: path.basename(fichier) } })],
  });
}

// pastille de couleur pour la palette
const pastille = hex => new TableCell({
  children: [new Paragraph({ children: [] })], width: { size: 900, type: WidthType.DXA }, borders: bords,
  shading: { fill: hex, type: ShadingType.CLEAR, color: 'auto' },
});

// ---------- page de garde ----------
const garde = [
  new Table({
    width: { size: LARG, type: WidthType.DXA }, columnWidths: [LARG],
    rows: [new TableRow({ children: [new TableCell({
      width: { size: LARG, type: WidthType.DXA },
      shading: { fill: B, type: ShadingType.CLEAR, color: 'auto' },
      borders: { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } },
      margins: { top: 500, bottom: 500, left: 500, right: 500 },
      children: [
        new Paragraph({ children: [t('GAZ', { bold: true, size: 64, color: 'FFFFFF' }), t('EXPRESS', { bold: true, size: 64, color: 'F9A8B8' })] }),
        new Paragraph({ spacing: { before: 60 }, children: [t('Marketplace du gaz domestique · Ouagadougou, Burkina Faso', { size: 22, color: 'F1D5DA' })] }),
      ],
    })] })],
  }),
  vide(1800),
  new Paragraph({ children: [t('CAHIER DES CHARGES', { bold: true, size: 26, color: ACC, characterSpacing: 60 })] }),
  new Paragraph({ spacing: { before: 120, after: 160 }, children: [t('Plateforme web de commande et de livraison de gaz domestique', { bold: true, size: 48, color: NUIT })] }),
  new Paragraph({
    border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: B, space: 8 } }, spacing: { after: 400 },
    children: [t('Expression des besoins, règles de gestion, charte graphique, architecture PHP / MySQL et modèle de données', { size: 24, color: GRIS })],
  }),
  tableau(['Rubrique', 'Information'], [
    ['Projet', 'GazExpress (nom provisoire)'],
    ['Nature du document', 'Cahier des charges fonctionnel et technique'],
    ['Version', '3.0'],
    ['Date', '18 novembre 2026'],
    ['Rédaction', 'Ramatou SIDIBE et l\'équipe projet GazExpress'],
    ['Cadre', 'Projet de fin de module, évaluation en classe'],
    ['Statut', 'Version de référence pour la réalisation'],
  ], [3000, 6638], { premiereGras: true }),
  new Paragraph({ children: [new PageBreak()] }),
];

// ---------- suivi du document ----------
const suivi = [
  titreLibre('Suivi du document', 34, B),
  tableau(['Version', 'Date', 'Objet des modifications'], [
    ['2.0', 'Antérieure', 'Choix de la pile PHP / MySQL, premier diagramme de classes (9 classes), liste des tables.'],
    ['3.0', '18 novembre 2026', 'Refonte complète : exigences numérotées et priorisées, règles de gestion, cycle de vie des commandes, charte graphique « Édition Bordeaux », modèle de données détaillé (14 classes), critères de recette.'],
  ], [1200, 2100, 6338]),
  vide(200),
  titreLibre('Documents associés', 26, NUIT),
  tableau(['Référence', 'Document', 'Emplacement'], [
    ['D1', 'Diagramme de classes (format draw.io, modifiable)', 'docs/diagramme_classes.drawio'],
    ['D2', 'Script de création de la base de données MySQL', 'database/gazexpress.sql'],
    ['D3', 'Maquettes et charte graphique (captures)', 'docs/maquette/'],
    ['D4', 'Code source de l\'application (PHP 8, architecture MVC)', 'Racine du projet GazExpress'],
  ], [1200, 5000, 3438]),
  vide(200),
  new Paragraph({ children: [new PageBreak()] }),
  titreLibre('Sommaire', 34, B),
  new TableOfContents('Sommaire', { hyperlink: true, headingStyleRange: '1-2' }),
];

// ---------- 1. Présentation ----------
const s1 = [
  h1('1. Présentation du projet'),
  h2('1.1 Contexte'),
  p('Au Burkina Faso, le gaz butane est devenu le combustible de cuisson de nombreux ménages urbains. À Ouagadougou, l\'approvisionnement repose sur un réseau de dépôts, de stations-service et de petites boutiques qui commercialisent les bouteilles des distributeurs SODIGAZ, TOTAL et ORYX, principalement en 6 kg et 12,5 kg.'),
  p('Lorsqu\'une bouteille est vide, le client se déplace de point de vente en point de vente ou téléphone à plusieurs commerçants pour trouver une recharge disponible. Les ruptures sont fréquentes et l\'information sur les stocks ne circule pas. Côté vendeurs, la plupart ne disposent d\'aucun outil numérique pour présenter leurs produits, suivre leurs quantités ou toucher une clientèle plus large.'),
  h2('1.2 Problématique'),
  encadre('Question à laquelle le projet répond', ['Comment faciliter l\'accès au gaz domestique, rendre les vendeurs visibles et simplifier la commande et la livraison grâce à une solution numérique adaptée au contexte de Ouagadougou ?']),
  vide(120),
  h2('1.3 Présentation de la solution'),
  p([gras('GazExpress'), t(' est une application web de type marketplace, pensée d\'abord pour le smartphone. Elle met en relation trois catégories d\'utilisateurs : les ménages (clients), les points de vente de gaz (vendeurs) et des livreurs indépendants. Un administrateur supervise l\'ensemble.')]),
  tableau(['Élément', 'Description'], [
    ['Nature', 'Application web responsive (marketplace)'],
    ['Secteur', 'Distribution et livraison de gaz domestique'],
    ['Zone de lancement', 'Ouagadougou ; quartiers pilotes : Ouaga 2000, 1200 Logements, Patte d\'Oie, Koulouba, Pissy, Zone du Bois'],
    ['Langage serveur', 'PHP 8 (PDO)'],
    ['Base de données', 'MySQL / MariaDB, moteur InnoDB'],
    ['Cartographie', 'Leaflet et OpenStreetMap'],
  ], [2800, 6838], { premiereGras: true }),
  vide(120),
  h2('1.4 Périmètre'),
  p('Le présent cahier des charges couvre la première version exploitable de la plateforme (MVP) déployée sur Ouagadougou. Sont hors périmètre de cette version : l\'application mobile native, le suivi GPS en temps réel du livreur, l\'intégration réelle des API de paiement Mobile Money et l\'ouverture à d\'autres villes. Ces évolutions sont décrites au chapitre 12.'),
];

// ---------- 2. Objectifs ----------
const s2 = [
  h1('2. Objectifs'),
  h2('2.1 Objectif général'),
  p('Digitaliser la distribution du gaz domestique à Ouagadougou en reliant consommateurs, vendeurs et livreurs sur une même plateforme fiable.'),
  h2('2.2 Objectifs spécifiques'),
  puce('Permettre au client de localiser les points de vente proches et de connaître leur stock avant tout déplacement.'),
  puce('Afficher des prix et des disponibilités à jour, par marque et par capacité.'),
  puce('Offrir aux vendeurs une vitrine et un outil simple de gestion des stocks et des commandes.'),
  puce('Permettre la commande en ligne avec retrait sur place ou livraison à domicile.'),
  puce('Assurer un suivi transparent de chaque commande, de la validation à la remise.'),
  puce('Centraliser les informations sur les vendeurs, les livreurs et les commandes pour l\'administration.'),
  h2('2.3 Indicateurs de réussite du pilote'),
  p('Les indicateurs suivants serviront à juger la phase pilote, sur une période de trois mois :'),
  tableau(['Indicateur', 'Cible'], [
    ['Vendeurs actifs et validés', '5 à 10 points de vente'],
    ['Délai moyen de réponse du vendeur à une commande', 'Moins de 10 minutes'],
    ['Délai moyen de livraison à domicile', 'Moins de 45 minutes'],
    ['Commandes annulées faute de stock', 'Moins de 10 %'],
    ['Note moyenne des vendeurs', 'Au moins 4 sur 5'],
  ], [6000, 3638]),
];

// ---------- 3. Acteurs ----------
const s3 = [
  h1('3. Acteurs et rôles'),
  p('Chaque personne dispose d\'un compte unique. Le rôle du compte détermine l\'espace auquel elle accède. Les comptes vendeur et livreur sont soumis à une validation de l\'administrateur avant de pouvoir être utilisés, compte tenu du caractère inflammable du produit.'),
  tableau(['Acteur', 'Description', 'Activation du compte'], [
    ['Client', 'Ménage ou particulier qui recherche un point de vente, commande une ou plusieurs bouteilles et suit sa commande.', 'Immédiate'],
    ['Vendeur', 'Gérant d\'un dépôt, d\'une station ou d\'une boutique. Publie ses stocks et ses prix, traite les commandes.', 'Après validation (autorisation d\'exploitation)'],
    ['Livreur', 'Indépendant équipé d\'une moto, d\'un tricycle ou d\'un vélo. Accepte les courses et livre les clients.', 'Après validation (pièce d\'identité, engin)'],
    ['Administrateur', 'Membre de l\'équipe GazExpress. Valide les partenaires, supervise l\'activité, traite les réclamations.', 'Créé par l\'équipe technique'],
  ], [1900, 5238, 2500], { premiereGras: true }),
];

// ---------- 4. Besoins fonctionnels ----------
const prio = ['Exigence', 'Description', 'Priorité'];
const lp = [1300, 6838, 1500];
const s4 = [
  h1('4. Besoins fonctionnels'),
  p('Chaque exigence est identifiée par un code unique, réutilisé dans le plan de recette. La priorité « MVP » désigne les fonctions indispensables à la première version ; « Évolution » désigne les fonctions prévues pour une version ultérieure.'),
  h2('4.1 Fonctions communes'),
  tableau(prio, [
    ['F-COM-01', 'Créer un compte en choisissant son rôle (client, vendeur ou livreur) avec nom, prénom, téléphone, e-mail, quartier et mot de passe.', 'MVP'],
    ['F-COM-02', 'Se connecter avec son e-mail ou son numéro de téléphone, puis se déconnecter.', 'MVP'],
    ['F-COM-03', 'Recevoir des notifications dans l\'application (nouvelle commande, acceptation, départ du livreur, livraison, réponse à une réclamation).', 'MVP'],
    ['F-COM-04', 'Consulter la carte des vendeurs et le catalogue sans être connecté.', 'MVP'],
    ['F-COM-05', 'Réinitialiser son mot de passe par SMS.', 'Évolution'],
  ], lp),
  vide(160),
  h2('4.2 Client'),
  tableau(prio, [
    ['F-CLI-01', 'Afficher les vendeurs validés sur une carte interactive centrée sur Ouagadougou, avec l\'état global de leur stock.', 'MVP'],
    ['F-CLI-02', 'Rechercher un vendeur par nom ou par quartier et filtrer par quartier.', 'MVP'],
    ['F-CLI-03', 'Autoriser la géolocalisation pour centrer la carte sur sa position et repérer le vendeur le plus proche.', 'MVP'],
    ['F-CLI-04', 'Consulter la fiche d\'un vendeur : adresse, horaires, téléphone, produits, prix, état du stock et avis.', 'MVP'],
    ['F-CLI-05', 'Consulter le catalogue général et filtrer par marque (SODIGAZ, TOTAL, ORYX).', 'MVP'],
    ['F-CLI-06', 'Ajouter des bouteilles au panier, modifier les quantités, vider le panier.', 'MVP'],
    ['F-CLI-07', 'Choisir le mode de récupération (livraison à domicile ou retrait sur place), le quartier, l\'adresse et le moyen de paiement, puis confirmer.', 'MVP'],
    ['F-CLI-08', 'Voir le détail des frais (sous-total, frais de livraison, total) avant confirmation.', 'MVP'],
    ['F-CLI-09', 'Suivre l\'avancement de sa commande en direct et consulter l\'historique de ses commandes.', 'MVP'],
    ['F-CLI-10', 'Annuler une commande tant qu\'elle n\'a pas été acceptée par le vendeur.', 'MVP'],
    ['F-CLI-11', 'Noter le vendeur et le livreur après une commande livrée et laisser un commentaire.', 'MVP'],
    ['F-CLI-12', 'Signaler un problème (réclamation) sur une commande.', 'MVP'],
  ], lp),
  vide(160),
  h2('4.3 Vendeur'),
  tableau(prio, [
    ['F-VEN-01', 'Créer un compte professionnel : nom du commerce, adresse, position, horaires, numéro d\'autorisation d\'exploitation.', 'MVP'],
    ['F-VEN-02', 'Consulter un tableau de bord : commandes du jour, chiffre d\'affaires du jour, bouteilles en stock.', 'MVP'],
    ['F-VEN-03', 'Ouvrir ou fermer sa boutique en un clic (une boutique fermée ne reçoit plus de commandes).', 'MVP'],
    ['F-VEN-04', 'Mettre à jour la quantité et le prix de chaque référence, déclarer une rupture, ajouter une nouvelle référence.', 'MVP'],
    ['F-VEN-05', 'Recevoir les commandes entrantes et les accepter ou les refuser selon le stock réel.', 'MVP'],
    ['F-VEN-06', 'Passer une commande acceptée en préparation ; confirmer la remise d\'une commande en retrait.', 'MVP'],
    ['F-VEN-07', 'Consulter l\'historique des ventes et les avis des clients.', 'MVP'],
    ['F-VEN-08', 'Exporter ses ventes mensuelles (tableur).', 'Évolution'],
  ], lp),
  vide(160),
  h2('4.4 Livreur'),
  tableau(prio, [
    ['F-LIV-01', 'Créer un compte livreur avec le type d\'engin, l\'immatriculation et le numéro de pièce d\'identité.', 'MVP'],
    ['F-LIV-02', 'Passer « En service » ou « Hors service ».', 'MVP'],
    ['F-LIV-03', 'Consulter les courses disponibles à proximité avec le trajet, la distance et le gain.', 'MVP'],
    ['F-LIV-04', 'Accepter une course ; une seule course en cours à la fois.', 'MVP'],
    ['F-LIV-05', 'Voir le point de retrait (A) et la destination du client (B), appeler le vendeur ou le client, ouvrir l\'itinéraire.', 'MVP'],
    ['F-LIV-06', 'Confirmer la récupération du colis, puis la remise au client.', 'MVP'],
    ['F-LIV-07', 'Consulter ses statistiques : courses effectuées, gains du jour, note moyenne, dernières livraisons.', 'MVP'],
    ['F-LIV-08', 'Partager sa position GPS en temps réel avec le client.', 'Évolution'],
  ], lp),
  vide(160),
  h2('4.5 Administrateur'),
  tableau(prio, [
    ['F-ADM-01', 'Consulter un tableau de bord : volume de commandes, commandes en cours, montant livré, commission, répartition des comptes.', 'MVP'],
    ['F-ADM-02', 'Valider ou refuser les demandes des vendeurs et des livreurs.', 'MVP'],
    ['F-ADM-03', 'Rechercher un utilisateur, suspendre ou réactiver un compte.', 'MVP'],
    ['F-ADM-04', 'Superviser toutes les commandes, les filtrer par statut et annuler une commande en litige.', 'MVP'],
    ['F-ADM-05', 'Traiter les réclamations et répondre au client.', 'MVP'],
    ['F-ADM-06', 'Gérer le référentiel des quartiers et des frais de livraison depuis l\'interface.', 'Évolution'],
  ], lp),
];

// ---------- 5. Règles de gestion ----------
const s5 = [
  h1('5. Règles de gestion'),
  tableau(['Code', 'Règle'], [
    ['RG-01', 'L\'état d\'un stock n\'est jamais saisi : il est calculé à partir de la quantité. 0 bouteille : Indisponible ; 1 à 4 : Stock limité ; 5 et plus : Disponible.'],
    ['RG-02', 'Une commande concerne un seul vendeur. Ajouter au panier un produit d\'un autre vendeur vide le panier en cours, avec un message d\'information.'],
    ['RG-03', 'Le stock est contrôlé à la création de la commande, puis de nouveau à l\'acceptation. Il n\'est décrémenté qu\'au moment où le vendeur accepte.'],
    ['RG-04', 'Seuls les vendeurs validés, dont le compte est actif et la boutique ouverte, peuvent recevoir des commandes.'],
    ['RG-05', 'Les frais de livraison dépendent du quartier de destination (500 FCFA par défaut, jusqu\'à 800 FCFA pour les quartiers éloignés). Le retrait sur place est gratuit.'],
    ['RG-06', 'Le client peut annuler sa commande tant qu\'elle est « En attente ». Au-delà, seul le vendeur (refus) ou l\'administrateur peut l\'annuler.'],
    ['RG-07', 'Toute annulation d\'une commande déjà acceptée restitue les quantités au stock du vendeur. Un paiement Mobile Money déjà validé passe à l\'état « Remboursé ».'],
    ['RG-08', 'Une course n\'est proposée aux livreurs qu\'après acceptation par le vendeur. La première acceptation l\'emporte : la mise à jour est atomique.'],
    ['RG-09', 'Un livreur ne peut avoir qu\'une course en cours et ne peut pas passer hors service pendant une course.'],
    ['RG-10', 'Le paiement en espèces est marqué « Validé » à la remise de la commande. Le paiement Mobile Money est validé à la commande (simulé dans le MVP).'],
    ['RG-11', 'Un avis ne peut être déposé qu\'une seule fois, sur une commande livrée, par le client de cette commande.'],
    ['RG-12', 'Chaque changement de statut d\'une commande est historisé avec sa date et son auteur.'],
    ['RG-13', 'Un compte suspendu est déconnecté à sa prochaine action et ne peut plus se connecter.'],
    ['RG-14', 'La plateforme prélève une commission de 5 % sur le montant des articles des commandes livrées.'],
  ], [1300, 8338], { premiereGras: true }),
];

// ---------- 6. Cycle de vie ----------
const s6 = [
  h1('6. Déroulement d\'une commande'),
  h2('6.1 Parcours type'),
  num('Le client ouvre l\'application et autorise sa localisation ; la carte affiche les vendeurs proches.'),
  num('Il compare les prix et les stocks, choisit une boutique et ajoute ses bouteilles au panier.'),
  num('Il choisit livraison ou retrait, indique son quartier et son moyen de paiement, puis confirme.'),
  num('Le vendeur reçoit la commande, vérifie son stock réel et l\'accepte ou la refuse.'),
  num('Le vendeur prépare la commande. En cas de livraison, un livreur validé prend la course.'),
  num('Le livreur récupère le colis au point de vente et se rend chez le client.'),
  num('La remise est confirmée (par le livreur ou par le vendeur pour un retrait) ; la commande est clôturée et le client peut laisser un avis.'),
  vide(80),
  h2('6.2 Statuts de la commande'),
  tableau(['Statut', 'Signification', 'Déclenché par'], [
    ['En attente', 'Commande transmise, en attente de la décision du vendeur.', 'Client (confirmation du panier)'],
    ['Acceptée', 'Stock confirmé et décrémenté.', 'Vendeur'],
    ['En préparation', 'Bouteilles en cours de préparation au point de vente.', 'Vendeur'],
    ['En livraison', 'Colis récupéré, livreur en route vers le client.', 'Livreur'],
    ['Livrée', 'Bouteille remise au client (livraison ou retrait). Paiement validé.', 'Livreur ou vendeur'],
    ['Annulée', 'Commande annulée ou refusée, motif enregistré, stock restitué si besoin.', 'Client, vendeur ou administrateur'],
  ], [1900, 4738, 3000], { premiereGras: true }),
  vide(120),
  encadre('Transitions autorisées', [
    'En attente → Acceptée ou Annulée',
    'Acceptée → En préparation, En livraison, Livrée (retrait) ou Annulée',
    'En préparation → En livraison, Livrée (retrait) ou Annulée',
    'En livraison → Livrée ou Annulée',
    'Livrée et Annulée sont des états finaux.',
  ]),
];

// ---------- 7. Non fonctionnels ----------
const s7 = [
  h1('7. Exigences non fonctionnelles'),
  tableau(['Domaine', 'Exigence'], [
    ['Ergonomie', 'Conception « mobile d\'abord » : boutons larges, navigation par onglets, commande en trois étapes au plus (choisir, configurer, confirmer). Interface entièrement en français.'],
    ['Compatibilité', 'Fonctionnement sur les navigateurs récents (Chrome, Firefox, Edge, Safari) et sur des écrans de 360 px de large et plus.'],
    ['Performance', 'Affichage d\'une page en moins de 3 secondes sur une connexion 3G. Rafraîchissement des notifications toutes les 20 secondes, du suivi de commande toutes les 10 secondes.'],
    ['Sécurité', 'Mots de passe hachés (password_hash / password_verify), requêtes préparées PDO, échappement des sorties (htmlspecialchars), jeton CSRF sur tous les formulaires, cookie de session HttpOnly et SameSite, régénération de l\'identifiant de session à la connexion, limitation des tentatives de connexion, contrôle du rôle sur chaque action.'],
    ['Intégrité', 'Clés étrangères InnoDB, contraintes d\'unicité, transactions lors de la création, de l\'acceptation et de l\'annulation des commandes.'],
    ['Traçabilité', 'Historique des statuts, horodatage des validations, des paiements et des livraisons.'],
    ['Disponibilité', 'En cas d\'indisponibilité de la base, l\'utilisateur voit un message clair au lieu d\'une erreur technique.'],
    ['Maintenabilité', 'Architecture MVC, code commenté, configuration centralisée dans un fichier unique.'],
  ], [2000, 7638], { premiereGras: true }),
];

// ---------- 8. Charte graphique ----------
const palette = new Table({
  width: { size: LARG, type: WidthType.DXA }, columnWidths: [900, 1700, 1500, 5538],
  rows: [
    new TableRow({ tableHeader: true, children: ['Aperçu', 'Rôle', 'Code HEX', 'Nom et usage'].map((e, i) => new TableCell({
      children: [new Paragraph({ children: [t(e, { bold: true, color: 'FFFFFF', size: 19 })] })],
      width: { size: [900, 1700, 1500, 5538][i], type: WidthType.DXA }, borders: bords,
      margins: { top: 80, bottom: 80, left: 110, right: 110 }, shading: { fill: B, type: ShadingType.CLEAR, color: 'auto' },
    })) }),
    ...[
      ['58111A', 'Primaire', '#58111A', 'Rouge Bordeaux Profond : boutons principaux, logo, marqueurs de la carte.'],
      ['7A1824', 'Accent', '#7A1824', 'Bordeaux Vif : survol des boutons, dégradés des bandeaux.'],
      ['FDF2F4', 'Fond léger', '#FDF2F4', 'Rosé Pâle : encarts actifs, fonds de badges, sélection.'],
      ['0F172A', 'Structure', '#0F172A / #1E293B', 'Bleu Nuit / Anthracite : titres, texte principal, bandeaux.'],
      ['F8FAFC', 'Surface', '#F8FAFC', 'Blanc Cassé : arrière-plan général des pages.'],
      ['10B981', 'État', '#10B981', 'Disponible / Validé : stock suffisant, commande acceptée ou livrée.'],
      ['F59E0B', 'État', '#F59E0B', 'Stock limité / En cours : moins de 5 bouteilles, commande en préparation.'],
      ['EF4444', 'État', '#EF4444', 'Indisponible / Annulé : rupture de stock, commande annulée.'],
    ].map(([hex, role, code, usage]) => new TableRow({ cantSplit: true, children: [
      pastille(hex), cellule(role, 1700, { gras: true }), cellule(code, 1500), cellule(usage, 5538),
    ] })),
  ],
});
const s8 = [
  h1('8. Charte graphique et ergonomie'),
  p('Le positionnement visuel recherche un équilibre entre sobriété institutionnelle, confiance et lisibilité sur smartphone. Le rouge bordeaux profond démarque GazExpress des plateformes généralistes tout en évoquant une chaleur maîtrisée et la sécurité énergétique. La charte est appliquée à l\'application et au présent document.'),
  h2('8.1 Palette chromatique'),
  palette,
  vide(160),
  h2('8.2 Typographie'),
  puce([gras('Police de l\'application : '), t('Inter (repli : Noto Sans, Segoe UI), choisie pour la lisibilité des chiffres : prix en FCFA, références de commande, horaires.')]),
  puce([gras('Hiérarchie : '), t('titres en graisse 800, sous-titres en 700, texte courant en 400 à 500, taille de base 16 px.')]),
  puce([gras('Montants : '), t('toujours suivis de « FCFA », avec une espace fine pour les milliers (3 000 FCFA).')]),
  h2('8.3 Composants'),
  tableau(['Composant', 'Règle d\'usage'], [
    ['Bouton principal', 'Fond bordeaux #58111A, texte blanc, coins arrondis. Une seule action principale par bloc.'],
    ['Bouton secondaire', 'Fond rosé #FDF2F4, texte bordeaux, filet rosé.'],
    ['Bouton sombre', 'Fond bleu nuit #0F172A ; actions neutres (accepter une course, filtres actifs).'],
    ['Pastille d\'état', 'Pastille arrondie avec point coloré : vert (Disponible), ambre (Stock limité), rouge (Indisponible).'],
    ['Bandeau d\'espace', 'Dégradé du bleu nuit vers le bordeaux, surtitre en capitales, titre en blanc.'],
    ['Carte', 'Fond blanc, filet gris clair, rayon de 20 à 28 px, ombre légère.'],
  ], [2400, 7238], { premiereGras: true }),
  h2('8.4 Écrans clés'),
  tableau(['Écran', 'Contenu'], [
    ['Carte et vendeurs', 'Bandeau d\'accroche, recherche par quartier, filtres rapides, carte Leaflet / OpenStreetMap avec marqueurs colorés selon le stock, liste des vendeurs partenaires.'],
    ['Catalogue', 'Sélection de la boutique, filtres par marque, grille des bouteilles (marque, capacité, état du stock, prix, bouton Ajouter).'],
    ['Panier', 'Tiroir latéral : articles et quantités, choix livraison (+500 F) ou retrait (0 F), quartier, paiement (Orange Money, Moov Money, espèces), totaux, confirmation.'],
    ['Suivi de commande', 'Référence, date, statut, barre de progression en cinq étapes, informations boutique et livraison, articles, historique, avis et réclamation.'],
    ['Espace vendeur', 'Statut de la boutique, trois indicateurs, gestion des stocks par marque, commandes entrantes avec Accepter / Refuser, commandes en cours, historique et avis.'],
    ['Espace livreur', 'Statut de service, courses effectuées, gains du jour, note, course en cours (points A et B), courses disponibles à proximité.'],
    ['Administration', 'Indicateurs, graphique des commandes sur 7 jours, validations, utilisateurs, commandes, réclamations.'],
  ], [2400, 7238], { premiereGras: true }),
  p([t('Les maquettes de référence figurent en annexe A.', { italics: true, color: GRIS })], { spacing: { before: 120 } }),
];

// ---------- 9. Architecture ----------
const s9 = [
  h1('9. Architecture technique'),
  h2('9.1 Pile technologique'),
  tableau(['Couche', 'Technologies retenues'], [
    ['Présentation', 'HTML5, CSS3 (feuille de style maison fidèle à la charte), JavaScript natif (fetch / AJAX), Font Awesome, police Inter'],
    ['Logique applicative', 'PHP 8 avec PDO, architecture MVC sans framework, contrôleur frontal et routeur'],
    ['Données', 'MySQL / MariaDB 10.4, moteur InnoDB, jeu de caractères utf8mb4'],
    ['Cartographie', 'Leaflet 1.9, fonds OpenStreetMap, géolocalisation du navigateur'],
    ['Paiement', 'Espèces à la livraison ; Orange Money et Moov Money simulés dans le MVP'],
    ['Environnement', 'XAMPP (Apache, MariaDB, phpMyAdmin), VS Code, Git'],
  ], [2400, 7238], { premiereGras: true }),
  vide(120),
  h2('9.2 Organisation du code'),
  p('Toutes les requêtes passent par un contrôleur frontal (index.php) grâce à la réécriture d\'URL d\'Apache (.htaccess). Le routeur associe chaque adresse à une action de contrôleur ; les contrôleurs s\'appuient sur les modèles pour l\'accès aux données et transmettent le résultat aux vues.'),
  tableau(['Dossier', 'Contenu'], [
    ['config/', 'Paramètres de connexion MySQL et constantes de l\'application'],
    ['app/core/', 'Routeur, connexion PDO, classes de base Model et Controller, authentification, panier, fonctions utilitaires'],
    ['app/models/', 'Un modèle par entité : Utilisateur, Vendeur, Livreur, Produit, Stock, Commande, Livraison, Avis, Notification, Reclamation, Quartier'],
    ['app/controllers/', 'Accueil, Catalogue, Panier, Commande, Vendeur, Livreur, Admin, Auth, Api (JSON)'],
    ['app/views/', 'Gabarit commun, tiroir du panier et vues de chaque écran'],
    ['public/', 'Feuilles de style, scripts JavaScript, images'],
    ['database/', 'Script SQL de création et jeu de données de démonstration'],
    ['docs/', 'Cahier des charges, diagramme de classes, maquettes'],
  ], [2400, 7238], { premiereGras: true }),
  vide(120),
  h2('9.3 Échanges asynchrones'),
  p('Les notifications, le suivi de commande et l\'arrivée de nouvelles commandes chez le vendeur sont rafraîchis par des requêtes AJAX régulières vers des points d\'accès JSON (api/notifications, api/commande-statut, api/vendeur-commandes). Cette approche évite l\'installation d\'un serveur WebSocket dans la première version.'),
];

// ---------- 10. Modèle de données ----------
const s10a = [
  h1('10. Modèle de données'),
  p('Le modèle comporte quatorze classes. Le rôle de chaque acteur est porté par la classe Utilisateur ; les classes Vendeur et Livreur sont des profils complémentaires (relation 1 – 0..1) qui portent les informations professionnelles et l\'état de validation. Cette organisation garde une seule table de comptes, donc une seule logique d\'authentification.'),
  p('Par rapport à la version 2.0, cinq classes ont été ajoutées pour couvrir les exigences du cahier : Quartier (frais de livraison par zone), Livreur (profil et disponibilité), Notification, HistoriqueStatut (traçabilité) et Reclamation.'),
  h2('10.1 Description des classes'),
  tableau(['Classe', 'Rôle'], [
    ['Utilisateur', 'Compte de tout acteur : identité, coordonnées, rôle, statut (actif ou suspendu).'],
    ['Vendeur', 'Profil commercial : commerce, localisation, horaires, ouverture, autorisation, validation.'],
    ['Livreur', 'Profil du livreur : engin, pièce d\'identité, disponibilité, validation.'],
    ['Quartier', 'Zone desservie avec ses coordonnées et ses frais de livraison.'],
    ['Produit', 'Type de bouteille : marque, capacité, prix de référence.'],
    ['Stock', 'Quantité et prix d\'un produit chez un vendeur.'],
    ['Commande', 'Demande d\'un client auprès d\'un vendeur, avec montants, statut et mode de récupération.'],
    ['DetailCommande', 'Ligne de commande : produit, quantité, prix unitaire figé au moment de la commande.'],
    ['Livraison', 'Livraison d\'une commande : adresse, distance, frais, livreur, statut, note du livreur.'],
    ['Paiement', 'Règlement d\'une commande : mode, statut, référence de transaction.'],
    ['Avis', 'Note et commentaire d\'un client sur un vendeur après une commande livrée.'],
    ['Notification', 'Message affiché à un utilisateur dans l\'application.'],
    ['HistoriqueStatut', 'Trace de chaque changement de statut d\'une commande.'],
    ['Reclamation', 'Signalement d\'un client sur une commande et réponse de l\'administration.'],
  ], [2400, 7238], { premiereGras: true }),
];
const s10diagramme = [
  new Paragraph({ heading: HeadingLevel.HEADING_2, children: [t('10.2 Diagramme de classes')] }),
  image(path.join(DOCS, 'diagramme_classes.png'), 715, { titre: 'Diagramme de classes GazExpress' }),
  legende('Figure 1 : diagramme de classes (source modifiable : docs/diagramme_classes.drawio)'),
];
const s10b = [
  h2('10.3 Dictionnaire des tables MySQL'),
  p('Chaque table possède une clé primaire id auto-incrémentée. Les clés étrangères sont déclarées en InnoDB.'),
  tableau(['Table', 'Colonnes principales', 'Contraintes notables'], [
    ['quartiers', 'id, nom, latitude, longitude, frais_livraison, actif', 'nom unique'],
    ['utilisateurs', 'id, nom, prenom, telephone, email, mot_de_passe, role, statut, quartier_id, adresse, date_creation, derniere_connexion', 'email et téléphone uniques'],
    ['vendeurs', 'id, utilisateur_id, nom_commerce, quartier_id, adresse, telephone, latitude, longitude, heure_ouverture, heure_fermeture, est_ouvert, numero_autorisation, statut_validation, date_validation', 'un profil par utilisateur'],
    ['livreurs', 'id, utilisateur_id, type_engin, immatriculation, numero_piece, disponible, statut_validation, date_validation', 'un profil par utilisateur'],
    ['produits', 'id, designation, marque, capacite_kg, description, prix_reference, actif', '(marque, capacite_kg) unique'],
    ['stocks', 'id, vendeur_id, produit_id, quantite, prix, date_mise_a_jour', '(vendeur_id, produit_id) unique'],
    ['commandes', 'id, reference, client_id, vendeur_id, date_commande, montant_articles, frais_livraison, montant_total, statut, mode_recuperation, motif_annulation, date_cloture', 'référence unique (GX-xxxx)'],
    ['details_commande', 'id, commande_id, produit_id, quantite, prix_unitaire', '(commande_id, produit_id) unique'],
    ['livraisons', 'id, commande_id, livreur_id, quartier_id, adresse_livraison, latitude, longitude, distance_km, frais, statut, note_livreur, date_prise, date_livraison', 'une livraison par commande ; absente en retrait'],
    ['paiements', 'id, commande_id, montant, mode_paiement, statut, reference_transaction, date_paiement', 'un paiement par commande'],
    ['avis', 'id, client_id, vendeur_id, commande_id, note, commentaire, date_avis', 'un avis par commande ; note de 1 à 5'],
    ['notifications', 'id, utilisateur_id, commande_id, message, lien, est_lue, date_creation', 'index (utilisateur_id, est_lue)'],
    ['historique_statuts', 'id, commande_id, ancien_statut, nouveau_statut, auteur_id, date_changement', ''],
    ['reclamations', 'id, commande_id, client_id, objet, message, statut, reponse, date_creation, date_traitement', ''],
  ], [2000, 5238, 2400], { premiereGras: true }),
  vide(120),
  p('Une vue v_synthese_vendeurs agrège, pour chaque vendeur, le stock total, le nombre de commandes, le chiffre d\'affaires livré et la note moyenne. Elle est consultable depuis phpMyAdmin.'),
];

// ---------- 11 à 16 ----------
const s11 = [
  h1('11. Sécurité du produit et contraintes réglementaires'),
  p('Le gaz butane est un produit inflammable. La plateforme intègre les mesures suivantes :'),
  puce('Vérification de l\'identité des vendeurs et de leur autorisation d\'exploitation avant toute mise en ligne.'),
  puce('Vérification de l\'identité des livreurs et de leur moyen de transport.'),
  puce('Rappel des consignes de sécurité aux clients (bouteille debout, local aéré, contrôle du joint et du détendeur).'),
  puce('Respect des règles de stockage et de transport du gaz en vigueur au Burkina Faso ; un livreur ne transporte que le nombre de bouteilles autorisé par son engin.'),
  puce('Protection des données personnelles : collecte limitée au nécessaire, numéro du client visible uniquement par le vendeur et le livreur de sa commande.'),
  puce('Sécurisation des paiements : aucune donnée de compte Mobile Money n\'est conservée par la plateforme.'),
  h1Suite('12. Modèle économique et évolutions'),
  h2('12.1 Sources de revenus'),
  puce('Commission de 5 % sur les commandes livrées.'),
  puce('Part des frais de livraison, ajustés selon la distance.'),
  puce('Abonnement facultatif pour les vendeurs (statistiques avancées, mise en avant).'),
  puce('Mise en avant payante des points de vente sur la carte.'),
  h2('12.2 Feuille de route après le MVP'),
  tableau(['Évolution', 'Bénéfice attendu'], [
    ['Intégration des API Orange Money et Moov Money', 'Paiement réel et rapprochement automatique'],
    ['Suivi GPS du livreur en temps réel', 'Meilleure information du client, estimation de l\'heure d\'arrivée'],
    ['Notifications SMS et WhatsApp', 'Joindre les clients sans connexion permanente'],
    ['Application mobile (PWA puis native)', 'Usage hors ligne partiel, notifications push'],
    ['Extension à Bobo-Dioulasso puis à d\'autres villes', 'Croissance du nombre de vendeurs et de clients'],
  ], [4800, 4838]),
];
const s13 = [
  h1('13. Version initiale (MVP)'),
  p('Pour limiter les coûts et les délais, la première version comprend :'),
  puce('Inscription, connexion et gestion des quatre rôles.'),
  puce('Validation des vendeurs et des livreurs par l\'administrateur.'),
  puce('Carte des vendeurs, fiches boutique, catalogue, stocks et prix.'),
  puce('Panier, commande avec retrait ou livraison, frais par quartier.'),
  puce('Suivi des statuts, notifications in-app, historique.'),
  puce('Espaces vendeur, livreur et administration.'),
  puce('Paiement à la livraison ; Mobile Money simulé.'),
  puce('Avis et réclamations.'),
  h1Suite('14. Planning de réalisation'),
  tableau(['Phase', 'Contenu', 'Durée indicative'], [
    ['1. Étude et conception', 'Analyse des besoins, enquête auprès de vendeurs et de clients, maquettes, charte, modèle de données', '2 semaines'],
    ['2. Socle', 'Base de données, architecture MVC, authentification, rôles', '1 semaine'],
    ['3. Espaces client et vendeur', 'Carte, catalogue, stocks, panier, commande, suivi', '2 semaines'],
    ['4. Livraison et administration', 'Espace livreur, validations, supervision, réclamations', '1 semaine'],
    ['5. Recette et sécurisation', 'Tests fonctionnels, tests de sécurité, corrections', '1 semaine'],
    ['6. Pilote', '5 à 10 vendeurs à Ouagadougou, retours, ajustements', '4 à 12 semaines'],
  ], [2700, 4938, 2000], { premiereGras: true }),
  h1Suite('15. Livrables'),
  tableau(['Livrable', 'Format'], [
    ['Cahier des charges (présent document)', 'Word (.docx)'],
    ['Diagramme de classes', 'draw.io (.drawio) et image PNG'],
    ['Base de données avec jeu de démonstration', 'Script SQL importable dans phpMyAdmin'],
    ['Application web', 'Code source PHP / MySQL'],
    ['Guide d\'installation et comptes de démonstration', 'Fichier README.md'],
  ], [6000, 3638]),
];
const s16 = [
  h1('16. Critères de recette'),
  p('La recette est prononcée lorsque les scénarios suivants sont réalisés sans erreur sur l\'environnement XAMPP avec le jeu de démonstration.'),
  tableau(['N°', 'Scénario', 'Résultat attendu', 'Exigences'], [
    ['R1', 'Un visiteur consulte la carte, filtre par quartier et ouvre une fiche vendeur.', 'Les vendeurs validés s\'affichent avec la bonne couleur d\'état.', 'F-CLI-01 à 04'],
    ['R2', 'Un client ajoute deux bouteilles, choisit la livraison à Ouaga 2000 et confirme.', 'Commande « En attente », total = articles + 500 FCFA, vendeur notifié.', 'F-CLI-06 à 08'],
    ['R3', 'Le vendeur accepte puis passe la commande en préparation.', 'Stock diminué de deux unités, client notifié à chaque étape.', 'F-VEN-05, 06, RG-03'],
    ['R4', 'Un livreur en service accepte la course, récupère le colis et confirme la livraison.', 'Commande « Livrée », paiement validé, gain ajouté au jour.', 'F-LIV-03 à 06'],
    ['R5', 'Le client note le vendeur et le livreur.', 'Notes visibles sur la fiche vendeur et dans l\'espace livreur.', 'F-CLI-11'],
    ['R6', 'Le client annule une commande en attente ; tente d\'annuler une commande acceptée.', 'Première annulation acceptée, seconde refusée.', 'RG-06'],
    ['R7', 'L\'administrateur valide un vendeur en attente et suspend un client.', 'Le vendeur apparaît sur la carte ; le client suspendu est déconnecté.', 'F-ADM-02, 03, RG-13'],
    ['R8', 'Un client tente d\'accéder à l\'espace vendeur ou d\'envoyer un formulaire sans jeton.', 'Accès refusé.', 'Sécurité'],
  ], [700, 3500, 3438, 2000], { premiereGras: true }),
];

// ---------- annexes ----------
const M = f => path.join(DOCS, 'maquette', f);
const annexes = [
  h1('Annexe A. Maquettes de référence'),
  p('Les captures suivantes constituent la référence visuelle de l\'application.'),
  image(M('Screenshot 2026-11-18 092442.png'), 600, { titre: 'Accueil et carte' }), legende('Figure A1 : accueil, bandeau d\'accroche et recherche par quartier'),
  image(M('Screenshot 2026-11-18 092507.png'), 600, { titre: 'Catalogue' }), legende('Figure A2 : catalogue général et filtres par marque'),
  image(M('Screenshot 2026-11-18 092531.png'), 600, { titre: 'Suivi de commande' }), legende('Figure A3 : suivi de commande en cinq étapes'),
  image(M('Screenshot 2026-11-18 092554.png'), 600, { titre: 'Espace vendeur' }), legende('Figure A4 : tableau de bord vendeur'),
  image(M('Screenshot 2026-11-18 092608.png'), 600, { titre: 'Espace livreur' }), legende('Figure A5 : espace livreur'),
  image(M('Screenshot 2026-11-18 092643.png'), 260, { titre: 'Panier' }), legende('Figure A6 : panier et tunnel de commande'),
  h1('Annexe B. Glossaire'),
  tableau(['Terme', 'Définition'], [
    ['AJAX', 'Technique permettant à une page d\'échanger des données avec le serveur sans être rechargée.'],
    ['CSRF', 'Attaque consistant à faire exécuter une action à un utilisateur connecté à son insu ; contrée par un jeton secret dans chaque formulaire.'],
    ['FCFA', 'Franc de la Communauté financière africaine, monnaie du Burkina Faso.'],
    ['Marketplace', 'Plateforme mettant en relation des vendeurs indépendants et des acheteurs.'],
    ['MVC', 'Modèle-Vue-Contrôleur : séparation des données, de l\'affichage et de la logique de traitement.'],
    ['MVP', 'Produit minimum viable : première version réduite aux fonctions essentielles.'],
    ['PDO', 'Extension PHP d\'accès aux bases de données, utilisée avec des requêtes préparées.'],
    ['Recharge / consignation', 'Échange d\'une bouteille vide contre une pleine (recharge) ou achat de la bouteille avec dépôt (consignation).'],
  ], [2400, 7238], { premiereGras: true }),
];

// ---------- en-tête / pied ----------
const entete = new Header({ children: [new Paragraph({
  border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: ROSE_B, space: 6 } },
  tabStops: [{ type: TabStopType.RIGHT, position: TabStopPosition.MAX }],
  children: [t('Gaz', { bold: true, size: 18, color: NUIT }), t('Express', { bold: true, size: 18, color: B }), t('\tCahier des charges · version 3.0', { size: 17, color: GRIS })],
})] });
const pied = new Footer({ children: [new Paragraph({
  tabStops: [{ type: TabStopType.RIGHT, position: TabStopPosition.MAX }],
  children: [t('Projet académique PHP / MySQL · Ouagadougou, novembre 2026', { size: 16, color: GRIS }),
    new TextRun({ font: FONT, children: ['\tPage ', PageNumber.CURRENT, ' sur ', PageNumber.TOTAL_PAGES], size: 16, color: GRIS })],
})] });

const marges = { top: 1134, bottom: 1134, left: 1134, right: 1134, header: 567, footer: 567 };

const doc = new Document({
  creator: 'Équipe projet GazExpress',
  title: 'Cahier des charges GazExpress v3.0',
  description: 'Cahier des charges de la plateforme GazExpress',
  features: { updateFields: true },
  styles: {
    default: { document: { run: { font: FONT, size: 21, color: NUIT } } },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: FONT, size: 34, bold: true, color: B },
        paragraph: { spacing: { before: 120, after: 220 }, keepNext: true, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: ROSE_B, space: 6 } } } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: FONT, size: 26, bold: true, color: NUIT },
        paragraph: { spacing: { before: 260, after: 120 }, keepNext: true, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { font: FONT, size: 22, bold: true, color: ACC },
        paragraph: { spacing: { before: 200, after: 80 }, keepNext: true, outlineLevel: 2 } },
    ],
  },
  numbering: { config: [
    { reference: 'puces', levels: [
      { level: 0, format: LevelFormat.BULLET, text: '▪', alignment: AlignmentType.LEFT, style: { run: { color: B }, paragraph: { indent: { left: 500, hanging: 280 } } } },
      { level: 1, format: LevelFormat.BULLET, text: '–', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 1000, hanging: 280 } } } },
    ] },
    { reference: 'etapes', levels: [
      { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { run: { bold: true, color: B }, paragraph: { indent: { left: 500, hanging: 360 } } } },
    ] },
  ] },
  sections: [
    { properties: { page: { margin: marges } }, children: garde },
    { properties: { page: { margin: marges } }, headers: { default: entete }, footers: { default: pied },
      children: [...suivi, ...s1, ...s2, ...s3, ...s4, ...s5, ...s6, ...s7, ...s8, ...s9, ...s10a] },
    { properties: { page: { margin: { top: 680, bottom: 680, left: 900, right: 900, header: 400, footer: 400 }, size: { orientation: PageOrientation.LANDSCAPE } } }, headers: { default: entete }, footers: { default: pied },
      children: s10diagramme },
    { properties: { page: { margin: marges } }, headers: { default: entete }, footers: { default: pied },
      children: [...s10b, ...s11, ...s13, ...s16, ...annexes] },
  ],
});

Packer.toBuffer(doc).then(buf => { fs.writeFileSync(OUT, buf); console.log('écrit', OUT, buf.length, 'octets'); });
