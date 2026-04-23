<?php

namespace App\Policies;

use App\Models\EquipeMembre;
use App\Models\Mesure;
use App\Models\Proprietaire;

class MesurePolicy
{
    public function viewAny(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function view(Proprietaire|EquipeMembre $user, Mesure $mesure): bool
    {
        return $mesure->atelier_id === $this->getAtelierId($user);
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function update(Proprietaire|EquipeMembre $user, Mesure $mesure): bool
    {
        return $mesure->atelier_id === $this->getAtelierId($user);
    }

    public function delete(Proprietaire|EquipeMembre $user, Mesure $mesure): bool
    {
        return $mesure->atelier_id === $this->getAtelierId($user);
    }

    private function getAtelierId(Proprietaire|EquipeMembre $user): ?string
    {
        return $user instanceof EquipeMembre
            ? $user->atelier_id
            : $user->atelierMaitre?->id;
    }
}
