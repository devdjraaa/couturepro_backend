# COUTURE PRO — Étude Technique Base de Données
## Blueprint Complet — Migrations, Modèles Laravel 13 & Schémas WatermelonDB

> **Fichier cible :** `docs/backend_database_blueprint.md` dans le projet
> **Version :** 3.0 — Intègre l'espace Admin, la gestion dynamique des plans, l'architecture Offline-First complète, et le système de paiement provider-agnostic avec webhook callback
> **Usage :** Coller ce fichier dans une conversation Claude et demander :
> *"Génère toutes les migrations Laravel 13, les modèles Eloquent et les schémas WatermelonDB basés sur ce blueprint."*

---

## 1. Contexte & Décisions d'Architecture

- **Laravel 13 / PHP 8.3+** — backend API REST sur VPS
- **MySQL** (sur VPS) — source de vérité absolue côté serveur
- **SQLite** (WatermelonDB SQLiteAdapter Phase 2 / LokiJSAdapter Phase 1) — base locale sur l'appareil mobile (Capacitor)
- **UUID v4** comme clé primaire partout (sauf tables de config statique et tables append-only)
- **Soft deletes** sur toutes les tables métier critiques
- **JSON columns** pour données flexibles (mesures, libellés, config plans) — MySQL 5.7+ supporte JSON nativement
- **Sanctum** pour l'auth : les `Proprietaire` ET les `EquipeMembre` utilisent `HasApiTokens` (morphable). Les `Admin` ont leur propre guard séparé.
- **Sécurité = Laravel Gates + Policies** — pas de RLS base de données côté MySQL, tout passe par le backend
- **Paiement provider-agnostic** = interface `PaymentProviderContract` + champ `provider` + JSON `provider_metadata`. Brancher FedaPay, Kkiapay, CinetPay, PayDunya, Stripe ou tout autre sans migration. Deux canaux d'activation : automatique (webhook callback après paiement) et manuel (code admin pour cas spéciaux)
- **Pas de sync temps réel** — tout est batch différé (20 opérations max par batch)
- **Photos (profil client, tissu commande)** = stockage LOCAL uniquement, jamais en base serveur
- **Photos VIP (album modèles)** = Cloudflare R2, Phase 3 — table prête dès Phase 1
- **Historique actions** = LOCAL uniquement, pas de table serveur
- **sync_queue** = LOCAL uniquement (WatermelonDB), pas de table serveur
- **config_snapshot** = JSON dans `abonnements` — permet à l'app de connaître ses limites et features en offline total
- **Plans d'abonnement dynamiques** = l'admin crée/modifie les plans depuis son panel sans toucher au code, via un catalogue de fonctionnalités (`fonctionnalites`) et un `config` JSON sur `niveaux_config`
- **Espace Admin** = 2 rôles (`super_admin`, `admin`), permissions granulaires JSON gérées par le super_admin

---

## 2. Changements sur le Projet Vierge

### Fichiers à SUPPRIMER
```
database/migrations/0001_01_01_000000_create_users_table.php
app/Models/User.php
```

### Fichiers à GARDER
```
database/migrations/0001_01_01_000001_create_cache_table.php
database/migrations/0001_01_01_000002_create_jobs_table.php
```

### Modifications config/auth.php
```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'proprietaires'],
    'admin' => ['driver' => 'sanctum', 'provider' => 'admins'],
],
'providers' => [
    'proprietaires' => [
        'driver' => 'eloquent',
        'model' => App\Models\Proprietaire::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
],
```

### Packages à installer
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## 3. Ordre des Migrations (respecter les FK)

```
001 — create_niveaux_config_table
002 — create_proprietaires_table              (remplace users)
003 — create_ateliers_table                   (FK -> proprietaires)
004 — create_abonnements_table                (FK -> ateliers)
005 — create_paiements_table                  (FK -> ateliers, niveaux_config)
006 — create_transactions_abonnement_table    (FK -> ateliers nullable, paiements nullable)
007 — create_equipe_membres_table             (FK -> ateliers, proprietaires)
008 — create_parametres_atelier_table         (FK -> ateliers)
009 — create_communications_config_table      (FK -> ateliers)
010 — create_points_fidelite_table            (FK -> ateliers)
011 — create_points_historique_table          (FK -> ateliers)
012 — create_notifications_systeme_table      (FK -> ateliers nullable)
013 — create_vetements_table                  (FK -> ateliers nullable)
014 — create_clients_table                    (FK -> ateliers)
015 — create_mesures_table                    (FK -> clients, vetements, ateliers)
016 — create_commandes_table                  (FK -> ateliers, clients, vetements)
017 — create_quotas_mensuels_table            (FK -> ateliers)
018 — create_photos_vip_table                 (FK -> ateliers)
019 — create_otp_tokens_table                 (sans FK)
020 — create_demandes_recuperation_table      (sans FK)

--- ESPACE ADMIN ---
021 — create_fonctionnalites_table            (sans FK)
022 — create_admins_table                     (sans FK)
023 — alter_niveaux_config_add_admin_columns  (FK -> admins)
024 — alter_paiements_add_admin_columns       (FK -> admins — ajoute validated_by)
025 — create_tickets_support_table            (FK -> ateliers, proprietaires, admins)
026 — create_tickets_messages_table           (FK -> tickets_support)
027 — create_admin_audit_log_table            (FK -> admins)
028 — create_niveaux_config_changelog_table   (FK -> admins)
029 — create_offres_speciales_table           (FK -> ateliers, admins, niveaux_config)
030 — create_liste_noire_table                (FK -> admins)
```

---

## 4. Tables Serveur — Définitions Complètes

---

### TABLE: `niveaux_config`
**But :** Plans d'abonnement avec leurs limites et fonctionnalités. Entièrement géré depuis le panel admin — l'admin peut créer, modifier et désactiver des plans sans toucher au code. Le `config` JSON stocke toutes les valeurs de limites et features en un seul champ flexible.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | bigIncrements | PK | Auto-increment |
| cle | string(50) | UNIQUE NOT NULL | `'standard_mensuel'`, `'premium_annuel'`, etc. |
| label | string(100) | NOT NULL | `'Premium Mensuel'` |
| duree_jours | smallInteger | NOT NULL | 31 ou 365 |
| prix_xof | decimal(10,2) | NOT NULL DEFAULT 0 | Prix officiel en FCFA |
| prix_mensuel_equivalent_xof | decimal(10,2) | NULLABLE | Pour calcul MRR — annuels ÷ 12 |
| config | json | NOT NULL | Toutes les limites + features (voir structure ci-dessous) |
| is_actif | boolean | NOT NULL DEFAULT true | `false` = plan masqué dans l'app, plus proposé aux nouveaux |
| ordre_affichage | tinyInteger | NOT NULL DEFAULT 0 | Ordre d'affichage dans l'app |
| description_courte | string(255) | NULLABLE | Tagline affichée dans l'app |
| updated_by | uuid | NULLABLE FK admins.id | Dernier admin ayant modifié ce plan |
| timestamps | | | |

**Structure JSON `config` — les clés correspondent exactement aux `cle` de la table `fonctionnalites` :**
```json
{
  "max_assistants": 1,
  "max_membres": 3,
  "max_clients_par_mois": 100,
  "max_photos_vip_par_mois": 5,
  "max_factures_par_mois": 25,
  "pts_par_client": 1,
  "pts_par_commande": 1,
  "pts_activation": 31,
  "seuil_conversion_pts": 45000,
  "photos_vip": true,
  "facture_whatsapp": true,
  "sauvegarde_auto": false,
  "module_caisse": false,
  "multi_ateliers": false
}
```

**Migration PHP — création initiale :**
```php
Schema::create('niveaux_config', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('cle', 50)->unique();
    $table->string('label', 100);
    $table->smallInteger('duree_jours');
    $table->decimal('prix_xof', 10, 2)->default(0);
    $table->decimal('prix_mensuel_equivalent_xof', 10, 2)->nullable();
    $table->json('config');
    $table->boolean('is_actif')->default(true);
    $table->tinyInteger('ordre_affichage')->default(0);
    $table->string('description_courte', 255)->nullable();
    $table->timestamps();
    // updated_by ajouté en migration 022 après création de la table admins
});
```

**Seeder — NiveauxConfigSeeder (6 plans du CDC intacts) :**
```php
[
  [
    'cle' => 'standard_mensuel', 'label' => 'Standard Mensuel',
    'duree_jours' => 31, 'prix_xof' => 3500, 'prix_mensuel_equivalent_xof' => 3500,
    'ordre_affichage' => 1, 'description_courte' => 'Idéal pour démarrer',
    'config' => json_encode([
      'max_assistants' => 0, 'max_membres' => 0, 'max_clients_par_mois' => 50,
      'max_photos_vip_par_mois' => null, 'max_factures_par_mois' => 0,
      'pts_par_client' => 1, 'pts_par_commande' => 1, 'pts_activation' => 31,
      'seuil_conversion_pts' => 10000,
      'photos_vip' => false, 'facture_whatsapp' => false,
      'sauvegarde_auto' => false, 'module_caisse' => false, 'multi_ateliers' => false,
    ]),
  ],
  [
    'cle' => 'standard_annuel', 'label' => 'Standard Annuel',
    'duree_jours' => 365, 'prix_xof' => 35000, 'prix_mensuel_equivalent_xof' => 2917,
    'ordre_affichage' => 2, 'description_courte' => 'Économisez avec l\'annuel',
    'config' => json_encode([
      'max_assistants' => 1, 'max_membres' => 1, 'max_clients_par_mois' => 80,
      'max_photos_vip_par_mois' => null, 'max_factures_par_mois' => 0,
      'pts_par_client' => 1, 'pts_par_commande' => 1, 'pts_activation' => 365,
      'seuil_conversion_pts' => 10000,
      'photos_vip' => false, 'facture_whatsapp' => false,
      'sauvegarde_auto' => false, 'module_caisse' => false, 'multi_ateliers' => false,
    ]),
  ],
  [
    'cle' => 'premium_mensuel', 'label' => 'Premium Mensuel',
    'duree_jours' => 31, 'prix_xof' => 7500, 'prix_mensuel_equivalent_xof' => 7500,
    'ordre_affichage' => 3, 'description_courte' => 'Le plus populaire',
    'config' => json_encode([
      'max_assistants' => 1, 'max_membres' => 3, 'max_clients_par_mois' => 100,
      'max_photos_vip_par_mois' => 5, 'max_factures_par_mois' => 25,
      'pts_par_client' => 1, 'pts_par_commande' => 1, 'pts_activation' => 31,
      'seuil_conversion_pts' => 45000,
      'photos_vip' => true, 'facture_whatsapp' => true,
      'sauvegarde_auto' => false, 'module_caisse' => true, 'multi_ateliers' => false,
    ]),
  ],
  [
    'cle' => 'premium_annuel', 'label' => 'Premium Annuel',
    'duree_jours' => 365, 'prix_xof' => 75000, 'prix_mensuel_equivalent_xof' => 6250,
    'ordre_affichage' => 4, 'description_courte' => 'Premium avec sauvegarde auto',
    'config' => json_encode([
      'max_assistants' => 2, 'max_membres' => 5, 'max_clients_par_mois' => 150,
      'max_photos_vip_par_mois' => 15, 'max_factures_par_mois' => null,
      'pts_par_client' => 2, 'pts_par_commande' => 2, 'pts_activation' => 365,
      'seuil_conversion_pts' => 45000,
      'photos_vip' => true, 'facture_whatsapp' => true,
      'sauvegarde_auto' => true, 'module_caisse' => true, 'multi_ateliers' => false,
    ]),
  ],
  [
    'cle' => 'magnat_mensuel', 'label' => 'Magnat Mensuel',
    'duree_jours' => 31, 'prix_xof' => 15000, 'prix_mensuel_equivalent_xof' => 15000,
    'ordre_affichage' => 5, 'description_courte' => 'Pour les grands ateliers',
    'config' => json_encode([
      'max_assistants' => 2, 'max_membres' => 5, 'max_clients_par_mois' => 300,
      'max_photos_vip_par_mois' => 15, 'max_factures_par_mois' => 50,
      'pts_par_client' => 2, 'pts_par_commande' => 2, 'pts_activation' => 31,
      'seuil_conversion_pts' => 100000,
      'photos_vip' => true, 'facture_whatsapp' => true,
      'sauvegarde_auto' => true, 'module_caisse' => true, 'multi_ateliers' => false,
    ]),
  ],
  [
    'cle' => 'magnat_annuel', 'label' => 'Magnat Annuel',
    'duree_jours' => 365, 'prix_xof' => 150000, 'prix_mensuel_equivalent_xof' => 12500,
    'ordre_affichage' => 6, 'description_courte' => 'Performance maximale',
    'config' => json_encode([
      'max_assistants' => 3, 'max_membres' => 7, 'max_clients_par_mois' => 500,
      'max_photos_vip_par_mois' => 25, 'max_factures_par_mois' => null,
      'pts_par_client' => 3, 'pts_par_commande' => 3, 'pts_activation' => 365,
      'seuil_conversion_pts' => 100000,
      'photos_vip' => true, 'facture_whatsapp' => true,
      'sauvegarde_auto' => true, 'module_caisse' => true, 'multi_ateliers' => false,
    ]),
  ],
]
```

> **⚠️ Accès config dans le code backend :**
> ```php
> // Avant (colonnes séparées — OBSOLÈTE — ne pas utiliser)
> $plan->can_facture_whatsapp
> // Après (config JSON — à utiliser partout)
> $plan->config['facture_whatsapp']
> $plan->config['max_clients_par_mois']
> ```

---

