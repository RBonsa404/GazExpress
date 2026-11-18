<?php
/** @var string $contenu  @var string $titre  @var string $page */
$utilisateur = Auth::user();
$nbPanier    = Panier::nombreArticles();
$nav = [
    'accueil'   => ['', 'fa-map-location-dot', 'Carte & Vendeurs', false],
    'catalogue' => ['catalogue', 'fa-gas-pump', 'Catalogue Gaz', false],
    'suivi'     => ['commandes', 'fa-clock-rotate-left', 'Suivi Commande', false],
    'vendeur'   => ['vendeur', 'fa-store', 'Espace Vendeur', true],
    'livreur'   => ['livreur', 'fa-motorcycle', 'Espace Livreur', true],
    'charte'    => ['charte', 'fa-palette', 'Charte UI', false],
];
if (Auth::est('admin')) {
    $nav['admin'] = ['admin', 'fa-shield-halved', 'Administration', true];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GazExpress : trouvez un vendeur de gaz près de chez vous à Ouagadougou, comparez prix et stocks, commandez en ligne et faites-vous livrer.">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($titre ?? APP_NAME) ?> · GazExpress</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?php if (($page ?? '') === 'accueil'): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-base="<?= e(BASE_URL) ?>" data-connecte="<?= $utilisateur ? '1' : '0' ?>">

<header class="entete">
    <div class="conteneur">
        <a href="<?= url() ?>" class="logo" aria-label="GazExpress — accueil">
            <span class="logo-icone"><i class="fa-solid fa-fire-flame-simple"></i></span>
            <span class="logo-texte">
                <strong>Gaz<span>Express</span></strong>
                <small><?= e(APP_CITY) ?></small>
            </span>
        </a>

        <nav class="nav" id="nav" aria-label="Navigation principale">
            <?php foreach ($nav as $cle => [$lien, $icone, $libelle, $encadre]): ?>
                <a href="<?= url($lien) ?>" class="<?= $encadre ? 'encadre' : '' ?> <?= ($page ?? '') === $cle ? 'actif' : '' ?>">
                    <i class="fa-solid <?= $icone ?>"></i><span><?= e($libelle) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="actions-entete">
            <button type="button" class="btn-panier" data-ouvrir-panier aria-label="Ouvrir le panier">
                <i class="fa-solid fa-cart-shopping"></i><span class="libelle-panier">Panier</span>
                <span class="pastille"><?= $nbPanier ?></span>
            </button>

            <?php if ($utilisateur): ?>
                <div class="menu-deroulant">
                    <button type="button" class="btn-icone" id="btn-notifs" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i><span class="point" id="nb-notifs">0</span>
                    </button>
                    <div class="panneau-deroulant" id="panneau-notifs">
                        <header>Notifications <small>Mises à jour en direct</small></header>
                        <div class="liste-notifs" id="liste-notifs"><div class="notif-vide">Chargement…</div></div>
                    </div>
                </div>
                <div class="menu-deroulant">
                    <button type="button" class="avatar" id="btn-compte" aria-label="Mon compte"><?= e(initiales($utilisateur['prenom'], $utilisateur['nom'])) ?></button>
                    <div class="panneau-deroulant menu-compte" id="panneau-compte">
                        <div class="identite">
                            <strong><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></strong>
                            <small><?= e(ucfirst($utilisateur['role'])) ?> · <?= e($utilisateur['email']) ?></small>
                        </div>
                        <?php if ($utilisateur['role'] === 'client'): ?>
                            <a href="<?= url('commandes') ?>"><i class="fa-solid fa-receipt"></i> Mes commandes</a>
                        <?php else: ?>
                            <a href="<?= url($utilisateur['role']) ?>"><i class="fa-solid fa-gauge"></i> Mon tableau de bord</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('deconnexion') ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Se déconnecter</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url('connexion') ?>" class="lien-connexion"><i class="fa-regular fa-user"></i> Connexion</a>
            <?php endif; ?>

            <button type="button" class="btn-icone burger" id="burger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
        </div>
    </div>
</header>

<div class="toasts" id="toasts">
    <?php foreach (flashes() as $f): ?>
        <div class="toast <?= e($f['type']) ?>" role="status">
            <i class="fa-solid <?= ['succes' => 'fa-circle-check', 'erreur' => 'fa-circle-exclamation'][$f['type']] ?? 'fa-circle-info' ?>"></i>
            <span><?= e($f['message']) ?></span>
            <button type="button" aria-label="Fermer" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endforeach; ?>
</div>

<main>
    <?= $contenu ?>
</main>

<footer class="pied">
    <div class="conteneur">
        <div class="grille">
            <div>
                <a href="<?= url() ?>" class="logo">
                    <span class="logo-icone"><i class="fa-solid fa-fire-flame-simple"></i></span>
                    <span class="logo-texte"><strong>Gaz<span>Express</span></strong></span>
                </a>
                <p>La marketplace du gaz domestique à Ouagadougou : vendeurs agréés, stocks vérifiés, livraison à domicile ou retrait sur place.</p>
            </div>
            <div>
                <h4>Clients</h4>
                <a href="<?= url() ?>">Trouver un vendeur</a>
                <a href="<?= url('catalogue') ?>">Catalogue des bouteilles</a>
                <a href="<?= url('commandes') ?>">Suivre une commande</a>
            </div>
            <div>
                <h4>Partenaires</h4>
                <a href="<?= url('inscription?role=vendeur') ?>">Devenir vendeur</a>
                <a href="<?= url('inscription?role=livreur') ?>">Devenir livreur</a>
                <a href="<?= url('charte') ?>">Charte graphique</a>
            </div>
            <div>
                <h4>Contact</h4>
                <a href="tel:+22625000000"><i class="fa-solid fa-phone"></i> +226 25 00 00 00</a>
                <a href="mailto:contact@gazexpress.bf"><i class="fa-regular fa-envelope"></i> contact@gazexpress.bf</a>
                <a href="#"><i class="fa-solid fa-location-dot"></i> Ouaga 2000, Ouagadougou</a>
            </div>
        </div>
        <div class="bas">
            <span>© <?= date('Y') ?> GazExpress — Projet académique PHP / MySQL.</span>
            <span>Le gaz est un produit inflammable : stockez vos bouteilles debout, dans un endroit aéré.</span>
        </div>
    </div>
</footer>

<?php
// Le tiroir interroge la base : s'il y a une panne MySQL, la page reste affichable.
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
