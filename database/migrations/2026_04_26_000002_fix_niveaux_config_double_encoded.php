<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Le seeder utilisait json_encode() alors que le modèle a le cast 'array'.
     * Résultat : la valeur stockée est un JSON doublement encodé :
     *   '"{\\"max_assistants\\":0,...}"'
     * Laravel le lit comme une string → la modal admin ne peut pas l'utiliser comme objet.
     * Cette migration décode une fois les configs qui sont encore des strings JSON.
     */
    public function up(): void
    {
        $plans = DB::table('niveaux_config')->get(['id', 'config']);

        foreach ($plans as $plan) {
            if ($plan->config === null) continue;

            // Détecter le double-encodage : json_decode donne une string (pas un array)
            $decoded = json_decode($plan->config, true);
            if (is_string($decoded)) {
                // On a un string → décoder une deuxième fois pour obtenir l'array
                $real = json_decode($decoded, true);
                if (is_array($real)) {
                    DB::table('niveaux_config')
                        ->where('id', $plan->id)
                        ->update(['config' => json_encode($real)]);
                }
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback — on ne réintroduit pas un bug
    }
};
