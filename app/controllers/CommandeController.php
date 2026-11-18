<?php
class CommandeController extends Controller
{
    public function index(): void
    {
        $this->suivi();
    }

    public function suivi(): void
    {
        $client    = Auth::exiger('client');
        $modele    = new Commande();
        $commandes = $modele->parClient($client['id']);

        $id = $this->entier('id', $_GET);
        $commande = null;
        if ($id) {
            $commande = $modele->trouver($id);
            if (!$commande || (int) $commande['client_id'] !== (int) $client['id']) {
                flash('erreur', 'Commande introuvable.');
                redirect('commandes');
            }
        } elseif ($commandes) {
            $commande = $modele->trouver((int) $commandes[0]['id']);
        }

        $this->render('commandes/suivi', [
            'titre'        => $commande ? 'Commande #' . $commande['reference'] : 'Suivi de commande',
            'page'         => 'suivi',
            'commandes'    => $commandes,
            'commande'     => $commande,
            'details'      => $commande ? $modele->details((int) $commande['id']) : [],
            'historique'   => $commande ? $modele->historique((int) $commande['id']) : [],
            'avis'         => $commande ? (new Avis())->parCommande((int) $commande['id']) : null,
            'reclamations' => $commande ? (new Reclamation())->parCommande((int) $commande['id']) : [],
        ]);
    }

    private function commandeDuClient(array $client): array
    {
        $commande = (new Commande())->trouver($this->entier('commande_id'));
        if (!$commande || (int) $commande['client_id'] !== (int) $client['id']) {
            flash('erreur', 'Commande introuvable.');
            redirect('commandes');
        }
        return $commande;
    }

    public function annuler(): void
    {
        $client   = Auth::exiger('client');
        $commande = $this->commandeDuClient($client);

        if ($commande['statut'] !== 'en_attente') {
            flash('erreur', 'Une commande déjà acceptée par le vendeur ne peut plus être annulée en ligne. Contactez la boutique.');
        } else {
            (new Commande())->annuler((int) $commande['id'], $client['id'], 'Annulée par le client');
            flash('succes', 'Commande ' . $commande['reference'] . ' annulée.');
        }
        redirect('commandes/suivi?id=' . $commande['id']);
    }

    public function avis(): void
    {
        $client   = Auth::exiger('client');
        $commande = $this->commandeDuClient($client);
        $avis     = new Avis();

        if ($commande['statut'] !== 'livree' || $avis->parCommande((int) $commande['id'])) {
            flash('erreur', 'Vous ne pouvez noter qu\'une commande livrée, une seule fois.');
            redirect('commandes/suivi?id=' . $commande['id']);
        }

        $note = max(1, min(5, $this->entier('note')));
        $avis->publier($client['id'], (int) $commande['vendeur_id'], (int) $commande['id'], $note, $this->texte('commentaire', 500));

        $noteLivreur = $this->entier('note_livreur');
        if ($commande['livraison_id'] && $noteLivreur >= 1 && $noteLivreur <= 5) {
            (new Livraison())->noter((int) $commande['id'], $noteLivreur);
        }
        flash('succes', 'Merci pour votre avis !');
        redirect('commandes/suivi?id=' . $commande['id']);
    }

    public function reclamation(): void
    {
        $client   = Auth::exiger('client');
        $commande = $this->commandeDuClient($client);
        $objet    = $this->texte('objet', 120);
        $message  = $this->texte('message', 2000);

        if ($objet === '' || mb_strlen($message) < 10) {
            flash('erreur', 'Précisez l\'objet et décrivez le problème (10 caractères minimum).');
            redirect('commandes/suivi?id=' . $commande['id']);
        }

        (new Reclamation())->creer((int) $commande['id'], $client['id'], $objet, $message);
        foreach ((new Utilisateur())->admins() as $a) {
            (new Notification())->envoyer((int) $a['id'], 'Nouvelle réclamation sur la commande ' . $commande['reference'] . '.', 'admin?onglet=reclamations', (int) $commande['id']);
        }
        flash('succes', 'Réclamation transmise à l\'équipe GazExpress. Vous serez notifié de la réponse.');
        redirect('commandes/suivi?id=' . $commande['id']);
    }
}
