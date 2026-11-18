<?php
/**
 * Panier conservé en session. Un panier est rattaché à un seul vendeur,
 * car une commande relie un client à un vendeur.
 */
class Panier
{
    public static function vendeurId(): ?int
    {
        return $_SESSION['panier']['vendeur_id'] ?? null;
    }

    /** @return array<int,int> produit_id => quantité */
    public static function lignes(): array
    {
        return $_SESSION['panier']['lignes'] ?? [];
    }

    public static function nombreArticles(): int
    {
        return array_sum(self::lignes());
    }

    public static function ajouter(int $vendeurId, int $produitId, int $quantite = 1): bool
    {
        $change = false;
        if (self::vendeurId() !== null && self::vendeurId() !== $vendeurId && self::lignes()) {
            self::vider();
            $change = true;
        }
        $_SESSION['panier']['vendeur_id'] = $vendeurId;
        $_SESSION['panier']['lignes'][$produitId] = (self::lignes()[$produitId] ?? 0) + $quantite;
        return $change;
    }

    public static function definir(int $produitId, int $quantite): void
    {
        if ($quantite <= 0) {
            unset($_SESSION['panier']['lignes'][$produitId]);
        } else {
            $_SESSION['panier']['lignes'][$produitId] = $quantite;
        }
        if (!self::lignes()) {
            self::vider();
        }
    }

    public static function vider(): void
    {
        unset($_SESSION['panier']);
    }

    /** Contenu détaillé (prix et stocks réels du vendeur). */
    public static function contenu(): array
    {
        $vendeurId = self::vendeurId();
        if (!$vendeurId || !self::lignes()) {
            return ['vendeur' => null, 'articles' => [], 'sous_total' => 0];
        }
        $vendeur  = (new Vendeur())->trouver($vendeurId);
        $stocks   = (new Stock())->parVendeurIndexe($vendeurId);
        $articles = [];
        $total    = 0;
        foreach (self::lignes() as $produitId => $qte) {
            if (!isset($stocks[$produitId])) {
                continue;
            }
            $s = $stocks[$produitId];
            $articles[] = [
                'produit_id'  => $produitId,
                'designation' => $s['marque'] . ' ' . capacite($s['capacite_kg']),
                'prix'        => (int) $s['prix'],
                'quantite'    => $qte,
                'stock'       => (int) $s['quantite'],
                'total'       => $qte * (int) $s['prix'],
            ];
            $total += $qte * (int) $s['prix'];
        }
        return ['vendeur' => $vendeur, 'articles' => $articles, 'sous_total' => $total];
    }
}
