<?php
/** @var array $vendeur @var array $stats @var array $stocks @var array $absents @var array $entrantes @var array $enCours @var array $historique @var array $avis */
$ouvert = (bool) $vendeur['est_ouvert'];
?>
<section class="tete-page">
    <div class="conteneur">
        <div>
            <div class="fil">Espace dépôt</div>
            <h1><?= e($vendeur['nom_commerce']) ?></h1>
            <p><?= e($vendeur['adresse']) ?> · <?= heure($vendeur['heure_ouverture']) ?> – <?= heure($vendeur['heure_fermeture']) ?></p>
        </div>
        <form method="post" action="<?= url('vendeur/boutique') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="interrupteur <?= $ouvert ? '' : 'eteint' ?>" title="Cliquer pour <?= $ouvert ? 'suspendre' : 'reprendre' ?> les commandes">
                <span class="voyant"></span>
                <?= $ouvert ? 'Commandes ouvertes' : 'Commandes suspendues' ?>
                <small><?= $ouvert ? 'Suspendre' : 'Reprendre' ?></small>
            </button>
        </form>
    </div>
</section>

<div class="conteneur">
    <div class="indicateurs">
        <div class="indicateur"><small>Commandes du jour</small><strong><?= (int) $stats['commandes_jour'] ?></strong></div>
        <div class="indicateur"><small>Chiffre d'affaires du jour</small><strong><?= fcfa($stats['ca_jour']) ?></strong></div>
        <div class="indicateur"><small>Bouteilles en stock</small><strong><?= (int) $stats['bouteilles'] ?></strong></div>
    </div>

    <div class="grille-tableau">
        <section class="panneau" id="stocks">
            <div class="panneau-tete">
                <h2>Stock et prix</h2>
                <span class="meta">Cliquez sur l’état pour modifier</span>
            </div>

            <?php foreach ($stocks as $s): $etat = etat_stock((int) $s['quantite']); ?>
                <div class="ligne-stock">
                    <img src="<?= visuel_produit($s['marque'], $s['capacite_kg']) ?>" alt="">
                    <div>
                        <strong><?= e($s['marque'] . ' ' . capacite($s['capacite_kg'])) ?></strong>
                        <small><?= (int) $s['quantite'] ?> en stock · <?= fcfa($s['prix']) ?></small>
                    </div>
                    <button type="button" class="btn-etat" data-editer aria-label="Modifier le stock et le prix">
                        <span class="etiquette <?= $etat['cle'] ?>"><?= $etat['libelle'] ?></span><?= icone('chevron-bas') ?>
                    </button>
                    <form method="post" action="<?= url('vendeur/stock') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="produit_id" value="<?= (int) $s['produit_id'] ?>">
                        <label>Quantité
                            <input type="number" name="quantite" min="0" max="9999" value="<?= (int) $s['quantite'] ?>" required>
                        </label>
                        <label>Prix (FCFA)
                            <input type="number" name="prix" min="500" max="50000" step="50" value="<?= (int) $s['prix'] ?>" required>
                        </label>
                        <div class="pas">
                            <button type="button" class="btn btn-blanc btn-petit" data-mettre="0" title="Déclarer en rupture">Rupture</button>
                            <button class="btn btn-primaire btn-petit">Enregistrer</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if ($absents): ?>
                <div class="panneau-corps">
                    <details>
                        <summary class="btn btn-secondaire btn-petit"><?= icone('plus') ?> Ajouter une référence</summary>
                        <form method="post" action="<?= url('vendeur/stock/ajouter') ?>" class="filtres-ligne" style="margin:12px 0 0">
                            <?= csrf_field() ?>
                            <select name="produit_id" required aria-label="Produit">
                                <?php foreach ($absents as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['marque'] . ' ' . capacite($p['capacite_kg'])) ?> (réf. <?= fcfa($p['prix_reference']) ?>)</option><?php endforeach; ?>
                            </select>
                            <input type="number" name="quantite" min="0" placeholder="Quantité" style="width:110px" required aria-label="Quantité">
                            <input type="number" name="prix" min="500" step="50" placeholder="Prix" style="width:110px" aria-label="Prix">
                            <button class="btn btn-primaire btn-petit">Ajouter</button>
                        </form>
                    </details>
                </div>
            <?php endif; ?>
        </section>

        <div class="pile">
            <section class="panneau" id="commandes" data-attente="<?= count($entrantes) ?>">
                <div class="panneau-tete">
                    <h2>Commandes à confirmer</h2>
                    <?php if ($entrantes): ?><span class="compteur"><?= count($entrantes) ?></span><?php endif; ?>
                </div>
                <?php if (!$entrantes): ?>
                    <div class="vide"><?= icone('cloche') ?>Aucune commande en attente. Les nouvelles commandes s'affichent automatiquement.</div>
                <?php endif; ?>
                <?php foreach ($entrantes as $c): ?>
                    <div class="commande-entrante">
                        <div class="haut">
                            <span class="ref">#<?= e($c['reference']) ?> <span>· <?= e($c['mode_recuperation'] === 'livraison' ? 'Livraison ' . $c['quartier_livraison'] : 'Retrait au dépôt') ?></span></span>
                            <b><?= fcfa($c['montant_articles']) ?></b>
                        </div>
                        <p><?= e($c['resume']) ?></p>
                        <small><?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?> · <?= e($c['client_tel']) ?> · <?= date_fr($c['date_commande']) ?> · <?= e(modes_paiement()[$c['mode_paiement']] ?? '') ?></small>
                        <div class="actions">
                            <form method="post" action="<?= url('vendeur/commande') ?>">
                                <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                <button name="action" value="accepter" class="btn btn-succes btn-petit btn-bloc"><?= icone('check') ?> Accepter</button>
                            </form>
                            <form method="post" action="<?= url('vendeur/commande') ?>" data-confirmer="Refuser la commande <?= e($c['reference']) ?> ?">
                                <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="motif" value="Stock indisponible chez le vendeur">
                                <button name="action" value="refuser" class="btn btn-danger btn-petit">Refuser</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="panneau">
                <div class="panneau-tete"><h2>En cours</h2><?php if ($enCours): ?><span class="meta"><?= count($enCours) ?></span><?php endif; ?></div>
                <?php if (!$enCours): ?><div class="vide">Aucune commande en cours.</div><?php endif; ?>
                <?php foreach ($enCours as $c): $s = statut_commande($c['statut']); ?>
                    <div class="commande-entrante">
                        <div class="haut">
                            <span class="ref">#<?= e($c['reference']) ?> <span>· <?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?></span></span>
                            <span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span>
                        </div>
                        <p><?= e($c['resume']) ?> · <?= $c['mode_recuperation'] === 'livraison' ? 'Livraison ' . e($c['quartier_livraison']) : 'Retrait' ?></p>
                        <small>
                            <?php if ($c['mode_recuperation'] === 'livraison'): ?>
                                <?= $c['livreur_id'] ? 'Livreur : ' . e($c['livreur_prenom'] . ' ' . $c['livreur_nom'] . ' · ' . $c['livreur_tel']) : 'En attente d\'un livreur' ?>
                            <?php else: ?>
                                Le client vient retirer au dépôt · <?= e($c['client_tel']) ?>
                            <?php endif; ?>
                        </small>
                        <?php if ($c['statut'] === 'acceptee' || $c['mode_recuperation'] === 'retrait'): ?>
                        <div class="actions">
                            <?php if ($c['statut'] === 'acceptee'): ?>
                                <form method="post" action="<?= url('vendeur/commande') ?>"><?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                    <button name="action" value="preparer" class="btn btn-sombre btn-petit btn-bloc"><?= icone('colis') ?> Passer en préparation</button></form>
                            <?php endif; ?>
                            <?php if ($c['mode_recuperation'] === 'retrait'): ?>
                                <form method="post" action="<?= url('vendeur/commande') ?>"><?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                    <button name="action" value="remettre" class="btn btn-succes btn-petit btn-bloc"><?= icone('check') ?> Remise au client</button></form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>

    <div class="grille-tableau">
        <section class="panneau">
            <div class="panneau-tete"><h2>Ventes clôturées</h2><span class="meta">Total encaissé : <b><?= fcfa($stats['ca_total']) ?></b></span></div>
            <div class="tableau-wrap">
                <table class="tableau">
                    <thead><tr><th>Réf.</th><th>Date</th><th>Articles</th><th class="num">Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($historique as $c): $s = statut_commande($c['statut']); ?>
                        <tr><td><b>#<?= e($c['reference']) ?></b></td><td><?= date_fr($c['date_commande'], false) ?></td>
                            <td><?= e($c['resume']) ?></td><td class="num"><?= fcfa($c['montant_articles']) ?></td>
                            <td><span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$historique): ?><tr><td colspan="5" class="texte-doux">Aucune vente clôturée.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panneau">
            <div class="panneau-tete"><h2>Avis clients</h2>
                <?php if ($vendeur['note_moyenne']): ?><span class="meta"><?= icone('etoile', 'ic-s') ?> <?= e(str_replace('.', ',', $vendeur['note_moyenne'])) ?> / 5</span><?php endif; ?></div>
            <?php foreach ($avis as $a): ?>
                <div class="commande-entrante">
                    <span class="note-fixe"><?= str_repeat('★', (int) $a['note']) ?><span class="eteinte"><?= str_repeat('★', 5 - (int) $a['note']) ?></span></span> <b><?= e($a['prenom']) ?></b>
                    <?php if ($a['commentaire']): ?><p class="texte-doux"><?= e($a['commentaire']) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$avis): ?><div class="vide">Pas encore d'avis.</div><?php endif; ?>
        </section>
    </div>
</div>
<script>
(function () {
    // Édition du stock : un clic sur l'état ouvre le formulaire de la ligne
    document.querySelectorAll('[data-editer]').forEach(b => b.addEventListener('click', () => b.closest('.ligne-stock').classList.toggle('edition')));
    document.querySelectorAll('[data-mettre]').forEach(b => b.addEventListener('click', () => {
        const f = b.closest('form'); f.quantite.value = b.dataset.mettre; f.submit();
    }));
    // Nouvelles commandes : vérification toutes les 15 s
    const bloc = document.getElementById('commandes');
    setInterval(async () => {
        try {
            const r = await fetch(document.body.dataset.base + '/api/vendeur-commandes');
            const d = await r.json();
            if (typeof d.enAttente === 'number' && d.enAttente !== parseInt(bloc.dataset.attente, 10)) location.reload();
        } catch (e) {}
    }, 15000);
})();
</script>
