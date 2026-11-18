<?php
class VendeurController extends Controller
{
    public function index(): void
    {
        if (!Auth::est('vendeur')) {
            $this->render('espace/acces', ['titre' => 'Espace Vendeur', 'page' => 'vendeur', 'espace' => 'vendeur']);
            return;
        }
        Auth::exiger('vendeur');
        $vendeur = (new Vendeur())->parUtilisateur(Auth::id());

        if ($vendeur['statut_validation'] !== 'valide') {
            $this->render('espace/en_attente', ['titre' => 'Espace Vendeur', 'page' => 'vendeur', 'espace' => 'vendeur', 'profil' => $vendeur]);
            return;
        }

        $commandes = new Commande();
        $stocks    = (new Stock())->parVendeur((int) $vendeur['id']);
        $proposes  = array_column($stocks, 'produit_id');

        $this->render('vendeur/index', [
            'titre'      => 'Tableau de bord Vendeur',
            'page'       => 'vendeur',
            'vendeur'    => $vendeur,
            'stats'      => (new Vendeur())->statistiques((int) $vendeur['id']),
            'stocks'     => $stocks,
            'absents'    => array_filter((new Produit())->tous(), fn ($p) => !in_array($p['id'], $proposes)),
            'entrantes'  => $commandes->parVendeur((int) $vendeur['id'], ['en_attente']),
            'enCours'    => $commandes->parVendeur((int) $vendeur['id'], ['acceptee', 'en_preparation', 'en_livraison']),
            'historique' => $commandes->parVendeur((int) $vendeur['id'], ['livree', 'annulee'], 10),
            'avis'       => (new Avis())->parVendeur((int) $vendeur['id'], 4),
        ]);
    }

    /** Profil vendeur validé du compte connecté (sinon arrêt). */
    private function profil(): array
    {
        Auth::exiger('vendeur');
        $vendeur = (new Vendeur())->parUtilisateur(Auth::id());
        if (!$vendeur || $vendeur['statut_validation'] !== 'valide') {
            flash('erreur', 'Votre boutique doit être validée par l\'administrateur.');
            redirect('vendeur');
        }
        return $vendeur;
    }

    public function basculerBoutique(): void
    {
        $v = $this->profil();
        (new Vendeur())->basculerOuverture((int) $v['id']);
        flash('succes', $v['est_ouvert'] ? 'Boutique fermée : vous n\'apparaissez plus comme ouvert.' : 'Boutique ouverte : vous recevez à nouveau des commandes.');
        redirect('vendeur');
    }

    public function majStock(): void
    {
        $v = $this->profil();
        $produitId = $this->entier('produit_id');
        $quantite  = max(0, $this->entier('quantite'));
        $prix      = $this->entier('prix');
        $stock     = new Stock();

        if (!$stock->trouver((int) $v['id'], $produitId)) {
            flash('erreur', 'Produit introuvable dans votre stock.');
        } elseif ($prix < 500 || $prix > 50000) {
            flash('erreur', 'Prix incohérent (entre 500 et 50 000 FCFA).');
        } else {
            $stock->mettreAJour((int) $v['id'], $produitId, $quantite, $prix);
            flash('succes', 'Stock mis à jour : ' . $quantite . ' unité(s) — ' . etat_stock($quantite)['libelle'] . '.');
        }
        redirect('vendeur#stocks');
    }

    public function ajouterProduit(): void
    {
        $v = $this->profil();
        $produit = (new Produit())->trouver($this->entier('produit_id'));
        if (!$produit) {
            flash('erreur', 'Produit inconnu.');
            redirect('vendeur#stocks');
        }
        $prix = $this->entier('prix') ?: (int) $produit['prix_reference'];
        (new Stock())->ajouter((int) $v['id'], (int) $produit['id'], max(0, $this->entier('quantite')), $prix);
        flash('succes', $produit['designation'] . ' ajouté à votre catalogue.');
        redirect('vendeur#stocks');
    }

    public function traiterCommande(): void
    {
        $v = $this->profil();
        $modele = new Commande();
        $c = $modele->trouver($this->entier('commande_id'));
        if (!$c || (int) $c['vendeur_id'] !== (int) $v['id']) {
            flash('erreur', 'Commande introuvable.');
            redirect('vendeur');
        }

        $action = $_POST['action'] ?? '';
        try {
            switch ($action) {
                case 'accepter':
                    $modele->accepter((int) $c['id'], (int) $v['id'], Auth::id());
                    flash('succes', 'Commande ' . $c['reference'] . ' acceptée, stock mis à jour.');
                    break;
                case 'refuser':
                    $motif = $this->texte('motif', 120) ?: 'Refusée par le vendeur';
                    $modele->annuler((int) $c['id'], Auth::id(), $motif);
                    flash('info', 'Commande ' . $c['reference'] . ' refusée.');
                    break;
                case 'preparer':
                    $modele->changerStatut((int) $c['id'], 'en_preparation', Auth::id());
                    flash('succes', 'Commande ' . $c['reference'] . ' passée en préparation.');
                    break;
                case 'remettre':
                    if ($c['mode_recuperation'] !== 'retrait') {
                        throw new RuntimeException('Une commande en livraison est confirmée par le livreur.');
                    }
                    $modele->changerStatut((int) $c['id'], 'livree', Auth::id());
                    flash('succes', 'Retrait confirmé : commande ' . $c['reference'] . ' clôturée.');
                    break;
                default:
                    throw new RuntimeException('Action inconnue.');
            }
        } catch (RuntimeException $e) {
            flash('erreur', $e->getMessage());
        }
        redirect('vendeur#commandes');
    }
}
