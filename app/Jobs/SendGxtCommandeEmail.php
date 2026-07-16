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

    private function contenu(): array
    {
        $ref = $this->reference;
        $d   = $this->designer;
        $pied = "\n\nSuivez votre commande à tout moment : https://gextimo.novafriq.africa/suivi"
            . "\n— L'équipe Gextimo";

        return match ($this->evenement) {
            'recue'      => ["Commande {$ref} bien reçue",
                "Bonjour,\n\nVotre commande {$ref} a bien été reçue par {$d}. Vous serez informé(e) à chaque étape.{$pied}"],
            'coupe'      => ["Commande {$ref} : la coupe a commencé",
                "Bonjour,\n\nBonne nouvelle : {$d} a commencé la coupe de votre tenue (commande {$ref}).{$pied}"],
            'confection' => ["Commande {$ref} : en confection",
                "Bonjour,\n\nVotre création est en cours de confection chez {$d} (commande {$ref}).{$pied}"],
            'essayage'   => ["Commande {$ref} : prête pour l'essayage",
                "Bonjour,\n\nVotre commande {$ref} est prête pour l'essayage. {$d} vous attend !{$pied}"],
            'livraison'  => ["Commande {$ref} : en cours de livraison",
                "Bonjour,\n\nVotre commande {$ref} est prête et en cours de livraison.{$pied}"],
            'livree'     => ["Commande {$ref} livrée — votre avis compte",
                "Bonjour,\n\nVotre commande {$ref} a été livrée. Satisfait(e) ? Laissez un avis à {$d} depuis votre espace client.{$pied}"],
            'reclamation_recue' => ["Réclamation reçue — commande {$ref}",
                "Bonjour,\n\nNous avons bien reçu votre réclamation concernant la commande {$ref}. {$d} et l'équipe Gextimo ont été prévenus et reviennent vers vous rapidement.{$pied}"],
            default => ["Commande {$ref} : mise à jour",
                "Bonjour,\n\nVotre commande {$ref} chez {$d} vient d'être mise à jour.{$pied}"],
        };
    }
}
