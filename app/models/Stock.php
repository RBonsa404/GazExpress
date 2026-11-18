<?php
class Stock extends Model
{
    public function parVendeur(int $vendeurId): array
    {
        return $this->all(
            'SELECT s.*, p.designation, p.marque, p.capacite_kg, p.description, p.prix_reference
             FROM stocks s JOIN produits p ON p.id = s.produit_id
             WHERE s.vendeur_id = ? AND p.actif = 1
             ORDER BY FIELD(p.marque,"SODIGAZ","TOTAL","ORYX"), p.capacite_kg',
            [$vendeurId]
        );
    }

    public function parVendeurIndexe(int $vendeurId): array
    {
        $index = [];
        foreach ($this->parVendeur($vendeurId) as $s) {
            $index[(int) $s['produit_id']] = $s;
        }
        return $index;
    }

    public function trouver(int $vendeurId, int $produitId): ?array
    {
        return $this->one('SELECT * FROM stocks WHERE vendeur_id = ? AND produit_id = ?', [$vendeurId, $produitId]);
    }

    public function mettreAJour(int $vendeurId, int $produitId, int $quantite, int $prix): void
    {
        $this->run(
            'UPDATE stocks SET quantite = ?, prix = ? WHERE vendeur_id = ? AND produit_id = ?',
            [max(0, $quantite), max(0, $prix), $vendeurId, $produitId]
        );
    }

    public function ajouter(int $vendeurId, int $produitId, int $quantite, int $prix): void
    {
        $this->run(
            'INSERT INTO stocks (vendeur_id, produit_id, quantite, prix) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantite = VALUES(quantite), prix = VALUES(prix)',
            [$vendeurId, $produitId, max(0, $quantite), max(0, $prix)]
        );
    }

    /**
     * Vendeur proposant le produit au meilleur prix (utilisé depuis le catalogue général).
     * Le vendeur du panier en cours est privilégié s'il a le produit en stock.
     */
    public function meilleureOffre(int $produitId, ?int $vendeurPrefere = null): ?array
    {
        return $this->one(
            "SELECT s.*, v.nom_commerce FROM stocks s
             JOIN vendeurs v ON v.id = s.vendeur_id
             JOIN utilisateurs u ON u.id = v.utilisateur_id
             WHERE s.produit_id = ? AND s.quantite > 0 AND v.statut_validation = 'valide'
               AND v.est_ouvert = 1 AND u.statut = 'actif'
             ORDER BY (s.vendeur_id = ?) DESC, s.prix ASC, s.quantite DESC
             LIMIT 1",
            [$produitId, $vendeurPrefere ?? 0]
        );
    }

    /** Pour la carte : stock par produit de chaque vendeur actif. */
    public function resumeParVendeur(): array
    {
        $lignes = $this->all(
            'SELECT s.vendeur_id, s.quantite, s.prix, p.marque, p.capacite_kg
             FROM stocks s JOIN produits p ON p.id = s.produit_id ORDER BY FIELD(p.marque,"SODIGAZ","TOTAL","ORYX"), p.capacite_kg'
        );
        $res = [];
        foreach ($lignes as $l) {
            $res[(int) $l['vendeur_id']][] = $l;
        }
        return $res;
    }
}
