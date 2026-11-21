<?php
/** @var string $onglet @var array $stats @var array $roles @var int $nbValidations @var int $nbReclamations */
$onglets = [
    'tableau'      => ['Vue d\'ensemble', 0],
    'validations'  => ['Validations', $nbValidations],
    'utilisateurs' => ['Utilisateurs', 0],
    'commandes'    => ['Commandes', 0],
    'reclamations' => ['Réclamations', $nbReclamations],
];
$validation = ['valide' => ['disponible', 'Validé'], 'en_attente' => ['limite', 'À valider'], 'refuse' => ['indisponible', 'Refusé']];
?>
<section class="tete-page">
    <div class="conteneur">
        <div>
            <div class="fil">Administration</div>
            <h1>Supervision GazExpress</h1>
            <p>Validation des partenaires, suivi des commandes et réclamations.</p>
        </div>
    </div>
</section>

<div class="conteneur">
    <div class="indicateurs quatre">
        <div class="indicateur"><small>Commandes</small><strong><?= (int) $stats['total'] ?></strong><span class="sous"><?= (int) $stats['jour'] ?> aujourd'hui</span></div>
        <div class="indicateur"><small>En cours</small><strong><?= (int) $stats['en_cours'] ?></strong><span class="sous"><?= (int) $stats['livrees'] ?> livrées · <?= (int) $stats['annulees'] ?> annulées</span></div>
        <div class="indicateur"><small>Volume livré</small><strong><?= fcfa($stats['volume']) ?></strong><span class="sous">Commission 5 % : <?= fcfa($stats['commission']) ?></span></div>
        <div class="indicateur"><small>Comptes</small><strong><?= (int) ($roles['client'] ?? 0) + (int) ($roles['vendeur'] ?? 0) + (int) ($roles['livreur'] ?? 0) ?></strong><span class="sous"><?= (int) ($roles['client'] ?? 0) ?> clients · <?= (int) ($roles['vendeur'] ?? 0) ?> dépôts · <?= (int) ($roles['livreur'] ?? 0) ?> livreurs</span></div>
    </div>

    <nav class="onglets" aria-label="Sections">
        <?php foreach ($onglets as $cle => [$lib, $n]): ?>
            <a href="<?= url('admin?onglet=' . $cle) ?>" class="<?= $onglet === $cle ? 'actif' : '' ?>"><?= $lib ?><?php if ($n): ?><span class="compte"><?= $n ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>

<?php if ($onglet === 'tableau'): $max = max(1, max($parJour)); ?>
    <div class="grille-tableau">
        <section class="panneau">
            <div class="panneau-tete"><h2>Commandes sur 7 jours</h2></div>
            <div class="panneau-corps">
                <div class="graphe">
                    <?php foreach ($parJour as $jour => $n): ?>
                        <div class="col"><b><?= $n ?></b><div class="barre" style="height:<?= round($n / $max * 130) ?>px"></div><small><?= date('d/m', strtotime($jour)) ?></small></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <section class="panneau">
            <div class="panneau-tete"><h2>Dépôts</h2><span class="meta"><?= count($vendeurs) ?></span></div>
            <div class="tableau-wrap"><table class="tableau">
                <thead><tr><th>Dépôt</th><th>Quartier</th><th class="num">Stock</th><th class="num">Note</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($vendeurs as $v): [$cls, $lib] = $validation[$v['statut_validation']]; ?>
                    <tr><td><b><?= e($v['nom_commerce']) ?></b></td><td><?= e($v['quartier']) ?></td><td class="num"><?= (int) $v['total_stock'] ?></td>
                        <td class="num"><?= $v['note_moyenne'] ? e(str_replace('.', ',', $v['note_moyenne'])) : '—' ?></td>
                        <td><span class="etiquette <?= $cls ?>"><?= $lib ?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <section class="panneau section">
        <div class="panneau-tete"><h2>Dernières commandes</h2><a href="<?= url('admin?onglet=commandes') ?>" class="lien" style="font-size:13px">Tout voir</a></div>
        <?php $liste = $dernieres; require __DIR__ . '/_commandes.php'; ?>
    </section>

