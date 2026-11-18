<?php
/** @var array $vendeur @var array $stats @var array $stocks @var array $absents @var array $entrantes @var array $enCours @var array $historique @var array $avis */
$ouvert = (bool) $vendeur['est_ouvert'];
?>
<div class="conteneur">

    <section class="heros heros-compact">
        <div style="position:relative;z-index:1">
            <span class="surtitre sombre">Espace partenaire</span>
            <h1>Tableau de bord Vendeur</h1>
            <p><?= e($vendeur['nom_commerce']) ?> — gérez vos stocks en temps réel et validez les commandes entrantes à Ouagadougou.</p>
        </div>
        <form method="post" action="<?= url('vendeur/boutique') ?>" class="statut-boite">
            <?= csrf_field() ?>
            <small>Statut Boutique</small>
            <button type="submit" title="Cliquer pour <?= $ouvert ? 'fermer' : 'ouvrir' ?> la boutique">
                <span class="voyant <?= $ouvert ? '' : 'eteint' ?>"></span>
                <?= $ouvert ? 'En Ligne &amp; Ouvert' : 'Hors ligne &amp; Fermé' ?>
            </button>
        </form>
    </section>

    <div class="stats">
        <div class="carte stat">
            <span class="ico rose"><i class="fa-solid fa-bag-shopping"></i></span>
            <div><small>Commandes du jour</small><strong><?= (int) $stats['commandes_jour'] ?></strong></div>
        </div>
        <div class="carte stat">
            <span class="ico vert"><i class="fa-solid fa-coins"></i></span>
            <div><small>Chiffre d'affaires du jour</small><strong><?= fcfa($stats['ca_jour']) ?></strong></div>
        </div>
        <div class="carte stat">
            <span class="ico bleu"><i class="fa-solid fa-boxes-stacked"></i></span>
            <div><small>Bouteilles en stock</small><strong><?= (int) $stats['bouteilles'] ?> unités</strong></div>
        </div>
    </div>

    <div class="grille-tableau">
        <section class="carte" id="stocks">
            <div class="carte-titre">
                <h2><i class="fa-solid fa-gas-pump"></i> Gestion des Stocks par Marque</h2>
                <span class="texte-doux" style="font-size:15px">Mise à jour instantanée</span>
            </div>

            <?php foreach ($stocks as $s): $etat = etat_stock((int) $s['quantite']); ?>
                <div class="ligne-stock">
                    <div class="haut">
                        <div>
                            <strong><?= e($s['marque'] . ' ' . str_replace(' ', '', capacite($s['capacite_kg']))) ?></strong>
                            <small>Stock actuel : <b><?= (int) $s['quantite'] ?> unités</b> · Prix : <b><?= fcfa($s['prix']) ?></b></small>
                        </div>
                        <button type="button" class="btn-etat" data-editer title="Modifier le stock et le prix">
                            <span class="etiquette <?= $etat['cle'] ?>"><?= $etat['libelle'] ?></span><i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
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
                            <button class="btn btn-primaire btn-petit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if ($absents): ?>
                <details style="margin-top:8px">
                    <summary class="btn btn-secondaire btn-petit"><i class="fa-solid fa-plus"></i> Ajouter une référence</summary>
                    <form method="post" action="<?= url('vendeur/stock/ajouter') ?>" class="filtres-ligne" style="margin-top:14px">
                        <?= csrf_field() ?>
                        <select name="produit_id" required>
                            <?php foreach ($absents as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['designation']) ?> (réf. <?= fcfa($p['prix_reference']) ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="number" name="quantite" min="0" placeholder="Quantité" style="width:110px" required>
                        <input type="number" name="prix" min="500" step="50" placeholder="Prix" style="width:110px">
                        <button class="btn btn-primaire btn-petit">Ajouter</button>
                    </form>
                </details>
            <?php endif; ?>
        </section>

        <div>
            <section class="carte" id="commandes" data-attente="<?= count($entrantes) ?>">
                <div class="carte-titre">
                    <h2><i class="fa-solid fa-bell"></i> Commandes entrantes</h2>
                    <span class="compteur"><?= count($entrantes) ?> En attente</span>
                </div>
                <?php if (!$entrantes): ?>
                    <div class="vide"><i class="fa-regular fa-bell-slash"></i>Aucune commande en attente. Les nouvelles commandes apparaissent automatiquement.</div>
                <?php endif; ?>
                <?php foreach ($entrantes as $c): ?>
                    <div class="commande-entrante">
                        <div class="haut">
                            <span class="ref">#<?= e($c['reference']) ?> <span>• <?= e($c['mode_recuperation'] === 'livraison' ? $c['quartier_livraison'] : 'Retrait boutique') ?></span></span>
                            <span class="etiquette limite">En attente</span>
                        </div>
                        <p><?= e($c['resume']) ?> (<?= $c['mode_recuperation'] === 'livraison' ? 'Livraison' : 'Retrait' ?>)</p>
                        <small><?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?> · <?= e($c['client_tel']) ?> · <?= date_fr($c['date_commande']) ?> · <?= fcfa($c['montant_articles']) ?> · <?= e(modes_paiement()[$c['mode_paiement']] ?? '') ?></small>
                        <div class="actions">
                            <form method="post" action="<?= url('vendeur/commande') ?>">
                                <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                <button name="action" value="accepter" class="btn btn-succes btn-bloc"><i class="fa-solid fa-check"></i> Accepter</button>
                            </form>
                            <form method="post" action="<?= url('vendeur/commande') ?>" data-confirmer="Refuser la commande <?= e($c['reference']) ?> ?">
                                <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="motif" value="Stock indisponible chez le vendeur">
                                <button name="action" value="refuser" class="btn btn-danger">Refuser</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="carte section">
                <div class="carte-titre"><h2><i class="fa-solid fa-list-check"></i> Commandes en cours</h2></div>
                <?php if (!$enCours): ?><div class="vide" style="padding:18px">Aucune commande en cours.</div><?php endif; ?>
                <?php foreach ($enCours as $c): $s = statut_commande($c['statut']); ?>
                    <div class="commande-entrante" style="background:#fff">
                        <div class="haut">
                            <span class="ref">#<?= e($c['reference']) ?> <span>• <?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?></span></span>
                            <span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span>
                        </div>
                        <p><?= e($c['resume']) ?> (<?= $c['mode_recuperation'] === 'livraison' ? 'Livraison — ' . e($c['quartier_livraison']) : 'Retrait' ?>)</p>
                        <small>
                            <?php if ($c['mode_recuperation'] === 'livraison'): ?>
                                <?= $c['livreur_id'] ? 'Livreur : ' . e($c['livreur_prenom'] . ' ' . $c['livreur_nom'] . ' · ' . $c['livreur_tel']) : 'En attente d\'un livreur' ?>
                            <?php else: ?>
                                Le client vient retirer en boutique · <?= e($c['client_tel']) ?>
                            <?php endif; ?>
                        </small>
                        <div class="actions">
                            <?php if ($c['statut'] === 'acceptee'): ?>
                                <form method="post" action="<?= url('vendeur/commande') ?>"><?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                    <button name="action" value="preparer" class="btn btn-sombre btn-petit btn-bloc"><i class="fa-solid fa-box-open"></i> Passer en préparation</button></form>
                            <?php endif; ?>
                            <?php if ($c['mode_recuperation'] === 'retrait'): ?>
                                <form method="post" action="<?= url('vendeur/commande') ?>"><?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $c['id'] ?>">
                                    <button name="action" value="remettre" class="btn btn-succes btn-petit btn-bloc"><i class="fa-solid fa-handshake"></i> Remise au client</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>

    <div class="grille-tableau">
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-clock-rotate-left"></i> Historique des ventes</h2>
                <span class="texte-doux">Total encaissé : <b><?= fcfa($stats['ca_total']) ?></b></span></div>
            <div class="tableau-wrap">
                <table class="tableau">
                    <thead><tr><th>Réf.</th><th>Date</th><th>Articles</th><th>Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($historique as $c): $s = statut_commande($c['statut']); ?>
                        <tr><td><b>#<?= e($c['reference']) ?></b></td><td><?= date_fr($c['date_commande'], false) ?></td>
                            <td><?= e($c['resume']) ?></td><td><?= fcfa($c['montant_articles']) ?></td>
                            <td><span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$historique): ?><tr><td colspan="5" class="texte-doux">Aucune vente clôturée.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-star"></i> Avis clients</h2>
                <?php if ($vendeur['note_moyenne']): ?><span class="compteur"><?= e($vendeur['note_moyenne']) ?> / 5</span><?php endif; ?></div>
            <?php foreach ($avis as $a): ?>
                <div style="padding:12px 0;border-bottom:1px solid var(--gris-50)">
                    <span class="note-fixe"><?= str_repeat('★', (int) $a['note']) ?></span> <b><?= e($a['prenom']) ?></b>
                    <p class="texte-doux" style="font-size:14px"><?= e($a['commentaire'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$avis): ?><p class="texte-doux">Pas encore d'avis.</p><?php endif; ?>
        </section>
    </div>
</div>
<script>
(function () {
    // Ouverture du formulaire d'édition de stock
    document.querySelectorAll('[data-editer]').forEach(b => b.addEventListener('click', () => b.closest('.ligne-stock').classList.toggle('edition')));
    document.querySelectorAll('[data-mettre]').forEach(b => b.addEventListener('click', () => {
        const f = b.closest('form'); f.quantite.value = b.dataset.mettre; f.submit();
    }));
    // Nouvelles commandes : vérification AJAX toutes les 15 s
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
