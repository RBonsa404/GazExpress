<div class="conteneur">
    <div class="page-auth">
        <div class="auth-cote">
            <h2>Un compte pour tout suivre</h2>
            <p>Clients, dépôts et livreurs utilisent le même accès.</p>
            <ul>
                <li><?= icone('recu') ?><span><b>Clients</b>Commandes, suivi de livraison et avis.</span></li>
                <li><?= icone('boutique') ?><span><b>Dépôts</b>Stock, prix et commandes entrantes.</span></li>
                <li><?= icone('moto') ?><span><b>Livreurs</b>Courses proches et gains du jour.</span></li>
            </ul>
        </div>
        <div class="auth-form">
            <h1>Connexion</h1>
            <p>Pas encore de compte ? <a href="<?= url('inscription') ?>" class="lien">Créer un compte</a></p>
            <form method="post" action="<?= url('connexion') ?>" id="form-connexion">
                <?= csrf_field() ?>
                <div class="groupe">
                    <label for="identifiant">E-mail ou téléphone</label>
                    <input type="text" id="identifiant" name="identifiant" class="champ" required autocomplete="username" value="<?= old('identifiant') ?>" placeholder="exemple@gmail.com ou +226 70 00 00 00">
                </div>
                <div class="groupe">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="champ" required autocomplete="current-password">
                </div>
                <button class="btn btn-primaire btn-bloc btn-grand">Se connecter</button>
            </form>

            <?php if (APP_DEMO): ?>
            <div class="comptes-demo">
                <b>Comptes de démonstration</b> · mot de passe <code>Gaz@2026</code>
                <div class="puces">
                    <button type="button" class="puce" data-demo="awa.ouedraogo@gmail.com">Client</button>
                    <button type="button" class="puce" data-demo="sodigaz.ouaga2000@gazexpress.bf">Vendeur</button>
                    <button type="button" class="puce" data-demo="moussa.livreur@gazexpress.bf">Livreur</button>
                    <button type="button" class="puce" data-demo="admin@gazexpress.bf">Administrateur</button>
                </div>
            </div>
            <?php endif; ?>
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
