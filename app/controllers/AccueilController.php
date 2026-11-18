<?php
class AccueilController extends Controller
{
    public function index(): void
    {
        $vendeurs = (new Vendeur())->actifs();
        $stocks   = (new Stock())->resumeParVendeur();

        // Données transmises à la carte Leaflet (JSON)
        $carte = array_map(function ($v) use ($stocks) {
            $etat = Vendeur::etatGlobal($v);
            return [
                'id'        => (int) $v['id'],
                'nom'       => $v['nom_commerce'],
                'quartier'  => $v['quartier'],
                'adresse'   => $v['adresse'],
                'telephone' => $v['telephone'],
                'horaires'  => heure($v['heure_ouverture']) . ' - ' . heure($v['heure_fermeture']),
                'ouvert'    => Vendeur::estOuvertMaintenant($v),
                'lat'       => (float) $v['latitude'],
                'lon'       => (float) $v['longitude'],
                'etat'      => $etat['cle'],
                'etatLib'   => $etat['libelle'],
                'note'      => $v['note_moyenne'],
                'stocks'    => array_map(fn ($s) => [
                    'produit' => $s['marque'] . ' ' . capacite($s['capacite_kg']),
                    'prix'    => fcfa($s['prix']),
                    'etat'    => etat_stock((int) $s['quantite'])['cle'],
                ], $stocks[$v['id']] ?? []),
                'url'       => url('catalogue?vendeur=' . $v['id']),
            ];
        }, $vendeurs);

        $this->render('accueil/index', [
            'titre'     => 'Commandez votre gaz domestique en un clic',
            'page'      => 'accueil',
            'vendeurs'  => $vendeurs,
            'quartiers' => (new Quartier())->avecVendeurs(),
            'carte'     => $carte,
        ]);
    }
}
