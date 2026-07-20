<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Commande;
use App\Models\CommandeEcheance;
use App\Models\CommandeItem;
use App\Models\CommandePaiement;
use App\Models\Mesure;
use App\Models\NotificationSysteme;
use App\Models\PointsFidelite;
use App\Models\PointsHistorique;
use App\Models\Vetement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncService
{
    private array $allowedTables = ['clients', 'commandes', 'mesures', 'vetements', 'collections', 'notifications', 'paiements', 'commande_items', 'commande_echeances'];

    private array $modelMap = [
        'clients'             => Client::class,
        'commandes'           => Commande::class,
        'mesures'             => Mesure::class,
        'vetements'           => Vetement::class,
        'collections'         => Collection::class,
        'notifications'       => NotificationSysteme::class,
        'paiements'           => CommandePaiement::class,
        'commande_items'      => CommandeItem::class,
        'commande_echeances'  => CommandeEcheance::class,
    ];

    // Tables portant les colonnes created_by / created_by_role.
    private array $tablesWithActor = ['clients', 'commandes', 'mesures', 'vetements'];

    // Tables sans atelier_id : scopées via la commande parente (whereHas commande).
    private array $tablesScopedByCommande = ['commande_items', 'commande_echeances'];

    // Cache des colonnes réelles par table (introspection schéma).
    private array $columnCache = [];

    private function tableColumns(string $modelClass): array
    {
        $table = (new $modelClass)->getTable();
        return $this->columnCache[$table] ??= Schema::getColumnListing($table);
    }

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
        $since = $lastPulledAt ? Carbon::parse($lastPulledAt) : null;

        $data = [];

        foreach ($this->modelMap as $table => $model) {
            $usesSoftDeletes = in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($model)
            );

            $base = $usesSoftDeletes ? $model::withTrashed() : $model::query();
            $query = in_array($table, $this->tablesScopedByCommande, true)
                // Tables sans atelier_id : scopées via la commande parente.
                ? $base->whereHas('commande', fn($q) => $q->where('atelier_id', $atelier->id))
                : $base->where('atelier_id', $atelier->id);

            if ($since) {
                $query->where('updated_at', '>', $since);
            }

            $data[$table] = $query->get()->map(fn($r) => array_merge(
                $r->toArray(),
                ['_deleted' => $usesSoftDeletes && !is_null($r->deleted_at)]
            ));
        }

        // Points de fidélité (WatermelonDB sync)
        $data['points_fidelite'] = [
            PointsFidelite::firstOrCreate(
                ['atelier_id' => $atelier->id],
                ['solde_pts'  => 0]
            ),
        ];

        $hQuery = PointsHistorique::where('atelier_id', $atelier->id);
        if ($since) {
            $hQuery->where('created_at', '>', $since);
        }
        $data['points_historique'] = $hQuery->orderBy('created_at')->get();

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
        $scopedByCommande = in_array($table, $this->tablesScopedByCommande, true);

        if ($scopedByCommande) {
            // Pas de colonne atelier_id : on valide via la commande parente.
            unset($data['atelier_id'], $data['created_by'], $data['created_by_role']);
            $commandeId = $data['commande_id'] ?? null;
            if (! $commandeId || ! Commande::where('id', $commandeId)->where('atelier_id', $atelier->id)->exists()) {
                return ['id' => $id, 'status' => 'error', 'message' => 'Commande parente introuvable pour cet atelier.'];
            }
        } else {
            $data['atelier_id'] = $atelier->id;
            if (in_array($table, $this->tablesWithActor, true)) {
                $data['created_by']      = $actorId;
                $data['created_by_role'] = $actorRole;
            } else {
                // Ces tables n'ont pas ces colonnes : ne jamais les insérer.
                unset($data['created_by'], $data['created_by_role']);
            }
        }

        // Sécurité : ne garder que les colonnes réelles de la table. `forceCreate`
        // bypasse $fillable → une colonne locale inconnue (client_nom, categorie,
        // date_creation…) provoquerait SQLSTATE 42703. Filtre robuste, toutes tables.
        $data = array_intersect_key($data, array_flip($this->tableColumns($modelClass)));

        // Récupère un record déjà scopé à l'atelier (direct ou via la commande).
        $findScoped = fn($id) => $scopedByCommande
            ? $modelClass::whereHas('commande', fn($q) => $q->where('atelier_id', $atelier->id))->findOrFail($id)
            : $modelClass::where('atelier_id', $atelier->id)->findOrFail($id);

        try {
            switch ($operation) {
                case 'create':
                    $record = $modelClass::forceCreate(array_merge($data, ['id' => $id]));
                    $this->maybeAwardPoints($atelier, $table, (string) $record->id);
                    $this->createActionNotification($atelier, $table, $record);
                    return ['id' => $record->id, 'status' => 'created'];

                case 'update':
                    $record = $findScoped($id);
                    $record->update($data);
                    return ['id' => $id, 'status' => 'updated'];

                case 'delete':
                    $record = $findScoped($id);
                    $record->delete();
                    return ['id' => $id, 'status' => 'deleted'];

                default:
                    return ['id' => $id, 'status' => 'error', 'message' => "Opération inconnue : {$operation}"];
            }
        } catch (\Throwable $e) {
            return ['id' => $id, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Délègue au service de fidélité : la règle est désormais PARTAGÉE avec les
     * endpoints web, qui ne créditaient rien auparavant.
     */
    private function maybeAwardPoints(Atelier $atelier, string $table, string $recordId): void
    {
        app(PointsFideliteService::class)->crediterCreation($atelier, $table, $recordId);
    }

    private function createActionNotification(Atelier $atelier, string $table, $record): void
    {
        $titre   = null;
        $contenu = null;
        $type    = null;
        $lien    = null;

        if ($table === 'clients') {
            $titre   = 'Nouveau client ajouté';
            $contenu = trim("{$record->prenom} {$record->nom}");
            $type    = 'client_cree';
            $lien    = '/clients/' . $record->id;
        } elseif ($table === 'commandes') {
            $titre   = 'Nouvelle commande créée';
            $contenu = "Commande pour {$record->client_nom}";
            $type    = 'commande_cree';
            $lien    = '/commandes/' . $record->id;
        }

        if (!$titre) {
            return;
        }

        // Notification locale uniquement : le rideau du téléphone est alimenté côté
        // app (notifications locales au sync), plus par push FCM (API legacy fermée).
        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => $titre,
            'contenu'    => $contenu,
            'type'       => $type,
            'lien'       => $lien,
            'is_read'    => false,
        ]);
    }
}
