<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbonnementExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Abonnement $abonnement,
        public readonly int $jours,
    ) {}

    public function envelope(): Envelope
    {
        $sujet = $this->jours === 1
            ? 'Votre abonnement CouturePro expire demain'
            : "Votre abonnement CouturePro expire dans {$this->jours} jours";

        return new Envelope(subject: $sujet);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abonnement_expiry',
            with: [
                'jours'        => $this->jours,
                'niveau_label' => $this->abonnement->niveau?->label ?? 'votre abonnement',
                'expire_at'    => $this->abonnement->timestamp_expiration?->translatedFormat('d F Y'),
                'app_url'      => config('payment.frontend_url', config('app.url')),
            ],
        );
    }
}
