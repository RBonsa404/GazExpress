<div class="conteneur">
    <div class="page-auth">
        <div class="auth-visuel">
            <div>
                <span class="surtitre sombre">Bienvenue</span>
                <h2>Votre gaz, sans faire le tour des dépôts.</h2>
                <p>Un seul compte pour commander, suivre vos livraisons ou gérer votre activité de vendeur et de livreur.</p>
                <ul>
                    <li><i class="fa-solid fa-map-location-dot"></i> Vendeurs proches sur la carte</li>
                    <li><i class="fa-solid fa-boxes-stacked"></i> Stocks et prix vérifiés</li>
                    <li><i class="fa-solid fa-motorcycle"></i> Livraison suivie en direct</li>
                </ul>
            </div>
        </div>
        <div class="auth-form">
            <h1>Connexion</h1>
            <p>Pas encore de compte ? <a href="<?= url('inscription') ?>" style="color:var(--primaire);font-weight:700">Inscrivez-vous gratuitement</a></p>
            <form method="post" action="<?= url('connexion') ?>" id="form-connexion">
                <?= csrf_field() ?>
                <div class="groupe">
                    <label for="identifiant">E-mail ou téléphone</label>
                    <input type="text" id="identifiant" name="identifiant" class="champ" required autocomplete="username" value="<?= old('identifiant') ?>" placeholder="exemple@gmail.com ou +22670000000">
                </div>
                <div class="groupe">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="champ" required autocomplete="current-password">
                </div>
                <button class="btn btn-primaire btn-bloc btn-grand" style="font-size:17px;margin-top:6px"><i class="fa-solid fa-right-to-bracket"></i> Se connecter</button>
            </form>

            <div class="comptes-demo">
                <b>Comptes de démonstration</b> (mot de passe <b>Gaz@2026</b>) :<br>
                <button type="button" data-demo="awa.ouedraogo@gmail.com">Client</button> ·
                <button type="button" data-demo="sodigaz.ouaga2000@gazexpress.bf">Vendeur</button> ·
                <button type="button" data-demo="moussa.livreur@gazexpress.bf">Livreur</button> ·
                <button type="button" data-demo="admin@gazexpress.bf">Administrateur</button>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-demo]').forEach(b => b.addEventListener('click', () => {
    document.getElementById('identifiant').value = b.dataset.demo;
    document.getElementById('mot_de_passe').value = 'Gaz@2026';
    document.getElementById('form-connexion').submit();
}));
</script>
