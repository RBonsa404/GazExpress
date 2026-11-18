<?php
class Produit extends Model
{
    public function tous(): array
    {
        return $this->all('SELECT * FROM produits WHERE actif = 1 ORDER BY FIELD(marque,"SODIGAZ","TOTAL","ORYX"), capacite_kg');
    }

    public function trouver(int $id): ?array
    {
        return $this->one('SELECT * FROM produits WHERE id = ? AND actif = 1', [$id]);
    }

    public function marques(): array
    {
        return array_column($this->all('SELECT DISTINCT marque FROM produits WHERE actif = 1 ORDER BY FIELD(marque,"SODIGAZ","TOTAL","ORYX")'), 'marque');
    }

    /**
     * Catalogue général : chaque produit avec le stock cumulé chez les vendeurs actifs
     * et le meilleur prix constaté.
     */
    public function catalogueGeneral(): array
    {
        return $this->all(
            "SELECT p.*,
                    COALESCE(SUM(CASE WHEN v.id IS NOT NULL THEN s.quantite END), 0) AS quantite,
                    MIN(CASE WHEN v.id IS NOT NULL AND s.quantite > 0 THEN s.prix END)  AS meilleur_prix,
                    COUNT(DISTINCT CASE WHEN v.id IS NOT NULL AND s.quantite > 0 THEN v.id END) AS nb_vendeurs
             FROM produits p
             LEFT JOIN stocks s ON s.produit_id = p.id
             LEFT JOIN vendeurs v ON v.id = s.vendeur_id AND v.statut_validation = 'valide' AND v.est_ouvert = 1
             WHERE p.actif = 1
             GROUP BY p.id
             ORDER BY FIELD(p.marque,'SODIGAZ','TOTAL','ORYX'), p.capacite_kg"
        );
    }
}
