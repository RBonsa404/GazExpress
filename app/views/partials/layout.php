<?php
/** @var string $contenu  @var string $titre  @var string $page */
$utilisateur = Auth::user();
$role        = $utilisateur['role'] ?? null;
$nbPanier    = Panier::nombreArticles();
$page        = $page ?? '';

// Navigation principale : ce que cherche un client
$nav = [
    'accueil'   => ['', 'Trouver un dépôt'],
    'catalogue' => ['catalogue', 'Bouteilles & prix'],
    'suivi'     => ['commandes', 'Suivi de commande'],
];
// Accès direct au tableau de bord pour les comptes professionnels
$espacePro = [
    'vendeur' => ['vendeur', 'Mon espace vendeur'],
    'livreur' => ['livreur', 'Mon espace livreur'],
    'admin'   => ['admin', 'Administration'],
][$role] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GazExpress : bouteilles de gaz domestique à Ouagadougou. Comparez les prix et les stocks des dépôts SODIGAZ, TOTAL et ORYX, commandez en ligne, faites-vous livrer ou retirez sur place.">
    <meta name="theme-color" content="#58111A">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($titre ?? APP_NAME) ?> · GazExpress</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($page === 'accueil'): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-base="<?= e(BASE_URL) ?>" data-icones="<?= asset('img/icones.svg') ?>" data-connecte="<?= $utilisateur ? '1' : '0' ?>">

<div class="barre-haut">
    <div class="conteneur">
        <span><?= icone('moto') ?> Livraison à domicile dans <?= e(APP_CITY_COURT) ?><span class="masque-mobile"> · tous les jours de 7 h à 21 h</span></span>
        <nav aria-label="Espaces partenaires">
            <a href="<?= url('vendeur') ?>">Espace vendeur</a>
            <a href="<?= url('livreur') ?>">Espace livreur</a>
            <a href="tel:<?= e(str_replace(' ', '', APP_TELEPHONE)) ?>"><?= e(APP_TELEPHONE) ?></a>
        </nav>
    </div>
</div>

<header class="entete">
    <div class="conteneur">
        <a href="<?= url() ?>" class="logo" aria-label="GazExpress, accueil">
            <img src="<?= asset('img/favicon.svg') ?>" alt="" width="32" height="32">
            <span>Gaz<b>Express</b></span>
        </a>

        <nav class="nav" id="nav" aria-label="Navigation principale">
            <?php foreach ($nav as $cle => [$lien, $libelle]): ?>
                <a href="<?= url($lien) ?>" <?= $page === $cle ? 'class="actif" aria-current="page"' : '' ?>><?= e($libelle) ?></a>
            <?php endforeach; ?>
            <?php if ($espacePro): ?>
                <a href="<?= url($espacePro[0]) ?>" class="lien-pro <?= $page === $role ? 'actif' : '' ?>"><?= e($espacePro[1]) ?></a>
            <?php endif; ?>
            <span class="nav-mobile-seul">
                <a href="<?= url('vendeur') ?>">Espace vendeur</a>
                <a href="<?= url('livreur') ?>">Espace livreur</a>
            </span>
        </nav>

        <div class="actions-entete">
            <?php if ($utilisateur): ?>
                <div class="menu-deroulant">
                    <button type="button" class="btn-icone" id="btn-notifs" aria-label="Notifications">
                        <?= icone('cloche') ?><span class="point" id="nb-notifs">0</span>
                    </button>
                    <div class="panneau-deroulant" id="panneau-notifs">
                        <header>Notifications</header>
                        <div class="liste-notifs" id="liste-notifs"><div class="notif-vide">Chargement…</div></div>
                    </div>
                </div>
                <div class="menu-deroulant">
                    <button type="button" class="btn-compte" id="btn-compte" aria-label="Mon compte">
                        <span class="avatar"><?= e(initiales($utilisateur['prenom'], $utilisateur['nom'])) ?></span>
                        <span class="nom-compte"><?= e($utilisateur['prenom']) ?></span>
                        <?= icone('chevron-bas', 'ic-s') ?>
                    </button>
                    <div class="panneau-deroulant menu-compte" id="panneau-compte">
                        <div class="identite">
                            <strong><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></strong>
                            <small><?= e($utilisateur['email']) ?></small>
                        </div>
                        <?php if ($role === 'client'): ?>
                            <a href="<?= url('commandes') ?>"><?= icone('recu') ?> Mes commandes</a>
                        <?php else: ?>
                            <a href="<?= url($role) ?>"><?= icone('tableau') ?> Tableau de bord</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('deconnexion') ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icone('sortie') ?> Se déconnecter</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url('connexion') ?>" class="lien-connexion"><?= icone('utilisateur') ?><span>Se connecter</span></a>
            <?php endif; ?>

            <button type="button" class="btn-panier" data-ouvrir-panier aria-label="Ouvrir le panier (<?= $nbPanier ?> article<?= $nbPanier > 1 ? 's' : '' ?>)">
                <?= icone('panier') ?><span class="libelle-panier">Panier</span>
                <span class="pastille <?= $nbPanier ? '' : 'vide' ?>"><?= $nbPanier ?></span>
            </button>

            <button type="button" class="btn-icone burger" id="burger" aria-label="Menu" aria-controls="nav" aria-expanded="false"><?= icone('menu') ?></button>
        </div>
    </div>
