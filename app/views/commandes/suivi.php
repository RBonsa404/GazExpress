<?php
/** @var array $commandes @var ?array $commande @var array $details @var array $historique @var ?array $avis @var array $reclamations */
?>
<?php if (!$commande): ?>
<div class="conteneur">
    <div class="page-simple">
        <?= icone('recu') ?>
        <h1>Aucune commande pour l'instant</h1>
        <p>Après votre première commande, vous suivrez ici chaque étape : confirmation du dépôt, préparation, livraison.</p>
        <div class="actions"><a href="<?= url('#vendeurs') ?>" class="btn btn-primaire">Trouver un dépôt</a></div>
    </div>
</div>
<?php else:
    $livraison = $commande['mode_recuperation'] === 'livraison';
    $etapes = $livraison
        ? ['en_attente' => ['Envoyée', 'horloge'], 'acceptee' => ['Confirmée', 'check'], 'en_preparation' => ['Préparation', 'colis'], 'en_livraison' => ['En route', 'moto'], 'livree' => ['Livrée', 'check-cercle']]
        : ['en_attente' => ['Envoyée', 'horloge'], 'acceptee' => ['Confirmée', 'check'], 'en_preparation' => ['Préparation', 'colis'], 'livree' => ['Retirée', 'boutique']];
    $cles    = array_keys($etapes);
    $annule  = $commande['statut'] === 'annulee';
    $rang    = $annule ? -1 : array_search($commande['statut'], $cles, true);
    $statut  = statut_commande($commande['statut']);
?>
<section class="tete-page">
    <div class="conteneur">
        <div>
            <div class="fil"><a href="<?= url('commandes') ?>">Mes commandes</a> / #<?= e($commande['reference']) ?></div>
            <h1>Commande #<?= e($commande['reference']) ?></h1>
            <p>Passée le <?= date_fr($commande['date_commande']) ?> · <?= e($commande['nom_commerce']) ?></p>
        </div>
        <span class="etiquette avec-point <?= $statut['classe'] ?>"><?= $statut['libelle'] ?></span>
    </div>
</section>

