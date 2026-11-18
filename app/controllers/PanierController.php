<?php
class PanierController extends Controller
{
    public function ajouter(): void
    {
        $produitId = $this->entier('produit_id');
        $vendeurId = $this->entier('vendeur_id');
        $stocks    = new Stock();

        if ($vendeurId) {
            $vendeur = (new Vendeur())->trouver($vendeurId);
            $offre   = $stocks->trouver($vendeurId, $produitId);
            if (!$vendeur || $vendeur['statut_validation'] !== 'valide' || !$vendeur['est_ouvert']) {
                flash('erreur', 'Cette boutique ne prend pas de commandes pour le moment.');
                redirect_back('catalogue');
            }
            if ($offre) {
                $offre['nom_commerce'] = $vendeur['nom_commerce'];
            }
        } else {
            // Catalogue général : on retient la meilleure offre disponible
            $offre = $stocks->meilleureOffre($produitId, Panier::vendeurId());
        }

        if (!$offre || (int) $offre['quantite'] <= 0) {
            flash('erreur', 'Ce produit est en rupture de stock chez ce vendeur.');
            redirect_back('catalogue');
        }

        $dejaPris = Panier::vendeurId() === (int) $offre['vendeur_id'] ? (Panier::lignes()[$produitId] ?? 0) : 0;
        if ($dejaPris + 1 > (int) $offre['quantite']) {
            flash('erreur', 'Quantité maximale atteinte : ' . (int) $offre['quantite'] . ' bouteille(s) en stock.');
            redirect_back('catalogue', '#panier');
        }

        $change = Panier::ajouter((int) $offre['vendeur_id'], $produitId);
        if ($change) {
            flash('info', 'Votre panier a été vidé : une commande ne peut concerner qu\'un seul vendeur.');
        }
        flash('succes', 'Produit ajouté au panier — vendeur : ' . $offre['nom_commerce'] . '.');
        redirect_back('catalogue', '#panier');
    }

    public function modifier(): void
    {
        $produitId = $this->entier('produit_id');
        $quantite  = $this->entier('quantite');

        if ($quantite > 0 && Panier::vendeurId()) {
            $stock = (new Stock())->trouver(Panier::vendeurId(), $produitId);
            if ($stock && $quantite > (int) $stock['quantite']) {
                $quantite = (int) $stock['quantite'];
                flash('info', 'Quantité ajustée au stock disponible (' . $quantite . ').');
            }
        }
        Panier::definir($produitId, $quantite);
        redirect_back('catalogue', '#panier');
    }

    public function vider(): void
    {
        Panier::vider();
        flash('info', 'Panier vidé.');
        redirect_back('catalogue');
    }

    public function valider(): void
    {
        if (!Auth::check()) {
            $_SESSION['apres_connexion'] = url('catalogue') . '#panier';
            flash('info', 'Connectez-vous ou créez un compte client pour confirmer votre commande. Votre panier est conservé.');
            redirect('connexion');
        }
        $client = Auth::exiger('client');

        $contenu = Panier::contenu();
        if (!$contenu['articles']) {
            flash('erreur', 'Votre panier est vide.');
            redirect('catalogue');
        }

        $mode     = ($_POST['mode'] ?? '') === 'retrait' ? 'retrait' : 'livraison';
        $paiement = $_POST['paiement'] ?? '';
        $adresse  = $this->texte('adresse', 255);
        $quartier = null;

        if (!array_key_exists($paiement, modes_paiement())) {
            flash('erreur', 'Choisissez un moyen de paiement valide.');
            redirect_back('catalogue', '#panier');
        }
        if ($mode === 'livraison') {
            $quartier = (new Quartier())->trouver($this->entier('quartier_id'));
            if (!$quartier) {
                flash('erreur', 'Sélectionnez votre quartier de livraison.');
                redirect_back('catalogue', '#panier');
            }
            if ($adresse === '') {
                $adresse = $client['adresse'] ?: $quartier['nom'];
            }
        }

        $lignes = [];
        foreach ($contenu['articles'] as $a) {
            $lignes[$a['produit_id']] = $a['quantite'];
        }

        try {
            $id = (new Commande())->creer($client['id'], (int) $contenu['vendeur']['id'], $lignes, $mode, $quartier, $adresse, $paiement);
        } catch (RuntimeException $e) {
            flash('erreur', $e->getMessage());
            redirect_back('catalogue', '#panier');
        }

        Panier::vider();
        flash('succes', 'Commande envoyée ! Le vendeur va vérifier son stock et vous répondre.');
        redirect('commandes/suivi?id=' . $id);
    }
}
