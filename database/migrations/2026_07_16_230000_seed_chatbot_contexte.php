<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Chatbot v2 (IA locale Ollama) : base de connaissances Gextimo injectée dans le contexte
// du modèle quand aucune intention ne matche. Stockée en base (vitrine_settings) → la
// direction/l'admin peut la corriger/enrichir SANS toucher au code (PUT admin/chatbot/contexte).
return new class extends Migration
{
    public function up(): void
    {
        $contexte = <<<'TXT'
GEXTIMO — plateforme de NOVAFRIQ GROUPE (Sèmè-Podji, Bénin) qui relie les clients aux créateurs de mode africains, et donne aux professionnels une application complète de gestion d'atelier.

SITE VITRINE (gextimo.novafriq.africa) : découvrir les créateurs (/createurs), parcourir la galerie de créations, commander une tenue sur mesure, suivre sa commande (/suivi), espace client (/espace-client), favoris, tarifs pros (/premium), mise en avant (/mise-en-avant), aide (/aide).

COMPTES PROFESSIONNELS (via l'application mobile, téléchargeable sur /inscription — l'inscription pro ne se fait PAS sur le site web) :
- ARTISAN : gestion d'atelier (clients, mesures, commandes, équipe, caisse, factures), 1 atelier, pas de vitrine publique.
- DESIGNER : tout l'artisan + profil public sur la vitrine, multi-ateliers, outils créatifs, vente de patrons.

ESPACE CLIENT (acheteurs) : connexion SANS mot de passe — e-mail + code à 6 chiffres reçu par e-mail (valable 10 minutes), ou connexion Google. On y suit ses commandes, on laisse des avis, on signale un problème, on met à jour son profil.

COMMANDER UNE TENUE : ouvrir le profil d'un créateur → bouton « Commander » → décrire la tenue (tissu, occasion, délai, budget) → le designer recontacte le client pour préciser détails, mesures et prix. Chaque commande a une référence GEX-XXXXXX.

SUIVI DE COMMANDE : étapes = reçue → coupe → confection → essayage → livraison. Le client reçoit un e-mail à chaque étape. Suivi via l'espace client ou la page /suivi avec la référence.

PAIEMENT : pour l'instant le paiement se règle directement entre le client et le créateur (Gextimo met en relation). Le paiement en ligne intégré arrivera plus tard sur la plateforme.

AVIS : possibles uniquement après livraison, depuis l'espace client (un avis par commande, modéré avant publication).

RÉCLAMATIONS : espace client → la commande → « Signaler un problème » → le designer ET l'équipe Gextimo sont prévenus immédiatement.

PATRONS : certains créateurs vendent leurs patrons en téléchargement (bouton « Télécharger » sur la création, paiement sécurisé). Récupération à tout moment avec le code de transaction sur /patrons/recuperer.

TARIFS PROS : l'inscription est gratuite. Plan Gratuit designer : 5 publications et 10 clients facturés par période. Plans payants (Standard ≈ 2 500 XOF/mois, Premium ≈ 5 000 XOF/mois, moins cher en annuel) pour plus de visibilité et de fonctionnalités. Détails et prix à jour : page /premium.

CONTACT : support.gextimo@novafriq.africa (réponse sous 48 h). Les professionnels ont aussi un canal Tickets dans leur application. Site du groupe : novafriq.africa.
TXT;

        DB::table('vitrine_settings')->updateOrInsert(
            ['cle' => 'chatbot_contexte'],
            [
                'id'         => DB::table('vitrine_settings')->where('cle', 'chatbot_contexte')->value('id') ?? (string) Str::uuid(),
                'valeur'     => json_encode(['texte' => $contexte]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('vitrine_settings')->where('cle', 'chatbot_contexte')->delete();
    }
};
