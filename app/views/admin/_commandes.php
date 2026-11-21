<?php /** @var array $liste */ $avecActions = $avecActions ?? false; ?>
<div class="tableau-wrap"><table class="tableau">
    <thead><tr><th>Réf.</th><th>Date</th><th>Client</th><th>Dépôt</th><th>Articles</th><th>Mode</th><th class="num">Total</th><th>Paiement</th><th>Statut</th><?= $avecActions ? '<th></th>' : '' ?></tr></thead>
    <tbody>
    <?php foreach ($liste as $c): $s = statut_commande($c['statut']); ?>
        <tr>
            <td class="nowrap"><b>#<?= e($c['reference']) ?></b></td>
            <td class="nowrap"><?= date('d/m/Y', strtotime($c['date_commande'])) ?><br><small><?= date('H:i', strtotime($c['date_commande'])) ?></small></td>
            <td><?= e($c['client_prenom'] . ' ' . $c['client_nom']) ?></td>
            <td><?= e($c['nom_commerce']) ?></td>
            <td><?= e($c['resume']) ?></td>
            <td><?= $c['mode_recuperation'] === 'livraison' ? 'Livraison' . ($c['livreur_prenom'] ? '<br><small>' . e($c['livreur_prenom'] . ' ' . $c['livreur_nom']) . '</small>' : '') : 'Retrait' ?></td>
            <td class="num"><?= fcfa($c['montant_total']) ?></td>
            <td class="nowrap"><?= e(['orange_money' => 'Orange Money', 'moov_money' => 'Moov Money', 'especes' => 'Espèces'][$c['mode_paiement']] ?? '') ?><br><small><?= statut_paiement($c['statut_paiement'] ?? '') ?></small></td>
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
