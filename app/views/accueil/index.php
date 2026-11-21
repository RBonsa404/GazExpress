<?php
/** @var array $vendeurs @var array $quartiers @var array $carte @var array $produits */
$scripts = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', asset('js/carte.js')];
$nbOuverts = count(array_filter($vendeurs, fn ($v) => Vendeur::estOuvertMaintenant($v)));
?>
<section class="intro">
    <div class="conteneur">
        <div>
            <h1>Votre bouteille de gaz, livrée chez vous à Ouagadougou</h1>
            <p class="chapeau">Comparez les prix et le stock des dépôts SODIGAZ, TOTAL et ORYX de votre quartier, puis faites-vous livrer ou passez la retirer.</p>

            <form class="recherche-intro" id="form-intro" action="<?= url() ?>#vendeurs" method="get">
                <label class="champ-icone">
                    <span class="sr-only">Votre quartier</span>
                    <?= icone('lieu') ?>
                    <input type="search" name="q" class="champ" placeholder="Votre quartier : Ouaga 2000, Pissy, 1200 Logements…" autocomplete="off">
                </label>
                <button class="btn btn-primaire">Voir les dépôts</button>
            </form>

            <div class="atouts">
                <span><?= icone('check') ?> Prix affichés par chaque dépôt</span>
                <span><?= icone('check') ?> Stock confirmé avant préparation</span>
                <span><?= icone('check') ?> Livraison dès 500 FCFA</span>
            </div>
        </div>

        <aside class="panneau prix-jour" aria-labelledby="titre-prix">
            <div class="panneau-tete">
                <h2 id="titre-prix">Prix du jour</h2>
                <span class="meta">Recharge, bouteille consignée</span>
            </div>
            <?php foreach ($produits as $p): $meilleur = $p['meilleur_prix'] ?: $p['prix_reference']; ?>
                <a class="ligne-prix" href="<?= url('catalogue#produit-' . $p['id']) ?>">
                    <img src="<?= visuel_produit($p['marque'], $p['capacite_kg']) ?>" alt="" loading="lazy" width="36" height="44">
                    <span>
                        <strong><?= e($p['marque']) ?> <?= capacite($p['capacite_kg']) ?></strong>
                        <small><?= (int) $p['nb_vendeurs'] ? (int) $p['nb_vendeurs'] . ' dépôt' . ($p['nb_vendeurs'] > 1 ? 's' : '') . ' en stock' : 'Rupture chez les dépôts' ?></small>
                    </span>
                    <span class="prix"><?= fcfa($meilleur) ?><?php if ($p['meilleur_prix'] && $p['meilleur_prix'] < $p['prix_reference']): ?><small>au lieu de <?= fcfa($p['prix_reference']) ?></small><?php endif; ?></span>
                </a>
            <?php endforeach; ?>
            <div class="pied-panneau">
                <span class="texte-doux">Meilleur prix constaté</span>
                <a href="<?= url('catalogue') ?>" class="lien">Tout le catalogue</a>
            </div>
        </aside>
    </div>
</section>

