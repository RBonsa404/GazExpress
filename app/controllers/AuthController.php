<?php
class AuthController extends Controller
{
    private const MAX_TENTATIVES = 5;

    public function connexion(): void
    {
        if (Auth::check()) {
            redirect($this->accueilDuRole(Auth::role()));
        }
        $this->render('auth/connexion', ['titre' => 'Connexion', 'page' => 'connexion']);
        unset($_SESSION['old']);
    }

    public function seConnecter(): void
    {
        $identifiant = trim((string) ($_POST['identifiant'] ?? ''));
        $motDePasse  = (string) ($_POST['mot_de_passe'] ?? '');
        $_SESSION['old'] = ['identifiant' => $identifiant];

        // Limitation simple des tentatives par session (anti force brute)
        $t = $_SESSION['tentatives'] ?? ['n' => 0, 'depuis' => time()];
        if (time() - $t['depuis'] > 600) {
            $t = ['n' => 0, 'depuis' => time()];
        }
        if ($t['n'] >= self::MAX_TENTATIVES) {
            flash('erreur', 'Trop de tentatives. Réessayez dans quelques minutes.');
            redirect('connexion');
        }

        $modele = new Utilisateur();
        $u = $identifiant !== '' ? $modele->parIdentifiant($identifiant) : null;

        if (!$u || !password_verify($motDePasse, $u['mot_de_passe'])) {
            $t['n']++;
            $_SESSION['tentatives'] = $t;
            flash('erreur', 'Identifiant ou mot de passe incorrect.');
            redirect('connexion');
        }
        if ($u['statut'] !== 'actif') {
            flash('erreur', 'Ce compte est suspendu. Contactez l\'administration GazExpress.');
            redirect('connexion');
        }

        unset($_SESSION['tentatives'], $_SESSION['old']);
        $panier = $_SESSION['panier'] ?? null;
        $apres  = $_SESSION['apres_connexion'] ?? null;

        Auth::connecter($u);
        $modele->enregistrerConnexion((int) $u['id']);
        $modele->majMotDePasseSiNecessaire((int) $u['id'], $motDePasse, $u['mot_de_passe']);

        if ($panier) {
            $_SESSION['panier'] = $panier;
        }
        unset($_SESSION['apres_connexion']);

        flash('succes', 'Bienvenue ' . $u['prenom'] . ' !');
        if ($apres && $u['role'] === 'client' && str_starts_with($apres, BASE_URL . '/')) {
            redirect($apres);
        }
        redirect($this->accueilDuRole($u['role']));
    }

    public function inscription(): void
    {
        if (Auth::check()) {
            redirect($this->accueilDuRole(Auth::role()));
        }
        $role = $_GET['role'] ?? 'client';
        $this->render('auth/inscription', [
            'titre'     => 'Créer un compte',
            'page'      => 'inscription',
            'role'      => in_array($role, ['client', 'vendeur', 'livreur'], true) ? $role : 'client',
            'quartiers' => (new Quartier())->tous(),
        ]);
        unset($_SESSION['old']);
    }

    public function sInscrire(): void
    {
        $d = [
            'role'        => $_POST['role'] ?? 'client',
            'nom'         => $this->texte('nom', 60),
            'prenom'      => $this->texte('prenom', 60),
            'telephone'   => preg_replace('/[^\d+]/', '', $this->texte('telephone', 20)),
            'email'       => mb_strtolower($this->texte('email', 120)),
            'mot_de_passe'=> (string) ($_POST['mot_de_passe'] ?? ''),
            'confirmation'=> (string) ($_POST['confirmation'] ?? ''),
            'quartier_id' => $this->entier('quartier_id'),
            'adresse'     => $this->texte('adresse', 255),
        ];
        $_SESSION['old'] = array_diff_key($_POST, ['mot_de_passe' => 1, 'confirmation' => 1, '_csrf' => 1]);

        $erreurs = [];
        if (!in_array($d['role'], ['client', 'vendeur', 'livreur'], true)) {
            $erreurs[] = 'Type de compte invalide.';
        }
        if ($d['nom'] === '' || $d['prenom'] === '') {
            $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Adresse e-mail invalide.';
        }
        if (!preg_match('/^(\+226)?\d{8}$/', $d['telephone'])) {
            $erreurs[] = 'Numéro de téléphone burkinabè invalide (8 chiffres, avec ou sans +226).';
        }
        if (strlen($d['mot_de_passe']) < 8 || !preg_match('/\d/', $d['mot_de_passe'])) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères dont un chiffre.';
        }
        if ($d['mot_de_passe'] !== $d['confirmation']) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (!str_starts_with($d['telephone'], '+226')) {
            $d['telephone'] = '+226' . $d['telephone'];
        }

