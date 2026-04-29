<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\EquipeMembre;
use App\Models\Proprietaire;

class CommandePolicy
{
    public function viewAny(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function view(Proprietaire|EquipeMembre $user, Commande $commande): bool
    {
        return $commande->atelier_id === $this->getAtelierId($user);
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function update(Proprietaire|EquipeMembre $user, Commande $commande): bool
    {
        return $commande->atelier_id === $this->getAtelierId($user);
    }

    public function archive(Proprietaire|EquipeMembre $user, Commande $commande): bool
    {
        if ($user instanceof EquipeMembre) {
            return $commande->atelier_id === $user->atelier_id && $user->role === 'assistant';
        }
        return $commande->atelier_id === $this->getAtelierId($user);
    }

    public function delete(Proprietaire|EquipeMembre $user, Commande $commande): bool
    {
        if ($user instanceof EquipeMembre) {
            return false;
        }
        return $commande->atelier_id === $user->atelierMaitre?->id;
    }

    private function getAtelierId(Proprietaire|EquipeMembre $user): ?string
    {
        return $user instanceof EquipeMembre
            ? $user->atelier_id
            : $user->atelierMaitre?->id;
    }
}
