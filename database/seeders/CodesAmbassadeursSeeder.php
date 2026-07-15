<?php

namespace Database\Seeders;

use App\Models\CodePromo;
use Illuminate\Database\Seeder;

// P158 : 10 codes ambassadeurs suivis GEXT-AMB-001 → GEXT-AMB-010.
// +17 jours au temps restant, 1× par téléphone, sans expiration. Idempotent.
class CodesAmbassadeursSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            CodePromo::updateOrCreate(
                ['code' => "GEXT-AMB-{$num}"],
                [
                    'type'             => 'ambassadeur',
                    'jours_bonus'      => 17,
                    'expire_at'        => null,
                    'max_utilisations' => null,
                    'is_actif'         => true,
                    'note'             => "Ambassadeur #{$num}",
                ],
            );
        }
    }
}
