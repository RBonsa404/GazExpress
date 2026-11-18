<?php
class LivreurController extends Controller
{
    public function index(): void
    {
        if (!Auth::est('livreur')) {
            $this->render('espace/acces', ['titre' => 'Espace Livreur', 'page' => 'livreur', 'espace' => 'livreur']);
            return;
        }
        Auth::exiger('livreur');
        $livreur = (new Livreur())->parUtilisateur(Auth::id());

        if ($livreur['statut_validation'] !== 'valide') {
            $this->render('espace/en_attente', ['titre' => 'Espace Livreur', 'page' => 'livreur', 'espace' => 'livreur', 'profil' => $livreur]);
            return;
        }

        $livraisons = new Livraison();
        $enCours = $livraisons->enCours((int) $livreur['id']);
        $details = $enCours ? (new Commande())->details((int) $enCours['commande_id']) : [];

        $disponibles = $livraisons->disponibles(
            $livreur['q_lat'] !== null ? (float) $livreur['q_lat'] : null,
            $livreur['q_lon'] !== null ? (float) $livreur['q_lon'] : null
        );
        // Résumé des articles pour chaque course proposée
        $commandes = (new Commande())->avecDetails(array_map(fn ($c) => ['id' => $c['commande_id'], 'livraison_id' => $c['id']] + $c, $disponibles));

        $this->render('livreur/index', [
            'titre'       => 'Espace Livreur',
            'page'        => 'livreur',
            'livreur'     => $livreur,
            'stats'       => (new Livreur())->statistiques((int) $livreur['id']),
            'enCours'     => $enCours,
            'details'     => $details,
            'disponibles' => $commandes,
            'historique'  => $livraisons->historique((int) $livreur['id']),
        ]);
    }

    private function profil(): array
    {
        Auth::exiger('livreur');
        $l = (new Livreur())->parUtilisateur(Auth::id());
        if (!$l || $l['statut_validation'] !== 'valide') {
            flash('erreur', 'Votre compte livreur doit être validé par l\'administrateur.');
            redirect('livreur');
        }
        return $l;
    }

    public function basculerDisponibilite(): void
    {
        $l = $this->profil();
        if ($l['disponible'] && (new Livraison())->enCours((int) $l['id'])) {
            flash('erreur', 'Terminez votre course en cours avant de passer hors service.');
            redirect('livreur');
        }
        (new Livreur())->basculerDisponibilite((int) $l['id']);
        flash('succes', $l['disponible'] ? 'Vous êtes hors service.' : 'Vous êtes en service : les courses à proximité s\'affichent.');
        redirect('livreur');
    }

    public function accepter(): void
    {
        $l = $this->profil();
        $livraisons = new Livraison();

        if (!$l['disponible']) {
            flash('erreur', 'Passez « En service » pour accepter une course.');
        } elseif ($livraisons->enCours((int) $l['id'])) {
            flash('erreur', 'Vous avez déjà une course en cours.');
        } elseif (!$livraisons->accepter($this->entier('livraison_id'), (int) $l['id'])) {
            flash('erreur', 'Cette course vient d\'être prise par un autre livreur.');
        } else {
            $course = $livraisons->trouver($this->entier('livraison_id'));
            (new Notification())->envoyer((int) $course['client_id'], 'Un livreur a pris en charge votre commande ' . $course['reference'] . '.', 'commandes/suivi?id=' . $course['commande_id'], (int) $course['commande_id']);
            flash('succes', 'Course ' . $course['reference'] . ' acceptée. Rendez-vous au point de retrait.');
        }
        redirect('livreur');
    }

    /** Colis récupéré chez le vendeur : la commande passe « En livraison ». */
    public function recuperer(): void
    {
        $l = $this->profil();
        $course = (new Livraison())->enCours((int) $l['id']);
        if (!$course || $course['statut'] !== 'acceptee') {
            flash('erreur', 'Aucune course à récupérer.');
            redirect('livreur');
        }
        try {
            (new Commande())->changerStatut((int) $course['commande_id'], 'en_livraison', Auth::id());
            (new Livraison())->marquerEnRoute((int) $course['id']);
            flash('succes', 'Colis récupéré. En route vers le client !');
        } catch (RuntimeException $e) {
            flash('erreur', $e->getMessage());
        }
        redirect('livreur');
    }

    public function livrer(): void
    {
        $l = $this->profil();
        $course = (new Livraison())->enCours((int) $l['id']);
        if (!$course || $course['statut'] !== 'en_route') {
            flash('erreur', 'Récupérez d\'abord le colis chez le vendeur.');
            redirect('livreur');
        }
        $db = Database::get();
        $db->beginTransaction();
        try {
            (new Livraison())->marquerLivree((int) $course['id']);
            (new Commande())->changerStatut((int) $course['commande_id'], 'livree', Auth::id());
            $db->commit();
            flash('succes', 'Livraison confirmée. +' . fcfa($course['frais']) . ' ajoutés à vos gains du jour.');
        } catch (Throwable $e) {
            $db->rollBack();
            flash('erreur', $e instanceof RuntimeException ? $e->getMessage() : 'Erreur lors de la confirmation.');
        }
        redirect('livreur');
    }
}
