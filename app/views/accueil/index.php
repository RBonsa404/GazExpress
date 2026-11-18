<?php
/** @var array $vendeurs @var array $quartiers @var array $carte */
$scripts = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', asset('js/carte.js')];
?>
<div class="conteneur">

    <section class="heros">
        <span class="surtitre sombre"><i class="fa-solid fa-bolt"></i> Service 24/7 à Ouagadougou</span>
        <h1>Commandez votre gaz domestique en <em>un clic</em></h1>
        <p>Trouvez les boutiques et dépôts de gaz (SODIGAZ, TOTAL, ORYX) les plus proches de chez vous avec vérification des stocks en temps réel.</p>
        <div class="heros-actions">
            <a href="<?= url('catalogue') ?>" class="btn btn-primaire btn-grand"><i class="fa-solid fa-gas-pump"></i> Voir le catalogue</a>
            <a href="#vendeurs" class="btn btn-verre btn-grand" id="btn-localiser"><i class="fa-solid fa-location-dot"></i> Explorer les vendeurs</a>
        </div>
    </section>

    <section class="carte barre-filtres section" id="vendeurs" style="padding:20px">
        <label class="champ-recherche">
            <span class="sr-only">Rechercher un quartier ou un vendeur</span>
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="recherche-vendeur" placeholder="Rechercher par quartier (Ouaga 2000, 1200...)" autocomplete="off">
        </label>
        <div class="puces" id="puces-quartiers">
            <button type="button" class="puce actif" data-quartier="">Tous les quartiers</button>
            <?php foreach (array_slice($quartiers, 0, 6) as $q): ?>
                <button type="button" class="puce" data-quartier="<?= e($q['nom']) ?>"><?= e($q['nom']) ?></button>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="grille-carte">
        <section class="carte">
            <div class="carte-titre" style="margin-bottom:18px">
                <h2><i class="fa-solid fa-map"></i> Carte Interactive - Ouagadougou</h2>
                <span class="aide">Cliquez sur un marqueur</span>
            </div>
            <div id="carte" data-vendeurs='<?= e(json_encode($carte, JSON_UNESCAPED_UNICODE)) ?>'></div>
        </section>

        <section class="carte">
            <div class="carte-titre" style="margin-bottom:18px">
                <h2 style="font-size:21px">Vendeurs Partenaires</h2>
                <span class="compteur"><span id="nb-vendeurs"><?= count($vendeurs) ?></span> Actifs</span>
            </div>
            <div class="liste-vendeurs" id="liste-vendeurs">
                <?php foreach ($vendeurs as $v): $etat = Vendeur::etatGlobal($v); $ouvert = Vendeur::estOuvertMaintenant($v); ?>
                    <article class="carte-vendeur" data-id="<?= $v['id'] ?>" data-quartier="<?= e($v['quartier']) ?>"
                             data-recherche="<?= e(mb_strtolower($v['nom_commerce'] . ' ' . $v['quartier'] . ' ' . $v['adresse'])) ?>">
                        <div class="haut">
                            <span class="tag-quartier"><?= e($v['quartier']) ?></span>
                            <span class="etiquette avec-point <?= $etat['cle'] ?>"><?= $etat['libelle'] ?></span>
                        </div>
                        <h3><?= e($v['nom_commerce']) ?></h3>
                        <div class="ligne"><i class="fa-solid fa-location-dot"></i> <?= e($v['adresse']) ?></div>
                        <?php if ($v['note_moyenne']): ?>
                            <div class="ligne"><i class="fa-solid fa-star" style="color:var(--orange)"></i> <?= e($v['note_moyenne']) ?>/5 · <?= (int) $v['nb_avis'] ?> avis</div>
                        <?php endif; ?>
                        <div class="bas">
                            <div class="ligne" style="margin:0">
                                <i class="fa-regular fa-clock"></i> <?= heure($v['heure_ouverture']) ?> - <?= heure($v['heure_fermeture']) ?>
                                <?php if (!$ouvert): ?><span class="ferme">· Fermé</span><?php endif; ?>
                            </div>
                            <a href="<?= url('catalogue?vendeur=' . $v['id']) ?>" class="lien-stock">Voir le stock <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div class="vide" id="aucun-vendeur" style="display:none"><i class="fa-solid fa-store-slash"></i>Aucun vendeur ne correspond à votre recherche.</div>
            </div>
        </section>
    </div>

    <section class="carte section">
        <div class="etapes">
            <div class="etape">
                <span class="num"><i class="fa-solid fa-location-crosshairs"></i></span>
                <div><h3>1. Localisez</h3><p>Autorisez la géolocalisation : la carte affiche les dépôts proches avec l'état réel de leur stock.</p></div>
            </div>
            <div class="etape">
                <span class="num"><i class="fa-solid fa-scale-balanced"></i></span>
                <div><h3>2. Comparez &amp; commandez</h3><p>Prix, marques et capacités (6 kg, 12,5 kg) : choisissez la livraison ou le retrait sur place.</p></div>
            </div>
            <div class="etape">
                <span class="num"><i class="fa-solid fa-route"></i></span>
                <div><h3>3. Suivez en direct</h3><p>Le vendeur confirme son stock, un livreur vérifié prend la course et vous suivez chaque étape.</p></div>
            </div>
        </div>
    </section>
</div>
