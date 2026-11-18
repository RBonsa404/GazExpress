<?php
/** @var array $commandes @var ?array $commande @var array $details @var array $historique @var ?array $avis @var array $reclamations */
?>
<div class="conteneur">
<?php if (!$commande): ?>
    <section class="carte carte-suivi vide">
        <i class="fa-solid fa-receipt"></i>
        <h1 style="font-size:26px;margin-bottom:10px">Aucune commande pour l'instant</h1>
        <p>Dès que vous aurez passé une commande, vous suivrez ici chaque étape en direct.</p>
        <a href="<?= url() ?>" class="btn btn-primaire" style="margin-top:22px"><i class="fa-solid fa-map-location-dot"></i> Trouver un vendeur</a>
    </section>
<?php else:
    $livraison = $commande['mode_recuperation'] === 'livraison';
    $etapes = $livraison
        ? ['en_attente' => ['En attente', 'fa-clock'], 'acceptee' => ['Acceptée', 'fa-check'], 'en_preparation' => ['Préparation', 'fa-box-open'], 'en_livraison' => ['En livraison', 'fa-motorcycle'], 'livree' => ['Livrée', 'fa-house-circle-check']]
        : ['en_attente' => ['En attente', 'fa-clock'], 'acceptee' => ['Acceptée', 'fa-check'], 'en_preparation' => ['Préparation', 'fa-box-open'], 'livree' => ['Retirée', 'fa-store']];
    $cles   = array_keys($etapes);
    $annule = $commande['statut'] === 'annulee';
    $rang   = $annule ? -1 : array_search($commande['statut'], $cles, true);
    $pct    = $annule ? 0 : ($rang / (count($cles) - 1)) * 100;
    $statut = statut_commande($commande['statut']);
    $termine = in_array($commande['statut'], ['livree', 'annulee'], true);
