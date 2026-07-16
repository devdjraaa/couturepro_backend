<?php

namespace App\Console\Commands;

use App\Models\GxtClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Brief 16/07 (points 3+6) : vœux d'anniversaire aux clients vitrine (quotidien 8h).
// Message court et discret, envoyé via le canal transactionnel (Brevo). Ne s'applique
// qu'aux clients ayant renseigné leur date de naissance (champ optionnel).
class GxtAnniversaires extends Command
{
    protected $signature = 'gxt:anniversaires';

    protected $description = 'Envoie un vœu d’anniversaire aux clients vitrine nés aujourd’hui';

    public function handle(): int
    {
        $aujourdhui = now();
        $clients = GxtClient::whereNotNull('date_naissance')
            ->whereMonth('date_naissance', $aujourdhui->month)
            ->whereDay('date_naissance', $aujourdhui->day)
            ->get();

        foreach ($clients as $client) {
            $prenom = $client->prenom ?: '';
            try {
                Mail::raw(
                    "Bonjour {$prenom},\n\nToute l'équipe Gextimo vous souhaite un très joyeux anniversaire !\n"
                    ."Que cette nouvelle année vous apporte style, réussite et belles créations.\n\n— L'équipe Gextimo\nhttps://gextimo.novafriq.africa",
                    fn ($m) => $m->to($client->email)->subject('Joyeux anniversaire de la part de Gextimo !')
                );
            } catch (\Throwable $e) {
                Log::warning("Vœu anniversaire échoué pour {$client->email} — ".$e->getMessage());
            }
        }

        $this->info("Vœux envoyés : {$clients->count()} client(s).");

        return self::SUCCESS;
    }
}
