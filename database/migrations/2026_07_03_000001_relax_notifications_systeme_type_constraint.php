<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Le `type` d'une NotificationSysteme est un enum applicatif qui évolue (commande_cree,
    // client_cree, statut_commande, points_convertis, sponsorisation, abonnement_active, …) et
    // n'est JAMAIS renseigné par une entrée utilisateur (uniquement par le code serveur).
    // La contrainte CHECK, restée désynchronisée du code, a provoqué des 500 en production
    // (connexion, création de commande/client…). On la retire : la validation de cet enum se fait
    // au niveau applicatif, pas en base.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications_systeme DROP CONSTRAINT IF EXISTS notifications_systeme_type_check');
        } else {
            // MySQL : ENUM → VARCHAR pour accepter tous les types applicatifs.
            DB::statement("ALTER TABLE notifications_systeme MODIFY COLUMN type VARCHAR(50) NOT NULL");
        }
    }

    public function down(): void
    {
        // Réversibilité : on restaure la contrainte avec l'ensemble complet des types connus.
        $types = "'promo','mise_a_jour','alerte_sync','alerte_abonnement','info','alerte_archive',"
            . "'abonnement_active','atelier_verrouille','bienvenue_plan','bonus_admin','client_cree',"
            . "'commande_cree','conversion','points_convertis','sponsorisation','statut_commande'";

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications_systeme DROP CONSTRAINT IF EXISTS notifications_systeme_type_check');
            DB::statement("ALTER TABLE notifications_systeme ADD CONSTRAINT notifications_systeme_type_check CHECK (type IN ($types))");
        } else {
            DB::statement("ALTER TABLE notifications_systeme MODIFY COLUMN type ENUM($types) NOT NULL");
        }
    }
};