?>
    <section class="carte carte-suivi" id="suivi" data-commande="<?= (int) $commande['id'] ?>" data-statut="<?= e($commande['statut']) ?>" data-livraison="<?= e($commande['statut_livraison'] ?? '') ?>">
        <div class="suivi-entete">
            <div>
                <span class="surtitre"><?= $termine ? 'Commande clôturée' : 'Suivi en direct' ?></span>
                <h1>Commande #<?= e($commande['reference']) ?></h1>
                <p class="texte-doux" style="font-size:17px">Passée le <?= date_fr($commande['date_commande']) ?></p>
            </div>
            <span class="etiquette avec-point <?= $statut['classe'] ?>"><?= $statut['libelle'] ?></span>
        </div>

        <?php if ($annule): ?>
            <div class="bandeau-info alerte">
                <span><i class="fa-solid fa-circle-xmark"></i> Cette commande a été annulée<?= $commande['motif_annulation'] ? ' : ' . e($commande['motif_annulation']) : '' ?>.
                <?php if ($commande['statut_paiement'] === 'rembourse'): ?> Le paiement Mobile Money vous a été remboursé.<?php endif; ?></span>
                <a href="<?= url('catalogue?vendeur=' . $commande['vendeur_id']) ?>" class="btn btn-primaire">Commander à nouveau</a>
            </div>
        <?php elseif ($commande['statut'] === 'livree'): ?>
            <div class="bandeau-info ok">
                <span><i class="fa-solid fa-circle-check"></i> Commande <?= $livraison ? 'livrée' : 'retirée' ?> le <?= date_fr($commande['date_cloture']) ?>. Merci pour votre confiance !</span>
            </div>
        <?php else: ?>
            <div class="bandeau-info">
                <span><i class="fa-solid fa-circle-info"></i>
                <?php if ($commande['statut'] === 'en_attente'): ?>
                    Le vendeur vérifie son stock réel. Cette page se met à jour automatiquement.
                <?php elseif ($livraison && $commande['livreur_id'] && $commande['statut'] !== 'en_livraison'): ?>
                    Livreur assigné : <?= e($commande['livreur_prenom'] . ' ' . $commande['livreur_nom']) ?>. Il se rend au point de retrait.
                <?php elseif ($commande['statut'] === 'en_livraison'): ?>
                    <?= e($commande['livreur_prenom']) ?> est en route vers vous. Préparez <?= $commande['statut_paiement'] === 'valide' ? 'votre bouteille vide' : 'le montant exact et votre bouteille vide' ?>.
                <?php elseif (!$livraison): ?>
                    Votre commande vous attend en boutique. Présentez la référence <b><?= e($commande['reference']) ?></b> au comptoir.
                <?php else: ?>
                    Le vendeur prépare votre commande ; un livreur vérifié va la prendre en charge.
                <?php endif; ?>
                </span>
                <?php if ($commande['statut'] === 'en_attente'): ?>
                    <form method="post" action="<?= url('commandes/annuler') ?>" data-confirmer="Annuler la commande <?= e($commande['reference']) ?> ?">
                        <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                        <button class="btn btn-sombre"><i class="fa-solid fa-xmark"></i> Annuler la commande</button>
                    </form>
                <?php elseif ($commande['statut'] === 'en_livraison' && $commande['livreur_tel']): ?>
                    <a href="tel:<?= e($commande['livreur_tel']) ?>" class="btn btn-primaire"><i class="fa-solid fa-phone"></i> Appeler le livreur</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$annule): ?>
        <div class="progression">
            <div class="barre"><span style="width:<?= $pct ?>%"></span></div>
            <ol style="grid-template-columns:repeat(<?= count($cles) ?>,1fr)">
                <?php foreach ($etapes as $cle => [$lib, $ico]): $i = array_search($cle, $cles, true); ?>
                    <li class="<?= $i < $rang ? 'fait' : ($i === $rang ? 'courant' : '') ?>">
                        <span class="pt"><i class="fa-solid <?= $ico ?>"></i></span><?= $lib ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

        <div class="bloc-infos">
            <div>
                <h4>Boutique vendeur</h4>
                <strong><?= e($commande['nom_commerce']) ?></strong>
                <p><i class="fa-solid fa-phone"></i> <a href="tel:<?= e($commande['vendeur_tel']) ?>"><?= e($commande['vendeur_tel']) ?></a></p>
                <p><i class="fa-solid fa-location-dot" style="color:var(--gris-400)"></i> <?= e($commande['vendeur_adresse']) ?></p>
            </div>
            <div>
                <h4>Mode de récupération</h4>
                <?php if ($livraison): ?>
                    <strong><i class="fa-solid fa-motorcycle" style="color:#E11D48"></i> Livraison à domicile (<?= e($commande['quartier_livraison']) ?>)</strong>
                    <p><?= e($commande['adresse_livraison']) ?></p>
                <?php else: ?>
                    <strong><i class="fa-solid fa-store" style="color:var(--primaire)"></i> Retrait sur place</strong>
                <?php endif; ?>
                <p><i class="fa-solid fa-wallet" style="color:var(--vert)"></i> Paiement <?= e(modes_paiement()[$commande['mode_paiement']] ?? '') ?> (<?= statut_paiement($commande['statut_paiement']) ?>)</p>
            </div>
        </div>

        <h3 class="titre-section">Articles commandés</h3>
        <?php foreach ($details as $d): ?>
            <div class="ligne-article">
                <span class="qte"><?= (int) $d['quantite'] ?>x</span>
                <strong>Bouteille <?= e($d['marque'] . ' ' . capacite($d['capacite_kg'])) ?></strong>
                <span class="montant"><?= fcfa($d['quantite'] * $d['prix_unitaire']) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="recap-total">
            <?php if ($commande['frais_livraison'] > 0): ?><span>Frais de livraison : <?= fcfa($commande['frais_livraison']) ?></span><?php endif; ?>
            <span>Total : <b><?= fcfa($commande['montant_total']) ?></b></span>
        </div>

        <div class="grille-2" style="margin-top:40px">
            <div>
                <h3 class="titre-section" style="margin-top:0">Historique</h3>
                <ul class="chronologie">
                    <?php foreach ($historique as $h): ?>
                        <li><b><?= statut_commande($h['nouveau_statut'])['libelle'] ?></b>
                            <small><?= date_fr($h['date_changement']) ?><?= $h['role'] && $h['role'] !== 'client' ? ' · par ' . e($h['role']) : '' ?></small></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <?php if ($commande['statut'] === 'livree' && !$avis): ?>
                    <h3 class="titre-section" style="margin-top:0">Votre avis</h3>
                    <form method="post" action="<?= url('commandes/avis') ?>">
                        <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                        <div class="groupe"><label>Note du vendeur</label>
                            <div class="etoiles"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="note" id="nv<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="nv<?= $i ?>">★</label><?php endfor; ?></div>
                        </div>
                        <?php if ($livraison): ?>
                        <div class="groupe"><label>Note du livreur</label>
                            <div class="etoiles"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="note_livreur" id="nl<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="nl<?= $i ?>">★</label><?php endfor; ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="groupe"><textarea name="commentaire" class="champ" rows="3" maxlength="500" placeholder="Votre commentaire (facultatif)"></textarea></div>
                        <button class="btn btn-primaire"><i class="fa-solid fa-paper-plane"></i> Publier l'avis</button>
                    </form>
                <?php elseif ($avis): ?>
                    <h3 class="titre-section" style="margin-top:0">Votre avis</h3>
                    <span class="note-fixe" style="font-size:22px"><?= str_repeat('★', (int) $avis['note']) ?></span>
                    <p class="texte-doux"><?= e($avis['commentaire'] ?? '') ?></p>
                <?php endif; ?>

                <h3 class="titre-section">Un problème ?</h3>
                <?php foreach ($reclamations as $r): ?>
                    <div class="ligne-stock">
                        <div class="haut"><strong><?= e($r['objet']) ?></strong><span class="etiquette <?= $r['statut'] === 'traitee' ? 'disponible' : 'limite' ?>"><?= $r['statut'] === 'traitee' ? 'Traitée' : 'En cours' ?></span></div>
                        <?php if ($r['reponse']): ?><small><b>Réponse :</b> <?= e($r['reponse']) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <details>
                    <summary class="btn btn-blanc btn-petit">Signaler un problème</summary>
                    <form method="post" action="<?= url('commandes/reclamation') ?>" style="margin-top:14px">
                        <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                        <div class="groupe">
                            <select name="objet" class="champ">
                                <option>Retard de livraison</option><option>Bouteille non conforme ou fuite</option>
                                <option>Problème de paiement</option><option>Comportement du livreur</option><option>Autre</option>
                            </select>
                        </div>
                        <div class="groupe"><textarea name="message" class="champ" rows="3" required minlength="10" maxlength="2000" placeholder="Décrivez le problème"></textarea></div>
                        <button class="btn btn-sombre btn-petit">Envoyer à l'administration</button>
                    </form>
                </details>
            </div>
        </div>
    </section>

    <?php if (count($commandes) > 1): ?>
        <section class="carte historique-commandes">
            <div class="carte-titre" style="margin-bottom:6px"><h2><i class="fa-solid fa-clock-rotate-left"></i> Mes commandes</h2><span class="texte-doux"><?= count($commandes) ?> commandes</span></div>
            <?php foreach ($commandes as $c): $s = statut_commande($c['statut']); ?>
                <a href="<?= url('commandes/suivi?id=' . $c['id']) ?>" class="ligne-commande <?= $c['id'] == $commande['id'] ? 'selection' : '' ?>">
                    <span><span class="ref">#<?= e($c['reference']) ?></span><small><?= date_fr($c['date_commande'], false) ?></small></span>
                    <span><?= e($c['nom_commerce']) ?><small><?= e($c['resume']) ?></small></span>
                    <b><?= fcfa($c['montant_total']) ?></b>
                    <span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
</div>
<?php if ($commande && !in_array($commande['statut'], ['livree', 'annulee'], true)): ?>
<script>
/* Suivi en direct : interrogation AJAX régulière, rechargement si le statut change */
(function () {
    const s = document.getElementById('suivi');
    setInterval(async () => {
        try {
            const r = await fetch(document.body.dataset.base + '/api/commande-statut?id=' + s.dataset.commande);
            const d = await r.json();
            if (d.statut && (d.statut !== s.dataset.statut || (d.livraison || '') !== s.dataset.livraison)) location.reload();
        } catch (e) {}
    }, 10000);
})();
</script>
<?php endif; ?>