<div class="conteneur">
    <div class="suivi-grille">
        <div class="pile">
            <section class="panneau" id="suivi" data-commande="<?= (int) $commande['id'] ?>" data-statut="<?= e($commande['statut']) ?>" data-livraison="<?= e($commande['statut_livraison'] ?? '') ?>">
                <div class="panneau-corps">
                    <?php if ($annule): ?>
                        <div class="bandeau-info alerte">
                            <span><?= icone('croix-cercle') ?> <span>Commande annulée<?= $commande['motif_annulation'] ? ' : ' . e($commande['motif_annulation']) : '' ?>.
                            <?php if ($commande['statut_paiement'] === 'rembourse'): ?> Votre paiement Mobile Money a été remboursé.<?php endif; ?></span></span>
                            <a href="<?= url('catalogue?vendeur=' . $commande['vendeur_id']) ?>" class="btn btn-primaire btn-petit">Commander à nouveau</a>
                        </div>
                    <?php elseif ($commande['statut'] === 'livree'): ?>
                        <div class="bandeau-info ok">
                            <span><?= icone('check-cercle') ?> Commande <?= $livraison ? 'livrée' : 'retirée' ?> le <?= date_fr($commande['date_cloture']) ?>.</span>
                        </div>
                    <?php else: ?>
                        <div class="bandeau-info">
                            <span><?= icone('info') ?> <span>
                            <?php if ($commande['statut'] === 'en_attente'): ?>
                                Le dépôt vérifie son stock. Cette page se met à jour toute seule.
                            <?php elseif ($livraison && $commande['livreur_id'] && $commande['statut'] !== 'en_livraison'): ?>
                                Livreur : <?= e($commande['livreur_prenom'] . ' ' . $commande['livreur_nom']) ?>. Il passe récupérer votre bouteille au dépôt.
                            <?php elseif ($commande['statut'] === 'en_livraison'): ?>
                                <?= e($commande['livreur_prenom']) ?> est en route. Préparez <?= $commande['statut_paiement'] === 'valide' ? 'votre bouteille vide' : 'le montant exact et votre bouteille vide' ?>.
                            <?php elseif (!$livraison): ?>
                                Votre commande vous attend au dépôt. Donnez la référence <b><?= e($commande['reference']) ?></b> au comptoir.
                            <?php else: ?>
                                Le dépôt prépare votre commande ; un livreur va la prendre en charge.
                            <?php endif; ?>
                            </span></span>
                            <?php if ($commande['statut'] === 'en_attente'): ?>
                                <form method="post" action="<?= url('commandes/annuler') ?>" data-confirmer="Annuler la commande <?= e($commande['reference']) ?> ?">
                                    <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                                    <button class="btn btn-danger btn-petit">Annuler la commande</button>
                                </form>
                            <?php elseif ($commande['statut'] === 'en_livraison' && $commande['livreur_tel']): ?>
                                <a href="tel:<?= e($commande['livreur_tel']) ?>" class="btn btn-primaire btn-petit"><?= icone('telephone') ?> Appeler le livreur</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$annule): ?>
                    <div class="progression">
                        <ol style="grid-template-columns:repeat(<?= count($cles) ?>,1fr)">
                            <?php foreach ($etapes as $cle => [$lib, $ico]): $i = array_search($cle, $cles, true); ?>
                                <li class="<?= $i < $rang ? 'fait' : ($i === $rang ? 'courant' : '') ?>">
                                    <span class="pt"><?= icone($i < $rang ? 'check' : $ico) ?></span><?= $lib ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panneau">
                <div class="panneau-tete"><h2>Articles</h2></div>
                <?php foreach ($details as $d): ?>
                    <div class="ligne-article">
                        <img src="<?= visuel_produit($d['marque'], $d['capacite_kg']) ?>" alt="">
                        <span><strong>Bouteille <?= e($d['marque'] . ' ' . capacite($d['capacite_kg'])) ?></strong><small><?= (int) $d['quantite'] ?> × <?= fcfa($d['prix_unitaire']) ?></small></span>
                        <span class="montant"><?= fcfa($d['quantite'] * $d['prix_unitaire']) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="recap-total">
                    <?php if ($commande['frais_livraison'] > 0): ?><div><span>Livraison</span><span><?= fcfa($commande['frais_livraison']) ?></span></div><?php endif; ?>
                    <div class="total"><span>Total</span><span><?= fcfa($commande['montant_total']) ?></span></div>
                </div>
            </section>

            <section class="panneau">
                <div class="panneau-tete"><h2>Historique</h2></div>
                <div class="panneau-corps">
                    <ul class="chronologie">
                        <?php foreach ($historique as $h): ?>
                            <li><b><?= statut_commande($h['nouveau_statut'])['libelle'] ?></b>
                                <small><?= date_fr($h['date_changement']) ?><?= $h['role'] && $h['role'] !== 'client' ? ' · ' . e(['vendeur' => 'par le dépôt', 'livreur' => 'par le livreur', 'admin' => 'par l\'administration'][$h['role']] ?? $h['role']) : '' ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>

        <aside class="pile">
            <section class="panneau">
                <div class="panneau-tete"><h2>Détails</h2></div>
                <div class="panneau-corps">
                    <dl class="infos-liste">
                        <dt>Dépôt</dt>
                        <dd><strong><?= e($commande['nom_commerce']) ?></strong><br><?= e($commande['vendeur_adresse']) ?><br><a href="tel:<?= e($commande['vendeur_tel']) ?>"><?= e($commande['vendeur_tel']) ?></a></dd>
                        <dt><?= $livraison ? 'Livraison' : 'Récupération' ?></dt>
                        <dd><?= $livraison ? e($commande['quartier_livraison']) . '<br>' . e($commande['adresse_livraison']) : 'Retrait au dépôt' ?></dd>
                        <dt>Paiement</dt>
                        <dd><?= e(modes_paiement()[$commande['mode_paiement']] ?? '') ?> · <?= statut_paiement($commande['statut_paiement']) ?></dd>
                    </dl>
                </div>
            </section>

            <?php if ($commande['statut'] === 'livree' && !$avis): ?>
                <section class="panneau">
                    <div class="panneau-tete"><h2>Donner votre avis</h2></div>
                    <form method="post" action="<?= url('commandes/avis') ?>" class="panneau-corps">
                        <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                        <div class="groupe"><label>Le dépôt</label>
                            <div class="etoiles"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="note" id="nv<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="nv<?= $i ?>" title="<?= $i ?> / 5">★</label><?php endfor; ?></div>
                        </div>
                        <?php if ($livraison): ?>
                            <div class="groupe"><label>Le livreur</label>
                                <div class="etoiles"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="note_livreur" id="nl<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>><label for="nl<?= $i ?>" title="<?= $i ?> / 5">★</label><?php endfor; ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="groupe"><textarea name="commentaire" class="champ" rows="3" maxlength="500" placeholder="Commentaire (facultatif)"></textarea></div>
                        <button class="btn btn-primaire btn-petit">Publier l'avis</button>
                    </form>
                </section>
            <?php elseif ($avis): ?>
                <section class="panneau">
                    <div class="panneau-tete"><h2>Votre avis</h2></div>
                    <div class="panneau-corps">
                        <span class="note-fixe"><?= str_repeat('★', (int) $avis['note']) ?><span class="eteinte"><?= str_repeat('★', 5 - (int) $avis['note']) ?></span></span>
                        <?php if ($avis['commentaire']): ?><p class="texte-doux" style="margin-top:4px"><?= e($avis['commentaire']) ?></p><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="panneau">
                <div class="panneau-tete"><h2>Un problème ?</h2></div>
                <div class="panneau-corps">
                    <?php foreach ($reclamations as $r): ?>
                        <div class="ligne-reclamation">
                            <div class="haut"><strong><?= e($r['objet']) ?></strong><span class="etiquette <?= $r['statut'] === 'traitee' ? 'disponible' : 'limite' ?>"><?= $r['statut'] === 'traitee' ? 'Traitée' : 'En cours' ?></span></div>
                            <?php if ($r['reponse']): ?><small><b>Réponse :</b> <?= e($r['reponse']) ?></small><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <details <?= $reclamations ? 'style="margin-top:12px"' : '' ?>>
                        <summary class="btn btn-secondaire btn-petit">Signaler un problème</summary>
                        <form method="post" action="<?= url('commandes/reclamation') ?>" style="margin-top:12px">
                            <?= csrf_field() ?><input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                            <div class="groupe">
                                <label for="objet">Motif</label>
                                <select name="objet" id="objet" class="champ">
                                    <option>Retard de livraison</option><option>Bouteille non conforme ou fuite</option>
                                    <option>Problème de paiement</option><option>Comportement du livreur</option><option>Autre</option>
                                </select>
                            </div>
                            <div class="groupe"><textarea name="message" class="champ" rows="3" required minlength="10" maxlength="2000" placeholder="Décrivez le problème"></textarea></div>
                            <button class="btn btn-sombre btn-petit">Envoyer</button>
                        </form>
                    </details>
                    <p class="aide" style="margin-top:12px">En cas d'odeur de gaz : fermez le robinet, aérez, n'actionnez aucun interrupteur.</p>
                </div>
            </section>
        </aside>
    </div>

    <?php if (count($commandes) > 1): ?>
        <section class="panneau section">
            <div class="panneau-tete"><h2>Mes commandes</h2><span class="meta"><?= count($commandes) ?> commandes</span></div>
            <div class="tableau-wrap">
                <table class="tableau">
                    <thead><tr><th>Référence</th><th>Date</th><th>Dépôt</th><th>Articles</th><th class="num">Total</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($commandes as $c): $s = statut_commande($c['statut']); ?>
                        <tr <?= $c['id'] == $commande['id'] ? 'style="background:var(--bordeaux-50)"' : '' ?>>
                            <td><a href="<?= url('commandes/suivi?id=' . $c['id']) ?>" class="lien">#<?= e($c['reference']) ?></a></td>
                            <td><?= date_fr($c['date_commande'], false) ?></td>
                            <td><?= e($c['nom_commerce']) ?></td>
                            <td><?= e($c['resume']) ?></td>
                            <td class="num"><?= fcfa($c['montant_total']) ?></td>
                            <td><span class="etiquette <?= $s['classe'] ?>"><?= $s['libelle'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($commande && !in_array($commande['statut'], ['livree', 'annulee'], true)): ?>
<script>
/* Suivi en direct : interrogation régulière, rechargement si le statut change */
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
