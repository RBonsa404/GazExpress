<?php
class CatalogueController extends Controller
{
    public function index(): void
    {
        $vendeurId = $this->entier('vendeur', $_GET);
        $vendeur   = $vendeurId ? (new Vendeur())->trouver($vendeurId) : null;

        if ($vendeur && ($vendeur['statut_validation'] !== 'valide' || $vendeur['statut_compte'] !== 'actif')) {
            flash('erreur', 'Ce vendeur n\'est pas disponible.');
            redirect('catalogue');
        }

        $this->render('catalogue/index', [
            'titre'    => $vendeur ? $vendeur['nom_commerce'] : 'Catalogue Gaz',
            'page'     => 'catalogue',
            'vendeur'  => $vendeur,
            'produits' => $vendeur ? (new Stock())->parVendeur($vendeurId) : (new Produit())->catalogueGeneral(),
            'marques'  => (new Produit())->marques(),
            'vendeurs' => (new Vendeur())->actifs(),
            'avis'     => $vendeur ? (new Avis())->parVendeur($vendeurId, 3) : [],
        ]);
    }
}
