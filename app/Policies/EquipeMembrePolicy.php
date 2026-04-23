<?php

namespace App\Policies;

use App\Models\EquipeMembre;
use App\Models\Proprietaire;

class EquipeMembrePolicy
{
    public function viewAny(Proprietaire|EquipeMembre $user): bool
    {
        return $user instanceof Proprietaire;
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return $user instanceof Proprietaire;
    }

    public function update(Proprietaire|EquipeMembre $user, EquipeMembre $membre): bool
    {
        return $user instanceof Proprietaire
            && $membre->atelier_id === $user->atelierMaitre?->id;
    }

    public function delete(Proprietaire|EquipeMembre $user, EquipeMembre $membre): bool
    {
        return $user instanceof Proprietaire
            && $membre->atelier_id === $user->atelierMaitre?->id;
    }
}
