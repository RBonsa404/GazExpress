<?php /** @var string $role @var array $quartiers */ ?>
<div class="conteneur">
    <div class="page-auth large">
        <div class="auth-cote">
            <h2>Créer un compte</h2>
            <p>Les comptes dépôt et livreur sont vérifiés par notre équipe avant activation : le gaz est un produit inflammable.</p>
            <ul>
                <li><?= icone('utilisateur') ?><span><b>Client</b>Compte actif immédiatement.</span></li>
                <li><?= icone('boutique') ?><span><b>Dépôt</b>Autorisation d'exploitation demandée.</span></li>
                <li><?= icone('carte-id') ?><span><b>Livreur</b>Pièce d'identité (CNIB) et engin.</span></li>
            </ul>
        </div>
        <div class="auth-form">
            <h1>Inscription</h1>
            <p>Déjà inscrit ? <a href="<?= url('connexion') ?>" class="lien">Se connecter</a></p>

            <form method="post" action="<?= url('inscription') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="choix-role">
                    <?php foreach (['client' => ['utilisateur', 'Client'], 'vendeur' => ['boutique', 'Dépôt'], 'livreur' => ['moto', 'Livreur']] as $r => [$ico, $lib]): ?>
                        <input type="radio" name="role" id="role-<?= $r ?>" value="<?= $r ?>" <?= $role === $r ? 'checked' : '' ?>>
                        <label for="role-<?= $r ?>"><?= icone($ico) ?><?= $lib ?></label>
                    <?php endforeach; ?>
                </div>

                <div class="ligne-2">
                    <div class="groupe"><label for="prenom">Prénom</label><input id="prenom" name="prenom" class="champ" required maxlength="60" value="<?= old('prenom') ?>"></div>
                    <div class="groupe"><label for="nom">Nom</label><input id="nom" name="nom" class="champ" required maxlength="60" value="<?= old('nom') ?>"></div>
                </div>
                <div class="ligne-2">
                    <div class="groupe"><label for="telephone">Téléphone</label><input id="telephone" name="telephone" class="champ" required placeholder="+226 70 00 00 00" value="<?= old('telephone') ?>"></div>
                    <div class="groupe"><label for="email">E-mail</label><input type="email" id="email" name="email" class="champ" required value="<?= old('email') ?>"></div>
                </div>
                <div class="ligne-2">
                    <div class="groupe"><label for="quartier_id">Quartier</label>
                        <select id="quartier_id" name="quartier_id" class="champ" required>
                            <option value="">Choisir…</option>
                            <?php foreach ($quartiers as $q): ?><option value="<?= $q['id'] ?>" <?= old('quartier_id') == $q['id'] ? 'selected' : '' ?>><?= e($q['nom']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="groupe"><label for="adresse">Adresse / repère</label><input id="adresse" name="adresse" class="champ" maxlength="255" value="<?= old('adresse') ?>" placeholder="Secteur, rue, repère"></div>
                </div>

                <div class="bloc-pro" data-pour="vendeur">
                    <h3><?= icone('boutique') ?> Votre dépôt</h3>
                    <div class="groupe"><label for="nom_commerce">Nom du dépôt</label><input id="nom_commerce" name="nom_commerce" class="champ" maxlength="120" value="<?= old('nom_commerce') ?>"></div>
                    <div class="groupe"><label for="adresse_commerce">Adresse du point de vente</label><input id="adresse_commerce" name="adresse_commerce" class="champ" maxlength="255" value="<?= old('adresse_commerce') ?>"></div>
                    <div class="ligne-2">
                        <div class="groupe"><label for="heure_ouverture">Ouverture</label><input type="time" id="heure_ouverture" name="heure_ouverture" class="champ" value="<?= old('heure_ouverture', '07:00') ?>"></div>
                        <div class="groupe"><label for="heure_fermeture">Fermeture</label><input type="time" id="heure_fermeture" name="heure_fermeture" class="champ" value="<?= old('heure_fermeture', '20:00') ?>"></div>
                    </div>
                    <div class="groupe"><label for="numero_autorisation">N° d'autorisation d'exploitation</label><input id="numero_autorisation" name="numero_autorisation" class="champ" maxlength="60" value="<?= old('numero_autorisation') ?>" placeholder="AUT-MCIA-…"></div>
                    <input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude">
                    <div class="groupe"><button type="button" class="btn btn-blanc btn-petit" id="geoloc"><?= icone('cible') ?> Utiliser ma position actuelle</button>
                        <div class="aide" id="geoloc-etat">Sinon, le centre du quartier sera utilisé ; l'administrateur pourra ajuster.</div></div>
                </div>

                <div class="bloc-pro" data-pour="livreur">
                    <h3><?= icone('carte-id') ?> Vos informations de livreur</h3>
                    <div class="ligne-2">
                        <div class="groupe"><label for="type_engin">Engin</label>
                            <select id="type_engin" name="type_engin" class="champ"><option value="moto">Moto</option><option value="tricycle">Tricycle</option><option value="velo">Vélo</option></select></div>
                        <div class="groupe"><label for="immatriculation">Immatriculation</label><input id="immatriculation" name="immatriculation" class="champ" maxlength="30" value="<?= old('immatriculation') ?>"></div>
                    </div>
                    <div class="groupe"><label for="numero_piece">N° de pièce d'identité (CNIB)</label><input id="numero_piece" name="numero_piece" class="champ" maxlength="40" value="<?= old('numero_piece') ?>"></div>
                </div>

                <div class="ligne-2">
                    <div class="groupe"><label for="mot_de_passe">Mot de passe</label><input type="password" id="mot_de_passe" name="mot_de_passe" class="champ" required minlength="8" autocomplete="new-password">
                        <div class="aide">8 caractères minimum, dont un chiffre.</div></div>
                    <div class="groupe"><label for="confirmation">Confirmation</label><input type="password" id="confirmation" name="confirmation" class="champ" required autocomplete="new-password"></div>
                </div>
                <button class="btn btn-primaire btn-bloc btn-grand">Créer mon compte</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const maj = () => {
        const r = document.querySelector('input[name="role"]:checked').value;
        document.querySelectorAll('.bloc-pro').forEach(b => b.classList.toggle('visible', b.dataset.pour === r));
    };
    document.querySelectorAll('input[name="role"]').forEach(i => i.addEventListener('change', maj));
    maj();
    document.getElementById('geoloc').addEventListener('click', () => {
        const etat = document.getElementById('geoloc-etat');
        if (!navigator.geolocation) { etat.textContent = 'Géolocalisation non prise en charge.'; return; }
        etat.textContent = 'Localisation en cours…';
        navigator.geolocation.getCurrentPosition(p => {
            document.getElementById('latitude').value = p.coords.latitude.toFixed(7);
            document.getElementById('longitude').value = p.coords.longitude.toFixed(7);
            etat.textContent = 'Position enregistrée (' + p.coords.latitude.toFixed(4) + ', ' + p.coords.longitude.toFixed(4) + ').';
        }, () => { etat.textContent = 'Position refusée : le centre du quartier sera utilisé.'; });
    });
})();
</script>