        $quartier = $d['quartier_id'] ? (new Quartier())->trouver($d['quartier_id']) : null;
        if (!$quartier) {
            $erreurs[] = 'Sélectionnez votre quartier.';
        }

        // Champs propres aux profils professionnels
        if ($d['role'] === 'vendeur') {
            $d['nom_commerce']        = $this->texte('nom_commerce', 120);
            $d['adresse_commerce']    = $this->texte('adresse_commerce', 255);
            $d['numero_autorisation'] = $this->texte('numero_autorisation', 60);
            $d['heure_ouverture']     = preg_match('/^\d{2}:\d{2}$/', $_POST['heure_ouverture'] ?? '') ? $_POST['heure_ouverture'] : '07:00';
            $d['heure_fermeture']     = preg_match('/^\d{2}:\d{2}$/', $_POST['heure_fermeture'] ?? '') ? $_POST['heure_fermeture'] : '20:00';
            $lat = filter_var($_POST['latitude'] ?? '', FILTER_VALIDATE_FLOAT);
            $lon = filter_var($_POST['longitude'] ?? '', FILTER_VALIDATE_FLOAT);
            $d['latitude']  = ($lat !== false && $lat > 12.2 && $lat < 12.5) ? $lat : ($quartier['latitude'] ?? null);
            $d['longitude'] = ($lon !== false && $lon > -1.7 && $lon < -1.4) ? $lon : ($quartier['longitude'] ?? null);
            $d['telephone_commerce'] = $d['telephone'];
            if ($d['nom_commerce'] === '' || $d['adresse_commerce'] === '' || $d['numero_autorisation'] === '') {
                $erreurs[] = 'Nom du commerce, adresse et numéro d\'autorisation sont obligatoires pour un vendeur.';
            }
        } elseif ($d['role'] === 'livreur') {
            $d['type_engin']      = in_array($_POST['type_engin'] ?? '', ['moto', 'tricycle', 'velo'], true) ? $_POST['type_engin'] : 'moto';
            $d['immatriculation'] = $this->texte('immatriculation', 30);
            $d['numero_piece']    = $this->texte('numero_piece', 40);
            if ($d['numero_piece'] === '') {
                $erreurs[] = 'Le numéro de pièce d\'identité est obligatoire pour un livreur.';
            }
        }

        $modele = new Utilisateur();
        if (!$erreurs && $modele->existe($d['email'], $d['telephone'])) {
            $erreurs[] = 'Un compte existe déjà avec cet e-mail ou ce numéro de téléphone.';
        }
        if ($erreurs) {
            foreach ($erreurs as $err) {
                flash('erreur', $err);
            }
            redirect('inscription?role=' . urlencode($d['role']));
        }

        $db = Database::get();
        $db->beginTransaction();
        try {
            $id = $modele->creer($d);
            if ($d['role'] === 'vendeur') {
                $d['quartier_id'] = $quartier['id'];
                (new Vendeur())->creer($id, $d);
            } elseif ($d['role'] === 'livreur') {
                (new Livreur())->creer($id, $d);
            }
            if ($d['role'] !== 'client') {
                $libelle = $d['role'] === 'vendeur' ? 'vendeur : ' . $d['nom_commerce'] : 'livreur : ' . $d['prenom'] . ' ' . mb_strtoupper($d['nom']);
                foreach ($modele->admins() as $a) {
                    (new Notification())->envoyer((int) $a['id'], 'Nouveau ' . $libelle . ' à valider.', 'admin?onglet=validations');
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        unset($_SESSION['old']);
        $panier = $_SESSION['panier'] ?? null;
        Auth::connecter($modele->trouver($id));
        if ($panier) {
            $_SESSION['panier'] = $panier;
        }

        if ($d['role'] === 'client') {
            flash('succes', 'Compte créé. Bienvenue sur GazExpress !');
            $apres = $_SESSION['apres_connexion'] ?? '';
            unset($_SESSION['apres_connexion']);
            redirect($apres && str_starts_with($apres, BASE_URL . '/') ? $apres : '');
        }
        flash('succes', 'Compte créé. Votre dossier est transmis à l\'administrateur pour validation.');
        redirect($d['role']);
    }

    public function deconnexion(): void
    {
        Auth::deconnecter();
        session_start();
        flash('info', 'Vous êtes déconnecté. À bientôt !');
        redirect('');
    }

    private function accueilDuRole(?string $role): string
    {
        return ['vendeur' => 'vendeur', 'livreur' => 'livreur', 'admin' => 'admin'][$role] ?? '';
    }
}
