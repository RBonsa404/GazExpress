<?php
/* Tiroir latéral du panier (présent sur toutes les pages). */
$panier    = Panier::contenu();
$quartiers = $panier['articles'] ? (new Quartier())->tous() : [];
$client    = Auth::est('client') ? (new Utilisateur())->trouver(Auth::id()) : null;
?>
<div class="voile" id="voile-panier" data-fermer-panier></div>
<aside class="tiroir" id="tiroir-panier" aria-label="Panier" aria-hidden="true">
    <div class="tiroir-entete">
        <h2><i class="fa-solid fa-cart-shopping"></i> Votre Panier GazExpress</h2>
        <button type="button" class="fermer" data-fermer-panier aria-label="Fermer le panier"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <?php if (!$panier['articles']): ?>
        <div class="panier-vide">
            <i class="fa-solid fa-gas-pump"></i>
            <p><strong>Votre panier est vide.</strong></p>
            <p style="margin:6px 0 22px">Choisissez une boutique proche puis ajoutez vos bouteilles.</p>
            <a href="<?= url('catalogue') ?>" class="btn btn-primaire">Voir le catalogue</a>
        </div>
    <?php else: ?>
        <div class="tiroir-corps">
            <div class="tiroir-articles">
                <div class="tiroir-vendeur">
                    <span>Boutique : <strong><?= e($panier['vendeur']['nom_commerce']) ?></strong></span>
                    <form method="post" action="<?= url('panier/vider') ?>"><?= csrf_field() ?><button class="lien-discret">Vider</button></form>
                </div>
                <?php foreach ($panier['articles'] as $a): ?>
                    <div class="article">
                        <span class="ico"><i class="fa-solid fa-gas-pump"></i></span>
                        <div class="infos">
                            <strong><?= e($a['designation']) ?></strong>
                            <span><?= fcfa($a['prix']) ?></span>
                        </div>
                        <form method="post" action="<?= url('panier/modifier') ?>" class="quantite">
                            <?= csrf_field() ?>
                            <input type="hidden" name="produit_id" value="<?= $a['produit_id'] ?>">
                            <button name="quantite" value="<?= $a['quantite'] - 1 ?>" aria-label="Retirer une bouteille">-</button>
                            <output><?= $a['quantite'] ?></output>
                            <button name="quantite" value="<?= $a['quantite'] + 1 ?>" aria-label="Ajouter une bouteille" <?= $a['quantite'] >= $a['stock'] ? 'disabled' : '' ?>>+</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= url('panier/valider') ?>" class="tiroir-form" id="form-commande" data-sous-total="<?= $panier['sous_total'] ?>">
                <?= csrf_field() ?>
                <span class="libelle">Mode de récupération</span>
                <div class="choix-mode">
                    <input type="radio" name="mode" id="mode-livraison" value="livraison" checked>
                    <label for="mode-livraison"><i class="fa-solid fa-motorcycle"></i> <span>Livraison à domicile (+<span data-frais-libelle>500</span>F)</span></label>
                    <input type="radio" name="mode" id="mode-retrait" value="retrait">
                    <label for="mode-retrait"><i class="fa-solid fa-store"></i> <span>Retrait sur place (0F)</span></label>
                </div>

                <div data-bloc-livraison>
                    <label class="libelle" for="quartier">Quartier à Ouagadougou</label>
                    <select name="quartier_id" id="quartier" class="champ">
                        <?php foreach ($quartiers as $q): ?>
                            <option value="<?= $q['id'] ?>" data-frais="<?= $q['frais_livraison'] ?>" <?= ($client['quartier_id'] ?? 1) == $q['id'] ? 'selected' : '' ?>>
                                <?= e($q['nom']) ?><?= $q['frais_livraison'] != 500 ? ' (' . $q['frais_livraison'] . ' F)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="libelle" for="adresse">Adresse / repère</label>
                    <input type="text" name="adresse" id="adresse" class="champ" maxlength="255" placeholder="Ex. Secteur 53, près du Monument des Martyrs" value="<?= e($client['adresse'] ?? '') ?>">
                </div>

                <label class="libelle" for="paiement">Paiement</label>
                <select name="paiement" id="paiement" class="champ">
                    <?php foreach (modes_paiement() as $cle => $lib): ?>
                        <option value="<?= $cle ?>"><?= e($lib) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="totaux">
                    <div><span>Sous-total articles</span><span><?= fcfa($panier['sous_total']) ?></span></div>
                    <div><span>Frais de service / livraison</span><span data-frais>500 FCFA</span></div>
                    <div class="total"><span>Total à payer</span><span data-total><?= fcfa($panier['sous_total'] + 500) ?></span></div>
                </div>
                <button type="submit" class="btn btn-primaire btn-grand btn-bloc"><i class="fa-solid fa-circle-check"></i> Confirmer la commande</button>
                <?php if (!Auth::check()): ?>
                    <p class="texte-doux" style="font-size:13px;text-align:center;margin-top:12px">Vous serez invité à vous connecter ; votre panier est conservé.</p>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</aside>