<?php elseif ($onglet === 'validations'): ?>
    <div class="grille-tableau">
        <section class="panneau">
            <div class="panneau-tete"><h2>Dépôts à valider</h2><?php if ($vendeursAttente): ?><span class="compteur"><?= count($vendeursAttente) ?></span><?php endif; ?></div>
            <?php if (!$vendeursAttente): ?><div class="vide"><?= icone('check-cercle') ?>Aucune demande en attente.</div><?php endif; ?>
            <?php foreach ($vendeursAttente as $v): ?>
                <div class="carte-validation">
                    <div>
                        <strong><?= e($v['nom_commerce']) ?></strong>
                        <dl>
                            <dt>Gérant</dt><dd><?= e($v['gerant_prenom'] . ' ' . $v['gerant_nom']) ?></dd>
                            <dt>Adresse</dt><dd><?= e($v['adresse']) ?> (<?= e($v['quartier']) ?>)</dd>
                            <dt>Téléphone</dt><dd><?= e($v['telephone']) ?></dd>
                            <dt>Autorisation</dt><dd><?= e($v['numero_autorisation']) ?></dd>
                            <dt>Horaires</dt><dd><?= heure($v['heure_ouverture']) ?> – <?= heure($v['heure_fermeture']) ?></dd>
                        </dl>
                    </div>
                    <div class="actions">
                        <form method="post" action="<?= url('admin/validation') ?>"><?= csrf_field() ?><input type="hidden" name="type" value="vendeur"><input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button name="decision" value="valide" class="btn btn-succes btn-petit"><?= icone('check') ?> Valider</button></form>
                        <form method="post" action="<?= url('admin/validation') ?>" data-confirmer="Refuser ce dépôt ?"><?= csrf_field() ?><input type="hidden" name="type" value="vendeur"><input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button name="decision" value="refuse" class="btn btn-danger btn-petit">Refuser</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <section class="panneau">
            <div class="panneau-tete"><h2>Livreurs à valider</h2><?php if ($livreursAttente): ?><span class="compteur"><?= count($livreursAttente) ?></span><?php endif; ?></div>
            <?php if (!$livreursAttente): ?><div class="vide"><?= icone('check-cercle') ?>Aucune demande en attente.</div><?php endif; ?>
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
                            <button name="decision" value="valide" class="btn btn-succes btn-petit"><?= icone('check') ?> Valider</button></form>
                        <form method="post" action="<?= url('admin/validation') ?>" data-confirmer="Refuser ce livreur ?"><?= csrf_field() ?><input type="hidden" name="type" value="livreur"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button name="decision" value="refuse" class="btn btn-danger btn-petit">Refuser</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

<?php elseif ($onglet === 'utilisateurs'): ?>
    <section class="panneau">
        <div class="panneau-corps" style="padding-bottom:0">
            <form method="get" action="<?= url('admin') ?>" class="filtres-ligne">
                <input type="hidden" name="onglet" value="utilisateurs">
                <input type="search" name="q" value="<?= e($recherche) ?>" placeholder="Nom, e-mail, téléphone…" aria-label="Rechercher">
                <select name="role" onchange="this.form.submit()" aria-label="Rôle">
                    <option value="">Tous les rôles</option>
                    <?php foreach (['client', 'vendeur', 'livreur', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $filtreRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-sombre btn-petit">Filtrer</button>
            </form>
        </div>
        <div class="tableau-wrap"><table class="tableau">
            <thead><tr><th>Nom</th><th>Rôle</th><th>Contact</th><th>Quartier</th><th>Inscrit le</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <tr>
                    <td><b><?= e($u['prenom'] . ' ' . $u['nom']) ?></b></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= e($u['email']) ?><br><small><?= e($u['telephone']) ?></small></td>
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
    <section class="panneau">
        <div class="panneau-corps" style="padding-bottom:12px">
            <div class="puces">
                <a href="<?= url('admin?onglet=commandes') ?>" class="puce <?= $filtreStatut === '' ? 'actif' : '' ?>">Toutes</a>
                <?php foreach (statuts_commande() as $cle => $s): ?>
                    <a href="<?= url('admin?onglet=commandes&statut=' . $cle) ?>" class="puce <?= $filtreStatut === $cle ? 'actif' : '' ?>"><?= $s['libelle'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php $liste = $commandes; $avecActions = true; require __DIR__ . '/_commandes.php'; ?>
    </section>

<?php else: ?>
    <section class="panneau">
        <?php if (!$reclamations): ?><div class="vide"><?= icone('check-cercle') ?>Aucune réclamation.</div><?php endif; ?>
        <?php foreach ($reclamations as $r): ?>
            <div class="carte-validation">
                <div style="flex:1;min-width:260px">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <strong><?= e($r['objet']) ?></strong>
                        <span class="etiquette <?= $r['statut'] === 'traitee' ? 'disponible' : 'limite' ?>"><?= $r['statut'] === 'traitee' ? 'Traitée' : 'Ouverte' ?></span>
                    </div>
                    <small class="texte-doux">Commande #<?= e($r['reference']) ?> · <?= e($r['prenom'] . ' ' . $r['nom'] . ' · ' . $r['telephone']) ?> · <?= date_fr($r['date_creation']) ?></small>
                    <p style="margin-top:8px;font-size:14px"><?= nl2br(e($r['message'])) ?></p>
                    <?php if ($r['reponse']): ?><p style="margin-top:8px;font-size:14px" class="texte-doux"><b>Réponse :</b> <?= e($r['reponse']) ?></p><?php endif; ?>
                </div>
                <?php if ($r['statut'] === 'ouverte'): ?>
                    <form method="post" action="<?= url('admin/reclamation') ?>" style="flex:1;min-width:240px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <textarea name="reponse" class="champ" rows="3" required placeholder="Réponse au client" aria-label="Réponse au client"></textarea>
                        <button class="btn btn-primaire btn-petit" style="margin-top:8px">Répondre et clôturer</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
</div>