### TABLE: `proprietaires`
**But :** Compte principal du Gérant. Remplace la table `users` par défaut. Utilisé par Laravel Sanctum pour l'auth principale.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| telephone | string(25) | UNIQUE NOT NULL | Identifiant pivot — jamais modifiable sauf procédure de récupération |
| email | string | UNIQUE NOT NULL | Pour reçus et récupération |
| nom | string(100) | NOT NULL | |
| prenom | string(100) | NOT NULL | |
| question_secrete | string | NOT NULL | Texte de la question |
| reponse_secrete | string | NOT NULL | bcrypt hashed |
| password | string | NOT NULL | bcrypt hashed |
| remember_token | string(100) | NULLABLE | |
| email_verified_at | timestamp | NULLABLE | |
| telephone_verified_at | timestamp | NULLABLE | |
| timestamps | | | |
| softDeletes | | | |

```php
Schema::create('proprietaires', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('telephone', 25)->unique();
    $table->string('email')->unique();
    $table->string('nom', 100);
    $table->string('prenom', 100);
    $table->string('question_secrete');
    $table->string('reponse_secrete');
    $table->string('password');
    $table->rememberToken();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('telephone_verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

**Modèle PHP :**
```php
// app/Models/Proprietaire.php
// extends Authenticatable, implements HasApiTokens (Sanctum)
// $fillable: telephone, email, nom, prenom, question_secrete, reponse_secrete, password
// $hidden: password, remember_token, reponse_secrete
// $casts: email_verified_at => datetime, telephone_verified_at => datetime, password => hashed
// Traits: HasApiTokens, HasFactory, Notifiable, SoftDeletes
// Relations: hasMany(Atelier), hasMany(EquipeMembre, 'created_by')
```

---

### TABLE: `ateliers`
**But :** Unité centrale = un atelier de couture. Un propriétaire peut en avoir 1 à 7. Le premier = maître.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| proprietaire_id | uuid | FK proprietaires.id NOT NULL | |
| nom | string(150) | NOT NULL | Nom de l'atelier |
| is_maitre | boolean | NOT NULL DEFAULT false | Premier atelier créé = true, jamais modifiable |
| statut | enum | NOT NULL DEFAULT 'essai' | `'actif'`, `'expire'`, `'essai'`, `'gele'` |
| essai_expire_at | timestamp | NULLABLE | Date fin des 14 jours d'essai |
| timestamps | | | |
| softDeletes | | | |

**Indexes :** `(proprietaire_id)`, `(statut)`

```php
Schema::create('ateliers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('proprietaire_id')->constrained('proprietaires')->cascadeOnDelete();
    $table->string('nom', 150);
    $table->boolean('is_maitre')->default(false);
    $table->enum('statut', ['actif', 'expire', 'essai', 'gele'])->default('essai');
    $table->timestamp('essai_expire_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('proprietaire_id');
    $table->index('statut');
});
```

---

### TABLE: `abonnements`
**But :** Un enregistrement par atelier. Gère le compteur principal ET le bonus (points). Le champ `config_snapshot` est la clé de l'offline-first — il contient la config complète du plan résolue (plan de base + offre spéciale si active), mise à jour à chaque sync.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | UNIQUE FK ateliers.id | Un seul abonnement par atelier |
| niveau_cle | string(50) | NOT NULL FK niveaux_config.cle | Niveau actuel |
| statut | enum | NOT NULL DEFAULT 'actif' | `'actif'`, `'expire'`, `'en_pause'` |
| jours_restants | integer | NOT NULL DEFAULT 0 | Jours restants abonnement PRINCIPAL |
| timestamp_debut | timestamp | NULLABLE | Début de la période courante |
| timestamp_expiration | timestamp | NULLABLE | Date d'expiration calculée |
| bonus_actif | boolean | NOT NULL DEFAULT false | `true` = bonus s'écoule, principal en pause |
| bonus_jours_restants | integer | NOT NULL DEFAULT 0 | Jours restants sur le bonus |
| bonus_niveau_cle | string(50) | NULLABLE | Niveau du bonus (= niveau principal) |
| bonus_timestamp_debut | timestamp | NULLABLE | Début du bonus |
| **config_snapshot** | **json** | **NULLABLE** | **Config complète résolue = plan + offre spéciale. Mise à jour à chaque sync. Utilisée par l'app en offline pour vérifier toutes les limites et features.** |
| timestamps | | | |

```php
Schema::create('abonnements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
    $table->string('niveau_cle', 50);
    $table->enum('statut', ['actif', 'expire', 'en_pause'])->default('actif');
    $table->integer('jours_restants')->default(0);
    $table->timestamp('timestamp_debut')->nullable();
    $table->timestamp('timestamp_expiration')->nullable();
    $table->boolean('bonus_actif')->default(false);
    $table->integer('bonus_jours_restants')->default(0);
    $table->string('bonus_niveau_cle', 50)->nullable();
    $table->timestamp('bonus_timestamp_debut')->nullable();
    $table->json('config_snapshot')->nullable(); // ← OFFLINE-FIRST KEY
    $table->timestamps();
    $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
});
```

> **⚠️ config_snapshot — mise à jour obligatoire :** À chaque réponse de sync envoyée à l'app, le backend doit recalculer et écrire `config_snapshot` via `AtelierLimitsService::getConfig()`. L'app ne doit JAMAIS appeler `niveaux_config` directement — elle lit toujours `abonnements.config_snapshot`.

---

### TABLE: `paiements`
**But :** Chaque transaction financière entrante (Mobile Money, Carte, etc.). Provider-agnostic — le champ `provider_metadata` JSON stocke les données spécifiques à chaque passerelle sans modifier le schéma. Le webhook du provider met à jour le statut automatiquement. Quand un paiement est confirmé, le backend génère et consomme automatiquement une `transactions_abonnement`, activant l'abonnement sans intervention humaine.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | Atelier payeur |
| niveau_cle | string(50) | NOT NULL FK niveaux_config.cle | Plan choisi au moment du paiement |
| duree_jours | smallInteger | NOT NULL | 31, 93, 186, ou 365 |
| montant | decimal(10,2) | NOT NULL | Montant attendu |
| devise | string(10) | NOT NULL DEFAULT 'XOF' | |
| provider | string(50) | NOT NULL | `'fedapay'`, `'kkiapay'`, `'cinetpay'`, `'paydunya'`, `'stripe'`, etc. |
| provider_transaction_id | string(255) | NULLABLE | ID de la transaction côté passerelle — rempli par le webhook |
| provider_metadata | json | NULLABLE | Données brutes spécifiques au provider (réponse webhook, détails Mobile Money, infos carte, etc.) |
| statut | enum | NOT NULL DEFAULT 'pending' | `'pending'`, `'completed'`, `'failed'`, `'refunded'`, `'expired'` |
| checkout_url | string(500) | NULLABLE | URL de paiement générée par le provider — l'app redirige l'utilisateur ici |
| initiated_at | timestamp | NOT NULL | Moment où l'utilisateur a cliqué "Payer" |
| webhook_received_at | timestamp | NULLABLE | Moment où le webhook du provider a répondu |
| completed_at | timestamp | NULLABLE | Moment où le paiement a été confirmé et l'abonnement activé |
| expires_at | timestamp | NULLABLE | Expiration du lien de paiement (ex: +2h après création) |
| validated_by | uuid | NULLABLE FK admins.id | Admin ayant validé manuellement (cas litigieux) — ajouté en migration 024 |
| ip_address | string(45) | NULLABLE | IP de l'utilisateur au moment de l'initiation |
| timestamps | | | |

**Indexes :** `(atelier_id)`, `(statut)`, `(provider, provider_transaction_id)`, `(expires_at)`

```php
Schema::create('paiements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->string('niveau_cle', 50);
    $table->smallInteger('duree_jours');
    $table->decimal('montant', 10, 2);
    $table->string('devise', 10)->default('XOF');
    $table->string('provider', 50);
    $table->string('provider_transaction_id', 255)->nullable();
    $table->json('provider_metadata')->nullable();
    $table->enum('statut', ['pending', 'completed', 'failed', 'refunded', 'expired'])->default('pending');
    $table->string('checkout_url', 500)->nullable();
    $table->timestamp('initiated_at');
    $table->timestamp('webhook_received_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamps();
    $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
    $table->index('atelier_id');
    $table->index('statut');
    $table->index(['provider', 'provider_transaction_id']);
    $table->index('expires_at');
    // validated_by ajouté en migration 024 après création de la table admins
});
```

**Migration 024 — alter_paiements_add_admin_columns :**
```php
Schema::table('paiements', function (Blueprint $table) {
    $table->foreignUuid('validated_by')->nullable()->after('expires_at')
          ->constrained('admins')->nullOnDelete();
});
```

**Modèle PHP :**
```php
// app/Models/Paiement.php
// $fillable: atelier_id, niveau_cle, duree_jours, montant, devise, provider,
//            provider_transaction_id, provider_metadata, statut, checkout_url,
//            initiated_at, webhook_received_at, completed_at, expires_at, ip_address, validated_by
// $casts: provider_metadata => array, initiated_at => datetime, webhook_received_at => datetime,
//         completed_at => datetime, expires_at => datetime
// Relations: belongsTo(Atelier), belongsTo(NiveauConfig, 'niveau_cle', 'cle'),
//            hasOne(TransactionAbonnement), belongsTo(Admin, 'validated_by')
```

> **⚠️ Flux de paiement automatique (callback webhook) :**
> 1. L'utilisateur choisit un plan dans l'app → l'app appelle `POST /api/paiements/initier`
> 2. Le backend crée un `paiement` en statut `pending`, appelle le provider via `PaymentProviderContract::initiate()`, récupère `checkout_url`
> 3. L'app ouvre `checkout_url` dans un InAppBrowser (Capacitor) ou redirige vers le navigateur
> 4. L'utilisateur paie (Mobile Money, Carte, etc.)
> 5. Le provider envoie un webhook sur `POST /api/webhooks/{provider}` avec le résultat
> 6. Le backend vérifie la signature du webhook, met à jour `paiement.statut = 'completed'`
> 7. Le backend génère automatiquement une `TransactionAbonnement` liée au `paiement_id` et l'active immédiatement
> 8. Au prochain sync (ou via polling court), l'app détecte le nouvel abonnement actif

> **⚠️ Expiration des paiements pendants :**
> Un job schedulé (`ExpireStalePayments`) tourne toutes les heures et passe en `expired` tout paiement `pending` dont `expires_at` est dépassé.

---

### TABLE: `transactions_abonnement`
**But :** Codes d'activation pour activer un abonnement. Deux modes de création : (1) automatique via webhook paiement — le code est généré et consommé dans la foulée, (2) manuel via l'admin panel — l'admin génère un code que l'utilisateur saisit dans l'app. Un code = usage unique.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| code_transaction | string(100) | UNIQUE NOT NULL | Code saisi par l'utilisateur (ou auto-généré par webhook) |
| atelier_id | uuid | NULLABLE FK ateliers.id | null avant usage (canal manuel) |
| paiement_id | uuid | NULLABLE FK paiements.id | Lié si créé automatiquement par webhook. null si créé manuellement par l'admin |
| niveau_cle | string(50) | NOT NULL FK niveaux_config.cle | |
| duree_jours | smallInteger | NOT NULL | 31, 93, 186, ou 365 |
| montant | decimal(10,2) | NOT NULL | Montant payé |
| devise | string(10) | NOT NULL DEFAULT 'XOF' | |
| canal | enum | NOT NULL DEFAULT 'manuel' | `'webhook'` = auto via paiement en ligne, `'manuel'` = créé par admin |
| statut | enum | NOT NULL DEFAULT 'disponible' | `'disponible'`, `'utilise'`, `'annule'` |
| utilise_at | timestamp | NULLABLE | |
| created_by | uuid | NULLABLE FK admins.id | Admin ayant généré ce code (null si canal webhook) |
| timestamps | | | |

```php
Schema::create('transactions_abonnement', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('code_transaction', 100)->unique();
    $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->nullOnDelete();
    $table->foreignUuid('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
    $table->string('niveau_cle', 50);
    $table->smallInteger('duree_jours');
    $table->decimal('montant', 10, 2);
    $table->string('devise', 10)->default('XOF');
    $table->enum('canal', ['webhook', 'manuel'])->default('manuel');
    $table->enum('statut', ['disponible', 'utilise', 'annule'])->default('disponible');
    $table->timestamp('utilise_at')->nullable();
    $table->foreignUuid('created_by')->nullable()->constrained('admins');
    $table->timestamps();
    $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
    $table->index('statut');
    $table->index('paiement_id');
});
```

---

### TABLE: `equipe_membres`
**But :** Assistants et Membres (lecteurs). Auth via code d'accès. Verrouillé à l'appareil après première connexion. Présent dans WatermelonDB pour vérification offline du device_id et affichage de l'équipe.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | |
| created_by | uuid | FK proprietaires.id NOT NULL | |
| code_acces | string(60) | UNIQUE NOT NULL | Format ATELIER123_ASST1 — généré auto |
| nom | string(100) | NOT NULL | |
| prenom | string(100) | NULLABLE | |
| role | enum | NOT NULL | `'assistant'`, `'membre'` |
| password | string | NOT NULL | bcrypt |
| device_id | string(255) | NULLABLE | Verrouillé à cet appareil après 1ère connexion |
| device_locked_at | timestamp | NULLABLE | |
| derniere_sync_at | timestamp | NULLABLE | Dernière synchronisation complète |
| code_reprise | string(10) | NULLABLE | Code 6 chiffres pour débloquer après 7 jours |
| code_reprise_expire_at | timestamp | NULLABLE | Valide 48h |
| is_active | boolean | NOT NULL DEFAULT true | |
| revoque_at | timestamp | NULLABLE | Mis par le Gérant lors de la révocation |
| timestamps | | | |
| softDeletes | | | |

**Indexes :** `(atelier_id)`, `(code_acces)`

```php
Schema::create('equipe_membres', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->foreignUuid('created_by')->constrained('proprietaires');
    $table->string('code_acces', 60)->unique();
    $table->string('nom', 100);
    $table->string('prenom', 100)->nullable();
    $table->enum('role', ['assistant', 'membre']);
    $table->string('password');
    $table->string('device_id', 255)->nullable();
    $table->timestamp('device_locked_at')->nullable();
    $table->timestamp('derniere_sync_at')->nullable();
    $table->string('code_reprise', 10)->nullable();
    $table->timestamp('code_reprise_expire_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('revoque_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('atelier_id');
    $table->index('code_acces');
});
```

---

### TABLE: `parametres_atelier`
**But :** Préférences de l'atelier (langue, devise, thème, sync photos, multi-ateliers). Présent dans WatermelonDB — requis au démarrage de l'app en offline.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | UNIQUE FK ateliers.id | |
| langue | char(2) | NOT NULL DEFAULT 'fr' | |
| devise | string(10) | NOT NULL DEFAULT 'XOF' | |
| unite_mesure | string(10) | NOT NULL DEFAULT 'cm' | |
| theme | enum | NOT NULL DEFAULT 'clair' | `'clair'`, `'sombre'` |
| mode_sync_photos | enum | NOT NULL DEFAULT 'libre' | `'economique'`, `'equilibre'`, `'libre'` |
| multi_ateliers_actif | boolean | NOT NULL DEFAULT false | |
| timestamps | | | |

```php
Schema::create('parametres_atelier', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
    $table->char('langue', 2)->default('fr');
    $table->string('devise', 10)->default('XOF');
    $table->string('unite_mesure', 10)->default('cm');
    $table->enum('theme', ['clair', 'sombre'])->default('clair');
    $table->enum('mode_sync_photos', ['economique', 'equilibre', 'libre'])->default('libre');
    $table->boolean('multi_ateliers_actif')->default(false);
    $table->timestamps();
});
```

---

### TABLE: `communications_config`
**But :** Active/désactive les 3 types de messages WhatsApp automatiques. Présent dans WatermelonDB — consulté offline lors de la composition des messages.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | UNIQUE FK ateliers.id | |
| confirmation_commande | boolean | NOT NULL DEFAULT true | Message à la création de commande |
| rappel_livraison_j2 | boolean | NOT NULL DEFAULT true | Message 2 jours avant livraison |
| commande_prete | boolean | NOT NULL DEFAULT true | Message quand commande livrée |
| timestamps | | | |

```php
Schema::create('communications_config', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
    $table->boolean('confirmation_commande')->default(true);
    $table->boolean('rappel_livraison_j2')->default(true);
    $table->boolean('commande_prete')->default(true);
    $table->timestamps();
});
```

---

### TABLE: `points_fidelite`
**But :** Solde actuel des points de fidélité d'un atelier. Présent dans WatermelonDB — mis à jour optimistiquement en offline (gains via client créé / commande validée), réconcilié au sync.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | UNIQUE FK ateliers.id | |
| solde_pts | bigInteger | NOT NULL DEFAULT 0 | |
| timestamps | | | |

```php
Schema::create('points_fidelite', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
    $table->bigInteger('solde_pts')->default(0);
    $table->timestamps();
});
```

---

### TABLE: `points_historique`
**But :** Chaque transaction de points (gain ou dépense). Présent dans WatermelonDB — lisible offline. Les nouvelles entrées créées offline sont mises en queue et syncées.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | |
| type | enum | NOT NULL | `'abonnement_activation'`, `'client_cree'`, `'commande_validee'`, `'reseau_social'`, `'note_store'`, `'conversion'`, `'bonus_admin'` |
| points | integer | NOT NULL | Positif = gain, négatif = dépense/conversion |
| description | string(255) | NOT NULL | Texte affiché à l'utilisateur |
| reference_id | uuid | NULLABLE | ID de l'entité liée (commande, client, etc.) |
| created_at | timestamp | NOT NULL | Immuable |

**Index :** `(atelier_id, created_at)`

```php
Schema::create('points_historique', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->enum('type', ['abonnement_activation','client_cree','commande_validee','reseau_social','note_store','conversion','bonus_admin']);
    $table->integer('points');
    $table->string('description', 255);
    $table->uuid('reference_id')->nullable();
    $table->timestamp('created_at');
    $table->index(['atelier_id', 'created_at']);
});
```

---

### TABLE: `notifications_systeme`
**But :** Notifications envoyées par l'Admin vers un atelier précis ou broadcast. Présent dans WatermelonDB — lues offline. Le marquage `is_read` est mis à jour localement puis syncé.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | NULLABLE FK ateliers.id | null = broadcast à tous |
| titre | string(255) | NOT NULL | |
| contenu | text | NOT NULL | |
| type | enum | NOT NULL | `'promo'`, `'mise_a_jour'`, `'alerte_sync'`, `'alerte_abonnement'`, `'info'` |
| is_read | boolean | NOT NULL DEFAULT false | Mis à jour localement, syncé |
| timestamps | | | |

```php
Schema::create('notifications_systeme', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->cascadeOnDelete();
    $table->string('titre', 255);
    $table->text('contenu');
    $table->enum('type', ['promo','mise_a_jour','alerte_sync','alerte_abonnement','info']);
    $table->boolean('is_read')->default(false);
    $table->timestamps();
    $table->index(['atelier_id', 'is_read']);
});
```

---

### TABLE: `vetements`
**But :** Templates de vêtements. 20 presets système + templates personnalisés par atelier. Présent dans WatermelonDB — catalogue requis offline pour créer des commandes et des mesures.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | NULLABLE FK ateliers.id | null = template système partagé |
| nom | string(150) | NOT NULL | |
| libelles_mesures | json | NOT NULL | `["Longueur totale", "Tour de poitrine", ...]` |
| template_numero | tinyInteger | NULLABLE | 1-20 pour les presets système |
| is_systeme | boolean | NOT NULL DEFAULT false | true = preset non supprimable |
| is_archived | boolean | NOT NULL DEFAULT false | |
| created_by | uuid | NULLABLE | |
| created_by_role | enum | NULLABLE | `'proprietaire'`, `'assistant'` |
| timestamps | | | |
| softDeletes | | | |

**Index :** `(atelier_id, is_archived)`

```php
Schema::create('vetements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->cascadeOnDelete();
    $table->string('nom', 150);
    $table->json('libelles_mesures');
    $table->tinyInteger('template_numero')->nullable();
    $table->boolean('is_systeme')->default(false);
    $table->boolean('is_archived')->default(false);
    $table->uuid('created_by')->nullable();
    $table->enum('created_by_role', ['proprietaire', 'assistant'])->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['atelier_id', 'is_archived']);
});
```

---

### TABLE: `clients`
**But :** Fiches clients de l'atelier. Photo profil = LOCAL uniquement, jamais envoyée au serveur. Présent dans WatermelonDB — feature principale, CRUD 100% offline.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | |
| nom | string(100) | NOT NULL | |
| prenom | string(100) | NOT NULL | |
| telephone | string(25) | NULLABLE | |
| type_profil | enum | NOT NULL | `'homme'`, `'femme'`, `'enfant'`, `'mixte'` |
| avatar_key | string(60) | NULLABLE | Ex: `"homme_3_medium"` — asset bundlé dans l'app |
| created_by | uuid | NOT NULL | |
| created_by_role | enum | NOT NULL | `'proprietaire'`, `'assistant'` |
| is_archived | boolean | NOT NULL DEFAULT false | |
| archived_at | timestamp | NULLABLE | |
| archived_by | uuid | NULLABLE | |
| timestamps | | | |
| softDeletes | | | |

**Indexes :** `(atelier_id)`, `(atelier_id, is_archived)`, UNIQUE `(atelier_id, nom, prenom)`

**⚠️ photo_local_path** : N'existe PAS en base serveur. Stockée uniquement en local sur l'appareil.

```php
Schema::create('clients', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->string('nom', 100);
    $table->string('prenom', 100);
    $table->string('telephone', 25)->nullable();
    $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte']);
    $table->string('avatar_key', 60)->nullable();
    $table->uuid('created_by');
    $table->enum('created_by_role', ['proprietaire', 'assistant']);
    $table->boolean('is_archived')->default(false);
    $table->timestamp('archived_at')->nullable();
    $table->uuid('archived_by')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('atelier_id');
    $table->index(['atelier_id', 'is_archived']);
    $table->unique(['atelier_id', 'nom', 'prenom']);
});
```

---

### TABLE: `mesures`
**But :** Fiche de mesures d'un client pour un type de vêtement précis. Une seule fiche par couple (client, vêtement). Présent dans WatermelonDB.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| client_id | uuid | FK clients.id NOT NULL | |
| vetement_id | uuid | FK vetements.id NOT NULL | |
| atelier_id | uuid | FK ateliers.id NOT NULL | Dénormalisé pour RLS |
| champs | json | NOT NULL | `{"Longueur totale": 120, "Tour de poitrine": 98, ...}` |
| created_by | uuid | NOT NULL | |
| created_by_role | enum | NOT NULL | `'proprietaire'`, `'assistant'` |
| timestamps | | | |

**Contrainte unique :** `(client_id, vetement_id)`

```php
Schema::create('mesures', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
    $table->foreignUuid('vetement_id')->constrained('vetements');
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->json('champs');
    $table->uuid('created_by');
    $table->enum('created_by_role', ['proprietaire', 'assistant']);
    $table->timestamps();
    $table->unique(['client_id', 'vetement_id']);
    $table->index('atelier_id');
});
```

---

### TABLE: `commandes`
**But :** Commandes de confection. photo_tissu = LOCAL uniquement. Présent dans WatermelonDB — feature principale, CRUD 100% offline.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | |
| client_id | uuid | FK clients.id NOT NULL | |
| vetement_id | uuid | FK vetements.id NOT NULL | |
| created_by | uuid | NOT NULL | |
| created_by_role | enum | NOT NULL | `'proprietaire'`, `'assistant'` |
| quantite | tinyInteger unsigned | NOT NULL DEFAULT 1 | |
| prix | decimal(12,2) | NULLABLE | Indicatif Phase 1 |
| acompte | decimal(12,2) | NULLABLE | Phase 2 |
| statut | enum | NOT NULL DEFAULT 'en_cours' | `'en_cours'`, `'livre'`, `'annule'` |
| date_commande | date | NOT NULL | |
| date_livraison_prevue | date | NULLABLE | |
| date_livraison_effective | timestamp | NULLABLE | |
| note_interne | text | NULLABLE | Jamais exposé au client |
| rappel_j2_envoye | boolean | NOT NULL DEFAULT false | |
| timestamps | | | |
| softDeletes | | | |

**⚠️ photo_tissu_local_path** : N'existe PAS en base serveur.

**Indexes :** `(atelier_id, statut)`, `(atelier_id, date_livraison_prevue)`, `(client_id)`

```php
Schema::create('commandes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
    $table->foreignUuid('vetement_id')->constrained('vetements');
    $table->uuid('created_by');
    $table->enum('created_by_role', ['proprietaire', 'assistant']);
    $table->tinyInteger('quantite')->unsigned()->default(1);
    $table->decimal('prix', 12, 2)->nullable();
    $table->decimal('acompte', 12, 2)->nullable();
    $table->enum('statut', ['en_cours', 'livre', 'annule'])->default('en_cours');
    $table->date('date_commande');
    $table->date('date_livraison_prevue')->nullable();
    $table->timestamp('date_livraison_effective')->nullable();
    $table->text('note_interne')->nullable();
    $table->boolean('rappel_j2_envoye')->default(false);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['atelier_id', 'statut']);
    $table->index(['atelier_id', 'date_livraison_prevue']);
    $table->index('client_id');
});
```

---

### TABLE: `quotas_mensuels`
**But :** Compteurs mensuels dénormalisés. Présent dans WatermelonDB (mois courant uniquement) — vérifié offline avant toute création de client ou commande. Incrémenté localement en optimiste, réconcilié au sync.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | |
| annee | smallInteger | NOT NULL | Ex: 2026 |
| mois | tinyInteger | NOT NULL | 1-12 |
| nb_clients_crees | smallInteger | NOT NULL DEFAULT 0 | |
| nb_commandes_creees | smallInteger | NOT NULL DEFAULT 0 | |
| nb_photos_vip | smallInteger | NOT NULL DEFAULT 0 | Phase 3 |
| nb_factures_envoyees | smallInteger | NOT NULL DEFAULT 0 | |
| timestamps | | | |

**Contrainte unique :** `(atelier_id, annee, mois)`

```php
Schema::create('quotas_mensuels', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->smallInteger('annee');
    $table->tinyInteger('mois');
    $table->smallInteger('nb_clients_crees')->default(0);
    $table->smallInteger('nb_commandes_creees')->default(0);
    $table->smallInteger('nb_photos_vip')->default(0);
    $table->smallInteger('nb_factures_envoyees')->default(0);
    $table->timestamps();
    $table->unique(['atelier_id', 'annee', 'mois']);
});
```

---

### TABLE: `photos_vip`
**But :** Album photos modèles. Stocké sur Cloudflare R2. Phase 3 uniquement — table prête dès Phase 1.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | FK ateliers.id NOT NULL | Premium & Magnat uniquement |
| uploaded_by | uuid | NOT NULL | proprietaire_id |
| file_path | string(500) | NOT NULL | Chemin relatif dans R2 |
| file_url | string(500) | NULLABLE | URL publique générée dynamiquement |
| nom | string(150) | NULLABLE | Titre de la photo |
| taille_octets | bigInteger | NOT NULL | |
| timestamps | | | |
| softDeletes | | | |

```php
Schema::create('photos_vip', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->uuid('uploaded_by');
    $table->string('file_path', 500);
    $table->string('file_url', 500)->nullable();
    $table->string('nom', 150)->nullable();
    $table->bigInteger('taille_octets');
    $table->timestamps();
    $table->softDeletes();
    $table->index('atelier_id');
});
```

---

### TABLE: `otp_tokens`
**But :** Codes OTP à 6 chiffres pour vérification téléphone à l'inscription et lors de la récupération de compte.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| telephone | string(25) | NOT NULL | |
| code | string(255) | NOT NULL | bcrypt hashed |
| type | enum | NOT NULL | `'verification_inscription'`, `'recuperation_compte'` |
| expires_at | timestamp | NOT NULL | |
| used_at | timestamp | NULLABLE | |
| tentatives_echec | tinyInteger | NOT NULL DEFAULT 0 | Bloquer après 5 tentatives |
| created_at | timestamp | NOT NULL | |

**Index :** `(telephone, type)`

```php
Schema::create('otp_tokens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('telephone', 25);
    $table->string('code');
    $table->enum('type', ['verification_inscription', 'recuperation_compte']);
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->tinyInteger('tentatives_echec')->default(0);
    $table->timestamp('created_at');
    $table->index(['telephone', 'type']);
});
```

---

### TABLE: `demandes_recuperation`
**But :** Procédure de récupération de compte quand le numéro de téléphone est inaccessible. 5 étapes avec délai d'opposition 24h.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| email | string(255) | NOT NULL | Email du compte à récupérer |
| telephone_nouveau | string(25) | NULLABLE | Nouveau numéro (rempli à l'étape 4) |
| statut | enum | NOT NULL DEFAULT 'en_attente_email' | `'en_attente_email'`, `'email_confirme'`, `'en_attente_otp'`, `'complete'`, `'expire'`, `'bloque'` |
| token_opposition | string(100) | UNIQUE NOT NULL | Token inclus dans l'email |
| opposition_expire_at | timestamp | NOT NULL | +24h après création |
| otp_envoye | boolean | NOT NULL DEFAULT false | |
| validated_at | timestamp | NULLABLE | |
| ip_address | string(45) | NULLABLE | |
| timestamps | | | |

```php
Schema::create('demandes_recuperation', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email', 255);
    $table->string('telephone_nouveau', 25)->nullable();
    $table->enum('statut', ['en_attente_email','email_confirme','en_attente_otp','complete','expire','bloque'])->default('en_attente_email');
    $table->string('token_opposition', 100)->unique();
    $table->timestamp('opposition_expire_at');
    $table->boolean('otp_envoye')->default(false);
    $table->timestamp('validated_at')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamps();
    $table->index('email');
});
```

---

## 4B. Tables Admin — Définitions Complètes

---

### TABLE: `fonctionnalites`
**But :** Catalogue de toutes les fonctionnalités de l'app. Permet à l'admin de builder un plan en cochant des features depuis une interface. Ajouter une feature = insérer une ligne ici, sans migration ni modification de code.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | bigIncrements | PK | |
| cle | string(50) | UNIQUE NOT NULL | `'photos_vip'`, `'facture_whatsapp'`, `'module_caisse'`, `'sauvegarde_auto'`, etc. |
| label | string(100) | NOT NULL | `'Album Photos VIP'` |
| description | string(255) | NULLABLE | Explication courte pour l'admin |
| type | enum | NOT NULL | `'booleen'` (on/off), `'numerique'` (limite chiffre), `'points'` (logique fidélité) |
| unite | string(30) | NULLABLE | `'/mois'`, `'par commande'`, `'pts'` — affiché dans le builder admin |
| categorie | enum | NOT NULL | `'equipe'`, `'clients_commandes'`, `'communication'`, `'stockage'`, `'module'`, `'fidelite'` |
| valeur_defaut | string(50) | NULLABLE | Valeur pré-remplie dans le builder : `'false'`, `'0'`, `'1'` |
| is_actif | boolean | NOT NULL DEFAULT true | `false` = feature cachée du builder (dépréciée ou Phase future) |
| ordre_affichage | tinyInteger | NOT NULL DEFAULT 0 | Ordre dans le builder admin |
| timestamps | | | |

```php
Schema::create('fonctionnalites', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('cle', 50)->unique();
    $table->string('label', 100);
    $table->string('description', 255)->nullable();
    $table->enum('type', ['booleen', 'numerique', 'points']);
    $table->string('unite', 30)->nullable();
    $table->enum('categorie', ['equipe', 'clients_commandes', 'communication', 'stockage', 'module', 'fidelite']);
    $table->string('valeur_defaut', 50)->nullable();
    $table->boolean('is_actif')->default(true);
    $table->tinyInteger('ordre_affichage')->default(0);
    $table->timestamps();
});
```

**FonctionnalitesSeeder — les 14 features du système :**
```php
[
  ['cle'=>'max_assistants',          'label'=>'Assistants max',               'type'=>'numerique', 'unite'=>'comptes',     'categorie'=>'equipe',            'valeur_defaut'=>'0',     'ordre_affichage'=>1],
  ['cle'=>'max_membres',             'label'=>'Membres / Lecteurs max',       'type'=>'numerique', 'unite'=>'comptes',     'categorie'=>'equipe',            'valeur_defaut'=>'0',     'ordre_affichage'=>2],
  ['cle'=>'max_clients_par_mois',    'label'=>'Clients & Commandes / mois',   'type'=>'numerique', 'unite'=>'/mois',       'categorie'=>'clients_commandes', 'valeur_defaut'=>'50',    'ordre_affichage'=>3],
  ['cle'=>'photos_vip',              'label'=>'Album Photos VIP',             'type'=>'booleen',   'unite'=>null,          'categorie'=>'stockage',          'valeur_defaut'=>'false', 'ordre_affichage'=>4],
  ['cle'=>'max_photos_vip_par_mois', 'label'=>'Photos VIP max / mois',        'type'=>'numerique', 'unite'=>'/mois',       'categorie'=>'stockage',          'valeur_defaut'=>'0',     'ordre_affichage'=>5],
  ['cle'=>'facture_whatsapp',        'label'=>'Envoi facture WhatsApp',       'type'=>'booleen',   'unite'=>null,          'categorie'=>'communication',     'valeur_defaut'=>'false', 'ordre_affichage'=>6],
  ['cle'=>'max_factures_par_mois',   'label'=>'Factures max / mois',          'type'=>'numerique', 'unite'=>'/mois',       'categorie'=>'communication',     'valeur_defaut'=>'0',     'ordre_affichage'=>7],
  ['cle'=>'sauvegarde_auto',         'label'=>'Sauvegarde automatique',       'type'=>'booleen',   'unite'=>null,          'categorie'=>'module',            'valeur_defaut'=>'false', 'ordre_affichage'=>8],
  ['cle'=>'module_caisse',           'label'=>'Module Caisse (Phase 2)',      'type'=>'booleen',   'unite'=>null,          'categorie'=>'module',            'valeur_defaut'=>'false', 'ordre_affichage'=>9],
  ['cle'=>'multi_ateliers',          'label'=>'Mode Multi-Ateliers',          'type'=>'booleen',   'unite'=>null,          'categorie'=>'module',            'valeur_defaut'=>'false', 'ordre_affichage'=>10],
  ['cle'=>'pts_par_client',          'label'=>'Points par client créé',       'type'=>'points',    'unite'=>'pts/client',  'categorie'=>'fidelite',          'valeur_defaut'=>'1',     'ordre_affichage'=>11],
  ['cle'=>'pts_par_commande',        'label'=>'Points par commande validée',  'type'=>'points',    'unite'=>'pts/commande','categorie'=>'fidelite',          'valeur_defaut'=>'1',     'ordre_affichage'=>12],
  ['cle'=>'pts_activation',          'label'=>'Points à l\'activation',       'type'=>'points',    'unite'=>'pts',         'categorie'=>'fidelite',          'valeur_defaut'=>'31',    'ordre_affichage'=>13],
  ['cle'=>'seuil_conversion_pts',    'label'=>'Seuil conversion points→bonus','type'=>'points',    'unite'=>'pts',         'categorie'=>'fidelite',          'valeur_defaut'=>'10000', 'ordre_affichage'=>14],
]
```

> **Ajouter une feature Phase 3 (ex: SMS, Stock tissu) ?** Insérer 1 ligne dans ce seeder. Le builder admin l'affiche automatiquement. Aucune migration, aucun ALTER TABLE.

---

### TABLE: `admins`
**But :** Comptes de l'équipe interne Couture Pro. Guard séparé de `proprietaires`. 2 rôles : `super_admin` (accès total) et `admin` (permissions définies par le super_admin via JSON).

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| nom | string(100) | NOT NULL | |
| prenom | string(100) | NOT NULL | |
| email | string | UNIQUE NOT NULL | Login |
| password | string | NOT NULL | bcrypt |
| role | enum | NOT NULL | `'super_admin'`, `'admin'` |
| permissions | json | NULLABLE | `null` si super_admin (tout accès). Sinon : `["tickets.view","tickets.respond","ateliers.view"]` |
| is_active | boolean | NOT NULL DEFAULT true | Désactivable sans suppression |
| derniere_connexion_at | timestamp | NULLABLE | |
| remember_token | string(100) | NULLABLE | |
| timestamps | | | |
| softDeletes | | | |

```php
Schema::create('admins', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nom', 100);
    $table->string('prenom', 100);
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['super_admin', 'admin']);
    $table->json('permissions')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('derniere_connexion_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

**Catalogue des permissions (app/Enums/AdminPermission.php) :**
```php
const PERMISSIONS = [
    'ateliers.view'           => 'Voir tous les ateliers',
    'ateliers.freeze'         => 'Geler / dégeler un atelier',
    'ateliers.subscription'   => 'Modifier l\'abonnement d\'un atelier',
    'paiements.view'          => 'Voir les paiements (historique, statuts)',
    'paiements.validate'      => 'Valider manuellement un paiement litigieux',
    'paiements.refund'        => 'Marquer un paiement comme remboursé',
    'transactions.view'       => 'Voir les transactions',
    'transactions.create'     => 'Créer des codes d\'activation manuels',
    'transactions.cancel'     => 'Annuler une transaction',
    'plans.view'              => 'Voir les plans d\'abonnement',
    'plans.edit'              => 'Modifier prix et fonctionnalités des plans',
    'plans.create'            => 'Créer de nouveaux plans',
    'offres.view'             => 'Voir les offres spéciales',
    'offres.create'           => 'Créer une offre spéciale',
    'tickets.view'            => 'Voir les tickets support',
    'tickets.respond'         => 'Répondre aux tickets',
    'tickets.assign'          => 'Assigner un ticket',
    'tickets.close'           => 'Fermer / rouvrir un ticket',
    'notifications.broadcast' => 'Envoyer une notification broadcast',
    'blacklist.manage'        => 'Gérer la liste noire',
    'audit.view'              => 'Voir le journal d\'audit',
    'stats.view'              => 'Voir les statistiques et rentabilité',
    'admins.manage'           => 'Gérer les comptes admins (super_admin seulement)',
];
```

**Vérification de permission :**
```php
public function hasPermission(Admin $admin, string $permission): bool
{
    if ($admin->role === 'super_admin') return true;
    if (!$admin->permissions) return false;
    return in_array($permission, $admin->permissions);
}
```

---

**Migration 022 — alter_niveaux_config_add_admin_columns :**
```php
Schema::table('niveaux_config', function (Blueprint $table) {
    $table->foreignUuid('updated_by')->nullable()->after('description_courte')
          ->constrained('admins')->nullOnDelete();
});
```

---

### TABLE: `tickets_support`
**But :** Tickets ouverts par les propriétaires d'atelier. Référence unique auto-générée. Assignable à un admin. Suivi complet du cycle de vie.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| reference | string(30) | UNIQUE NOT NULL | `TKT-20260422-A3F9`, généré auto |
| atelier_id | uuid | NULLABLE FK ateliers.id | Nullable si atelier supprimé |
| proprietaire_id | uuid | NOT NULL FK proprietaires.id | |
| categorie | enum | NOT NULL | `'facturation'`, `'technique'`, `'compte'`, `'abonnement'`, `'autre'` |
| priorite | enum | NOT NULL DEFAULT 'normale' | `'faible'`, `'normale'`, `'haute'`, `'urgente'` |
| statut | enum | NOT NULL DEFAULT 'ouvert' | `'ouvert'`, `'en_cours'`, `'en_attente_client'`, `'resolu'`, `'ferme'` |
| sujet | string(255) | NOT NULL | |
| assigned_to | uuid | NULLABLE FK admins.id | |
| resolu_at | timestamp | NULLABLE | |
| timestamps | | | |
| softDeletes | | | |

```php
Schema::create('tickets_support', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('reference', 30)->unique();
    $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->nullOnDelete();
    $table->foreignUuid('proprietaire_id')->constrained('proprietaires');
    $table->enum('categorie', ['facturation', 'technique', 'compte', 'abonnement', 'autre']);
    $table->enum('priorite', ['faible', 'normale', 'haute', 'urgente'])->default('normale');
    $table->enum('statut', ['ouvert', 'en_cours', 'en_attente_client', 'resolu', 'ferme'])->default('ouvert');
    $table->string('sujet', 255);
    $table->foreignUuid('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
    $table->timestamp('resolu_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('statut');
    $table->index('priorite');
    $table->index('assigned_to');
    $table->index('proprietaire_id');
});
```

---

### TABLE: `tickets_messages`
**But :** Fil de conversation d'un ticket. Support des notes internes admin (invisibles au client). Polymorphisme léger sur l'expéditeur.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| ticket_id | uuid | NOT NULL FK tickets_support.id | |
| expediteur_type | enum | NOT NULL | `'proprietaire'`, `'admin'` |
| expediteur_id | uuid | NOT NULL | ID polymorphe |
| contenu | text | NOT NULL | |
| is_note_interne | boolean | NOT NULL DEFAULT false | `true` = visible admin seulement |
| lu_par_client_at | timestamp | NULLABLE | |
| lu_par_admin_at | timestamp | NULLABLE | |
| created_at | timestamp | NOT NULL | Immuable |

```php
Schema::create('tickets_messages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('ticket_id')->constrained('tickets_support')->cascadeOnDelete();
    $table->enum('expediteur_type', ['proprietaire', 'admin']);
    $table->uuid('expediteur_id');
    $table->text('contenu');
    $table->boolean('is_note_interne')->default(false);
    $table->timestamp('lu_par_client_at')->nullable();
    $table->timestamp('lu_par_admin_at')->nullable();
    $table->timestamp('created_at');
    $table->index('ticket_id');
    $table->index(['expediteur_type', 'expediteur_id']);
});
```

---

### TABLE: `admin_audit_log`
**But :** Journal immuable de toutes les actions sensibles admin. Append-only — jamais d'UPDATE ni de DELETE.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | bigIncrements | PK | Volume élevé prévu |
| admin_id | uuid | NOT NULL FK admins.id | |
| action | string(100) | NOT NULL | `'plan.modifier'`, `'atelier.geler'`, `'offre.creer'`, `'transaction.creer'` |
| entite_type | string(50) | NULLABLE | `'plan'`, `'atelier'`, `'ticket'`, `'offre_speciale'`, `'transaction'` |
| entite_id | string(36) | NULLABLE | UUID ou cle de l'entité |
| details | json | NULLABLE | Snapshot avant/après |
| ip_address | string(45) | NULLABLE | IPv4 ou IPv6 |
| created_at | timestamp | NOT NULL | |

```php
Schema::create('admin_audit_log', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignUuid('admin_id')->constrained('admins');
    $table->string('action', 100);
    $table->string('entite_type', 50)->nullable();
    $table->string('entite_id', 36)->nullable();
    $table->json('details')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('created_at');
    $table->index('admin_id');
    $table->index('action');
    $table->index(['entite_type', 'entite_id']);
});
```

---

### TABLE: `niveaux_config_changelog`
**But :** Journal immuable champ-par-champ de chaque modification d'un plan. Append-only. Survivre aux plans dépréciés — pas de FK stricte sur `niveau_cle`.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | bigIncrements | PK | |
| niveau_cle | string(50) | NOT NULL | Pas de FK — survit à la suppression du plan |
| admin_id | uuid | NOT NULL FK admins.id | |
| champ_modifie | string(100) | NOT NULL | `'prix_xof'`, `'config.max_clients_par_mois'`, `'is_actif'` |
| ancienne_valeur | text | NULLABLE | Cast string de l'ancienne valeur |
| nouvelle_valeur | text | NULLABLE | Cast string de la nouvelle valeur |
| created_at | timestamp | NOT NULL | |

```php
Schema::create('niveaux_config_changelog', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('niveau_cle', 50);
    $table->foreignUuid('admin_id')->constrained('admins');
    $table->string('champ_modifie', 100);
    $table->text('ancienne_valeur')->nullable();
    $table->text('nouvelle_valeur')->nullable();
    $table->timestamp('created_at');
    $table->index(['niveau_cle', 'created_at']);
});
```

---

### TABLE: `offres_speciales`
**But :** Deals custom accordés à un atelier précis. Surcharge n'importe quelle limite du plan standard via `config_override` JSON. Résolu dans `abonnements.config_snapshot` à chaque sync.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| atelier_id | uuid | NOT NULL FK ateliers.id | Atelier bénéficiaire |
| admin_id | uuid | NOT NULL FK admins.id | Admin ayant créé l'offre |
| label | string(150) | NOT NULL | `"Deal Fashion Week 2026"`, `"Compensation panne"` |
| niveau_base_cle | string(50) | NOT NULL FK niveaux_config.cle | Plan de base |
| config_override | json | NOT NULL | Clés qui écrasent le `config` du plan de base ex: `{"max_clients_par_mois":500}` |
| prix_special | decimal(10,2) | NULLABLE | `null` = offre cadeau |
| duree_jours | smallInteger | NOT NULL | |
| statut | enum | NOT NULL DEFAULT 'actif' | `'actif'`, `'expire'`, `'annule'` |
| expire_at | timestamp | NULLABLE | Calculé = created_at + duree_jours |
| notes_internes | text | NULLABLE | Visible admin uniquement |
| timestamps | | | |
| softDeletes | | | |

```php
Schema::create('offres_speciales', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
    $table->foreignUuid('admin_id')->constrained('admins');
    $table->string('label', 150);
    $table->string('niveau_base_cle', 50);
    $table->json('config_override');
    $table->decimal('prix_special', 10, 2)->nullable();
    $table->smallInteger('duree_jours');
    $table->enum('statut', ['actif', 'expire', 'annule'])->default('actif');
    $table->timestamp('expire_at')->nullable();
    $table->text('notes_internes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('niveau_base_cle')->references('cle')->on('niveaux_config');
    $table->index(['atelier_id', 'statut']);
});
```

---

### TABLE: `liste_noire`
**But :** Bloquer les inscriptions frauduleuses ou abusives. Vérifié à chaque inscription et connexion côté serveur.

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| id | uuid | PK | |
| type | enum | NOT NULL | `'telephone'`, `'email'`, `'ip'` |
| valeur | string(255) | NOT NULL | UNIQUE sur `(type, valeur)` |
| raison | text | NULLABLE | Note interne admin |
| admin_id | uuid | NOT NULL FK admins.id | Admin ayant ajouté |
| timestamps | | | |

```php
Schema::create('liste_noire', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->enum('type', ['telephone', 'email', 'ip']);
    $table->string('valeur', 255);
    $table->text('raison')->nullable();
    $table->foreignUuid('admin_id')->constrained('admins');
    $table->timestamps();
    $table->unique(['type', 'valeur']);
    $table->index(['type', 'valeur']);
});
```

---

## 5. Résumé des Relations Eloquent

```
Proprietaire
  hasMany    → Atelier (proprietaire_id)
  hasMany    → EquipeMembre (created_by)

Atelier
  belongsTo  → Proprietaire
  hasOne     → Abonnement
  hasMany    → EquipeMembre
  hasOne     → ParametresAtelier
  hasOne     → CommunicationsConfig
  hasOne     → PointsFidelite
  hasMany    → PointsHistorique
  hasMany    → NotificationSysteme
  hasMany    → Vetement
  hasMany    → Client
  hasMany    → Commande
  hasMany    → QuotaMensuel
  hasMany    → PhotoVip
  hasMany    → Paiement
  hasMany    → TransactionAbonnement
  hasMany    → OffreSpeciale

Paiement
  belongsTo  → Atelier
  belongsTo  → NiveauConfig (via niveau_cle / cle)
  belongsTo  → Admin (validated_by, nullable)
  hasOne     → TransactionAbonnement

TransactionAbonnement
  belongsTo  → Atelier (nullable)
  belongsTo  → Paiement (nullable — null si canal manuel)
  belongsTo  → NiveauConfig (via niveau_cle / cle)
  belongsTo  → Admin (created_by, nullable — null si canal webhook)

Client
  belongsTo  → Atelier
  hasMany    → Mesure
  hasMany    → Commande

Vetement
  belongsTo  → Atelier (nullable — null = système)
  hasMany    → Mesure
  hasMany    → Commande

Commande
  belongsTo  → Atelier
  belongsTo  → Client
  belongsTo  → Vetement

Mesure
  belongsTo  → Client
  belongsTo  → Vetement
  belongsTo  → Atelier

Abonnement
  belongsTo  → Atelier
  belongsTo  → NiveauConfig (via niveau_cle / cle)

Admin
  hasMany    → TicketSupport (assigned_to)
  hasMany    → TicketSupport (via tickets_messages)
  hasMany    → AdminAuditLog
  hasMany    → NiveauConfigChangelog
  hasMany    → OffreSpeciale
  hasMany    → ListeNoire
  hasMany    → TransactionAbonnement (created_by)
  hasMany    → Paiement (validated_by)

TicketSupport
  belongsTo  → Proprietaire
  belongsTo  → Atelier (nullable)
  belongsTo  → Admin (assigned_to)
  hasMany    → TicketMessage

NiveauConfig
  hasMany    → Abonnement (via niveau_cle)
  hasMany    → Paiement (via niveau_cle)
  hasMany    → TransactionAbonnement (via niveau_cle)
  hasMany    → OffreSpeciale (via niveau_base_cle)
  hasMany    → NiveauConfigChangelog (via niveau_cle)
```

---

## 6. Modèles — Traits et Casts Requis

| Modèle | Traits | Casts importants |
|---|---|---|
| Proprietaire | HasApiTokens, HasFactory, Notifiable, SoftDeletes | email_verified_at: datetime, telephone_verified_at: datetime, password: hashed |
| Atelier | HasFactory, SoftDeletes | essai_expire_at: datetime |
| Abonnement | — | timestamp_debut: datetime, timestamp_expiration: datetime, bonus_timestamp_debut: datetime, bonus_actif: boolean, **config_snapshot: array** |
| Paiement | — | provider_metadata: array, initiated_at: datetime, webhook_received_at: datetime, completed_at: datetime, expires_at: datetime |
| TransactionAbonnement | — | utilise_at: datetime |
| EquipeMembre | HasApiTokens, HasFactory, SoftDeletes | device_locked_at: datetime, code_reprise_expire_at: datetime, derniere_sync_at: datetime, password: hashed |
| Client | HasFactory, SoftDeletes | archived_at: datetime |
| Mesure | — | champs: array (JSON cast) |
| Vetement | HasFactory, SoftDeletes | libelles_mesures: array (JSON cast) |
| Commande | SoftDeletes | date_commande: date, date_livraison_prevue: date, date_livraison_effective: datetime |
| NiveauConfig | — | config: array (JSON cast), is_actif: boolean |
| Admin | HasApiTokens, HasFactory, Notifiable, SoftDeletes | permissions: array (JSON cast), derniere_connexion_at: datetime, password: hashed |
| OffreSpeciale | SoftDeletes | config_override: array (JSON cast), expire_at: datetime |

---

## 7. Sécurité — Laravel Gates & Policies

> Toute la sécurité passe par le backend Laravel. Chaque requête API vérifie que l'utilisateur authentifié a bien accès à l'atelier cible.

### Middleware à créer : `EnsureAtelierAccess`
```
Vérifie que l'utilisateur (Proprietaire ou EquipeMembre)
appartient à l'atelier de la requête (atelier_id).
Un EquipeMembre ne peut accéder qu'à son propre atelier.
```

### Middleware à créer : `EnsureAdminPermission`
```
Vérifie que l'Admin connecté (guard 'admin') possède la permission
requise pour l'action demandée.
Super_admin : toujours autorisé.
Admin : vérifie dans le JSON permissions.
```

### Matrice droits utilisateur app

| Action | Gérant | Assistant | Membre |
|---|---|---|---|
| Lire clients/commandes/mesures | ✅ | ✅ | ✅ |
| Créer client/commande | ✅ | ✅ | ❌ |
| Modifier client | ✅ | ❌ | ❌ |
| Archiver client | ✅ | ✅ | ❌ |
| Supprimer client (soft delete) | ✅ | ❌ | ❌ |
| Créer vêtement | ✅ | ✅ | ❌ |
| Modifier/supprimer vêtement | ✅ | ❌ | ❌ |
| Exporter PDF mesures | ✅ | ✅ | ❌ |
| Gérer équipe | ✅ | ❌ | ❌ |
| Voir menu Abonnement | ✅ | ❌ | ❌ |
| Voir Notifications système | ✅ | ❌ | ❌ |

### Matrice droits admin panel

| Action | super_admin | admin (selon permissions) |
|---|---|---|
| Voir les paiements / statuts | ✅ | `paiements.view` |
| Valider manuellement un paiement | ✅ | `paiements.validate` |
| Marquer un paiement comme remboursé | ✅ | `paiements.refund` |
| Créer / annuler transaction manuelle | ✅ | `transactions.create` / `transactions.cancel` |
| Geler / dégeler atelier | ✅ | `ateliers.freeze` |
| Modifier plans (prix + limites) | ✅ | `plans.edit` |
| Créer nouveau plan | ✅ | `plans.create` |
| Voir historique modifications plan | ✅ | `plans.view` |
| Créer offre spéciale | ✅ | `offres.create` |
| Voir tickets / Répondre / Assigner | ✅ | `tickets.view` / `tickets.respond` / `tickets.assign` |
| Fermer / Rouvrir ticket | ✅ | `tickets.close` |
| Voir audit log | ✅ | `audit.view` |
| Dashboard rentabilité / stats | ✅ | `stats.view` |
| Envoyer notification broadcast | ✅ | `notifications.broadcast` |
| Gérer liste noire | ✅ | `blacklist.manage` |
| Gérer comptes admins | ✅ | ❌ (super_admin uniquement) |

### Policies Laravel à créer
```
app/Policies/ClientPolicy.php
app/Policies/CommandePolicy.php
app/Policies/VetementPolicy.php
app/Policies/MesurePolicy.php
app/Policies/EquipeMembrePolicy.php
app/Policies/AdminPolicy.php
app/Policies/PaiementPolicy.php
app/Policies/TicketSupportPolicy.php
app/Policies/OffreSpecialePolicy.php
app/Policies/NiveauConfigPolicy.php
```

---

## 8. Seeders à Créer

| Seeder | Description |
|---|---|
| `NiveauxConfigSeeder` | 6 plans avec config JSON complète (voir section 4) |
| `FonctionnalitesSeeder` | 14 features du catalogue (voir section 4B) |
| `VetementsSeeder` | 20 templates système avec `atelier_id=null`, `is_systeme=true` |
| `AdminSeeder` | 1er compte super_admin pour dev/staging |
| `DatabaseSeeder` | Appelle tous les seeders dans l'ordre |

**Ordre des seeders :**
```php
// database/seeders/DatabaseSeeder.php
$this->call([
    FonctionnalitesSeeder::class, // avant niveaux_config (référence catalogue)
    NiveauxConfigSeeder::class,
    VetementsSeeder::class,
    AdminSeeder::class,
]);
```

**Les 20 templates vêtements (VetementsSeeder) :**
```php
// template_numero 1 — Boubou traditionnel homme
['Longueur totale', 'Tour de poitrine', 'Tour de cou', 'Longueur manche', 'Tour de poignet', 'Épaule à épaule', 'Tour de taille']
// 2 — Chemise africaine
['Tour de poitrine', 'Tour de cou', 'Longueur totale', 'Longueur manche', 'Tour de poignet', 'Épaule à épaule']
// 3 — Pantalon africain
['Tour de taille', 'Tour de bassin', 'Longueur totale', 'Hauteur entrejambe', 'Tour de cuisse', 'Tour de bas']
// 4 — Agbada 3 pièces
['Longueur agbada', 'Tour de poitrine', 'Tour de cou', 'Longueur manche', 'Tour de poignet', 'Largeur épaule', 'Longueur tunique intérieure']
// 5 — Dashiki
['Longueur totale', 'Tour de poitrine', 'Tour de cou', 'Tour de taille', 'Épaule à épaule', 'Longueur manche']
// 6 — Tunique longue homme
['Longueur totale', 'Tour de poitrine', 'Tour de cou', 'Tour de taille', 'Épaule à épaule', 'Longueur manche']
// 7 — Robe africaine simple femme
['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanche', 'Tour de bras', 'Tour de cou', 'Carrure épaule']
// 8 — Robe pagne stylisée
['Longueur robe', 'Tour de poitrine', 'Tour de taille', 'Tour de hanche', 'Longueur manche', 'Tour de bras', 'Hauteur taille']
// 9 — Jupe pagne droite/évasée
['Tour de taille', 'Tour de hanche', 'Longueur jupe', 'Hauteur taille', 'Tour de bas']
// 10 — Taille haute femme
['Tour de taille', 'Tour de hanche', 'Longueur pantalon/jupe', 'Tour de cuisse', 'Tour de bas']
// 11 — Chemisier femme africain
['Tour de poitrine', 'Tour de taille', 'Longueur totale', 'Longueur manche', 'Tour de bras', 'Épaule à épaule']
// 12 — Tenue enfant africaine
['Tour de poitrine', 'Tour de taille', 'Longueur haut', 'Longueur bas', 'Tour de bras', 'Tour de cuisse']
// 13 — Chemise manche longue homme
['Tour de poitrine', 'Tour de cou', 'Longueur manche', 'Longueur totale', 'Tour de poignet', 'Épaule à épaule']
// 14 — Pantalon classique costume
['Tour de taille', 'Tour de hanche', 'Longueur totale', 'Hauteur entrejambe', 'Tour de cuisse', 'Tour de bas']
// 15 — T-shirt
['Tour de poitrine', 'Tour de cou', 'Longueur totale', 'Épaule à épaule', 'Longueur manche']
// 16 — Veste de costume
['Tour de poitrine', 'Tour de taille', 'Tour de cou', 'Longueur totale', 'Longueur manche', 'Épaule à épaule', 'Tour de poignet']
// 17 — Robe droite européenne
['Tour de poitrine', 'Tour de taille', 'Tour de hanche', 'Longueur robe', 'Carrure épaule', 'Tour de bras']
// 18 — Jupe crayon femme
['Tour de taille', 'Tour de hanche', 'Longueur jupe', 'Hauteur taille', 'Tour de bas']
// 19 — Short homme/femme
['Tour de taille', 'Tour de hanche', 'Longueur totale', 'Tour de cuisse', 'Tour de bas']
// 20 — Chemisier moderne femme
['Tour de poitrine', 'Tour de taille', 'Longueur totale', 'Longueur manche', 'Épaule à épaule', 'Tour de bras']
```

---

## 9. Logique Métier Importante à Coder

### Service critique : `PaymentService` + `PaymentProviderContract`
```php
// app/Contracts/PaymentProviderContract.php
// Interface que chaque provider de paiement doit implémenter
interface PaymentProviderContract
{
    /**
     * Initier un paiement — retourne un checkout_url vers lequel rediriger l'utilisateur
     */
    public function initiate(array $data): PaymentInitiationResult;
    // $data = ['amount' => 7500, 'currency' => 'XOF', 'description' => 'Premium Mensuel',
    //          'callback_url' => '...', 'return_url' => '...', 'metadata' => [...]]
    // Retourne: PaymentInitiationResult { provider_transaction_id, checkout_url, raw_response }

    /**
     * Vérifier la signature du webhook entrant — sécurité anti-fraude
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Extraire les données du webhook (statut, montant, transaction_id, etc.)
     */
    public function parseWebhook(Request $request): WebhookPayload;
    // Retourne: WebhookPayload { provider_transaction_id, status, amount, currency, raw_data }

    /**
     * Vérifier le statut d'un paiement côté provider (fallback si webhook manqué)
     */
    public function checkStatus(string $providerTransactionId): PaymentStatus;
}
```

```php
// app/Services/PaymentService.php
// Orchestre tout le flux : initiation → webhook → activation abonnement

public function initiatePayment(string $atelierId, string $niveauCle, int $dureeJours): Paiement
{
    $plan = NiveauConfig::where('cle', $niveauCle)->firstOrFail();
    $montant = $this->calculateAmount($plan, $dureeJours);
    $provider = $this->resolveProvider(); // lit config('payment.default_provider')

    $paiement = Paiement::create([
        'atelier_id' => $atelierId,
        'niveau_cle' => $niveauCle,
        'duree_jours' => $dureeJours,
        'montant' => $montant,
        'devise' => 'XOF',
        'provider' => config('payment.default_provider'),
        'statut' => 'pending',
        'initiated_at' => now(),
        'expires_at' => now()->addHours(2),
        'ip_address' => request()->ip(),
    ]);

    $result = $provider->initiate([
        'amount' => $montant,
        'currency' => 'XOF',
        'description' => "Couture Pro — {$plan->label} ({$dureeJours}j)",
        'callback_url' => route('webhooks.payment', ['provider' => config('payment.default_provider')]),
        'return_url' => config('payment.return_url'),
        'metadata' => ['paiement_id' => $paiement->id, 'atelier_id' => $atelierId],
    ]);

    $paiement->update([
        'provider_transaction_id' => $result->provider_transaction_id,
        'checkout_url' => $result->checkout_url,
        'provider_metadata' => $result->raw_response,
    ]);

    return $paiement;
}

public function handleWebhook(string $providerName, Request $request): void
{
    $provider = $this->resolveProvider($providerName);

    if (!$provider->verifyWebhook($request)) {
        Log::warning("Webhook signature invalide pour {$providerName}");
        abort(403, 'Signature invalide');
    }

    $payload = $provider->parseWebhook($request);
    $paiement = Paiement::where('provider', $providerName)
                        ->where('provider_transaction_id', $payload->provider_transaction_id)
                        ->firstOrFail();

    if ($paiement->statut !== 'pending') return; // idempotence

    $paiement->update([
        'statut' => $payload->status === 'success' ? 'completed' : 'failed',
        'webhook_received_at' => now(),
        'completed_at' => $payload->status === 'success' ? now() : null,
        'provider_metadata' => $payload->raw_data,
    ]);

    if ($payload->status === 'success') {
        $this->activateSubscription($paiement);
    }
}

private function activateSubscription(Paiement $paiement): void
{
    // 1. Générer automatiquement une TransactionAbonnement liée
    $code = 'AUTO-' . strtoupper(Str::random(12));
    $transaction = TransactionAbonnement::create([
        'code_transaction' => $code,
        'atelier_id' => $paiement->atelier_id,
        'paiement_id' => $paiement->id,
        'niveau_cle' => $paiement->niveau_cle,
        'duree_jours' => $paiement->duree_jours,
        'montant' => $paiement->montant,
        'devise' => $paiement->devise,
        'canal' => 'webhook',
        'statut' => 'utilise',
        'utilise_at' => now(),
        'created_by' => null, // pas d'admin — activation automatique
    ]);

    // 2. Activer l'abonnement (même logique que l'activation manuelle — voir ci-dessous)
    $this->applySubscriptionActivation($paiement->atelier_id, $transaction);
}
```

> **⚠️ Configuration provider :**
> ```php
> // config/payment.php
> return [
>     'default_provider' => env('PAYMENT_PROVIDER', 'fedapay'),
>     'return_url' => env('PAYMENT_RETURN_URL', 'couturepro://payment-return'),
>     'providers' => [
>         'fedapay' => [
>             'class' => App\Services\Payment\FedaPayProvider::class,
>             'api_key' => env('FEDAPAY_API_KEY'),
>             'secret_key' => env('FEDAPAY_SECRET_KEY'),
>             'environment' => env('FEDAPAY_ENV', 'sandbox'),
>         ],
>         'kkiapay' => [
>             'class' => App\Services\Payment\KkiapayProvider::class,
>             'public_key' => env('KKIAPAY_PUBLIC_KEY'),
>             'private_key' => env('KKIAPAY_PRIVATE_KEY'),
>             'secret' => env('KKIAPAY_SECRET'),
>         ],
>         // Ajouter un nouveau provider = 1 classe + 3 lignes ici. Aucune migration.
>     ],
> ];
> ```

> **⚠️ Routes webhook :**
> ```php
> // routes/api.php
> Route::post('/paiements/initier', [PaiementController::class, 'initier'])->middleware('auth:sanctum');
> Route::post('/paiements/{id}/status', [PaiementController::class, 'checkStatus'])->middleware('auth:sanctum');
> Route::post('/webhooks/{provider}', [WebhookController::class, 'handle']); // PAS d'auth — le webhook vient du provider
> ```

> **⚠️ Job schedulé — expiration des paiements pendants :**
> ```php
> // app/Console/Commands/ExpireStalePayments.php
> // Schedule: ->hourly()
> Paiement::where('statut', 'pending')
>         ->where('expires_at', '<', now())
>         ->update(['statut' => 'expired']);
> ```

> **⚠️ Job schedulé — vérification fallback (webhook manqué) :**
> ```php
> // app/Console/Commands/CheckPendingPayments.php
> // Schedule: ->everyFifteenMinutes()
> // Pour chaque paiement 'pending' de plus de 10 minutes :
> // Appeler PaymentProviderContract::checkStatus() et traiter le résultat
> ```

### Service critique : `AtelierLimitsService`
```php
// app/Services/AtelierLimitsService.php
// Retourne la config effective d'un atelier : plan de base + offre spéciale si active
public function getConfig(string $atelierId): array
{
    $abonnement = Abonnement::where('atelier_id', $atelierId)->first();
    $plan = NiveauConfig::where('cle', $abonnement->niveau_cle)->first();
    $config = $plan->config; // array via JSON cast

    $offre = OffreSpeciale::where('atelier_id', $atelierId)
                          ->where('statut', 'actif')
                          ->first();
    if ($offre) {
        $config = array_merge($config, $offre->config_override);
    }
    return $config;
}

// ⚠️ À appeler OBLIGATOIREMENT à chaque réponse de sync
// pour mettre à jour config_snapshot sur l'abonnement
public function refreshSnapshot(string $atelierId): void
{
    $config = $this->getConfig($atelierId);
    Abonnement::where('atelier_id', $atelierId)
              ->update(['config_snapshot' => $config]);
}
```

### Création d'un compte (inscription)
1. Vérifier `telephone` et `email` non présents dans `liste_noire`
2. Créer `Proprietaire`
3. Créer `Atelier` (`is_maitre=true`, `statut='essai'`, `essai_expire_at=now+14 jours`)
4. Créer `Abonnement` (`niveau_cle='premium_mensuel'`, `statut='actif'`, `jours_restants=14`)
5. Appeler `AtelierLimitsService::refreshSnapshot()` → remplit `config_snapshot`
6. Créer `ParametresAtelier` (defaults)
7. Créer `CommunicationsConfig` (tout à true)
8. Créer `PointsFidelite` (`solde=0`)
9. Créer `QuotaMensuel` pour le mois courant
10. Charger les 20 vêtements système (`atelier_id=null`)

### Réponse de sync (endpoint POST /sync)
```
À chaque réponse de sync envoyée à l'app :
1. Traiter les opérations entrantes (batch)
2. Calculer le delta sortant
3. Appeler AtelierLimitsService::refreshSnapshot(atelierId)
4. Inclure config_snapshot dans la réponse → l'app met à jour WatermelonDB
```

### Activation d'un abonnement — logique commune aux deux canaux
> Cette logique est identique qu'elle soit déclenchée par un webhook (canal automatique) ou par la saisie d'un code (canal manuel). Elle est centralisée dans `PaymentService::applySubscriptionActivation()`.

1. **Canal webhook** : appelé automatiquement après confirmation du paiement (voir `PaymentService::activateSubscription()`)
2. **Canal manuel** : l'utilisateur saisit un code → vérifier `code_transaction` existe et `statut='disponible'` → marquer `statut='utilise'`, relier à `atelier_id`
3. **Si `bonus_actif=false`** : ajouter `duree_jours` à `jours_restants`, recalculer `timestamp_expiration`
4. **Si `bonus_actif=true`** : ajouter `duree_jours` à `jours_restants` (principal en pause — s'accumulera)
5. Attribuer `pts_activation` points → insérer dans `points_historique` (`type='abonnement_activation'`), incrémenter `points_fidelite.solde_pts`
6. Mettre à jour `abonnements.niveau_cle` si changement de niveau
7. Mettre à jour `ateliers.statut='actif'`
8. Appeler `AtelierLimitsService::refreshSnapshot()` → met à jour `config_snapshot`

### Vérification quota avant création client (backend)
```php
$config = $abonnement->config_snapshot;
$quota = QuotaMensuel::where('atelier_id', $atelierId)
                     ->where('annee', now()->year)
                     ->where('mois', now()->month)
                     ->first();

if ($quota && $quota->nb_clients_crees >= $config['max_clients_par_mois']) {
    return response()->json(['error' => 'quota_clients_atteint'], 422);
}
// Créer le client puis incrémenter le quota
$quota->increment('nb_clients_crees');
```

### Gains de points offline (logique locale React + réconciliation serveur)
```
Quand un client est créé en offline :
1. App → incrémenter points_fidelite.solde_pts localement (+pts_par_client depuis config_snapshot)
2. App → créer entrée points_historique locale (type='client_cree', reference_id=client.id)
3. App → mettre en queue sync_queue

Au sync, le serveur :
1. Traite la création du client
2. Vérifie que les points correspondants n'ont pas déjà été attribués (via reference_id)
3. Insère dans points_historique serveur si non dupliqué
4. Recalcule solde_pts et retourne la valeur authoritative dans la réponse
5. L'app met à jour points_fidelite.solde_pts avec la valeur du serveur (le serveur gagne)

Même logique pour commande validée → type='commande_validee'
```

### Vérification device_id offline (logique locale React)
```js
// À l'ouverture de l'app par un Assistant
const membre = await database.collections.get('equipe_membres').find(membreId)
const currentDeviceId = await CapacitorDevice.getId()

if (membre.deviceId && membre.deviceId !== currentDeviceId.identifier) {
  // Accès refusé offline — afficher message "Appareil non reconnu"
  // Forcer connexion internet pour vérification Cloud
  redirectToLogin()
}
```

### Vérification quota avant création client (logique locale React)
```js
// 100% offline — lit config_snapshot et quotas_mensuels locaux
const abonnement = await database.collections.get('abonnements').find(atelierId)
const config = JSON.parse(abonnement.configSnapshot)

const quota = await database.collections.get('quotas_mensuels')
  .query(Q.where('atelier_id', atelierId), Q.where('annee', currentYear), Q.where('mois', currentMonth))
  .fetch()

if (quota[0]?.nbClientsCrees >= config.max_clients_par_mois) {
  showError('Quota mensuel de clients atteint. Renouvelez ou passez à un plan supérieur.')
  return
}
// Créer le client, incrémenter quota local, mettre en queue
```

### Vérification feature offline (logique locale React)
```js
// Afficher/masquer les menus selon le plan — 100% offline
const config = JSON.parse(abonnement.configSnapshot)

// Menu Photos VIP
if (!config.photos_vip) hideMenu('photos_vip')

// Menu Facturation
if (!config.facture_whatsapp) hideMenu('facturation')

// Limite équipe
if (config.max_assistants === 0) disableButton('creer_assistant')
```

### Conversion points → bonus abonnement
1. Vérifier `solde_pts >= seuil_conversion_pts` (depuis `config_snapshot`)
2. Vérifier `bonus_actif=false`
3. Déduire les points : insérer dans `points_historique` (`type='conversion'`, points négatif)
4. Mettre à jour `points_fidelite.solde_pts`
5. Activer le bonus : `bonus_actif=true`, `bonus_jours_restants=31`, `bonus_timestamp_debut=now()`
6. Le principal `jours_restants` reste figé pendant tout le bonus
7. Appeler `AtelierLimitsService::refreshSnapshot()`

### Fin du bonus → reprise du principal
1. Détecter `bonus_jours_restants=0`
2. `bonus_actif=false`, `bonus_jours_restants=0`, `bonus_timestamp_debut=null`
3. Recalculer `timestamp_expiration` depuis maintenant + `jours_restants` du principal
4. Appeler `AtelierLimitsService::refreshSnapshot()`

### Dashboard 3 indicateurs (calculé localement côté client)
```js
// 100% offline, WatermelonDB, cible < 50ms
const commandes = await database.collections.get('commandes')
  .query(Q.where('atelier_id', atelierId), Q.where('statut', 'en_cours'))
  .fetch()

const today = new Date()
const in48h = new Date(today.getTime() + 48 * 60 * 60 * 1000)

const enRetard = commandes.filter(c => new Date(c.dateLivraisonPrevue) < today).length
const dans48h = commandes.filter(c => {
  const d = new Date(c.dateLivraisonPrevue)
  return d >= today && d <= in48h
}).length
const enCours = commandes.length
```

### Rentabilité Admin — requêtes SQL
```sql
-- MRR estimé (Monthly Recurring Revenue)
SELECT SUM(nc.prix_mensuel_equivalent_xof) as mrr_xof, COUNT(*) as nb_actifs
FROM abonnements a
JOIN niveaux_config nc ON a.niveau_cle = nc.cle
WHERE a.statut = 'actif';

-- CA mensuel réel (paiements en ligne confirmés)
SELECT p.provider, p.niveau_cle, COUNT(*) as nb_paiements, SUM(p.montant) as ca_xof
FROM paiements p
WHERE p.statut = 'completed' AND YEAR(p.completed_at) = ? AND MONTH(p.completed_at) = ?
GROUP BY p.provider, p.niveau_cle ORDER BY ca_xof DESC;

-- CA mensuel réel (transactions manuelles utilisées)
SELECT niveau_cle, COUNT(*) as nb_activations, SUM(montant) as ca_xof
FROM transactions_abonnement
WHERE statut = 'utilise' AND canal = 'manuel' AND YEAR(utilise_at) = ? AND MONTH(utilise_at) = ?
GROUP BY niveau_cle ORDER BY ca_xof DESC;

-- Taux de conversion paiement (pending → completed)
SELECT provider,
  COUNT(*) as total,
  SUM(statut = 'completed') as confirmes,
  SUM(statut = 'failed') as echoues,
  SUM(statut = 'expired') as expires,
  ROUND(SUM(statut = 'completed') / COUNT(*) * 100, 1) as taux_conversion_pct
FROM paiements WHERE YEAR(initiated_at) = ? AND MONTH(initiated_at) = ?
GROUP BY provider;

-- Taux de conversion essai → payant
SELECT
  COUNT(*) as total,
  SUM(statut = 'actif')  as actifs,
  SUM(statut = 'essai')  as en_essai,
  SUM(statut = 'expire') as expires,
  ROUND(SUM(statut = 'actif') / COUNT(*) * 100, 1) as taux_conversion_pct
FROM ateliers WHERE deleted_at IS NULL;

-- Churn du mois
SELECT COUNT(*) as churned FROM ateliers
WHERE statut = 'expire' AND YEAR(updated_at) = ? AND MONTH(updated_at) = ?;
```

---

## 10. WatermelonDB — Schémas Locaux (Offline-First)

> Ces schémas définissent la base SQLite locale sur l'appareil React/Capacitor.
> **Règle absolue :** L'app ne requête JAMAIS le serveur pour les données listées ici — tout se passe en local, la sync est différée.

### Tableau de référence — stockage local vs serveur uniquement

| Table | WatermelonDB | Serveur uniquement | Notes |
|---|---|---|---|
| `ateliers` | ✅ | | Pull only depuis serveur |
| `abonnements` | ✅ | | Pull only + `config_snapshot` |
| `equipe_membres` | ✅ | | Pull only — pour device_id check et affichage équipe |
| `parametres_atelier` | ✅ | | Bidirectionnel |
| `communications_config` | ✅ | | Bidirectionnel |
| `points_fidelite` | ✅ | | Optimiste local, serveur reconcile |
| `points_historique` | ✅ | | Append local, push à la sync |
| `notifications_systeme` | ✅ | | Pull only, `is_read` bidirectionnel |
| `vetements` | ✅ | | Bidirectionnel |
| `clients` | ✅ | | Bidirectionnel — feature principale |
| `mesures` | ✅ | | Bidirectionnel |
| `commandes` | ✅ | | Bidirectionnel — feature principale |
| `quotas_mensuels` | ✅ mois courant | | Optimiste local, serveur reconcile |
| `niveaux_config` | | ✅ | Résolu dans `config_snapshot` |
| `fonctionnalites` | | ✅ | Panel admin uniquement |
| `paiements` | | ✅ | Initiation nécessite internet — webhook côté serveur |
| `transactions_abonnement` | | ✅ | Activation nécessite internet |
| `otp_tokens` | | ✅ | Auth flow nécessite internet |
| `demandes_recuperation` | | ✅ | |
| `photos_vip` (métadonnées) | | ✅ | Fichiers sur R2 |
| Toutes les tables admin | | ✅ | Panel web uniquement |

### Schéma WatermelonDB complet (src/database/schema.js)

```js
import { appSchema, tableSchema } from '@nozbe/watermelondb'

export default appSchema({
  version: 1,
  tables: [

    tableSchema({
      name: 'ateliers',
      columns: [
        { name: 'proprietaire_id', type: 'string' },
        { name: 'nom', type: 'string' },
        { name: 'is_maitre', type: 'boolean' },
        { name: 'statut', type: 'string' }, // 'actif','expire','essai','gele'
        { name: 'essai_expire_at', type: 'number', isOptional: true },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
        { name: 'deleted_at', type: 'number', isOptional: true },
      ]
    }),

    tableSchema({
      name: 'abonnements',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'niveau_cle', type: 'string' },
        { name: 'statut', type: 'string' },
        { name: 'jours_restants', type: 'number' },
        { name: 'timestamp_debut', type: 'number', isOptional: true },
        { name: 'timestamp_expiration', type: 'number', isOptional: true },
        { name: 'bonus_actif', type: 'boolean' },
        { name: 'bonus_jours_restants', type: 'number' },
        { name: 'bonus_niveau_cle', type: 'string', isOptional: true },
        { name: 'bonus_timestamp_debut', type: 'number', isOptional: true },
        { name: 'config_snapshot', type: 'string', isOptional: true }, // JSON string — source de vérité offline pour toutes les limites et features
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'equipe_membres',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'code_acces', type: 'string' },
        { name: 'nom', type: 'string' },
        { name: 'prenom', type: 'string', isOptional: true },
        { name: 'role', type: 'string' }, // 'assistant','membre'
        { name: 'device_id', type: 'string', isOptional: true },
        { name: 'is_active', type: 'boolean' },
        { name: 'derniere_sync_at', type: 'number', isOptional: true },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
        { name: 'deleted_at', type: 'number', isOptional: true },
      ]
    }),

    tableSchema({
      name: 'parametres_atelier',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'langue', type: 'string' },
        { name: 'devise', type: 'string' },
        { name: 'unite_mesure', type: 'string' },
        { name: 'theme', type: 'string' },
        { name: 'mode_sync_photos', type: 'string' },
        { name: 'multi_ateliers_actif', type: 'boolean' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'communications_config',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'confirmation_commande', type: 'boolean' },
        { name: 'rappel_livraison_j2', type: 'boolean' },
        { name: 'commande_prete', type: 'boolean' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'points_fidelite',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'solde_pts', type: 'number' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'points_historique',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'type', type: 'string' },
        { name: 'points', type: 'number' },
        { name: 'description', type: 'string' },
        { name: 'reference_id', type: 'string', isOptional: true },
        { name: 'created_at', type: 'number' },
        { name: 'synced', type: 'boolean' }, // false = entrée créée offline, pas encore syncée
      ]
    }),

    tableSchema({
      name: 'notifications_systeme',
      columns: [
        { name: 'atelier_id', type: 'string', isOptional: true, isIndexed: true },
        { name: 'titre', type: 'string' },
        { name: 'contenu', type: 'string' },
        { name: 'type', type: 'string' },
        { name: 'is_read', type: 'boolean' },
        { name: 'read_synced', type: 'boolean' }, // false = is_read changé offline, pas encore syncé
        { name: 'created_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'vetements',
      columns: [
        { name: 'atelier_id', type: 'string', isOptional: true, isIndexed: true },
        { name: 'nom', type: 'string' },
        { name: 'libelles_mesures', type: 'string' }, // JSON string array
        { name: 'template_numero', type: 'number', isOptional: true },
        { name: 'is_systeme', type: 'boolean' },
        { name: 'is_archived', type: 'boolean' },
        { name: 'created_by', type: 'string', isOptional: true },
        { name: 'created_by_role', type: 'string', isOptional: true },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
        { name: 'deleted_at', type: 'number', isOptional: true },
      ]
    }),

    tableSchema({
      name: 'clients',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'nom', type: 'string', isIndexed: true },
        { name: 'prenom', type: 'string' },
        { name: 'telephone', type: 'string', isOptional: true },
        { name: 'type_profil', type: 'string' },
        { name: 'avatar_key', type: 'string', isOptional: true },
        { name: 'photo_local_path', type: 'string', isOptional: true }, // local only — jamais syncé
        { name: 'created_by', type: 'string' },
        { name: 'created_by_role', type: 'string' },
        { name: 'is_archived', type: 'boolean' },
        { name: 'archived_at', type: 'number', isOptional: true },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
        { name: 'deleted_at', type: 'number', isOptional: true },
      ]
    }),

    tableSchema({
      name: 'mesures',
      columns: [
        { name: 'client_id', type: 'string', isIndexed: true },
        { name: 'vetement_id', type: 'string' },
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'champs', type: 'string' }, // JSON string {"Longueur totale": 120, ...}
        { name: 'created_by', type: 'string' },
        { name: 'created_by_role', type: 'string' },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

    tableSchema({
      name: 'commandes',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'client_id', type: 'string', isIndexed: true },
        { name: 'vetement_id', type: 'string' },
        { name: 'created_by', type: 'string' },
        { name: 'created_by_role', type: 'string' },
        { name: 'quantite', type: 'number' },
        { name: 'prix', type: 'number', isOptional: true },
        { name: 'acompte', type: 'number', isOptional: true },
        { name: 'statut', type: 'string' },
        { name: 'date_commande', type: 'number' },
        { name: 'date_livraison_prevue', type: 'number', isOptional: true },
        { name: 'date_livraison_effective', type: 'number', isOptional: true },
        { name: 'note_interne', type: 'string', isOptional: true },
        { name: 'photo_tissu_local_path', type: 'string', isOptional: true }, // local only — jamais syncé
        { name: 'rappel_j2_envoye', type: 'boolean' },
        { name: 'created_at', type: 'number' },
        { name: 'updated_at', type: 'number' },
        { name: 'deleted_at', type: 'number', isOptional: true },
      ]
    }),

    tableSchema({
      name: 'quotas_mensuels',
      columns: [
        { name: 'atelier_id', type: 'string', isIndexed: true },
        { name: 'annee', type: 'number' },
        { name: 'mois', type: 'number' },
        { name: 'nb_clients_crees', type: 'number' },
        { name: 'nb_commandes_creees', type: 'number' },
        { name: 'nb_photos_vip', type: 'number' },
        { name: 'nb_factures_envoyees', type: 'number' },
        { name: 'updated_at', type: 'number' },
      ]
    }),

  ]
})
```

> **⚠️ Champs locaux uniquement — ne jamais inclure dans le payload de sync vers le serveur :**
> - `clients.photo_local_path`
> - `commandes.photo_tissu_local_path`
> - `points_historique.synced`
> - `notifications_systeme.read_synced`

### Stratégie de sync par table

| Table | Direction | Stratégie de conflit |
|---|---|---|
| `ateliers` | Serveur → Local | Pull only — le serveur gagne toujours |
| `abonnements` | Serveur → Local | Pull only — inclut config_snapshot recalculé |
| `paiements` | Serveur uniquement | Jamais en local — initiation et webhook côté serveur |
| `transactions_abonnement` | Serveur uniquement | Jamais en local — activation nécessite internet |
| `equipe_membres` | Serveur → Local | Pull only — création/révocation nécessitent internet |
| `parametres_atelier` | Bidirectionnel | Last-write-wins — serveur gagne en cas de conflit |
| `communications_config` | Bidirectionnel | Last-write-wins |
| `points_fidelite` | Bidirectionnel | Serveur gagne — la valeur authoritative vient du serveur |
| `points_historique` | Push nouvelles entrées | Append-only — dédupliqué par `reference_id` côté serveur |
| `notifications_systeme` | Pull + Push `is_read` | Le serveur gagne sur le contenu, local gagne sur `is_read` |
| `vetements` | Bidirectionnel | Last-write-wins par `updated_at` |
| `clients` | Bidirectionnel | Last-write-wins par `updated_at` |
| `mesures` | Bidirectionnel | Last-write-wins par `updated_at` |
| `commandes` | Bidirectionnel | Last-write-wins par `updated_at` |
| `quotas_mensuels` | Bidirectionnel | Serveur gagne — reconciliation au sync pour éviter dépassement |

---

## 11. Fichiers à Créer / Modifier

```
SUPPRIMER:
  database/migrations/0001_01_01_000000_create_users_table.php
  app/Models/User.php

CRÉER — Migrations (dans l'ordre):
  database/migrations/2026_04_21_000001_create_niveaux_config_table.php
  database/migrations/2026_04_21_000002_create_proprietaires_table.php
  database/migrations/2026_04_21_000003_create_ateliers_table.php
  database/migrations/2026_04_21_000004_create_abonnements_table.php
  database/migrations/2026_04_21_000005_create_paiements_table.php
  database/migrations/2026_04_21_000006_create_transactions_abonnement_table.php
  database/migrations/2026_04_21_000007_create_equipe_membres_table.php
  database/migrations/2026_04_21_000008_create_parametres_atelier_table.php
  database/migrations/2026_04_21_000009_create_communications_config_table.php
  database/migrations/2026_04_21_000010_create_points_fidelite_table.php
  database/migrations/2026_04_21_000011_create_points_historique_table.php
  database/migrations/2026_04_21_000012_create_notifications_systeme_table.php
  database/migrations/2026_04_21_000013_create_vetements_table.php
  database/migrations/2026_04_21_000014_create_clients_table.php
  database/migrations/2026_04_21_000015_create_mesures_table.php
  database/migrations/2026_04_21_000016_create_commandes_table.php
  database/migrations/2026_04_21_000017_create_quotas_mensuels_table.php
  database/migrations/2026_04_21_000018_create_photos_vip_table.php
  database/migrations/2026_04_21_000019_create_otp_tokens_table.php
  database/migrations/2026_04_21_000020_create_demandes_recuperation_table.php
  database/migrations/2026_04_21_000021_create_fonctionnalites_table.php
  database/migrations/2026_04_21_000022_create_admins_table.php
  database/migrations/2026_04_21_000023_alter_niveaux_config_add_admin_columns.php
  database/migrations/2026_04_21_000024_alter_paiements_add_admin_columns.php
  database/migrations/2026_04_21_000025_create_tickets_support_table.php
  database/migrations/2026_04_21_000026_create_tickets_messages_table.php
  database/migrations/2026_04_21_000027_create_admin_audit_log_table.php
  database/migrations/2026_04_21_000028_create_niveaux_config_changelog_table.php
  database/migrations/2026_04_21_000029_create_offres_speciales_table.php
  database/migrations/2026_04_21_000030_create_liste_noire_table.php

CRÉER — Modèles:
  app/Models/Proprietaire.php
  app/Models/Atelier.php
  app/Models/Abonnement.php
  app/Models/Paiement.php
  app/Models/TransactionAbonnement.php
  app/Models/EquipeMembre.php
  app/Models/ParametresAtelier.php
  app/Models/CommunicationsConfig.php
  app/Models/PointsFidelite.php
  app/Models/PointsHistorique.php
  app/Models/NotificationSysteme.php
  app/Models/Vetement.php
  app/Models/Client.php
  app/Models/Mesure.php
  app/Models/Commande.php
  app/Models/QuotaMensuel.php
  app/Models/PhotoVip.php
  app/Models/OtpToken.php
  app/Models/DemandeRecuperation.php
  app/Models/NiveauConfig.php
  app/Models/Admin.php
  app/Models/Fonctionnalite.php
  app/Models/TicketSupport.php
  app/Models/TicketMessage.php
  app/Models/AdminAuditLog.php
  app/Models/NiveauConfigChangelog.php
  app/Models/OffreSpeciale.php
  app/Models/ListeNoire.php

CRÉER — Services:
  app/Services/AtelierLimitsService.php     ← CRITIQUE offline-first
  app/Services/PaymentService.php           ← CRITIQUE orchestration paiement + activation
  app/Contracts/PaymentProviderContract.php  ← Interface provider-agnostic
  app/Services/Payment/FedaPayProvider.php   ← Premier provider (ou celui choisi)
  app/DTOs/PaymentInitiationResult.php       ← Résultat de l'initiation
  app/DTOs/WebhookPayload.php                ← Données parsées du webhook
  app/DTOs/PaymentStatus.php                 ← Statut vérifié côté provider

CRÉER — Controllers (paiement):
  app/Http/Controllers/Api/PaiementController.php   ← initier, checkStatus
  app/Http/Controllers/Api/WebhookController.php     ← handle (pas d'auth — webhook externe)

CRÉER — Commands (schedulées):
  app/Console/Commands/ExpireStalePayments.php       ← ->hourly() — expire les paiements pending
  app/Console/Commands/CheckPendingPayments.php      ← ->everyFifteenMinutes() — fallback webhook manqué

CRÉER — Config:
  config/payment.php                                 ← providers, clés API, return_url

CRÉER — Enums:
  app/Enums/AdminPermission.php

CRÉER — Policies:
  app/Policies/ClientPolicy.php
  app/Policies/CommandePolicy.php
  app/Policies/VetementPolicy.php
  app/Policies/MesurePolicy.php
  app/Policies/EquipeMembrePolicy.php
  app/Policies/AdminPolicy.php
  app/Policies/TicketSupportPolicy.php
  app/Policies/OffreSpecialePolicy.php
  app/Policies/NiveauConfigPolicy.php

CRÉER — Seeders:
  database/seeders/FonctionnalitesSeeder.php
  database/seeders/NiveauxConfigSeeder.php
  database/seeders/VetementsSeeder.php
  database/seeders/AdminSeeder.php

CRÉER — WatermelonDB (frontend):
  src/database/schema.js                    ← Schéma complet (voir Section 10)
  src/database/index.js                     ← Initialisation DB
  src/database/models/Atelier.js
  src/database/models/Abonnement.js
  src/database/models/EquipeMembre.js
  src/database/models/ParametresAtelier.js
  src/database/models/CommunicationsConfig.js
  src/database/models/PointsFidelite.js
  src/database/models/PointsHistorique.js
  src/database/models/NotificationSysteme.js
  src/database/models/Vetement.js
  src/database/models/Client.js
  src/database/models/Mesure.js
  src/database/models/Commande.js
  src/database/models/QuotaMensuel.js
  src/services/AtelierLimitsService.js      ← Lit config_snapshot localement

MODIFIER:
  database/seeders/DatabaseSeeder.php       → appeler les 4 seeders dans l'ordre
  config/auth.php                           → guards proprietaires + admins
  app/Http/Kernel.php ou bootstrap/app.php  → activer Sanctum middleware
  routes/api.php                            → ajouter routes paiement + webhook
  app/Console/Kernel.php                    → scheduler ExpireStalePayments + CheckPendingPayments
```

---

## 12. Notes de Scalabilité

- **UUID partout** → compatible avec la sync offline multi-appareils, pas de collision d'IDs entre client local et serveur
- **MySQL sur VPS** → pas de dépendance externe, contrôle total, coût maîtrisé
- **WatermelonDB LokiJSAdapter Phase 1, SQLiteAdapter Phase 2** → surveiller consommation RAM dès 500 clients sur téléphones 2 Go RAM
- **config_snapshot sur abonnements** → l'app n'a JAMAIS besoin de requêter niveaux_config. 0 requête serveur pour connaître ses droits en offline
- **config JSON sur niveaux_config** → ajouter une feature = 1 ligne dans FonctionnalitesSeeder + 1 clé dans les configs JSON. Aucune migration, aucun ALTER TABLE
- **quotas_mensuels dénormalisé** → pas de COUNT() sur millions de lignes à chaque vérification de quota
- **JSON pour mesures et libellés** → chaque atelier peut avoir ses propres noms de mesures
- **created_by + created_by_role** (polymorphisme léger) → évite un JOIN complexe pour savoir qui a créé quoi
- **bonus_actif séparé** → la logique pause/reprise n'impacte pas le compteur principal
- **is_systeme sur vetements** → les 20 templates restent en base partagée, un atelier ne les duplique pas
- **softDeletes sur tables métier** → les données ne disparaissent jamais accidentellement
- **Sanctum morphable** → Proprietaire et EquipeMembre partagent personal_access_tokens. Guard séparé pour Admin
- **Paiement provider-agnostic** → `PaymentProviderContract` interface + config/payment.php. Changer de provider = 1 classe + 3 lignes de config. Aucune migration, aucun changement de schéma. `provider_metadata` JSON absorbe les différences entre passerelles
- **Deux canaux d'activation coexistent** → webhook automatique (flux normal utilisateur) + code manuel admin (promos, cash, partenariats). Le canal est tracé sur chaque `TransactionAbonnement` via le champ `canal`
- **Idempotence webhook** → le handler vérifie `paiement.statut !== 'pending'` avant traitement. Même si le provider envoie 3 fois le webhook, l'abonnement n'est activé qu'une fois
- **Fallback webhook manqué** → job schedulé `CheckPendingPayments` vérifie le statut côté provider toutes les 15 minutes pour les paiements `pending` de plus de 10 minutes
- **Expiration paiements stale** → job `ExpireStalePayments` nettoie les `pending` expirés toutes les heures
- **Laravel Policies** → sécurité applicative complète, chaque action vérifiée par Gate
- **offres_speciales + config_override** → deals enterprise sans créer de plan dédié. Résolu transparentement dans config_snapshot
- **Admin permissions JSON** → ajouter une nouvelle permission = 1 constante dans AdminPermission.php. Aucune migration
- **photos locales jamais syncées** → photo_local_path et photo_tissu_local_path existent uniquement dans WatermelonDB, exclus du payload de sync
- **append-only sur audit_log et points_historique** → traçabilité complète sans risque de modification
