<?php

namespace App\Support;

/**
 * Pt 24 — Source UNIQUE des messages envoyés au client sur sa commande.
 *
 * Ces textes vivaient dans le job d'e-mail. En ajoutant les notifications
 * dans l'application, il aurait été naturel d'en réécrire une seconde version —
 * et c'est exactement ce qui s'était produit pour le partage WhatsApp (L2-WA) :
 * deux constructeurs de message concurrents produisaient des contenus
 * différents selon le chemin emprunté, sans que personne s'en aperçoive.
 *
 * Le client reçoit donc rigoureusement le même message par e-mail et dans
 * l'application. `titre` sert de sujet d'e-mail ET de titre de notification ;
 * `corps` est le texte long, `resume` la version courte affichée en liste.
 */
class MessagesCommandeClient
{
    /** Événements reconnus. Un événement inconnu retombe sur un message neutre. */
    public const EVENEMENTS = [
        'recue', 'coupe', 'confection', 'essayage', 'livraison', 'livree', 'reclamation_recue',
    ];

    private const PIED = "\n\nSuivez votre commande à tout moment : https://gextimo.novafriq.africa/suivi"
        . "\n— L'équipe Gextimo";

    /**
     * @return array{titre:string, corps:string, resume:string}
     */
    public static function pour(string $evenement, string $reference, string $designer): array
    {
        $ref = $reference;
        $d = $designer;

        [$titre, $resume] = match ($evenement) {
            'recue' => ["Commande {$ref} bien reçue",
                "Votre commande a bien été reçue par {$d}. Vous serez informé(e) à chaque étape."],
            'coupe' => ["Commande {$ref} : la coupe a commencé",
                "Bonne nouvelle : {$d} a commencé la coupe de votre tenue."],
            'confection' => ["Commande {$ref} : en confection",
                "Votre création est en cours de confection chez {$d}."],
            'essayage' => ["Commande {$ref} : prête pour l'essayage",
                "Votre commande est prête pour l'essayage. {$d} vous attend !"],
            'livraison' => ["Commande {$ref} : en cours de livraison",
                "Votre commande est prête et en cours de livraison."],
            'livree' => ["Commande {$ref} livrée — votre avis compte",
                "Votre commande a été livrée. Satisfait(e) ? Laissez un avis à {$d} depuis votre espace client."],
            'reclamation_recue' => ["Réclamation reçue — commande {$ref}",
                "Nous avons bien reçu votre réclamation. {$d} et l'équipe Gextimo ont été prévenus et reviennent vers vous rapidement."],
            default => ["Commande {$ref} : mise à jour",
                "Votre commande chez {$d} vient d'être mise à jour."],
        };

        return [
            'titre'  => $titre,
            'resume' => $resume,
            'corps'  => "Bonjour,\n\n{$resume}" . self::PIED,
        ];
    }
}
