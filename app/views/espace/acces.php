<?php
/** @var string $espace */
$vendeur   = $espace === 'vendeur';
$autreRole = Auth::check() ? Auth::role() : null;
$avantages = $vendeur
    ? [['Visibilité', 'Votre dépôt et vos prix sur la carte de Ouagadougou.'], ['Stock', 'Mis à jour en quelques secondes, depuis un téléphone.'], ['Commandes', 'Vous acceptez seulement ce que vous avez en stock.']]
    : [['Courses', 'Proches de votre quartier, dans un rayon de ' . RAYON_COURSES_KM . ' km.'], ['Gains', '500 à 800 FCFA par course, suivis au jour le jour.'], ['Horaires', 'Vous passez en service quand vous le souhaitez.']];
?>
<div class="conteneur">
    <div class="page-simple">
        <?= icone($vendeur ? 'boutique' : 'moto') ?>
        <?php if ($autreRole): ?>
            <h1>Cet espace est réservé aux <?= $vendeur ? 'dépôts' : 'livreurs' ?></h1>
            <p>Vous êtes connecté avec un compte « <?= e($autreRole) ?> ». Déconnectez-vous pour utiliser un autre compte.</p>
            <div class="actions">
                <form method="post" action="<?= url('deconnexion') ?>"><?= csrf_field() ?><button class="btn btn-sombre">Se déconnecter</button></form>
            </div>
        <?php else: ?>
            <h1><?= $vendeur ? 'Espace dépôts de gaz' : 'Espace livreurs' ?></h1>
            <p><?= $vendeur
                ? 'Gérez votre stock, vos prix et les commandes de votre dépôt.'
                : 'Recevez des courses près de chez vous et suivez vos gains.' ?> Les nouveaux comptes sont vérifiés avant activation.</p>
            <div class="actions">
                <a href="<?= url('connexion') ?>" class="btn btn-primaire">Se connecter</a>
                <a href="<?= url('inscription?role=' . $espace) ?>" class="btn btn-secondaire"><?= $vendeur ? 'Référencer mon dépôt' : 'Devenir livreur' ?></a>
            </div>
        <?php endif; ?>
        <div class="liste-avantages">
            <?php foreach ($avantages as [$t, $d]): ?>
                <div><b><?= e($t) ?></b><?= e($d) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
