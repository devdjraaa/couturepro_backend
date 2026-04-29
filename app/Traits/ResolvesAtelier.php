<?php

namespace App\Traits;

use App\Models\Atelier;
use App\Models\EquipeMembre;
use Illuminate\Http\Request;

trait ResolvesAtelier
{
    protected function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        if ($user instanceof EquipeMembre) {
            return $user->atelier;
        }

        // Support multi-atelier : le frontend envoie X-Atelier-Id pour cibler un sous-atelier
        $atelierIdHeader = $request->header('X-Atelier-Id');
        if ($atelierIdHeader) {
            $atelier = Atelier::where('id', $atelierIdHeader)
                ->where('proprietaire_id', $user->id)
                ->first();
            if ($atelier) return $atelier;
        }

        return $user->atelierMaitre;
    }
}
