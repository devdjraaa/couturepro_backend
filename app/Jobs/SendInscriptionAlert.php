<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

// P201 : alerte interne à l'équipe à chaque nouvelle inscription (heads-up temps réel).
class SendInscriptionAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private string $nom,
        private string $telephone,
        private ?string $email,
        private string $type,
    ) {}

    public function handle(): void
    {
        $dest = config('novafriq.inscription_alert_email');
        if (! $dest) {
            return;
        }

        Mail::raw(
            "Nouvelle inscription Gextimo :\n\n"
            . "Nom : {$this->nom}\n"
            . "Type : {$this->type}\n"
            . "Téléphone : {$this->telephone}\n"
            . "E-mail : " . ($this->email ?: '—') . "\n"
            . "Le : " . now()->format('d/m/Y H:i'),
            fn ($m) => $m->to($dest)->subject('Gextimo — nouvelle inscription')
        );
    }
}
