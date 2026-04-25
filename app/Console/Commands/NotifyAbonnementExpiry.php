<?php

namespace App\Console\Commands;

use App\Mail\AbonnementExpiryMail;
use App\Models\Abonnement;
use App\Models\NotificationSysteme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyAbonnementExpiry extends Command
{
    protected $signature   = 'abonnements:notify-expiry';
    protected $description = 'Envoie des notifications (app + email) pour les abonnements qui expirent dans 7, 3 ou 1 jour(s).';

    private array $seuils = [7, 3, 1];

    public function handle(): int
    {
        foreach ($this->seuils as $jours) {
            $abonnements = Abonnement::with(['atelier.proprietaire', 'niveau'])
                ->whereIn('statut', ['actif', 'essai'])
                ->whereBetween('timestamp_expiration', [now(), now()->addDays($jours)->endOfDay()])
                ->get();

            foreach ($abonnements as $abonnement) {
                if ($this->dejaNotifie($abonnement->atelier_id)) {
                    continue;
                }

                $this->creerNotificationApp($abonnement, $jours);
                $this->envoyerEmail($abonnement, $jours);

                $this->line("  ✓ Atelier {$abonnement->atelier_id} notifié ({$jours} j restants)");
            }
        }

        $this->info('Notifications d\'expiration traitées.');
        return self::SUCCESS;
    }

    private function dejaNotifie(string $atelierId): bool
    {
        return NotificationSysteme::where('atelier_id', $atelierId)
            ->where('type', 'alerte_abonnement')
            ->where('created_at', '>=', now()->subHours(20))
            ->exists();
    }

    private function creerNotificationApp(Abonnement $abonnement, int $jours): void
    {
        $label = $jours === 1 ? 'demain' : "dans {$jours} jours";

        NotificationSysteme::create([
            'atelier_id' => $abonnement->atelier_id,
            'titre'      => 'Abonnement bientôt expiré',
            'contenu'    => "Votre abonnement {$abonnement->niveau?->label} expire {$label}. Renouvelez-le pour maintenir votre accès.",
            'type'       => 'alerte_abonnement',
            'is_read'    => false,
        ]);
    }

    private function envoyerEmail(Abonnement $abonnement, int $jours): void
    {
        $proprietaire = $abonnement->atelier?->proprietaire;

        if (!$proprietaire?->email) {
            return;
        }

        try {
            Mail::to($proprietaire->email)->send(new AbonnementExpiryMail($abonnement, $jours));
        } catch (\Throwable) {
            // Ne pas bloquer le traitement si l'email échoue
        }
    }
}
