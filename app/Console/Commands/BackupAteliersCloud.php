<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use App\Models\Atelier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// PL-10 : sauvegarde cloud PAR ATELIER, cadencée (Studio: quotidienne, Atelier: /3 jours).
// Complémentaire des dumps DB complets du VPS : donne à chaque atelier son propre
// snapshot de données (clients, mesures, commandes, factures) exportable/restaurable.
class BackupAteliersCloud extends Command
{
    protected $signature = 'atelier:backup-cloud {--force : ignore la cadence}';
    protected $description = 'Sauvegarde cloud cadencée des données par atelier (PL-10)';

    private const ROTATION = 5; // nombre de snapshots conservés par atelier

    public function handle(): int
    {
        $ateliers = Atelier::with('abonnement')->get()
            ->filter(fn ($a) => ($a->abonnement?->getConfigEffective()['backup_cloud'] ?? false) === true);

        $faits = 0;
        foreach ($ateliers as $atelier) {
            if (! $this->option('force') && ! $this->estDu($atelier)) {
                continue;
            }
            $this->sauvegarder($atelier);
            $faits++;
        }

        $this->info("Sauvegarde cloud : {$faits} atelier(s).");

        return self::SUCCESS;
    }

    /** Cadence : quotidienne si plan Studio (videos_presentation), sinon tous les 3 jours. */
    private function estDu(Atelier $atelier): bool
    {
        $config   = $atelier->abonnement?->getConfigEffective() ?? [];
        $cadence  = ! empty($config['videos_presentation']) ? 1 : 3; // jours
        $derniere = DB::table('atelier_backups')->where('atelier_id', $atelier->id)->max('created_at');

        return ! $derniere || now()->diffInDays($derniere) >= $cadence;
    }

    private function sauvegarder(Atelier $atelier): void
    {
        $data = [
            'atelier'   => $atelier->only(['id', 'nom', 'type', 'ville']),
            'exporte_le' => now()->toIso8601String(),
            'clients'   => DB::table('clients')->where('atelier_id', $atelier->id)->get(),
            'mesures'   => DB::table('mesures')->where('atelier_id', $atelier->id)->get(),
            'commandes' => DB::table('commandes')->where('atelier_id', $atelier->id)->get(),
            'factures'  => DB::table('factures')->where('atelier_id', $atelier->id)->get(),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $path = "backups/ateliers/{$atelier->id}/" . now()->format('Y-m-d_His') . '.json';
        Storage::put($path, $json);

        DB::table('atelier_backups')->insert([
            'atelier_id' => $atelier->id,
            'path'       => $path,
            'taille'     => strlen($json),
            'created_at' => now(),
        ]);

        // Rotation : ne garder que les N derniers snapshots.
        $anciens = DB::table('atelier_backups')
            ->where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->skip(self::ROTATION)->take(100)
            ->get();

        foreach ($anciens as $vieux) {
            Storage::delete($vieux->path);
            DB::table('atelier_backups')->where('id', $vieux->id)->delete();
        }
    }
}
