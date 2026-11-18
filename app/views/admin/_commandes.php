<?php /** @var array $liste */ $avecActions = $avecActions ?? false; ?>
<div class="tableau-wrap"><table class="tableau">
    <thead><tr><th>Réf.</th><th>Date</th><th>Client</th><th>Vendeur</th><th>Articles</th><th>Mode</th><th>Total</th><th>Paiement</th><th>Statut</th><?= $avecActions ? '<th></th>' : '' ?></tr></thead>
    <tbody>
    <?php foreach ($liste as $c): $s = statut_commande($c['statut']); ?>
        <tr>
            <td><b>#<?= e($c['reference']) ?></b></td>
            <td><?= date_fr($c['date_commande']) ?></td>
            <td><?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?></td>
            <td><?= e($c['nom_commerce']) ?></td>
            <td><?= e($c['resume']) ?></td>
            <td><?= $c['mode_recuperation'] === 'livraison' ? 'Livraison' . ($c['livreur_prenom'] ? '<br><small class="texte-doux">' . e($c['livreur_prenom'] . ' ' . $c['livreur_nom']) . '</small>' : '') : 'Retrait' ?></td>
            <td><?= fcfa($c['montant_total']) ?></td>
            <td><small><?= e(modes_paiement()[$c['mode_paiement']] ?? '') ?><br><?= statut_paiement($c['statut_paiement'] ?? '') ?></small></td>
            <td><span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span></td>
            <?php if ($avecActions): ?>
                <td class="actions">
                    <?php if (!in_array($c['statut'], ['livree', 'annulee'], true)): ?>
                        <form method="post" action="<?= url('admin/commande') ?>" data-confirmer="Annuler la commande <?= e($c['reference']) ?> et restituer le stock ?">
                            <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= $c['id'] ?>">
                            <button class="btn btn-danger btn-petit">Annuler</button>
                        </form>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$liste): ?><tr><td colspan="10" class="texte-doux">Aucune commande.</td></tr><?php endif; ?>
    </tbody>
</table></div>
