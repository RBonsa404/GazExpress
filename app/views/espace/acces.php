<?php
/** @var string $espace */
$vendeur = $espace === 'vendeur';
$autreRole = Auth::check() ? Auth::role() : null;
?>
<div class="conteneur">
    <section class="heros heros-compact">
        <div style="position:relative;z-index:1">
            <span class="surtitre sombre"><?= $vendeur ? 'Espace partenaire' : 'Flotte logistique Ouaga' ?></span>
            <h1><?= $vendeur ? 'Vendez plus, sans changer vos habitudes' : 'Livrez du gaz, gagnez à chaque course' ?></h1>
            <p><?= $vendeur
                ? 'Affichez vos stocks et vos prix sur la carte de Ouagadougou et recevez des commandes vérifiées.'
                : 'Recevez les courses proches de vous, suivez vos gains du jour et soyez payé à chaque livraison.' ?></p>
        </div>
    </section>

    <div class="stats">
        <?php foreach ($vendeur
            ? [['fa-map-location-dot', 'rose', 'Visibilité', 'Votre boutique sur la carte'], ['fa-boxes-stacked', 'vert', 'Stocks', 'Mis à jour en un clic'], ['fa-bell', 'bleu', 'Commandes', 'Acceptez ou refusez selon le stock']]
            : [['fa-route', 'rose', 'Courses', 'Proches de votre quartier'], ['fa-wallet', 'vert', 'Gains', '500 à 800 FCFA par course'], ['fa-clock', 'jaune', 'Liberté', 'Vous choisissez quand travailler']] as [$ico, $c, $t, $d]): ?>
            <div class="carte stat"><span class="ico <?= $c ?>"><i class="fa-solid <?= $ico ?>"></i></span><div><small><?= $t ?></small><strong style="font-size:19px"><?= $d ?></strong></div></div>
        <?php endforeach; ?>
    </div>

    <section class="carte section" style="text-align:center;padding:44px">
        <?php if ($autreRole): ?>
            <h2 style="font-size:24px">Vous êtes connecté avec un compte « <?= e($autreRole) ?> ».</h2>
            <p class="texte-doux" style="margin:10px 0 22px">Cet espace est réservé aux comptes <?= $espace ?>. Déconnectez-vous pour utiliser un autre compte.</p>
            <form method="post" action="<?= url('deconnexion') ?>"><?= csrf_field() ?><button class="btn btn-sombre">Se déconnecter</button></form>
        <?php else: ?>
            <h2 style="font-size:24px">Accédez à votre espace <?= $espace ?></h2>
            <p class="texte-doux" style="margin:10px 0 22px">Les nouveaux comptes sont vérifiés par l'administration avant activation.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                <a href="<?= url('connexion') ?>" class="btn btn-primaire"><i class="fa-solid fa-right-to-bracket"></i> Se connecter</a>
                <a href="<?= url('inscription?role=' . $espace) ?>" class="btn btn-secondaire"><i class="fa-solid fa-user-plus"></i> Devenir <?= $espace ?></a>
            </div>
        <?php endif; ?>
    </section>
</div>
