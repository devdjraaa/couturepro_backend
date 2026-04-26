<?php

namespace App\Observers;

use App\Models\NiveauConfig;
use App\Models\NiveauConfigChangelog;

class NiveauConfigObserver
{
    public function updating(NiveauConfig $plan): void
    {
        $adminId = auth('admin')->id();

        if (! $adminId) {
            return; // Mise à jour système/console, pas de log
        }

        $dirty = $plan->getDirty();

        foreach ($dirty as $champ => $nouvelleValeur) {
            $ancienne = $plan->getOriginal($champ);
            NiveauConfigChangelog::create([
                'niveau_cle'      => $plan->cle,
                'admin_id'        => $adminId,
                'champ_modifie'   => $champ,
                'ancienne_valeur' => is_array($ancienne) ? json_encode($ancienne) : (string) ($ancienne ?? ''),
                'nouvelle_valeur' => is_array($nouvelleValeur) ? json_encode($nouvelleValeur) : (string) $nouvelleValeur,
                'created_at'      => now(),
            ]);
        }
    }
}
