<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

// P202 / Espace Client v3 — notification e-mail au client vitrine (via Brevo) à chaque
// évènement de sa commande. Textes centralisés ici : un évènement = un message clair.
class SendGxtCommandeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private string $email,
        private string $evenement, // recue|coupe|confection|essayage|livraison|livree|reclamation_recue
        private string $reference,
        private string $designer,
    ) {}

    public function handle(): void
    {
        [$sujet, $corps] = $this->contenu();

        Mail::raw($corps, function ($message) use ($sujet) {
            $message->to($this->email)->subject($sujet);
        });
    }

    /**
     * Les textes viennent de `MessagesCommandeClient`, partagé avec les
     * notifications dans l'application : le client doit lire rigoureusement le
     * même message par les deux canaux. Les réécrire ici avait déjà produit,
     * pour le partage WhatsApp, deux versions divergentes selon le chemin.
     */
    private function contenu(): array
    {
        $m = \App\Support\MessagesCommandeClient::pour($this->evenement, $this->reference, $this->designer);

        return [$m['titre'], $m['corps']];
    }
}
