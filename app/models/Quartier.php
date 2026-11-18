<?php
class Quartier extends Model
{
    public function tous(): array
    {
        return $this->all('SELECT * FROM quartiers WHERE actif = 1 ORDER BY nom');
    }

    public function trouver(int $id): ?array
    {
        return $this->one('SELECT * FROM quartiers WHERE id = ? AND actif = 1', [$id]);
    }

    /** Quartiers qui comptent au moins un vendeur validé (filtres de la page d'accueil). */
    public function avecVendeurs(): array
    {
        return $this->all(
            "SELECT DISTINCT q.* FROM quartiers q
             JOIN vendeurs v ON v.quartier_id = q.id AND v.statut_validation = 'valide'
             ORDER BY q.nom"
        );
    }
}
