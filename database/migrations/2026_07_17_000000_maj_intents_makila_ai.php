<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Charte Makila AI (validée par Pat, 17/07) : nom officiel de l'assistant + textes
// réécrits selon le ton de voix (chaleureux, précis, vouvoiement, action concrète
// en fin de réponse) et SANS tiret long « — » (demande explicite : les tirets font
// « message d'IA », pas « mot de l'équipe »).
return new class extends Migration
{
    public function up(): void
    {
        $textes = [
            'salutation' => [
                "Bonjour ! Je suis Makila AI, votre assistant dédié à la mode, la couture et la création textile africaines. Posez-moi votre question : commandes, suivi, tarifs, devenir designer… Comment puis-je vous accompagner aujourd'hui ?",
                "Hello! I'm Makila AI, your assistant for African fashion, tailoring and textile creation. Ask me anything: orders, tracking, pricing, becoming a designer… How can I help you today?",
            ],
            'commander' => [
                "Pour commander : ouvrez le profil d'un créateur, cliquez sur « Commander », puis décrivez votre tenue (tissu, occasion, délai). Le designer vous recontacte pour préciser les détails et le prix. Vous suivez ensuite chaque étape depuis votre espace client. Souhaitez-vous découvrir nos créateurs ?",
                "To order: open a designer's profile, click \"Order\", then describe your outfit (fabric, occasion, deadline). The designer will contact you to confirm details and price. You can then follow every step from your account. Would you like to discover our designers?",
            ],
            'suivi' => [
                "Suivez votre commande depuis votre espace client (menu « Espace client ») ou sur la page Suivi avec votre référence GEX-XXXXXX. Vous recevez aussi un e-mail à chaque étape : coupe, confection, essayage, livraison.",
                "Track your order from your account (\"My account\" menu) or on the Tracking page with your GEX-XXXXXX reference. You also receive an e-mail at every step: cutting, sewing, fitting, delivery.",
            ],
            'tarifs' => [
                "Les prix des tenues sont fixés par chaque créateur, souvent sur devis. Pour les créateurs et artisans, l'inscription est gratuite, avec des plans payants pour gagner en visibilité. Retrouvez le détail sur la page Tarifs (/premium).",
                "Outfit prices are set by each designer, often on quote. For designers and artisans, signing up is free, with paid plans for more visibility. See the details on the Pricing page (/premium).",
            ],
            'devenir_designer' => [
                "Pour rejoindre Gextimo comme créateur ou artisan : téléchargez l'application depuis la page /inscription, créez votre compte en deux minutes et publiez vos créations. Le site web sert à découvrir les créateurs et à commander.",
                "To join Gextimo as a designer or artisan: download the app from the /inscription page, create your account in two minutes and publish your creations. The website is for discovering designers and ordering.",
            ],
            'paiement' => [
                "Pour le moment, le paiement se règle directement entre vous et le créateur : Gextimo assure la mise en relation. Le paiement en ligne intégré arrivera prochainement sur la plateforme.",
                "For now, payment is arranged directly between you and the designer: Gextimo connects you. Integrated online payment is coming soon to the platform.",
            ],
            'delais' => [
                "Les délais dépendent de la tenue et du créateur : vous les convenez ensemble au moment de la commande. Vous êtes ensuite informé par e-mail à chaque étape, jusqu'à la livraison.",
                "Deadlines depend on the outfit and the designer: you agree on them together when ordering. You are then notified by e-mail at every step, until delivery.",
            ],
            'compte' => [
                "Votre espace client fonctionne sans mot de passe : entrez votre e-mail, recevez un code à 6 chiffres, et c'est tout. Vous pouvez aussi vous connecter avec Google. Rendez-vous dans « Espace client ».",
                "Your account works without a password: enter your e-mail, receive a 6-digit code, and that's it. You can also sign in with Google. Go to \"My account\".",
            ],
            'probleme' => [
                "Désolé pour ce désagrément. Ouvrez votre espace client, puis votre commande, et cliquez sur « Signaler un problème » : le designer et l'équipe Gextimo sont prévenus immédiatement et vous répondent rapidement.",
                "Sorry about that. Open your account, then your order, and click \"Report an issue\": the designer and the Gextimo team are notified immediately and will get back to you quickly.",
            ],
            'contact' => [
                "Vous pouvez écrire à support.gextimo@novafriq.africa, nous répondons sous 48 heures. Les créateurs disposent aussi d'un canal Tickets dans leur application.",
                "You can write to support.gextimo@novafriq.africa, we reply within 48 hours. Designers also have a Tickets channel in their app.",
            ],
            'patrons' => [
                "Certains créateurs vendent leurs patrons en téléchargement : bouton « Télécharger » sur la création, paiement sécurisé, puis récupération à tout moment avec votre code sur la page /patrons/recuperer.",
                "Some designers sell downloadable patterns: \"Download\" button on the creation, secure payment, then retrieve anytime with your code on the /patrons/recuperer page.",
            ],
            'merci' => [
                "Avec plaisir ! N'hésitez pas si vous avez une autre question. Belle journée !",
                "You're welcome! Feel free to ask anything else. Have a great day!",
            ],
        ];

        foreach ($textes as $code => [$fr, $en]) {
            DB::table('gxt_chat_intents')->where('code', $code)
                ->update(['reponse_fr' => $fr, 'reponse_en' => $en, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Textes précédents non restaurés (contenu éditorial, modifiable via l'admin).
    }
};
