<div class="conteneur page-erreur">
    <div class="code"><i class="fa-solid fa-plug-circle-xmark"></i></div>
    <h1 style="font-size:28px;margin:10px 0"><?= e($titre ?? 'Erreur') ?></h1>
    <p class="texte-doux"><?= e($message ?? 'Une erreur est survenue.') ?></p>
    <a href="<?= url() ?>" class="btn btn-primaire" style="margin-top:24px">Réessayer</a>
</div>
