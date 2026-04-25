<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use App\Models\Atelier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAtelierTrial extends Command
{
    protected $signature = 'app:reset-trial
                            {atelier_id? : UUID de l\'atelier à réinitialiser (tous si absent)}
                            {--force : Pas de confirmation interactive}';

    protected $description = 'Réinitialise un ou tous les ateliers en période d\'essai (14 jours). Vide paiements et transactions.';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Cette action supprime les paiements et transactions. Continuer ?')) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $atelierUuid = $this->argument('atelier_id');

        $query = Atelier::query();

        if ($atelierUuid) {
            $query->where('id', $atelierUuid);
        } else {
            $query->where('is_demo', false);
        }

        $ateliers = $query->get();

        if ($ateliers->isEmpty()) {
            $this->warn('Aucun atelier trouvé.');
            return self::SUCCESS;
        }

        $this->info("Reset de {$ateliers->count()} atelier(s)…");

        foreach ($ateliers as $atelier) {
            DB::transaction(function () use ($atelier) {
                DB::table('paiements')->where('atelier_id', $atelier->id)->delete();
                DB::table('transactions_abonnement')->where('atelier_id', $atelier->id)->delete();

                $expire = now()->addDays(14);

                Abonnement::updateOrCreate(
                    ['atelier_id' => $atelier->id],
                    [
                        'statut'               => 'essai',
                        'niveau_cle'           => 'standard_mensuel',
                        'jours_restants'       => 14,
                        'timestamp_debut'      => now(),
                        'timestamp_expiration' => $expire,
                        'config_snapshot'      => null,
                        'bonus_actif'          => false,
                        'bonus_jours_restants' => 0,
                        'bonus_niveau_cle'     => null,
                        'bonus_timestamp_debut'=> null,
                    ]
                );

                $atelier->update([
                    'statut'          => 'essai',
                    'essai_expire_at' => $expire,
                ]);
            });

            $this->line("  ✓ {$atelier->nom} ({$atelier->id})");
        }

        $this->info('Terminé.');
        return self::SUCCESS;
    }
}
