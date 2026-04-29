<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\EquipeMembre;
use App\Models\Proprietaire;

class ClientPolicy
{
    public function viewAny(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function view(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        return $client->atelier_id === $this->getAtelierId($user);
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function update(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        return $client->atelier_id === $this->getAtelierId($user);
    }

    public function archive(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        if ($user instanceof EquipeMembre) {
            return $client->atelier_id === $user->atelier_id && $user->role === 'assistant';
        }
        return $client->atelier_id === $this->getAtelierId($user);
    }

    public function delete(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        if ($user instanceof EquipeMembre) {
            return false; // Seul le propriétaire peut supprimer
        }
        return $client->atelier_id === $user->atelierMaitre?->id;
    }

    private function getAtelierId(Proprietaire|EquipeMembre $user): ?string
    {
        return $user instanceof EquipeMembre
            ? $user->atelier_id
            : $user->atelierMaitre?->id;
    }
}
