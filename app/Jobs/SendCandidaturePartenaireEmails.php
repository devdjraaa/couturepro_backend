<?php

namespace App\Jobs;

use App\Models\CandidaturePartenaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

// P204 : à la soumission d'une candidature partenaire — confirmation au candidat
// + alerte interne à l'équipe. AUCUN document contractuel n'est envoyé ici
// (l'envoi des conventions/NDA reste MANUEL après validation humaine du dossier).
class SendCandidaturePartenaireEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(private CandidaturePartenaire $candidature) {}

    public function handle(): void
    {
        $c = $this->candidature;

        // 1) Confirmation au candidat (accusé de réception, pas de document joint).
        Mail::raw(
            "Bonjour {$c->contact_nom},\n\n"
            . "Nous avons bien reçu votre candidature de partenariat pour « {$c->nom_organisation} ».\n"
            . "Notre équipe l'étudie et reviendra vers vous. Aucun document n'est à signer à ce stade.\n\n"
            . "Merci de votre intérêt pour Gextimo.\n— L'équipe Gextimo",
            function ($m) use ($c) {
                $m->to($c->contact_email)->subject('Votre candidature de partenariat Gextimo');
            }
        );

        // 2) Alerte interne (destinataire configurable).
        $interne = config('partenaires.notify_email');
        if ($interne) {
            Mail::raw(
                "Nouvelle candidature partenaire :\n\n"
                . "Organisation : {$c->nom_organisation}\n"
                . "Pays/région : {$c->pays_region}\n"
                . "Catégorie souhaitée : {$c->categorie_souhaitee}\n"
                . "Apport proposé : {$c->type_apport}\n"
                . "Contact : {$c->contact_nom} — {$c->contact_email} — {$c->contact_telephone}\n"
                . "Message : {$c->message}\n\n"
                . "À traiter dans l'admin (validation manuelle avant tout envoi de document).",
                function ($m) use ($interne) {
                    $m->to($interne)->subject('Gextimo — nouvelle candidature partenaire');
                }
            );
        }
    }
}
