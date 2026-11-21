<div class="conteneur">
    <div class="page-simple">
        <?= icone('alerte') ?>
        <h1><?= e($titre ?? 'Erreur') ?></h1>
        <p><?= e($message ?? 'Une erreur est survenue.') ?></p>
        <div class="actions"><a href="<?= url() ?>" class="btn btn-primaire">Réessayer</a></div>
    </div>
</div>
