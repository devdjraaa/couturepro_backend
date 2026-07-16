<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Brief 16/07 (point 1) : chatbot d'assistance vitrine + mémoire des échanges.
// v1 = moteur d'intentions CONFIGURABLE EN BASE (mots-clés → réponse, éditable admin,
// zéro hardcoding). L'architecture permet de brancher un LLM plus tard sans refonte.
return new class extends Migration
{
    public function up(): void
    {
        // Conversations (anonyme via session_id, ou rattachée au client connecté).
        Schema::create('gxt_chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gxt_client_id')->nullable()->index();
            $table->string('session_id', 100)->index();
            $table->string('langue', 10)->default('fr');
            $table->timestamps();
        });

        // Chaque message utilisateur + la réponse donnée + l'intention détectée + le feedback.
        Schema::create('gxt_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('gxt_chat_conversations')->cascadeOnDelete();
            $table->text('question');
            $table->text('reponse');
            $table->string('intention', 60)->index();  // code intent ou 'fallback'
            $table->boolean('utile')->nullable();      // feedback utile/inutile (null = pas noté)
            $table->timestamp('created_at')->useCurrent();
        });

        // Base de connaissances : une intention = mots-clés + réponses fr/en (éditable admin).
        Schema::create('gxt_chat_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 60)->unique();
            $table->json('mots_cles');                 // ["prix","tarif","abonnement",…]
            $table->text('reponse_fr');
            $table->text('reponse_en');
            $table->unsignedInteger('priorite')->default(0); // départage en cas d'égalité
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        $this->seedIntents();
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_chat_messages');
        Schema::dropIfExists('gxt_chat_conversations');
        Schema::dropIfExists('gxt_chat_intents');
    }

    /** Base de connaissances initiale (modifiable ensuite via l'admin, jamais figée en code). */
    private function seedIntents(): void
    {
        $intents = [
            ['salutation', ['bonjour', 'salut', 'bonsoir', 'hello', 'coucou', 'hey'],
                "Bonjour ! Je suis l'assistant Gextimo. Posez-moi votre question : commandes, suivi, tarifs, devenir designer…",
                "Hello! I'm the Gextimo assistant. Ask me anything: orders, tracking, pricing, becoming a designer…", 1],
            ['commander', ['commander', 'commande', 'acheter', 'passer commande', 'order', 'achat'],
                "Pour commander : ouvrez le profil d'un créateur, cliquez « Commander », décrivez votre tenue (tissu, occasion, délai) — le designer vous recontacte pour les détails et le prix. Vous suivez ensuite chaque étape depuis votre espace client.",
                "To order: open a designer's profile, click “Order”, describe your outfit (fabric, occasion, deadline) — the designer will contact you to confirm details and price. You can then track every step from your account.", 5],
            ['suivi', ['suivi', 'suivre', 'où en est', 'avancement', 'étape', 'tracking', 'track', 'statut'],
                // priorité 6 : départage face à « commander » quand les deux matchent (ex. "track my order")
                "Suivez votre commande dans votre espace client (menu « Espace client ») ou via la page Suivi avec votre référence GEX-XXXXXX. Vous recevez aussi un e-mail à chaque étape (coupe, confection, essayage, livraison).",
                "Track your order in your account (“My account” menu) or on the Tracking page with your GEX-XXXXXX reference. You also get an e-mail at every step (cutting, sewing, fitting, delivery).", 6],
            ['tarifs', ['prix', 'tarif', 'coût', 'cout', 'combien', 'abonnement', 'plan', 'gratuit', 'premium', 'price', 'pricing'],
                "Les prix des tenues sont fixés par chaque créateur (souvent sur devis). Pour les créateurs/artisans : l'inscription est gratuite, avec des plans payants pour plus de visibilité — détails sur la page Tarifs (/premium).",
                "Outfit prices are set by each designer (often on quote). For designers/artisans: signing up is free, with paid plans for more visibility — see the Pricing page (/premium).", 4],
            ['devenir_designer', ['devenir', 'inscrire', 'inscription', 'designer', 'créateur', 'createur', 'artisan', 'vendre', 'atelier', 'rejoindre', 'sign up', 'register'],
                "Pour rejoindre Gextimo comme créateur ou artisan : téléchargez l'application (page /inscription), créez votre compte en 2 minutes et publiez vos créations. La vitrine web est réservée à la découverte et aux commandes.",
                "To join Gextimo as a designer or artisan: download the app (/inscription page), create your account in 2 minutes and publish your creations. The website is for discovering designers and ordering.", 4],
            ['paiement', ['paiement', 'payer', 'payement', 'momo', 'mobile money', 'espèces', 'fedapay', 'payment', 'pay'],
                "Aujourd'hui, le paiement se règle directement entre vous et le créateur (mise en relation). Le paiement en ligne intégré arrivera prochainement sur la plateforme.",
                "For now, payment is arranged directly between you and the designer. Integrated online payment is coming soon to the platform.", 3],
            ['delais', ['délai', 'delai', 'temps', 'durée', 'duree', 'livraison', 'livré', 'livre', 'quand', 'delivery', 'deadline'],
                "Les délais dépendent de la tenue et du créateur : ils sont convenus ensemble à la commande. Vous êtes notifié par e-mail à chaque étape jusqu'à la livraison.",
                "Deadlines depend on the outfit and the designer: you agree on them when ordering. You're notified by e-mail at every step until delivery.", 3],
            ['compte', ['compte', 'connexion', 'connecter', 'code', 'otp', 'mot de passe', 'email', 'login', 'account'],
                "L'espace client fonctionne sans mot de passe : entrez votre e-mail, recevez un code à 6 chiffres, et c'est tout. Vous pouvez aussi vous connecter avec Google. Rendez-vous sur « Espace client ».",
                "Your account works without a password: enter your e-mail, receive a 6-digit code, done. You can also sign in with Google. Go to “My account”.", 3],
            ['probleme', ['problème', 'probleme', 'réclamation', 'reclamation', 'plainte', 'erreur', 'bug', 'insatisfait', 'retard', 'issue', 'complaint'],
                "Désolé pour ce souci. Ouvrez votre espace client → votre commande → « Signaler un problème » : le designer ET l'équipe Gextimo sont prévenus immédiatement et vous répondent vite.",
                "Sorry about that. Open your account → your order → “Report an issue”: the designer AND the Gextimo team are notified immediately and will get back to you quickly.", 4],
            ['contact', ['contact', 'contacter', 'joindre', 'humain', 'conseiller', 'support', 'aide', 'help', 'whatsapp'],
                "Vous pouvez écrire à support.gextimo@novafriq.africa — nous répondons sous 48 h. Les créateurs ont aussi un canal Tickets dans leur application.",
                "You can write to support.gextimo@novafriq.africa — we reply within 48 h. Designers also have a Tickets channel in their app.", 3],
            ['patrons', ['patron', 'patrons', 'télécharger', 'telecharger', 'pdf', 'download', 'pattern'],
                "Certains créateurs vendent leurs patrons en téléchargement : bouton « Télécharger » sur la création, paiement sécurisé, puis récupération à tout moment avec votre code sur /patrons/recuperer.",
                "Some designers sell downloadable patterns: “Download” button on the creation, secure payment, then retrieve anytime with your code at /patrons/recuperer.", 2],
            ['merci', ['merci', 'thanks', 'thank', 'super', 'parfait', 'ok'],
                "Avec plaisir ! N'hésitez pas si vous avez une autre question. Belle journée !",
                "You're welcome! Feel free to ask anything else. Have a great day!", 1],
        ];

        $now = now();
        foreach ($intents as [$code, $mots, $fr, $en, $prio]) {
            DB::table('gxt_chat_intents')->insert([
                'id' => (string) Str::uuid(), 'code' => $code,
                'mots_cles' => json_encode($mots), 'reponse_fr' => $fr, 'reponse_en' => $en,
                'priorite' => $prio, 'actif' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
};
