<?php
/** @var ?array $vendeur @var array $produits @var array $marques @var array $vendeurs @var array $avis */
?>
<div class="conteneur">

    <section class="carte entete-page">
        <div>
            <?php if ($vendeur): ?>
                <span class="surtitre"><i class="fa-solid fa-store"></i> Boutique sélectionnée</span>
                <h1><?= e($vendeur['nom_commerce']) ?></h1>
                <div class="meta">
                    <span><i class="fa-solid fa-location-dot"></i><?= e($vendeur['adresse']) ?></span>
                    <span><i class="fa-regular fa-clock"></i><?= heure($vendeur['heure_ouverture']) ?> - <?= heure($vendeur['heure_fermeture']) ?></span>
                    <span><i class="fa-solid fa-phone"></i><a href="tel:<?= e($vendeur['telephone']) ?>"><?= e($vendeur['telephone']) ?></a></span>
                    <?php if ($vendeur['note_moyenne']): ?>
                        <span><i class="fa-solid fa-star" style="color:var(--orange)"></i><?= e($vendeur['note_moyenne']) ?>/5 (<?= (int) $vendeur['nb_avis'] ?> avis)</span>
                    <?php endif; ?>
                    <?php if (!Vendeur::estOuvertMaintenant($vendeur)): ?>
                        <span class="etiquette indisponible">Boutique fermée actuellement</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <span class="surtitre">Sélectionnez une boutique</span>
                <h1>Catalogue Général GazExpress</h1>
                <p>Commandez vos bouteilles SODIGAZ, TOTAL et ORYX de 6kg et 12.5kg au meilleur prix à Ouagadougou.</p>
            <?php endif; ?>
        </div>
        <form method="get" action="<?= url('catalogue') ?>" class="selecteur-vendeur">
            <label class="sr-only" for="choix-vendeur">Changer de vendeur</label>
            <select name="vendeur" id="choix-vendeur" onchange="this.form.submit()">
                <option value="">Tous les vendeurs (meilleur prix)</option>
                <?php foreach ($vendeurs as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $vendeur && $vendeur['id'] == $v['id'] ? 'selected' : '' ?>><?= e($v['nom_commerce']) ?></option>
                <?php endforeach; ?>
            </select>
            <a href="<?= url('#vendeurs') ?>" class="btn btn-secondaire"><i class="fa-solid fa-arrow-left"></i> Changer de vendeur</a>
        </form>
    </section>

    <div class="puces sombres section" id="puces-marques" data-filtre-groupe=".produit">
        <button type="button" class="puce actif" data-valeur="">Toutes les marques</button>
        <?php foreach ($marques as $m): ?>
            <button type="button" class="puce" data-valeur="<?= e($m) ?>"><?= e($m === 'ORYX' ? 'ORYX Gaz' : $m) ?></button>
        <?php endforeach; ?>
    </div>

    <?php if (!$produits): ?>
        <div class="carte vide section"><i class="fa-solid fa-box-open"></i>Cette boutique n'a pas encore renseigné son stock.</div>
    <?php endif; ?>

    <div class="grille-produits">
        <?php foreach ($produits as $p):
            $quantite = (int) $p['quantite'];
            $etat     = etat_stock($quantite);
            $prix     = $vendeur ? $p['prix'] : ($p['meilleur_prix'] ?? $p['prix_reference']);
            $produitId = $vendeur ? $p['produit_id'] : $p['id'];
        ?>
            <article class="produit <?= $quantite <= 0 ? 'rupture' : '' ?>" data-filtre="<?= e($p['marque']) ?>">
                <div class="produit-visuel">
                    <span class="etiquette <?= $etat['cle'] ?>"><?= $etat['libelle'] ?></span>
                    <span class="tag-marque"><?= e($p['marque']) ?></span>
                    <span class="rond-produit <?= $p['capacite_kg'] > 10 ? 'grand' : '' ?>"><i class="fa-solid fa-gas-pump"></i></span>
                </div>
                <div class="produit-corps">
                    <h3><?= e($p['designation']) ?></h3>
                    <p class="desc"><?= e($p['description']) ?></p>
                    <?php if (!$vendeur): ?>
                        <p class="chez"><i class="fa-solid fa-store"></i>
                            <?= (int) $p['nb_vendeurs'] ?> vendeur<?= $p['nb_vendeurs'] > 1 ? 's' : '' ?> en stock<?= $p['meilleur_prix'] && $p['meilleur_prix'] < $p['prix_reference'] ? ' · dès ' . fcfa($p['meilleur_prix']) : '' ?>
                        </p>
                    <?php elseif ($quantite > 0 && $quantite < SEUIL_STOCK_LIMITE): ?>
                        <p class="chez" style="color:var(--orange-fonce)"><i class="fa-solid fa-triangle-exclamation"></i> Plus que <?= $quantite ?> bouteille<?= $quantite > 1 ? 's' : '' ?></p>
                    <?php endif; ?>
                    <div class="produit-pied">
                        <div>
                            <small><?= $vendeur ? 'Prix boutique' : 'Prix Officiel' ?></small>
                            <span class="prix"><?= fcfa($vendeur ? $prix : $p['prix_reference']) ?></span>
                        </div>
                        <form method="post" action="<?= url('panier/ajouter') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="produit_id" value="<?= (int) $produitId ?>">
                            <?php if ($vendeur): ?><input type="hidden" name="vendeur_id" value="<?= (int) $vendeur['id'] ?>"><?php endif; ?>
                            <button class="btn btn-primaire" <?= $quantite <= 0 || ($vendeur && !$vendeur['est_ouvert']) ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($avis): ?>
        <section class="carte section">
            <div class="carte-titre"><h2><i class="fa-solid fa-star"></i> Avis des clients</h2></div>
            <div class="grille-2" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                <?php foreach ($avis as $a): ?>
                    <blockquote style="margin:0;padding:18px;border:1px solid var(--gris-100);border-radius:16px">
                        <span class="note-fixe"><?= str_repeat('★', (int) $a['note']) ?><span style="color:var(--gris-300)"><?= str_repeat('★', 5 - (int) $a['note']) ?></span></span>
                        <p style="margin:8px 0"><?= e($a['commentaire'] ?? '') ?></p>
                        <small class="texte-doux"><?= e($a['prenom'] . ' ' . mb_substr($a['nom'], 0, 1)) ?>. · <?= date_fr($a['date_avis'], false) ?></small>
                    </blockquote>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
