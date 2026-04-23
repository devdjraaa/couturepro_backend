<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Mesure;
use App\Models\Vetement;
use Illuminate\Support\Facades\DB;

class SyncService
{
    private array $allowedTables = ['clients', 'commandes', 'mesures', 'vetements'];

    private array $modelMap = [
        'clients'   => Client::class,
        'commandes' => Commande::class,
        'mesures'   => Mesure::class,
        'vetements' => Vetement::class,
    ];

    public function push(Atelier $atelier, array $operations, string $actorId, string $actorRole): array
    {
        $results = [];

        DB::transaction(function () use ($atelier, $operations, $actorId, $actorRole, &$results) {
            foreach ($operations as $op) {
                $results[] = $this->applyOperation($atelier, $op, $actorId, $actorRole);
            }
        });

        return $results;
    }

    public function pull(Atelier $atelier, ?string $lastPulledAt): array
    {
        $since = $lastPulledAt ? \Carbon\Carbon::parse($lastPulledAt) : null;

        $data = [];

        foreach ($this->modelMap as $table => $model) {
            $query = $model::withTrashed()->where('atelier_id', $atelier->id);

            if ($since) {
                $query->where('updated_at', '>', $since);
            }

            $data[$table] = $query->get()->map(fn($r) => array_merge(
                $r->toArray(),
                ['_deleted' => !is_null($r->deleted_at)]
            ));
        }

        // Régénère le config_snapshot à chaque pull
        $abonnement = $atelier->abonnement;
        if ($abonnement && $abonnement->niveau) {
            $abonnement->update(['config_snapshot' => $abonnement->niveau->config]);
        }

        return [
            'last_pulled_at' => now()->toISOString(),
            'data'           => $data,
        ];
    }

    private function applyOperation(Atelier $atelier, array $op, string $actorId, string $actorRole): array
    {
        $table     = $op['table'] ?? null;
        $operation = $op['operation'] ?? null;
        $id        = $op['id'] ?? null;
        $data      = $op['data'] ?? [];

        if (!in_array($table, $this->allowedTables)) {
            return ['id' => $id, 'status' => 'error', 'message' => "Table {$table} non supportée."];
        }

        $modelClass = $this->modelMap[$table];
        $data['atelier_id']    = $atelier->id;
        $data['created_by']    = $actorId;
        $data['created_by_role'] = $actorRole;

        try {
            switch ($operation) {
                case 'create':
                    $record = $modelClass::create(array_merge($data, ['id' => $id]));
                    return ['id' => $record->id, 'status' => 'created'];

                case 'update':
                    $record = $modelClass::where('atelier_id', $atelier->id)->findOrFail($id);
                    $record->update($data);
                    return ['id' => $id, 'status' => 'updated'];

                case 'delete':
                    $record = $modelClass::where('atelier_id', $atelier->id)->findOrFail($id);
                    $record->delete();
                    return ['id' => $id, 'status' => 'deleted'];

                default:
                    return ['id' => $id, 'status' => 'error', 'message' => "Opération inconnue : {$operation}"];
            }
        } catch (\Throwable $e) {
            return ['id' => $id, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
