<?php

namespace App\Policies;

use App\Models\EquipeMembre;
use App\Models\Proprietaire;
use App\Models\Vetement;

class VetementPolicy
{
    public function viewAny(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function view(Proprietaire|EquipeMembre $user, Vetement $vetement): bool
    {
        return $this->ownsAtelier($user, $vetement->atelier_id)
            || $vetement->is_systeme;
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return $user instanceof Proprietaire;
    }

    public function update(Proprietaire|EquipeMembre $user, Vetement $vetement): bool
    {
        return $user instanceof Proprietaire
            && $this->ownsAtelier($user, $vetement->atelier_id)
            && !$vetement->is_systeme;
    }

    public function delete(Proprietaire|EquipeMembre $user, Vetement $vetement): bool
    {
        return $user instanceof Proprietaire
            && $this->ownsAtelier($user, $vetement->atelier_id)
            && !$vetement->is_systeme;
    }

    /**
     * Le vêtement appartient-il à un atelier de l'utilisateur ?
     * - Propriétaire : n'importe lequel de SES ateliers (support multi-ateliers P72-77 :
     *   un vêtement créé dans un sous-atelier reste éditable par le propriétaire).
     * - Membre d'équipe : uniquement son atelier.
     */
    private function ownsAtelier(Proprietaire|EquipeMembre $user, ?string $atelierId): bool
    {
        if ($atelierId === null) {
            return false;
        }

        return $user instanceof EquipeMembre
            ? $user->atelier_id === $atelierId
            : $user->ateliers()->whereKey($atelierId)->exists();
    }
}
