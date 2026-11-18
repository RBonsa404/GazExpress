<?php
class AdminController extends Controller
{
    private const ONGLETS = ['tableau', 'validations', 'utilisateurs', 'commandes', 'reclamations'];

    public function index(): void
    {
        Auth::exiger('admin');
        $onglet = in_array($_GET['onglet'] ?? '', self::ONGLETS, true) ? $_GET['onglet'] : 'tableau';

        $commandes = new Commande();
        $vendeurs  = new Vendeur();
        $livreurs  = new Livreur();
        $reclam    = new Reclamation();

        $vendeursAttente = $vendeurs->enAttente();
        $livreursAttente = $livreurs->enAttente();

        $donnees = [
            'titre'          => 'Administration',
            'page'           => 'admin',
            'onglet'         => $onglet,
            'stats'          => $commandes->statistiquesGlobales(),
            'roles'          => (new Utilisateur())->compterParRole(),
            'nbValidations'  => count($vendeursAttente) + count($livreursAttente),
            'nbReclamations' => $reclam->ouvertes(),
        ];

        switch ($onglet) {
            case 'validations':
                $donnees['vendeursAttente'] = $vendeursAttente;
                $donnees['livreursAttente'] = $livreursAttente;
                break;
            case 'utilisateurs':
                $donnees['filtreRole'] = in_array($_GET['role'] ?? '', ['client', 'vendeur', 'livreur', 'admin'], true) ? $_GET['role'] : '';
                $donnees['recherche']  = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
                $donnees['utilisateurs'] = (new Utilisateur())->liste($donnees['filtreRole'], $donnees['recherche']);
                break;
            case 'commandes':
                $donnees['filtreStatut'] = array_key_exists($_GET['statut'] ?? '', statuts_commande()) ? $_GET['statut'] : '';
                $donnees['commandes'] = $commandes->toutes($donnees['filtreStatut']);
                break;
            case 'reclamations':
                $donnees['reclamations'] = $reclam->toutes();
                break;
            default:
                $donnees['parJour']   = $commandes->parJour(7);
                $donnees['vendeurs']  = $vendeurs->tous();
                $donnees['dernieres'] = $commandes->toutes('', 6);
        }

        $this->render('admin/index', $donnees);
    }

    public function validation(): void
    {
        Auth::exiger('admin');
        $type     = $_POST['type'] ?? '';
        $id       = $this->entier('id');
        $decision = ($_POST['decision'] ?? '') === 'valide' ? 'valide' : 'refuse';
        $notif    = new Notification();

        if ($type === 'vendeur' && ($v = (new Vendeur())->trouver($id))) {
            (new Vendeur())->valider($id, $decision);
            $notif->envoyer((int) $v['utilisateur_id'], $decision === 'valide'
                ? 'Votre boutique « ' . $v['nom_commerce'] . ' » est validée. Renseignez vos stocks pour apparaître sur la carte.'
                : 'Votre demande de boutique a été refusée. Contactez l\'administration pour plus d\'informations.', 'vendeur');
            flash('succes', 'Vendeur « ' . $v['nom_commerce'] . ' » ' . ($decision === 'valide' ? 'validé.' : 'refusé.'));
        } elseif ($type === 'livreur' && ($l = (new Livreur())->trouver($id))) {
            (new Livreur())->valider($id, $decision);
            $notif->envoyer((int) $l['utilisateur_id'], $decision === 'valide'
                ? 'Votre compte livreur est validé. Passez « En service » pour recevoir des courses.'
                : 'Votre demande de compte livreur a été refusée.', 'livreur');
            flash('succes', 'Livreur ' . $l['prenom'] . ' ' . $l['nom'] . ' ' . ($decision === 'valide' ? 'validé.' : 'refusé.'));
        } else {
            flash('erreur', 'Demande introuvable.');
        }
        redirect('admin?onglet=validations');
    }

    public function statutUtilisateur(): void
    {
        $admin  = Auth::exiger('admin');
        $id     = $this->entier('id');
        $statut = ($_POST['statut'] ?? '') === 'suspendu' ? 'suspendu' : 'actif';

        if ($id === (int) $admin['id']) {
            flash('erreur', 'Vous ne pouvez pas suspendre votre propre compte.');
        } else {
            (new Utilisateur())->changerStatut($id, $statut);
            flash('succes', $statut === 'suspendu' ? 'Compte suspendu.' : 'Compte réactivé.');
        }
        redirect_back('admin?onglet=utilisateurs');
    }

    public function traiterReclamation(): void
    {
        Auth::exiger('admin');
        $r = (new Reclamation())->trouver($this->entier('id'));
        $reponse = $this->texte('reponse', 2000);
        if (!$r || $reponse === '') {
            flash('erreur', 'Saisissez une réponse.');
        } else {
            (new Reclamation())->traiter((int) $r['id'], $reponse);
            (new Notification())->envoyer((int) $r['client_id'], 'Réponse à votre réclamation « ' . $r['objet'] . ' » : ' . mb_substr($reponse, 0, 150), 'commandes/suivi?id=' . $r['commande_id'], (int) $r['commande_id']);
            flash('succes', 'Réclamation traitée, le client a été notifié.');
        }
        redirect('admin?onglet=reclamations');
    }

    public function annulerCommande(): void
    {
        Auth::exiger('admin');
        try {
            (new Commande())->annuler($this->entier('commande_id'), Auth::id(), 'Annulée par l\'administration GazExpress');
            flash('succes', 'Commande annulée et stock restitué.');
        } catch (RuntimeException $e) {
            flash('erreur', $e->getMessage());
        }
        redirect_back('admin?onglet=commandes');
    }
}
