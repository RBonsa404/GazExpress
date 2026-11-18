<?php
/**
 * Points d'accès JSON appelés en AJAX (fetch) par le front-end.
 */
class ApiController extends Controller
{
    public function notifications(): void
    {
        if (!Auth::check()) {
            $this->json(['nonLues' => 0, 'items' => []]);
            return;
        }
        $n = new Notification();
        $items = array_map(fn ($x) => [
            'id'      => (int) $x['id'],
            'message' => $x['message'],
            'lien'    => $x['lien'] ? url($x['lien']) : null,
            'lue'     => (bool) $x['est_lue'],
            'date'    => date_fr($x['date_creation']),
        ], $n->recentes(Auth::id()));

        $this->json(['nonLues' => $n->nonLues(Auth::id()), 'items' => $items]);
    }

    public function marquerLues(): void
    {
        if (Auth::check()) {
            (new Notification())->marquerLues(Auth::id());
        }
        $this->json(['ok' => true]);
    }

    /** Statut courant d'une commande du client connecté (suivi en direct). */
    public function statutCommande(): void
    {
        $c = Auth::est('client') ? (new Commande())->trouver($this->entier('id', $_GET)) : null;
        if (!$c || (int) $c['client_id'] !== Auth::id()) {
            $this->json(['erreur' => 'introuvable'], 404);
            return;
        }
        $this->json(['statut' => $c['statut'], 'livraison' => $c['statut_livraison']]);
    }

    /** Nombre de commandes en attente du vendeur connecté (rafraîchissement du tableau de bord). */
    public function commandesVendeur(): void
    {
        $v = Auth::est('vendeur') ? (new Vendeur())->parUtilisateur(Auth::id()) : null;
        if (!$v) {
            $this->json(['erreur' => 'interdit'], 403);
            return;
        }
        $ids = array_column((new Commande())->parVendeur((int) $v['id'], ['en_attente']), 'id');
        $this->json(['enAttente' => count($ids), 'ids' => array_map('intval', $ids)]);
    }
}
