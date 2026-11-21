<?php /** @var string $espace @var array $profil */ $refuse = $profil['statut_validation'] === 'refuse'; ?>
<div class="conteneur">
    <div class="page-simple">
        <?= icone($refuse ? 'croix-cercle' : 'sablier') ?>
        <h1><?= $refuse ? 'Demande refusée' : 'Dossier en cours de vérification' ?></h1>
        <p><?= $refuse
            ? 'Votre demande n\'a pas été acceptée. Contactez GazExpress au ' . e(APP_TELEPHONE) . ' pour en connaître la raison.'
            : 'Nous vérifions vos informations' . ($espace === 'vendeur' ? ' (autorisation d\'exploitation, emplacement du dépôt)' : ' (pièce d\'identité, engin)') . '. Vous recevrez une notification dès la validation, en général sous 48 h.' ?></p>
        <?php if ($espace === 'vendeur'): ?>
            <div class="actions"><span class="etiquette <?= $refuse ? 'indisponible' : 'limite' ?>"><?= e($profil['nom_commerce']) ?> · <?= e($profil['quartier']) ?></span></div>
        <?php endif; ?>
    </div>
</div>
