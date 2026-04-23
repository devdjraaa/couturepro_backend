# COUTURE PRO — Roadmap Sprints (Claude Code)

> Coller chaque sprint dans une conversation Claude Code fraîche.
> Chaque sprint est **autonome et testable** avant de passer au suivant.

---

## ✅ SPRINT 1 — Fondations DB
**Objectif :** `php artisan migrate --seed` passe sans erreur. Toute la structure est en place.

### Ce que ce sprint crée
- Supprimer `database/migrations/0001_01_01_000000_create_users_table.php` et `app/Models/User.php`
- 30 fichiers de migration (ordre FK respecté)
- 28 modèles Eloquent
- 4 seeders (Fonctionnalites, NiveauxConfig, Vetements, Admin)
- `config/auth.php` mis à jour (guards proprietaires + admins)
- Sanctum installé et configuré

### Commandes à lancer après le sprint
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate --seed
```

### Critère de succès
- `php artisan migrate --seed` tourne sans erreur
- `php artisan tinker` → `App\Models\NiveauConfig::count()` retourne 6
- `php artisan tinker` → `App\Models\Fonctionnalite::count()` retourne 14

---

## ✅ SPRINT 2 — Auth complète
**Objectif :** Inscription, login, OTP, récupération de compte, tokens Sanctum.

### Ce que ce sprint crée
- `app/Http/Controllers/Api/Auth/ProprietaireAuthController.php`
  - `POST /api/auth/inscription` → étape 1 : créer le compte + envoyer OTP
  - `POST /api/auth/verifier-otp` → étape 2 : valider OTP + créer atelier maître + abonnement essai
  - `POST /api/auth/login` → retourne token Sanctum
  - `POST /api/auth/logout`
  - `GET /api/auth/me`
- `app/Http/Controllers/Api/Auth/EquipeMembreAuthController.php`
  - `POST /api/auth/equipe/login` → code_acces + password + device_id
  - Lock appareil après 1ère connexion
- `app/Http/Controllers/Api/Auth/RecuperationController.php`
  - 5 étapes de récupération de compte
- `app/Services/OtpService.php`
- `app/Http/Requests/Auth/*.php` (FormRequests)
- Vérification liste noire à l'inscription
- Routes dans `routes/api.php`

### Critère de succès
- Postman : inscription → OTP → login → token valide
- `App\Models\Proprietaire::first()->atelier` non null
- `App\Models\Atelier::first()->abonnement` non null avec `statut = 'essai'`

---

## ✅ SPRINT 3 — Core métier offline-first
**Objectif :** CRUD complet clients/commandes/mesures + endpoint sync batch.

### Ce que ce sprint crée
- `app/Http/Controllers/Api/ClientController.php` (index, store, update, destroy, archiver)
- `app/Http/Controllers/Api/CommandeController.php`
- `app/Http/Controllers/Api/MesureController.php`
- `app/Http/Controllers/Api/VetementController.php`
- `app/Http/Controllers/Api/SyncController.php`
  - `POST /api/sync/push` → reçoit batch (20 ops max), applique en transaction
  - `GET /api/sync/pull` → retourne les données mises à jour depuis `last_pulled_at`
- `app/Services/AtelierLimitsService.php` → `getConfig()`, `canCreateClient()`, `canCreateCommande()`
- `app/Services/SyncService.php` → orchestration batch
- `app/Policies/*.php` (Client, Commande, Mesure, Vetement, EquipeMembre)
- `app/Http/Requests/Api/*.php`
- Incrémentation `quotas_mensuels` à chaque création
- Régénération `config_snapshot` sur `abonnements` à chaque pull

### Critère de succès
- Créer 1 client via API → quota incrémenté
- Push/Pull sync répond en < 500ms pour 20 ops
- Policy bloque si quota atteint

---

## ✅ SPRINT 4 — Paiement provider-agnostic
**Objectif :** Flux paiement complet FedaPay + activation auto + fallback.

### Ce que ce sprint crée
- `app/Contracts/PaymentProviderContract.php` (interface)
- `app/Services/Payment/FedaPayProvider.php`
- `app/Services/PaymentService.php` → `initiate()`, `activate()`, `handleWebhook()`
- `app/DTOs/PaymentInitiationResult.php`
- `app/DTOs/WebhookPayload.php`
- `app/DTOs/PaymentStatus.php`
- `app/Http/Controllers/Api/PaiementController.php`
  - `POST /api/paiements/initier`
  - `GET /api/paiements/{id}/status`
- `app/Http/Controllers/Api/WebhookController.php`
  - `POST /api/webhooks/{provider}` (pas d'auth)
- `app/Console/Commands/ExpireStalePayments.php` → `->hourly()`
- `app/Console/Commands/CheckPendingPayments.php` → `->everyFifteenMinutes()`
- `config/payment.php`
- Scheduler dans `bootstrap/app.php`

### Critère de succès
- Initier un paiement → reçoit `checkout_url`
- Simuler webhook → abonnement activé, `config_snapshot` mis à jour
- Job expire les `pending` après `expires_at`

---

## ✅ SPRINT 5 — Espace Admin
**Objectif :** Panel admin fonctionnel avec permissions granulaires.

### Ce que ce sprint crée
- `app/Http/Controllers/Admin/AuthController.php` (login admin, guard séparé)
- `app/Http/Controllers/Admin/AtelierController.php` (liste, détail, geler, dégeler)
- `app/Http/Controllers/Admin/NiveauConfigController.php` (CRUD plans)
- `app/Http/Controllers/Admin/TransactionController.php` (créer codes manuels)
- `app/Http/Controllers/Admin/PaiementController.php` (valider manuellement)
- `app/Http/Controllers/Admin/TicketController.php` (list, assigner, répondre, fermer)
- `app/Http/Controllers/Admin/OffreSpecialeController.php`
- `app/Http/Controllers/Admin/ListeNoireController.php`
- `app/Http/Controllers/Admin/AuditLogController.php`
- `app/Http/Controllers/Admin/NotificationController.php` (broadcast)
- `app/Http/Middleware/AdminAuth.php` + `CheckAdminPermission.php`
- `app/Policies/AdminPolicy.php` etc.
- `routes/admin.php` avec prefix `/api/admin`
- `app/Observers/NiveauConfigObserver.php` → écrit dans `niveaux_config_changelog`

### Critère de succès
- Login admin → token valide guard `admin`
- Super admin peut tout faire
- Admin avec permissions limitées bloqué sur les autres routes
- Modification d'un plan → entrée dans `niveaux_config_changelog`

---

## 📱 SPRINT 6 — WatermelonDB (frontend)
**Objectif :** Schéma local SQLite + modèles JS + service limits offline.

### Ce que ce sprint crée
- `src/database/schema.js` (schéma complet toutes les tables)
- `src/database/index.js` (init DB LokiJSAdapter Phase 1)
- `src/database/models/Atelier.js`
- `src/database/models/Abonnement.js`
- `src/database/models/EquipeMembre.js`
- `src/database/models/ParametresAtelier.js`
- `src/database/models/CommunicationsConfig.js`
- `src/database/models/PointsFidelite.js`
- `src/database/models/PointsHistorique.js`
- `src/database/models/NotificationSysteme.js`
- `src/database/models/Vetement.js`
- `src/database/models/Client.js` (avec `photo_local_path` local uniquement)
- `src/database/models/Mesure.js`
- `src/database/models/Commande.js` (avec `photo_tissu_local_path` local uniquement)
- `src/database/models/QuotaMensuel.js`
- `src/services/AtelierLimitsService.js` → lit `config_snapshot` offline
- `src/services/SyncService.js` → push/pull batch vers l'API

### Critère de succès
- App démarre offline sans crash
- `AtelierLimitsService.canCreateClient()` retourne false si quota atteint (sans réseau)
- Sync push/pull fonctionnel en ligne

---

## 🗺️ Résumé visuel

```
Sprint 1  │████████│ DB structure + models         → migrate --seed ✅
Sprint 2  │████████│ Auth + OTP + tokens            → login fonctionnel✅
Sprint 3  │████████│ CRUD métier + sync batch       → offline ready✅
Sprint 4  │████████│ Paiement + webhook             → FedaPay live ✅
Sprint 5  │████████│ Panel admin complet            → admin opérationnel ✅
Sprint 6  │████████│ WatermelonDB frontend          → app 100% offline
```
