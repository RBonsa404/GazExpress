<?php
/** @var array $livreur @var array $stats @var ?array $enCours @var array $details @var array $disponibles @var array $historique */
$enService = (bool) $livreur['disponible'];
?>
<div class="conteneur">

    <section class="heros heros-compact">
        <div style="position:relative;z-index:1">
            <span class="surtitre sombre">Flotte logistique Ouaga</span>
            <h1>Espace Livreur GazExpress</h1>
            <p>Bonjour <?= e($livreur['prenom']) ?> ! Acceptez les courses de proximité et suivez vos gains en temps réel.</p>
        </div>
        <form method="post" action="<?= url('livreur/disponibilite') ?>">
            <?= csrf_field() ?>
            <button class="btn-service <?= $enService ? '' : 'hors' ?>" title="Changer de statut">
                <span class="rond"></span> Statut : <?= $enService ? 'En service (Disponible)' : 'Hors service' ?>
            </button>
        </form>
    </section>

    <div class="stats">
        <div class="carte stat">
            <span class="ico rose"><i class="fa-solid fa-route"></i></span>
            <div><small>Courses effectuées</small><strong><?= (int) $stats['courses'] ?> course<?= $stats['courses'] > 1 ? 's' : '' ?></strong></div>
        </div>
        <div class="carte stat">
            <span class="ico vert"><i class="fa-solid fa-wallet"></i></span>
            <div><small>Gains du jour</small><strong><?= fcfa($stats['gains_jour']) ?></strong></div>
        </div>
        <div class="carte stat">
            <span class="ico jaune"><i class="fa-solid fa-star"></i></span>
            <div><small>Note moyenne</small><strong><?= $stats['note'] ? e($stats['note']) . ' / 5' : '—' ?></strong></div>
        </div>
    </div>

    <div class="grille-tableau">
        <section class="carte <?= $enCours ? 'carte-course-active' : '' ?>">
            <?php if ($enCours): $enRoute = $enCours['statut'] === 'en_route'; ?>
                <div class="carte-titre">
                    <h2><i class="fa-solid fa-motorcycle"></i> Course en cours (#<?= e($enCours['reference']) ?>)</h2>
                    <span class="etiquette rose"><?= $enRoute ? 'En route vers client' : 'Vers le point de retrait' ?></span>
                </div>
                <div class="course">
                    <div class="point-course">
                        <span class="lettre">A</span>
                        <div><small>Point de retrait (vendeur)</small><strong><?= e($enCours['nom_commerce']) ?></strong>
                            <p><?= e($enCours['vendeur_adresse']) ?> · <a href="tel:<?= e($enCours['vendeur_tel']) ?>"><?= e($enCours['vendeur_tel']) ?></a></p></div>
                    </div>
                    <div class="point-course">
                        <span class="lettre b">B</span>
                        <div><small>Destination client</small><strong><?= e($enCours['adresse_livraison']) ?> (<?= e($enCours['quartier']) ?>)</strong>
                            <p>Colis : <?= e(implode(', ', array_map(fn ($d) => $d['quantite'] . 'x Bouteille ' . $d['marque'] . ' ' . capacite($d['capacite_kg']), $details))) ?>
                            • Paiement : <?= e(modes_paiement()[$enCours['mode_paiement']] ?? '') ?>
                            <?= $enCours['statut_paiement'] === 'valide' ? '(Déjà réglé)' : '(À encaisser : ' . fcfa($enCours['montant_total']) . ')' ?></p>
                            <p>Client : <?= e($enCours['client_prenom'] . ' ' . $enCours['client_nom']) ?> · Distance : <?= e($enCours['distance_km']) ?> km</p></div>
                    </div>
                    <div class="actions">
                        <?php if ($enRoute): ?>
                            <form method="post" action="<?= url('livreur/livrer') ?>" data-confirmer="Confirmer la remise de la bouteille au client ?">
                                <?= csrf_field() ?><button class="btn btn-primaire btn-bloc"><i class="fa-solid fa-circle-check"></i> Confirmer la livraison au client</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= url('livreur/recuperer') ?>">
                                <?= csrf_field() ?><button class="btn btn-sombre btn-bloc"><i class="fa-solid fa-box"></i> Colis récupéré, je pars</button>
                            </form>
                        <?php endif; ?>
                        <a href="tel:<?= e($enRoute ? $enCours['client_tel'] : $enCours['vendeur_tel']) ?>" class="btn btn-blanc" aria-label="Appeler"><i class="fa-solid fa-phone"></i></a>
                        <a href="https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=<?= e($enCours['v_lat'] . ',' . $enCours['v_lon'] . ';' . $enCours['latitude'] . ',' . $enCours['longitude']) ?>"
                           target="_blank" rel="noopener" class="btn btn-blanc" aria-label="Itinéraire"><i class="fa-solid fa-diamond-turn-right"></i></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="carte-titre"><h2><i class="fa-solid fa-motorcycle"></i> Course en cours</h2></div>
                <div class="vide"><i class="fa-solid fa-mug-hot"></i>Aucune course en cours.<br><?= $enService ? 'Acceptez une course disponible à proximité.' : 'Passez « En service » pour recevoir des courses.' ?></div>
            <?php endif; ?>
        </section>

        <section class="carte">
            <div class="carte-titre">
                <h2>Courses disponibles à proximité</h2>
                <span class="texte-doux" style="font-size:15px">Rayon <?= RAYON_COURSES_KM ?> km<?= $livreur['quartier'] ? ' · ' . e($livreur['quartier']) : '' ?></span>
            </div>
            <?php if (!$enService): ?>
                <div class="vide"><i class="fa-solid fa-power-off"></i>Vous êtes hors service.</div>
            <?php elseif (!$disponibles): ?>
                <div class="vide"><i class="fa-solid fa-satellite-dish"></i>Aucune course disponible pour le moment. La page se rafraîchit toutes les 30 secondes.</div>
            <?php else: foreach ($disponibles as $c): ?>
                <div class="offre-course">
                    <div>
                        <span class="ref">#<?= e($c['reference']) ?> <span class="gain">+<?= fcfa($c['frais']) ?></span></span>
                        <strong><?= e($c['nom_commerce']) ?> <i class="fa-solid fa-arrow-right"></i> <?= e($c['quartier']) ?></strong>
                        <small><?= e($c['resume']) ?> • Distance : <?= e($c['distance_km']) ?> km<?= $c['distance_livreur'] !== null ? ' • À ' . e($c['distance_livreur']) . ' km de vous' : '' ?></small>
                    </div>
                    <form method="post" action="<?= url('livreur/accepter') ?>">
                        <?= csrf_field() ?><input type="hidden" name="livraison_id" value="<?= (int) $c['livraison_id'] ?>">
                        <button class="btn btn-sombre" <?= $enCours ? 'disabled title="Terminez votre course en cours"' : '' ?>>Accepter</button>
                    </form>
                </div>
            <?php endforeach; endif; ?>
        </section>
    </div>

    <section class="carte section">
        <div class="carte-titre"><h2><i class="fa-solid fa-clock-rotate-left"></i> Dernières livraisons</h2></div>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead><tr><th>Réf.</th><th>Livrée le</th><th>Trajet</th><th>Distance</th><th>Gain</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($historique as $h): ?>
                    <tr><td><b>#<?= e($h['reference']) ?></b></td><td><?= date_fr($h['date_livraison']) ?></td>
                        <td><?= e($h['nom_commerce']) ?> → <?= e($h['quartier']) ?></td><td><?= e($h['distance_km']) ?> km</td>
                        <td><?= fcfa($h['frais']) ?></td><td class="note-fixe"><?= $h['note_livreur'] ? str_repeat('★', (int) $h['note_livreur']) : '—' ?></td></tr>
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
