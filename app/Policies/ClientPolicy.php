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
        return $this->ownsAtelier($user, $client->atelier_id);
    }

    public function create(Proprietaire|EquipeMembre $user): bool
    {
        return true;
    }

    public function update(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        return $this->ownsAtelier($user, $client->atelier_id);
    }

    public function archive(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        if ($user instanceof EquipeMembre) {
            return $client->atelier_id === $user->atelier_id && $user->role === 'assistant';
        }
        return $this->ownsAtelier($user, $client->atelier_id);
    }

    public function delete(Proprietaire|EquipeMembre $user, Client $client): bool
    {
        if ($user instanceof EquipeMembre) {
            return false; // Seul le propriétaire peut supprimer
        }
        return $this->ownsAtelier($user, $client->atelier_id);
    }

    /**
     * L'entité appartient-elle à un atelier de l'utilisateur ?
     * Propriétaire : n'importe lequel de SES ateliers (multi-ateliers P72-77) ;
     * membre d'équipe : son atelier uniquement.
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
