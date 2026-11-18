<?php
/** @var string $onglet @var array $stats @var array $roles @var int $nbValidations @var int $nbReclamations */
$onglets = [
    'tableau'      => ['Tableau de bord', 'fa-chart-line', 0],
    'validations'  => ['Validations', 'fa-user-check', $nbValidations],
    'utilisateurs' => ['Utilisateurs', 'fa-users', 0],
    'commandes'    => ['Commandes', 'fa-receipt', 0],
    'reclamations' => ['Réclamations', 'fa-headset', $nbReclamations],
];
?>
<div class="conteneur">
    <section class="heros heros-compact">
        <div style="position:relative;z-index:1">
            <span class="surtitre sombre">Back-office</span>
            <h1>Administration GazExpress</h1>
            <p>Validez les partenaires, supervisez les commandes et traitez les réclamations.</p>
        </div>
    </section>

    <div class="stats quatre">
        <div class="carte stat"><span class="ico rose"><i class="fa-solid fa-receipt"></i></span>
            <div><small>Commandes (dont aujourd'hui)</small><strong><?= (int) $stats['total'] ?> <span class="texte-doux" style="font-size:16px">(<?= (int) $stats['jour'] ?>)</span></strong></div></div>
        <div class="carte stat"><span class="ico jaune"><i class="fa-solid fa-hourglass-half"></i></span>
            <div><small>Commandes en cours</small><strong><?= (int) $stats['en_cours'] ?></strong></div></div>
        <div class="carte stat"><span class="ico vert"><i class="fa-solid fa-coins"></i></span>
            <div><small>Volume livré · commission 5 %</small><strong><?= fcfa($stats['volume']) ?></strong><small><?= fcfa($stats['commission']) ?> de commission</small></div></div>
        <div class="carte stat"><span class="ico bleu"><i class="fa-solid fa-users"></i></span>
            <div><small>Clients · vendeurs · livreurs</small><strong><?= (int) ($roles['client'] ?? 0) ?> · <?= (int) ($roles['vendeur'] ?? 0) ?> · <?= (int) ($roles['livreur'] ?? 0) ?></strong></div></div>
    </div>

    <nav class="onglets">
        <?php foreach ($onglets as $cle => [$lib, $ico, $n]): ?>
            <a href="<?= url('admin?onglet=' . $cle) ?>" class="<?= $onglet === $cle ? 'actif' : '' ?>"><i class="fa-solid <?= $ico ?>"></i> <?= $lib ?><?php if ($n): ?><span class="compte"><?= $n ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>

<?php if ($onglet === 'tableau'): $max = max(1, max($parJour)); ?>
    <div class="grille-tableau">
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-chart-column"></i> Commandes des 7 derniers jours</h2></div>
            <div class="graphe">
                <?php foreach ($parJour as $jour => $n): ?>
                    <div class="col"><b><?= $n ?></b><div class="barre" style="height:<?= round($n / $max * 150) ?>px"></div><small><?= date('d/m', strtotime($jour)) ?></small></div>
                <?php endforeach; ?>
            </div>
            <p class="texte-doux" style="margin-top:16px;font-size:14px">Livrées : <?= (int) $stats['livrees'] ?> · Annulées : <?= (int) $stats['annulees'] ?></p>
        </section>
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-store"></i> Vendeurs</h2></div>
            <div class="tableau-wrap"><table class="tableau">
                <thead><tr><th>Boutique</th><th>Quartier</th><th>Stock</th><th>Note</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($vendeurs as $v): ?>
                    <tr><td><b><?= e($v['nom_commerce']) ?></b></td><td><?= e($v['quartier']) ?></td><td><?= (int) $v['total_stock'] ?></td>
                        <td><?= $v['note_moyenne'] ? e($v['note_moyenne']) . '/5' : '—' ?></td>
                        <td><span class="etiquette <?= ['valide' => 'disponible', 'en_attente' => 'limite', 'refuse' => 'indisponible'][$v['statut_validation']] ?>"><?= ['valide' => 'Validé', 'en_attente' => 'À valider', 'refuse' => 'Refusé'][$v['statut_validation']] ?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <section class="carte section">
        <div class="carte-titre"><h2><i class="fa-solid fa-clock"></i> Dernières commandes</h2><a href="<?= url('admin?onglet=commandes') ?>" class="btn btn-secondaire btn-petit">Tout voir</a></div>
        <?php $liste = $dernieres; require __DIR__ . '/_commandes.php'; ?>
    </section>

<?php elseif ($onglet === 'validations'): ?>
    <div class="grille-tableau">
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-store"></i> Vendeurs à valider</h2></div>
            <?php if (!$vendeursAttente): ?><div class="vide"><i class="fa-solid fa-circle-check"></i>Aucune demande en attente.</div><?php endif; ?>
            <?php foreach ($vendeursAttente as $v): ?>
                <div class="carte-validation">
                    <div>
                        <strong><?= e($v['nom_commerce']) ?></strong>
                        <dl>
                            <dt>Gérant</dt><dd><?= e($v['gerant_prenom'] . ' ' . $v['gerant_nom']) ?></dd>
                            <dt>Adresse</dt><dd><?= e($v['adresse']) ?> (<?= e($v['quartier']) ?>)</dd>
                            <dt>Téléphone</dt><dd><?= e($v['telephone']) ?></dd>
                            <dt>Autorisation</dt><dd><?= e($v['numero_autorisation']) ?></dd>
                            <dt>Horaires</dt><dd><?= heure($v['heure_ouverture']) ?> - <?= heure($v['heure_fermeture']) ?></dd>
                        </dl>
                    </div>
                    <div class="actions">
                        <form method="post" action="<?= url('admin/validation') ?>"><?= csrf_field() ?><input type="hidden" name="type" value="vendeur"><input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button name="decision" value="valide" class="btn btn-succes btn-petit"><i class="fa-solid fa-check"></i> Valider</button></form>
                        <form method="post" action="<?= url('admin/validation') ?>" data-confirmer="Refuser ce vendeur ?"><?= csrf_field() ?><input type="hidden" name="type" value="vendeur"><input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button name="decision" value="refuse" class="btn btn-danger btn-petit">Refuser</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <section class="carte">
            <div class="carte-titre"><h2><i class="fa-solid fa-motorcycle"></i> Livreurs à valider</h2></div>
            <?php if (!$livreursAttente): ?><div class="vide"><i class="fa-solid fa-circle-check"></i>Aucune demande en attente.</div><?php endif; ?>
            <?php foreach ($livreursAttente as $l): ?>
                <div class="carte-validation">
                    <div>
                        <strong><?= e($l['prenom'] . ' ' . $l['nom']) ?></strong>
                        <dl>
                            <dt>Téléphone</dt><dd><?= e($l['telephone']) ?></dd>
                            <dt>Engin</dt><dd><?= e(ucfirst($l['type_engin'])) ?> <?= e($l['immatriculation'] ?? '') ?></dd>
                            <dt>Pièce d'identité</dt><dd><?= e($l['numero_piece']) ?></dd>
                            <dt>Quartier</dt><dd><?= e($l['quartier'] ?? '—') ?></dd>
                        </dl>
                    </div>
                    <div class="actions">
                        <form method="post" action="<?= url('admin/validation') ?>"><?= csrf_field() ?><input type="hidden" name="type" value="livreur"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button name="decision" value="valide" class="btn btn-succes btn-petit"><i class="fa-solid fa-check"></i> Valider</button></form>
                        <form method="post" action="<?= url('admin/validation') ?>" data-confirmer="Refuser ce livreur ?"><?= csrf_field() ?><input type="hidden" name="type" value="livreur"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button name="decision" value="refuse" class="btn btn-danger btn-petit">Refuser</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

<?php elseif ($onglet === 'utilisateurs'): ?>
    <section class="carte section">
        <form method="get" action="<?= url('admin') ?>" class="filtres-ligne">
            <input type="hidden" name="onglet" value="utilisateurs">
            <input type="search" name="q" value="<?= e($recherche) ?>" placeholder="Nom, e-mail, téléphone…">
            <select name="role" onchange="this.form.submit()">
                <option value="">Tous les rôles</option>
                <?php foreach (['client', 'vendeur', 'livreur', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $filtreRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-sombre btn-petit">Filtrer</button>
        </form>
        <div class="tableau-wrap"><table class="tableau">
            <thead><tr><th>Nom</th><th>Rôle</th><th>Contact</th><th>Quartier</th><th>Inscrit le</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <tr>
                    <td><b><?= e($u['prenom'] . ' ' . $u['nom']) ?></b></td>
                    <td><span class="etiquette neutre"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= e($u['email']) ?><br><small class="texte-doux"><?= e($u['telephone']) ?></small></td>
                    <td><?= e($u['quartier'] ?? '—') ?></td>
                    <td><?= date_fr($u['date_creation'], false) ?></td>
                    <td><span class="etiquette <?= $u['statut'] === 'actif' ? 'disponible' : 'indisponible' ?>"><?= ucfirst($u['statut']) ?></span></td>
                    <td class="actions">
                        <?php if ($u['role'] !== 'admin'): ?>
                            <form method="post" action="<?= url('admin/utilisateur') ?>" <?= $u['statut'] === 'actif' ? 'data-confirmer="Suspendre ce compte ?"' : '' ?>>
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <?php if ($u['statut'] === 'actif'): ?>
                                    <button name="statut" value="suspendu" class="btn btn-danger btn-petit">Suspendre</button>
                                <?php else: ?>
                                    <button name="statut" value="actif" class="btn btn-succes btn-petit">Réactiver</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>

<?php elseif ($onglet === 'commandes'): ?>
    <section class="carte section">
        <div class="puces" style="margin-bottom:18px">
            <a href="<?= url('admin?onglet=commandes') ?>" class="puce <?= $filtreStatut === '' ? 'actif' : '' ?>">Toutes</a>
            <?php foreach (statuts_commande() as $cle => $s): ?>
                <a href="<?= url('admin?onglet=commandes&statut=' . $cle) ?>" class="puce <?= $filtreStatut === $cle ? 'actif' : '' ?>"><?= $s['libelle'] ?></a>
            <?php endforeach; ?>
        </div>
        <?php $liste = $commandes; $avecActions = true; require __DIR__ . '/_commandes.php'; ?>
    </section>

<?php else: ?>
    <section class="carte section">
        <?php if (!$reclamations): ?><div class="vide"><i class="fa-solid fa-face-smile"></i>Aucune réclamation.</div><?php endif; ?>
        <?php foreach ($reclamations as $r): ?>
            <div class="carte-validation">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <strong><?= e($r['objet']) ?></strong>
                        <span class="etiquette <?= $r['statut'] === 'traitee' ? 'disponible' : 'limite' ?>"><?= $r['statut'] === 'traitee' ? 'Traitée' : 'Ouverte' ?></span>
                    </div>
                    <small class="texte-doux">Commande #<?= e($r['reference']) ?> · <?= e($r['prenom'] . ' ' . $r['nom'] . ' · ' . $r['telephone']) ?> · <?= date_fr($r['date_creation']) ?></small>
                    <p style="margin-top:10px"><?= nl2br(e($r['message'])) ?></p>
                    <?php if ($r['reponse']): ?><p style="margin-top:10px" class="texte-doux"><b>Réponse :</b> <?= e($r['reponse']) ?></p><?php endif; ?>
                </div>
                <?php if ($r['statut'] === 'ouverte'): ?>
                    <form method="post" action="<?= url('admin/reclamation') ?>" style="flex:1;min-width:260px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <textarea name="reponse" class="champ" rows="3" required placeholder="Réponse au client"></textarea>
                        <button class="btn btn-primaire btn-petit" style="margin-top:8px">Répondre et clôturer</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
</div>
