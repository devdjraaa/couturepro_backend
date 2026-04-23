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
        return $vetement->atelier_id === $this->getAtelierId($user)
            || $vetement->is_systeme;
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return $user instanceof Proprietaire;
    }

    public function update(Proprietaire|EquipeMembre $user, Vetement $vetement): bool
    {
        return $user instanceof Proprietaire
            && $vetement->atelier_id === $user->atelierMaitre?->id
            && !$vetement->is_systeme;
    }

    public function delete(Proprietaire|EquipeMembre $user, Vetement $vetement): bool
    {
        return $user instanceof Proprietaire
            && $vetement->atelier_id === $user->atelierMaitre?->id
            && !$vetement->is_systeme;
    }

    private function getAtelierId(Proprietaire|EquipeMembre $user): ?string
    {
        return $user instanceof EquipeMembre
            ? $user->atelier_id
            : $user->atelierMaitre?->id;
    }
}