<div class="conteneur">
    <div class="titre-bloc" id="vendeurs">
        <div>
            <h2>Dépôts de gaz partenaires</h2>
            <p><?= count($vendeurs) ?> dépôts vérifiés<?= $nbOuverts ? ', dont ' . $nbOuverts . ' ouvert' . ($nbOuverts > 1 ? 's' : '') . ' en ce moment' : '' ?>. Sélectionnez un dépôt pour voir son stock et ses prix.</p>
        </div>
        <button type="button" class="btn btn-secondaire btn-petit" id="btn-localiser"><?= icone('cible') ?> Me localiser</button>
    </div>

    <div class="outils-vendeurs">
        <label class="champ-icone">
            <span class="sr-only">Rechercher un quartier ou un dépôt</span>
            <?= icone('recherche') ?>
            <input type="search" id="recherche-vendeur" class="champ" placeholder="Quartier ou nom du dépôt" autocomplete="off">
        </label>
        <div class="puces" id="puces-quartiers">
            <button type="button" class="puce actif" data-quartier="">Tous</button>
            <?php foreach (array_slice($quartiers, 0, 6) as $q): ?>
                <button type="button" class="puce" data-quartier="<?= e($q['nom']) ?>"><?= e($q['nom']) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grille-carte">
        <div id="carte" role="region" aria-label="Carte des dépôts" data-vendeurs='<?= e(json_encode($carte, JSON_UNESCAPED_UNICODE)) ?>'></div>

        <div class="liste-vendeurs" id="liste-vendeurs">
            <div class="entete-liste"><span><span id="nb-vendeurs"><?= count($vendeurs) ?></span> dépôts</span><span>Stock · horaires</span></div>
            <?php foreach ($vendeurs as $v): $etat = Vendeur::etatGlobal($v); $ouvert = Vendeur::estOuvertMaintenant($v); ?>
                <article class="carte-vendeur" data-id="<?= $v['id'] ?>" data-quartier="<?= e($v['quartier']) ?>"
                         data-recherche="<?= e(mb_strtolower($v['nom_commerce'] . ' ' . $v['quartier'] . ' ' . $v['adresse'])) ?>">
                    <div class="haut">
                        <div>
                            <h3><?= e($v['nom_commerce']) ?></h3>
                            <span class="quartier"><?= e($v['quartier']) ?></span>
                        </div>
                        <span class="etiquette avec-point <?= $etat['cle'] ?>"><?= $etat['libelle'] ?></span>
                    </div>
                    <div class="ligne"><?= icone('lieu') ?> <?= e($v['adresse']) ?></div>
                    <div class="bas">
                        <div class="ligne" style="margin:0">
                            <?= icone('horloge') ?> <?= heure($v['heure_ouverture']) ?> – <?= heure($v['heure_fermeture']) ?>
                            <?php if (!$ouvert): ?><span class="ferme">· Fermé</span><?php endif; ?>
                            <?php if ($v['note_moyenne']): ?><span class="note">· <?= icone('etoile', 'ic-s') ?> <?= e(str_replace('.', ',', $v['note_moyenne'])) ?></span><?php endif; ?>
                        </div>
                        <a href="<?= url('catalogue?vendeur=' . $v['id']) ?>" class="lien-stock">Commander <?= icone('fleche-droite') ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
            <div class="vide" id="aucun-vendeur" style="display:none"><?= icone('boutique') ?>Aucun dépôt ne correspond à votre recherche.</div>
        </div>
    </div>

    <div class="titre-bloc">
        <div><h2>Comment ça marche</h2></div>
    </div>
    <div class="panneau panneau-corps">
        <div class="etapes">
            <div class="etape">
                <span class="num">1</span>
                <div><h3>Choisissez un dépôt</h3><p>Sur la carte, chaque dépôt affiche son stock réel, ses prix et ses horaires.</p></div>
            </div>
            <div class="etape">
                <span class="num">2</span>
                <div><h3>Commandez en ligne</h3><p>Livraison à domicile ou retrait sur place. Paiement Orange Money, Moov Money ou espèces.</p></div>
            </div>
            <div class="etape">
                <span class="num">3</span>
                <div><h3>Suivez la livraison</h3><p>Le dépôt confirme le stock, un livreur vérifié prend la course. Préparez votre bouteille vide.</p></div>
            </div>
        </div>
    </div>

    <div class="partenaires">
        <div class="partenaire">
            <?= icone('boutique') ?>
            <div>
                <h3>Vous tenez un dépôt de gaz ?</h3>
                <p>Affichez votre stock et vos prix sur la carte et recevez des commandes confirmées.</p>
                <a href="<?= url('inscription?role=vendeur') ?>" class="lien">Référencer mon dépôt</a>
            </div>
        </div>
        <div class="partenaire">
            <?= icone('moto') ?>
            <div>
                <h3>Vous avez une moto ou un tricycle ?</h3>
                <p>Livrez des bouteilles dans votre quartier et soyez payé à chaque course.</p>
                <a href="<?= url('inscription?role=livreur') ?>" class="lien">Devenir livreur</a>
            </div>
        </div>
    </div>
</div>
