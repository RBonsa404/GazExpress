<?php
/** @var ?array $vendeur @var array $produits @var array $marques @var array $vendeurs @var array $avis */
$capacites = array_values(array_unique(array_map(fn ($p) => capacite($p['capacite_kg']), $produits)));
$boutiqueFermee = $vendeur && !Vendeur::estOuvertMaintenant($vendeur);   // hors horaires
$horsLigne      = $vendeur && !$vendeur['est_ouvert'];                  // le dépôt ne prend pas de commandes
?>
<section class="tete-page">
    <div class="conteneur">
        <?php if ($vendeur): ?>
            <div>
                <div class="fil"><a href="<?= url('catalogue') ?>">Catalogue</a> / Dépôt</div>
                <h1><?= e($vendeur['nom_commerce']) ?></h1>
                <div class="meta">
                    <span><?= icone('lieu') ?><?= e($vendeur['adresse']) ?></span>
                    <span><?= icone('horloge') ?><?= heure($vendeur['heure_ouverture']) ?> – <?= heure($vendeur['heure_fermeture']) ?></span>
                    <span><?= icone('telephone') ?><a href="tel:<?= e($vendeur['telephone']) ?>"><?= e($vendeur['telephone']) ?></a></span>
                    <?php if ($vendeur['note_moyenne']): ?>
                        <span><?= icone('etoile') ?><?= e(str_replace('.', ',', $vendeur['note_moyenne'])) ?> / 5 · <?= (int) $vendeur['nb_avis'] ?> avis</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($boutiqueFermee): ?>
                <span class="etiquette indisponible">Fermé actuellement</span>
            <?php else: ?>
                <span class="etiquette avec-point disponible">Ouvert</span>
            <?php endif; ?>
        <?php else: ?>
            <div>
                <h1>Bouteilles de gaz et prix</h1>
                <p>Prix officiels des recharges 6 kg et 12,5 kg à Ouagadougou. Choisissez un dépôt pour voir son stock et commander.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="conteneur">
    <?php if ($horsLigne): ?>
        <div class="bandeau-info attention" style="margin-bottom:16px">
            <span><?= icone('horloge') ?> Ce dépôt ne prend pas de commandes en ce moment. Choisissez un autre dépôt ou revenez plus tard.</span>
        </div>
    <?php endif; ?>

    <div class="catalogue">
        <aside class="filtres" aria-label="Filtres">
            <div class="bloc vendeur">
                <h3><label for="choix-vendeur" style="font:inherit;color:inherit">Dépôt</label></h3>
                <form method="get" action="<?= url('catalogue') ?>">
                    <select name="vendeur" id="choix-vendeur" class="champ" onchange="this.form.submit()">
                        <option value="">Tous les dépôts</option>
                        <?php foreach ($vendeurs as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $vendeur && $vendeur['id'] == $v['id'] ? 'selected' : '' ?>><?= e($v['nom_commerce']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-secondaire btn-petit" style="margin-top:8px">Afficher</button></noscript>
                </form>
            </div>
            <div class="bloc">
                <h3>Marque</h3>
                <div class="puces" data-filtre-groupe=".produit" data-filtre-attr="marque">
                    <button type="button" class="puce actif" data-valeur="">Toutes</button>
                    <?php foreach ($marques as $m): ?>
                        <button type="button" class="puce" data-valeur="<?= e($m) ?>"><?= e($m) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bloc">
                <h3>Capacité</h3>
                <div class="puces" data-filtre-groupe=".produit" data-filtre-attr="capacite">
                    <button type="button" class="puce actif" data-valeur="">Toutes</button>
                    <?php foreach ($capacites as $c): ?>
                        <button type="button" class="puce" data-valeur="<?= e($c) ?>"><?= e($c) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <div>
            <div class="barre-resultats">
                <span data-compteur-produits><?= count($produits) ?> bouteille<?= count($produits) > 1 ? 's' : '' ?></span>
                <?php if (!$vendeur): ?><span class="masque-mobile">Prix de référence · meilleur prix par dépôt</span><?php endif; ?>
            </div>

            <?php if (!$produits): ?>
                <div class="panneau vide"><?= icone('colis') ?>Ce dépôt n'a pas encore renseigné son stock.</div>
            <?php endif; ?>
            <div class="panneau vide" data-aucun-produit hidden><?= icone('recherche') ?>Aucune bouteille ne correspond à ces filtres.</div>

            <div class="grille-produits">
                <?php foreach ($produits as $p):
                    $quantite  = (int) $p['quantite'];
                    $etat      = etat_stock($quantite);
                    $produitId = $vendeur ? $p['produit_id'] : $p['id'];
                    $prix      = $vendeur ? $p['prix'] : $p['prix_reference'];
                    $bloque    = $quantite <= 0 || $horsLigne;
                ?>
                    <article class="produit <?= $quantite <= 0 ? 'rupture' : '' ?>" id="produit-<?= (int) $produitId ?>"
                             data-marque="<?= e($p['marque']) ?>" data-capacite="<?= e(capacite($p['capacite_kg'])) ?>">
                        <div class="produit-visuel">
                            <span class="etiquette <?= $etat['cle'] ?>"><?= $vendeur ? $etat['libelle'] : ($quantite > 0 ? 'En stock' : 'Rupture') ?></span>
                            <img src="<?= visuel_produit($p['marque'], $p['capacite_kg']) ?>" alt="Bouteille de gaz <?= e($p['marque'] . ' ' . capacite($p['capacite_kg'])) ?>" loading="lazy" width="131" height="170">
                        </div>
                        <div class="produit-corps">
                            <span class="marque"><?= e($p['marque']) ?></span>
                            <h3>Bouteille <?= capacite($p['capacite_kg']) ?></h3>
                            <p class="desc">Recharge butane, échange contre votre bouteille vide.</p>
                            <?php if (!$vendeur): ?>
                                <p class="chez"><?= icone('boutique') ?>
                                    <?php if ((int) $p['nb_vendeurs']): ?>
                                        <?= (int) $p['nb_vendeurs'] ?> dépôt<?= $p['nb_vendeurs'] > 1 ? 's' : '' ?><?= $p['meilleur_prix'] && $p['meilleur_prix'] < $p['prix_reference'] ? ', dès ' . fcfa($p['meilleur_prix']) : '' ?>
                                    <?php else: ?>
                                        Aucun dépôt en stock
                                    <?php endif; ?>
                                </p>
                            <?php elseif ($quantite > 0 && $quantite < SEUIL_STOCK_LIMITE): ?>
                                <p class="chez alerte"><?= icone('alerte') ?> Plus que <?= $quantite ?> en stock</p>
                            <?php endif; ?>
                            <div class="produit-pied">
                                <div>
                                    <small><?= $vendeur ? 'Prix du dépôt' : 'Prix officiel' ?></small>
                                    <span class="prix"><?= fcfa($prix) ?></span>
                                </div>
                                <form method="post" action="<?= url('panier/ajouter') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="produit_id" value="<?= (int) $produitId ?>">
                                    <?php if ($vendeur): ?><input type="hidden" name="vendeur_id" value="<?= (int) $vendeur['id'] ?>"><?php endif; ?>
                                    <button class="btn btn-primaire btn-petit" <?= $bloque ? 'disabled' : '' ?>><?= icone('plus') ?> Ajouter</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($avis): ?>
                <section class="panneau section">
                    <div class="panneau-tete"><h2>Avis des clients</h2><span class="meta"><?= (int) $vendeur['nb_avis'] ?> avis</span></div>
                    <div class="avis-liste">
                        <?php foreach ($avis as $a): ?>
                            <blockquote class="avis" style="margin:0">
                                <span class="note-fixe"><?= str_repeat('★', (int) $a['note']) ?><span class="eteinte"><?= str_repeat('★', 5 - (int) $a['note']) ?></span></span>
                                <p><?= e($a['commentaire'] ?? '') ?></p>
                                <small><?= e($a['prenom'] . ' ' . mb_substr($a['nom'], 0, 1)) ?>. · <?= date_fr($a['date_avis'], false) ?></small>
                            </blockquote>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
