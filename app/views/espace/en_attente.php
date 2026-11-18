<?php /** @var string $espace @var array $profil */ $refuse = $profil['statut_validation'] === 'refuse'; ?>
<div class="conteneur">
    <section class="carte carte-suivi vide">
        <i class="fa-solid <?= $refuse ? 'fa-circle-xmark' : 'fa-hourglass-half' ?>" style="color:<?= $refuse ? 'var(--rouge)' : 'var(--orange)' ?>"></i>
        <h1 style="font-size:28px;margin-bottom:12px"><?= $refuse ? 'Demande refusée' : 'Dossier en cours de validation' ?></h1>
        <p><?= $refuse
            ? 'Votre demande n\'a pas été acceptée. Contactez l\'administration GazExpress au +226 25 00 00 00 pour en connaître les raisons.'
            : 'Merci ! L\'équipe GazExpress vérifie vos informations' . ($espace === 'vendeur' ? ' (autorisation d\'exploitation, localisation)' : ' (pièce d\'identité, engin)') . '. Vous recevrez une notification dès la validation.' ?></p>
        <?php if ($espace === 'vendeur'): ?>
            <p style="margin-top:14px"><span class="etiquette <?= $refuse ? 'indisponible' : 'limite' ?>"><?= e($profil['nom_commerce']) ?> · <?= e($profil['quartier']) ?></span></p>
        <?php endif; ?>
    </section>
</div>
