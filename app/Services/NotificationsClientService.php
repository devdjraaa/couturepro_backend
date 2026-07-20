<?php

namespace App\Services;

use App\Jobs\SendGxtCommandeEmail;
use App\Models\Commande;
use App\Models\GxtClient;
use App\Models\NotificationClient;
use App\Support\MessagesCommandeClient;

/**
 * Pt 24 — Prévenir le client final : dans l'application ET par e-mail, d'un
 * seul geste.
 *
 * Le client était déjà notifié par e-mail à chaque avancée de sa commande.
 * Mais un e-mail se perd, part en indésirable ou n'est pas relevé — et le
 * client revient alors sur son espace sans rien y trouver qui lui dise où en
 * est sa commande. C'est précisément le moment où il écrit au créateur.
 *
 * Les deux canaux partent d'ICI, jamais séparément : deux appels distincts
 * dans le code appelant, c'est la garantie qu'un jour l'un des deux soit
 * oublié à un endroit.
 */
class NotificationsClientService
{
    /**
     * Notifie le client d'une commande, si elle en a un.
     *
     * Rend `false` quand il n'y a personne à prévenir (commande saisie par
     * l'atelier, sans client Gextimo rattaché) : le cas est normal et ne doit
     * ni échouer ni être traité comme une erreur par l'appelant.
     */
    public function pourCommande(Commande $commande, string $evenement): bool
    {
        if (! $commande->gxt_client_id) {
            return false;
        }

        $client = GxtClient::find($commande->gxt_client_id);
        if (! $client) {
            return false;
        }

        $designer = $commande->atelier?->nom ?: 'votre designer';
        $msg = MessagesCommandeClient::pour($evenement, (string) $commande->reference, $designer);

        NotificationClient::create([
            'gxt_client_id' => $client->id,
            'type'          => $evenement,
            'titre'         => $msg['titre'],
            'contenu'       => $msg['resume'],
            // Ramène le client là où l'information est : son espace, pas une
            // page de suivi anonyme qu'il faudrait retrouver.
            'lien'          => '/espace-client',
            'sujet_type'    => 'commande',
            'sujet_id'      => $commande->id,
        ]);

        // L'e-mail reste le canal de rappel : tout le monde ne rouvre pas
        // l'application. Même texte, puisqu'il vient de la même source.
        if ($client->email) {
            SendGxtCommandeEmail::dispatch($client->email, $evenement, (string) $commande->reference, $designer);
        }

        return true;
    }

    /** Notification libre (hors commande) — avis publié, message de l'équipe… */
    public function envoyer(GxtClient $client, string $type, string $titre, ?string $contenu = null, ?string $lien = null): NotificationClient
    {
        return NotificationClient::create([
            'gxt_client_id' => $client->id,
            'type'          => $type,
            'titre'         => $titre,
            'contenu'       => $contenu,
            'lien'          => $lien,
        ]);
    }
}