</header>

<div class="toasts" id="toasts">
    <?php foreach (flashes() as $f): ?>
        <div class="toast <?= e($f['type']) ?>" role="status">
            <?= icone(['succes' => 'check-cercle', 'erreur' => 'alerte'][$f['type']] ?? 'info') ?>
            <span><?= e($f['message']) ?></span>
            <button type="button" aria-label="Fermer" onclick="this.parentElement.remove()"><?= icone('fermer') ?></button>
        </div>
    <?php endforeach; ?>
</div>

<main id="contenu">
    <?= $contenu ?>
</main>

<footer class="pied">
    <div class="conteneur">
        <div class="pied-grille">
            <div class="pied-marque">
                <a href="<?= url() ?>" class="logo">
                    <img src="<?= asset('img/favicon.svg') ?>" alt="" width="28" height="28">
                    <span>Gaz<b>Express</b></span>
                </a>
                <p>Commande et livraison de bouteilles de gaz domestique auprès des dépôts agréés de Ouagadougou.</p>
            </div>
            <div>
                <h4>Commander</h4>
                <a href="<?= url() ?>">Trouver un dépôt</a>
                <a href="<?= url('catalogue') ?>">Bouteilles et prix</a>
                <a href="<?= url('commandes') ?>">Suivre une commande</a>
            </div>
            <div>
                <h4>Partenaires</h4>
                <a href="<?= url('inscription?role=vendeur') ?>">Référencer mon dépôt</a>
                <a href="<?= url('inscription?role=livreur') ?>">Devenir livreur</a>
                <a href="<?= url('connexion') ?>">Accès professionnel</a>
            </div>
            <div>
                <h4>Contact</h4>
                <a href="tel:<?= e(str_replace(' ', '', APP_TELEPHONE)) ?>"><?= e(APP_TELEPHONE) ?></a>
                <a href="mailto:<?= e(APP_EMAIL) ?>"><?= e(APP_EMAIL) ?></a>
                <span>Ouaga 2000, <?= e(APP_CITY_COURT) ?></span>
            </div>
        </div>
        <div class="pied-bas">
            <span>© <?= date('Y') ?> GazExpress</span>
            <span>Sécurité : stockez vos bouteilles debout, dans un endroit aéré, loin de toute flamme.</span>
        </div>
    </div>
</footer>

<?php
// Le tiroir interroge la base : en cas de panne MySQL, la page reste affichable.
ob_start();
try {
    require APP_PATH . '/views/partials/panier.php';
    echo ob_get_clean();
} catch (PDOException $e) {
    ob_end_clean();
}
?>

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($scripts)): foreach ($scripts as $s): ?>
    <script src="<?= $s ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
