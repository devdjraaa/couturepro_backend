<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionEquipe extends Model
{
    protected $table = 'permissions_equipe';

    protected $fillable = [
        'atelier_id',
        'role',
        'ressource',
        'action',
        'autorise',
    ];

    protected $casts = [
        'autorise' => 'boolean',
    ];

    // Permissions CDC §4.3 — utilisées quand aucune ligne n'existe en DB
    public const DEFAULTS = [
        'assistant' => [
            'clients.view', 'clients.create', 'clients.edit',
            'commandes.view', 'commandes.create', 'commandes.edit',
            'mesures.view', 'mesures.edit',
            'vetements.manage',
            'paiements.view', 'paiements.create',
            'notifications.view',
        ],
        'membre' => [
            'clients.view',
            'commandes.view', 'commandes.create',
            'mesures.view',
            'notifications.view',
        ],
    ];

    // Toutes les permissions configurables (exposées dans l'UI)
    public const ALL_PERMISSIONS = [
        'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'commandes.view', 'commandes.create', 'commandes.edit', 'commandes.delete',
        'mesures.view', 'mesures.edit',
        'vetements.manage',
        'paiements.view', 'paiements.create',
        'points.convert',
        'notifications.view',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public static function getForAtelier(string $atelierIdStr, string $role): array
    {
        $rows = static::where('atelier_id', $atelierIdStr)
            ->where('role', $role)
            ->get(['ressource', 'action', 'autorise']);

        if ($rows->isEmpty()) {
            return static::DEFAULTS[$role] ?? [];
        }

        return $rows
            ->filter(fn($r) => $r->autorise)
            ->map(fn($r) => "{$r->ressource}.{$r->action}")
            ->values()
            ->toArray();
    }
}
