<?php
/* Tiroir latéral du panier (présent sur toutes les pages). */
$panier    = Panier::contenu();
$quartiers = $panier['articles'] ? (new Quartier())->tous() : [];
$client    = Auth::est('client') ? (new Utilisateur())->trouver(Auth::id()) : null;
$nbBouteilles = array_sum(array_column($panier['articles'], 'quantite'));
?>
<div class="voile" id="voile-panier" data-fermer-panier></div>
<aside class="tiroir" id="tiroir-panier" aria-label="Panier" aria-hidden="true">
    <div class="tiroir-entete">
        <h2>Panier<?= $nbBouteilles ? ' · ' . $nbBouteilles . ' bouteille' . ($nbBouteilles > 1 ? 's' : '') : '' ?></h2>
        <button type="button" class="fermer" data-fermer-panier aria-label="Fermer le panier"><?= icone('fermer') ?></button>
    </div>

    <?php if (!$panier['articles']): ?>
        <div class="panier-vide">
            <img src="<?= asset('img/produits/generique.svg') ?>" alt="">
            <strong>Votre panier est vide</strong>
            <p>Choisissez un dépôt proche de chez vous, puis ajoutez vos bouteilles.</p>
            <a href="<?= url('#vendeurs') ?>" class="btn btn-primaire">Trouver un dépôt</a>
        </div>
    <?php else: ?>
        <div class="tiroir-corps">
            <div class="tiroir-vendeur">
                <span>Dépôt : <strong><?= e($panier['vendeur']['nom_commerce']) ?></strong></span>
                <form method="post" action="<?= url('panier/vider') ?>"><?= csrf_field() ?><button class="lien-discret">Vider le panier</button></form>
            </div>
            <?php foreach ($panier['articles'] as $a): ?>
                <div class="article">
                    <img src="<?= visuel_produit($a['marque'], $a['capacite_kg']) ?>" alt="">
                    <div class="infos">
                        <strong><?= e($a['designation']) ?></strong>
                        <span><?= fcfa($a['prix']) ?> l'unité</span>
                    </div>
                    <form method="post" action="<?= url('panier/modifier') ?>" class="quantite">
                        <?= csrf_field() ?>
                        <input type="hidden" name="produit_id" value="<?= $a['produit_id'] ?>">
                        <button name="quantite" value="<?= $a['quantite'] - 1 ?>" aria-label="Retirer une bouteille"><?= icone('moins') ?></button>
                        <output><?= $a['quantite'] ?></output>
                        <button name="quantite" value="<?= $a['quantite'] + 1 ?>" aria-label="Ajouter une bouteille" <?= $a['quantite'] >= $a['stock'] ? 'disabled' : '' ?>><?= icone('plus') ?></button>
                    </form>
                </div>
            <?php endforeach; ?>

            <form method="post" action="<?= url('panier/valider') ?>" class="tiroir-form" id="form-commande" data-sous-total="<?= $panier['sous_total'] ?>">
                <?= csrf_field() ?>
                <span class="libelle premier">Récupération</span>
                <div class="choix-mode">
                    <input type="radio" name="mode" id="mode-livraison" value="livraison" checked>
                    <label for="mode-livraison"><?= icone('moto') ?> Livraison <small>+<span data-frais-libelle>500</span> FCFA</small></label>
                    <input type="radio" name="mode" id="mode-retrait" value="retrait">
                    <label for="mode-retrait"><?= icone('boutique') ?> Retrait au dépôt <small>Gratuit</small></label>
                </div>

                <div data-bloc-livraison>
                    <label class="libelle" for="quartier">Quartier</label>
                    <select name="quartier_id" id="quartier" class="champ">
                        <?php foreach ($quartiers as $q): ?>
                            <option value="<?= $q['id'] ?>" data-frais="<?= $q['frais_livraison'] ?>" <?= ($client['quartier_id'] ?? 1) == $q['id'] ? 'selected' : '' ?>>
                                <?= e($q['nom']) ?><?= $q['frais_livraison'] != 500 ? ' (' . $q['frais_livraison'] . ' F)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="libelle" for="adresse">Adresse ou repère</label>
                    <input type="text" name="adresse" id="adresse" class="champ" maxlength="255" placeholder="Ex. Secteur 53, près du Monument des Martyrs" value="<?= e($client['adresse'] ?? '') ?>">
                </div>

                <label class="libelle" for="paiement">Paiement</label>
                <select name="paiement" id="paiement" class="champ">
                    <?php foreach (modes_paiement() as $cle => $lib): ?>
                        <option value="<?= $cle ?>"><?= e($lib) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="totaux">
                    <div><span>Bouteilles</span><span><?= fcfa($panier['sous_total']) ?></span></div>
                    <div><span>Livraison</span><span data-frais>500 FCFA</span></div>
                    <div class="total"><span>Total</span><span data-total><?= fcfa($panier['sous_total'] + 500) ?></span></div>
                </div>
                <button type="submit" class="btn btn-primaire btn-grand btn-bloc">Valider la commande</button>
                <?php if (!Auth::check()): ?>
                    <p class="aide" style="text-align:center;margin-top:10px">Connexion demandée à l'étape suivante. Votre panier est conservé.</p>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</aside>
