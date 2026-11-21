<?php
/** @var array $livreur @var array $stats @var ?array $enCours @var array $details @var array $disponibles @var array $historique */
$enService = (bool) $livreur['disponible'];
?>
<section class="tete-page">
    <div class="conteneur">
        <div>
            <div class="fil">Espace livreur</div>
            <h1>Bonjour <?= e($livreur['prenom']) ?></h1>
            <p><?= $livreur['quartier'] ? 'Secteur ' . e($livreur['quartier']) . ' · ' : '' ?>courses dans un rayon de <?= RAYON_COURSES_KM ?> km</p>
        </div>
        <form method="post" action="<?= url('livreur/disponibilite') ?>">
            <?= csrf_field() ?>
            <button class="interrupteur <?= $enService ? '' : 'eteint' ?>" title="Changer de statut">
                <span class="voyant"></span>
                <?= $enService ? 'En service' : 'Hors service' ?>
                <small><?= $enService ? 'Arrêter' : 'Commencer' ?></small>
            </button>
        </form>
    </div>
</section>

<div class="conteneur">
    <div class="indicateurs">
        <div class="indicateur"><small>Courses effectuées</small><strong><?= (int) $stats['courses'] ?></strong></div>
        <div class="indicateur"><small>Gains du jour</small><strong><?= fcfa($stats['gains_jour']) ?></strong></div>
        <div class="indicateur"><small>Note moyenne</small><strong><?= $stats['note'] ? e(str_replace('.', ',', $stats['note'])) . ' / 5' : '—' ?></strong></div>
    </div>

    <div class="grille-tableau">
        <section class="panneau">
            <?php if ($enCours): $enRoute = $enCours['statut'] === 'en_route'; ?>
                <div class="panneau-tete">
                    <h2>Course en cours · #<?= e($enCours['reference']) ?></h2>
                    <span class="etiquette rose"><?= $enRoute ? 'Vers le client' : 'Vers le dépôt' ?></span>
                </div>
                <div class="course">
                    <div class="point-course">
                        <span class="lettre">A</span>
                        <div><small>Retrait au dépôt</small><strong><?= e($enCours['nom_commerce']) ?></strong>
                            <p><?= e($enCours['vendeur_adresse']) ?> · <a href="tel:<?= e($enCours['vendeur_tel']) ?>"><?= e($enCours['vendeur_tel']) ?></a></p></div>
                    </div>
                    <div class="point-course">
                        <span class="lettre b">B</span>
                        <div><small>Livraison client · <?= e($enCours['distance_km']) ?> km</small><strong><?= e($enCours['adresse_livraison']) ?> (<?= e($enCours['quartier']) ?>)</strong>
                            <p><?= e($enCours['client_prenom'] . ' ' . $enCours['client_nom']) ?> · <?= e(implode(', ', array_map(fn ($d) => $d['quantite'] . ' × ' . $d['marque'] . ' ' . capacite($d['capacite_kg']), $details))) ?></p>
                            <p><?= e(modes_paiement()[$enCours['mode_paiement']] ?? '') ?> · <?= $enCours['statut_paiement'] === 'valide' ? 'déjà réglé' : '<b>à encaisser : ' . fcfa($enCours['montant_total']) . '</b>' ?></p></div>
                    </div>
                    <div class="actions">
                        <?php if ($enRoute): ?>
                            <form method="post" action="<?= url('livreur/livrer') ?>" data-confirmer="Confirmer la remise de la bouteille au client ?">
                                <?= csrf_field() ?><button class="btn btn-primaire btn-bloc"><?= icone('check-cercle') ?> Bouteille remise au client</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= url('livreur/recuperer') ?>">
                                <?= csrf_field() ?><button class="btn btn-sombre btn-bloc"><?= icone('colis') ?> Bouteille récupérée, je pars</button>
                            </form>
                        <?php endif; ?>
                        <a href="tel:<?= e($enRoute ? $enCours['client_tel'] : $enCours['vendeur_tel']) ?>" class="btn btn-blanc" aria-label="Appeler <?= $enRoute ? 'le client' : 'le dépôt' ?>" title="Appeler"><?= icone('telephone') ?></a>
                        <a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=<?= e($enCours['v_lat'] . ',' . $enCours['v_lon'] . ';' . $enCours['latitude'] . ',' . $enCours['longitude']) ?>"
                           target="_blank" rel="noopener" class="btn btn-blanc" aria-label="Itinéraire" title="Itinéraire"><?= icone('itineraire') ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="panneau-tete"><h2>Course en cours</h2></div>
                <div class="vide"><?= icone('moto') ?>Aucune course en cours.<br><?= $enService ? 'Acceptez une course disponible ci-contre.' : 'Passez en service pour recevoir des courses.' ?></div>
            <?php endif; ?>
        </section>

        <section class="panneau">
            <div class="panneau-tete">
                <h2>Courses disponibles</h2>
                <?php if ($enService && $disponibles): ?><span class="compteur"><?= count($disponibles) ?></span><?php endif; ?>
            </div>
            <?php if (!$enService): ?>
                <div class="vide"><?= icone('marche') ?>Vous êtes hors service.</div>
            <?php elseif (!$disponibles): ?>
                <div class="vide"><?= icone('horloge') ?>Aucune course pour le moment. La liste se met à jour toutes les 30 secondes.</div>
            <?php else: foreach ($disponibles as $c): ?>
                <div class="offre-course">
                    <div>
                        <span class="ref">#<?= e($c['reference']) ?> · <?= e($c['distance_km']) ?> km<?= $c['distance_livreur'] !== null ? ' · à ' . e($c['distance_livreur']) . ' km de vous' : '' ?></span>
                        <strong><?= e($c['nom_commerce']) ?> → <?= e($c['quartier']) ?></strong>
                        <small><?= e($c['resume']) ?></small>
                    </div>
                    <div style="text-align:right">
                        <span class="gain">+<?= fcfa($c['frais']) ?></span>
                        <form method="post" action="<?= url('livreur/accepter') ?>" style="margin-top:6px">
                            <?= csrf_field() ?><input type="hidden" name="livraison_id" value="<?= (int) $c['livraison_id'] ?>">
                            <button class="btn btn-sombre btn-petit" <?= $enCours ? 'disabled title="Terminez votre course en cours"' : '' ?>>Accepter</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </section>
    </div>

    <section class="panneau section">
        <div class="panneau-tete"><h2>Dernières livraisons</h2></div>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead><tr><th>Réf.</th><th>Livrée le</th><th>Trajet</th><th class="num">Distance</th><th class="num">Gain</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($historique as $h): ?>
                    <tr><td><b>#<?= e($h['reference']) ?></b></td><td><?= date_fr($h['date_livraison']) ?></td>
                        <td><?= e($h['nom_commerce']) ?> → <?= e($h['quartier']) ?></td><td class="num"><?= e($h['distance_km']) ?> km</td>
                        <td class="num"><?= fcfa($h['frais']) ?></td><td class="note-fixe"><?= $h['note_livreur'] ? str_repeat('★', (int) $h['note_livreur']) : '<span class="texte-doux">—</span>' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$historique): ?><tr><td colspan="6" class="texte-doux">Aucune livraison effectuée.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php if ($enService && !$enCours): ?>
<script>setTimeout(() => location.reload(), 30000);</script>
<?php endif; ?>
