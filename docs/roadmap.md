# CouturePro — Roadmap & Ce qui reste à faire

> Référence unique remplaçant `etat-avancement.md` et `sprints-restants-mvp1.md`.
> Dernière mise à jour : 26 avril 2026

---

## Table des matières

1. [Stack technique réelle](#1-stack-technique-réelle--corrections)
2. [Ce qui est implémenté](#2-ce-qui-est-implémenté)
3. [Ce qui reste — Phase 1 MVP (priorité immédiate)](#3-ce-qui-reste--phase-1-mvp)
4. [Nouvelles fonctionnalités identifiées (Phase 1.5)](#4-nouvelles-fonctionnalités-identifiées--phase-15)
5. [Phase 2 — Module Caisse + Multi-tenant](#5-phase-2--module-caisse--multi-tenant)
6. [Phase 3 — Mobile Capacitor + Sync offline](#6-phase-3--mobile-capacitor--sync-offline)

---

## 1. Stack technique réelle — Corrections

> **Important** : les documents précédents et le CDC v1.3 mentionnent Supabase, Cloudflare R2 et Retool. Ces services ont été abandonnés. Tout est centralisé sur le VPS.

| Ancienne référence CDC | Réalité actuelle |
|---|---|
| Supabase (PostgreSQL + Auth + RLS) | MySQL sur VPS — Laravel gère l'isolation par `atelier_id` |
| Cloudflare R2 (photos VIP) | `Storage::disk('public')` sur VPS — même règle pour toutes les photos |
| Retool (Dashboard Admin) | Panel admin web custom ✅ déjà implémenté (React + API admin) |
| WatermelonDB (offline) | API directe pour l'instant — sync différée à venir (Phase 3) |
| Plugin timer Capacitor | Timestamps serveur utilisés — timer natif optionnel Phase 3 |

**Conséquences** :
- Les photos (tissu, profil client, vêtements) sont toutes sur le VPS via `Storage::disk('public')`.
- Le CDC v1.3 dit "photos tissu = local uniquement" — acceptable pour MVP web, à réévaluer pour l'APK Capacitor.
- La sync batch `POST /sync/push` + `GET /sync/pull` reste la seule couche de synchronisation.

---

## 2. Ce qui est implémenté

### Backend ✅

| Domaine | État | Notes |
|---|---|---|
| Auth proprietaire (OTP, récupération 5 étapes) | ✅ Complet | |
| Auth équipe (code d'accès, device_id) | ✅ Complet | |
| Auth admin (login + change-password) | ✅ Complet | 1 seul niveau admin actuellement |
| Clients (CRUD + VIP + archivage + quotas) | ✅ Complet | |
| Mesures (JSON flexible par client) | ✅ Complet | |
| Vêtements (20 templates + custom + galerie multi-images) | ✅ Complet | |
| Commandes (CRUD + statuts + urgence + photo tissu) | ✅ Complet | |
| Paiements commandes (3 modes + WhatsApp relevé auto) | ✅ Complet | |
| Abonnement (essai 14j, actif/expiré, bonus, config_snapshot) | ✅ Complet | |
| Paiements FedaPay (initier + webhooks + crons purge) | ✅ Complet | |
| Points fidélité (accumulation + conversion bonus 31j) | ✅ Complet | |
| Équipe (rôles, codes accès, révocation) | ✅ Complet | Permissions hardcodées |
| Notifications système (broadcast + mark-as-read) | ✅ Complet | |
| Support tickets (conversation + PJ + notes internes) | ✅ Complet | |
| Paramètres (profil, atelier, communications, MDP) | ✅ Complet | |
| WhatsApp (rappel client, relevé après paiement) | ✅ Complet | |
| Sync push/pull | ✅ Complet (backend) | Frontend non connecté |
| Admin : ateliers, plans, transactions, paiements, tickets | ✅ Complet | |
| Admin : offres, liste noire, audit, fidélité, notifications | ✅ Complet | |
| Crons : purge paiements, expiry abonnements, alertes expiration | ✅ Complet | |

### Frontend ✅

| Domaine | État | Notes |
|---|---|---|
| Auth complète (proprio + équipe + admin) | ✅ Complet | |
| Dashboard (stats + commandes récentes) | ⚠️ Partiel | Indicateurs "en retard" / "48h" à vérifier |
| Clients (CRUD + VIP + mesures + commandes onglets) | ✅ Complet | |
| Commandes (CRUD + statuts + paiements + relevé PDF) | ✅ Complet | |
| Mesures (CRUD + export PDF local) | ✅ Complet | |
| Vêtements / Catalogue (galerie multi-images, système/perso) | ✅ Complet | |
| Points fidélité (solde + historique + conversion) | ✅ Complet | |
| Communications (toggles WhatsApp) | ⚠️ Partiel | 3 déclenchements auto manquants |
| Équipe (ajout + code + suppression) | ✅ Complet | UI permissions manquante |
| Paramètres (profil + atelier + abonnement + sécurité + thème) | ✅ Complet | Devise/unité mesure manquante |
| Notifications | ✅ Complet | |
| Support tickets | ✅ Complet | |
| Abonnement (activation code, countdown, FeatureGate) | ✅ Complet | |
| Admin panel complet (toutes les pages) | ✅ Complet | |
| À propos, FAQ, Contact, Historique | ✅ Complet | |
| Export PDF mesures (jsPDF local) | ✅ Complet | |
| Export PDF relevé paiements (jsPDF local) | ✅ Complet | |

### Ce qui est **délibérément non fonctionnel**

| Élément | Raison |
|---|---|
| **i18n / traductions** | Structure `lang/i18n.js` + `locales/fr/` existe MAIS **tous les textes sont hardcodés** en JSX — le système de traduction n'est pas branché. À traiter avant le support anglais (Phase 2). |
| **Sync offline frontend** | `SyncController` backend opérationnel. Frontend appelle l'API directement sans sync_queue locale. |
| **Photos profil client (réelle)** | Avatars SVG implémentés. Upload photo galerie/caméra = Phase 1 restant (Sprint 9). |
| **Capacitor** | L'app tourne en web. Les plugins natifs (Share, Camera, Network, Notifications) ne sont pas encore intégrés. |

---

## 3. Ce qui reste — Phase 1 MVP

> Sprints à faire dans l'ordre. Chaque sprint est autonome.

---

### Sprint 8 — Dashboard 3 indicateurs + Badges alertes livraisons
> CDC §9.2 + §9.9

**Frontend**
- `DashboardPage.jsx` : remplacer stats génériques par 3 cards :
  - "En retard" (`date_livraison_prevue < today` + `statut = en_cours`) → fond rouge si > 0
  - "Dans 48h" (livraison dans les 2 prochains jours) → fond orange si > 0
  - "En cours" (total `statut = en_cours`) → neutre
- `BottomNavigation.jsx` : badge rouge sur icône Commandes si `en_retard > 0 || dans48h > 0`
- `useCommandeStats()` : calculer les 3 indicateurs côté client à partir de `useCommandes()`
- `CommandesPage.jsx` : paramètre URL `?filtre=retard` et `?filtre=48h` pour filtrer depuis le dashboard

**Effort estimé** : 4h

---

### Sprint 9 — Photo profil client + Anti-doublon
> CDC §9.3

**Frontend**
- `ClientForm.jsx` : option upload photo (galerie) en plus des avatars existants
  - `<input type="file" accept="image/*">` → compression canvas (max 200 Ko) → base64 → localStorage `client_photo_{id}`
  - Pas envoyé au backend (local uniquement)
- `Avatar.jsx` : priorité photo base64 locale > avatar_index > initiales
- `clientService.create()` : vérification anti-doublon Nom+Prénom (côté frontend + côté backend)

**Backend**
- `ClientController::store()` : ajouter vérification `where('nom', x)->where('prenom', y)->exists()`

**Effort estimé** : 3h

---

### Sprint 10 — Communications WhatsApp (3 déclenchements automatiques)
> CDC §9.8

Les 3 modules WhatsApp du CDC doivent se déclencher au bon moment :

| Module | Déclencheur | Config |
|---|---|---|
| Confirmation commande | Après création commande (`useCreateCommande onSuccess`) | `confirmation_commande_on` |
| Rappel J-2 | Affiché dans Dashboard si livraison dans 48h (manuel) | `rappel_j2_on` |
| Commande prête | Quand `statut` passe à `livre` (`handleStatut` dans CommandeDetailPage) | `commande_prete_on` |

**Frontend**
- `CommunicationsPage.jsx` : 3 toggles séparés (+ master toggle existant) → backend `PUT /parametres/communications`
- `CommandeDetailPage.jsx` : dans `handleStatut`, si `statut === 'livre'` et `commande_prete_on` → ouvrir WhatsApp deeplink
- `useCreateCommande onSuccess` : si `confirmation_commande_on` → ouvrir deeplink de confirmation
- `WhatsAppController` backend : ajouter message type "commande_prete" avec montant restant

**Effort estimé** : 4h

---

### Sprint 11 — Compteur quota factures WhatsApp
> CDC §10.2

| Plan | Quota factures/mois |
|---|---|
| Standard | Non disponible |
| Premium Mensuel | 25 |
| Premium Annuel | Illimité |
| Magnat Mensuel | 50 |
| Magnat Annuel | Illimité |

**Backend**
- Ajouter `nb_factures_whatsapp` dans `QuotaMensuel`
- `CommandePaiementController::store()` : incrémenter le compteur si WhatsApp URL générée
- Bloquer si quota atteint (retourner `whatsapp_url: null` + message)

**Frontend**
- `usePlanFeature('facture_whatsapp')` : retourner aussi le quota restant
- Afficher le quota consommé dans `CommunicationsPage`

**Effort estimé** : 3h

---

### Sprint 12 — Paramètres : devise + unité de mesure
> CDC §9.11

**Frontend**
- `ParametresPage.jsx` onglet Préférences :
  - Select devise : XOF (FCFA), EUR, USD, GHS, NGN, MAD...
  - Select unité de mesure : cm / pouces
- Stocker dans `ParametresAtelier` backend + exposer dans `useAuth()` → `atelier.devise`, `atelier.unite_mesure`
- `formatCurrency.js` : utiliser la devise de l'atelier
- `MesureForm.jsx` + `MesureDisplay.jsx` : afficher l'unité (cm / pouces)

**Backend**
- `ParametresAtelier` : ajouter `devise` (default 'XOF') + `unite_mesure` (default 'cm')
- `ParametresController::updateAtelier()` : accepter ces nouveaux champs
- Migration : `add_devise_unite_to_parametres_ateliers`

**Effort estimé** : 3h

---

## 4. Nouvelles fonctionnalités identifiées — Phase 1.5

> Ces fonctionnalités ont été identifiées après la rédaction du CDC v1.3. Elles sont prioritaires avant la Phase 2.

---

### 4.1 i18n — Rendre les traductions fonctionnelles

**Problème** : `lang/i18n.js` + `locales/fr/` existent mais **tous les textes JSX sont hardcodés en français**. Le système ne sert à rien actuellement.

**Ce qu'il faut faire** :
1. Passer tous les composants et pages à `useTranslation()` (hook i18next)
2. Extraire les chaînes vers `locales/fr/translation.json` (clés structurées par domaine)
3. Créer `locales/en/translation.json` vide prêt pour Phase 2
4. Tester que tout l'UI reste en français après la migration

**Effort estimé** : 1-2 jours (tâche de refactoring pur — à planifier en dehors des sprints fonctionnels)

---

### 4.2 Gestion des permissions Assistant/Membre (granulaire)

**Problème** : Les permissions sont hardcodées dans `AuthContext.can()`. Le gérant ne peut pas configurer ce que ses assistants ont le droit de faire depuis l'interface.

**Ce qu'il faut faire** :

**Backend**
- Nouvelle table `permissions_equipe` : `atelier_id`, `role` (assistant/membre), `ressource` (clients/commandes/mesures/catalogue/paiements), `action` (create/update/delete/view`), `autorise` (boolean)
- Migration + seeder avec les valeurs par défaut du CDC §4.3
- `EquipeMembreController` : charger les permissions dans la réponse `GET /auth/me`
- Middleware ou helper `can()` côté backend qui vérifie `permissions_equipe`

**Frontend**
- `EquipePage.jsx` : section "Permissions" avec grille de toggles par ressource/action
- `AuthContext.can(action, ressource)` : lire depuis les permissions chargées à la connexion
- `FeatureGate` : utiliser `can()` pour les boutons Modifier/Supprimer

**Effort estimé** : 1 jour

---

### 4.3 Super Admin — Création d'admins de niveau inférieur

**Problème** : Actuellement il n'y a qu'un seul compte admin (`is_super_admin`). Le Super Admin ne peut pas déléguer l'accès support à un modérateur.

**Ce qu'il faut faire** :

**Backend**
- `Admin.role` : ajouter `super_admin | admin | support`
- Table `admin_permissions` ou champ JSON `permissions[]` sur `Admin`
- `Admin\AuthController` + `Admin\AdminsController` (nouveau) : `GET /admin/admins`, `POST /admin/admins`, `DELETE /admin/admins/{id}`
- Middleware `admin.permission` : vérifier le rôle avant chaque action sensible
- Super Admin : tous les droits. Admin : gérer ateliers/tickets/paiements. Support : tickets seulement.

**Frontend**
- Nouvelle page `AdminsPage` (`/admin/admins`) : liste + créer + révoquer
- `AdminSidebar` : lien visible uniquement pour super_admin
- `AdminProtectedRoute` : adapter selon les permissions

**Effort estimé** : 1 jour

---

### 4.4 Atelier maître — Gestion des sous-ateliers

**Problème** : Le CDC prévoit qu'un propriétaire peut avoir jusqu'à 7 ateliers et que l'atelier maître peut voir ses sous-ateliers. Cette logique n'est pas implémentée côté frontend (et partiellement côté backend).

**Ce qu'il faut faire** :

**Backend** (partiellement fait)
- `Atelier.is_maitre` existe. La relation "sous-ateliers du même proprietaire" existe via `proprietaire_id`.
- Ajouter route : `GET /ateliers/mes-ateliers` → tous les ateliers du propriétaire connecté
- Route `POST /ateliers` : créer un sous-atelier (avec essai 14j auto)
- Route `GET /ateliers/{id}/stats` : stats résumées d'un sous-atelier (pour tableau consolidé)

**Frontend**
- `ParametresPage.jsx` : section "Mes ateliers" avec liste + bouton "Ajouter un atelier"
- Switcher d'atelier dans le header (dropdown si > 1 atelier)
- Dashboard consolidé optionnel : chiffres agrégés tous ateliers

**Effort estimé** : 2 jours

---

## 5. Phase 2 — Module Caisse + Multi-tenant

---

### 5.1 Module Caisse

> CDC §10.3 — Gated par plan (Standard n'y a pas accès)

**Fonctionnalités**
- Tableau de bord financier mensuel : total encaissé, total en attente, commandes soldées vs en cours
- Suivi par client : total dû, historique versements, dette restante
- Export rapport mensuel PDF

**Ce qu'il faut faire** :

**Backend — Seeder**
- Ajouter feature `module_caisse` dans `FonctionnalitesSeeder` + dans la config de chaque plan `NiveauxConfigSeeder`
- `module_caisse: true` pour Premium et Magnat, `false` pour Standard

**Backend — Endpoints**
- `GET /caisse/stats` : total encaissé (mois courant), total en attente, nb commandes soldées
- `GET /caisse/clients` : classement clients par montant dû
- `GET /caisse/rapport-mensuel` : données pour export PDF

**Frontend**
- `CaissePage.jsx` : page protégée par `FeatureGate('module_caisse')`
- `useCaisseStats()` : hook React Query
- Export PDF rapport mensuel (jsPDF)
- Lien dans la navigation (visible selon plan)

**Effort estimé** : 3 jours

---

### 5.2 Multi-tenant — Wildcard Subdomains

> Architecture recommandée au lieu du toggle "Mode Multi-Ateliers"

**Vision** : chaque atelier a son sous-domaine `ateliernom.couturepro.com`. L'app est la même, le tenant est détecté depuis `window.location.hostname`.

**DNS** : wildcard `*.couturepro.com` → même VPS Laravel.

**Ce qu'il faut faire** :

**Backend**
- Middleware `DetectTenant` : extrait `{slug}` depuis `Host: slug.couturepro.com`, charge l'atelier correspondant, injecte dans `app('tenant')`
- `Atelier.slug` : nouveau champ unique (slugifié depuis `nom`)
- Migration : ajouter `slug` à `ateliers`
- Toutes les routes protégées : scoper les données sur le tenant courant (déjà fait via `atelier_id`)
- `POST /ateliers` (super-admin) : créer un nouveau tenant + provisionner

**Frontend**
- `App.jsx` : détecter `window.location.hostname`, extraire le sous-domaine, stocker dans contexte
- `api.js` : `baseURL` dynamique selon le sous-domaine détecté
- Page d'accueil `couturepro.com` (landing) : séparée de l'app SPA

**Impact** : l'isolation multi-tenant est déjà assurée par `atelier_id`. Le wildcard subdomain est un changement d'URL et de routing, pas de logique métier.

**Effort estimé** : 2 jours (backend middleware + migration + frontend URL detection)

---

## 6. Phase 3 — Mobile Capacitor + Sync offline

---

### 6.1 Plugins Capacitor requis

| Plugin | Fonctionnalité | CDC |
|---|---|---|
| `@capacitor/share` | Partager PDF mesures/relevé vers WhatsApp natif | §9.7, §10.1 |
| `@capacitor/camera` | Photo profil client depuis galerie ou caméra | §9.3 |
| `@capacitor/local-notifications` | Alertes livraison (J-2, retard) même app fermée | §9.9 |
| `@capacitor/network` | Détecter online/offline → déclencher sync | §2.2 |
| `@capacitor/preferences` | Stockage sécurisé tokens (remplace localStorage) | §8.1 |
| `@capacitor/device` | Device ID stable (déjà simulé avec localStorage) | §4.4 |
| `@capacitor/status-bar` | Couleur status bar iOS/Android | UI |
| Plugin timer custom | `SystemClock.elapsedRealtime()` (anti-fraude timer) | §8.1 |

**Pour PDF + WhatsApp** : remplacer `pdf.save()` (téléchargement web) par `Share.share({ files: [pdfBase64] })` depuis Capacitor.

---

### 6.2 Sync Offline-First (frontend)

**Problème** : `SyncController` backend (`POST /sync/push`, `GET /sync/pull`) est opérationnel mais le frontend ne l'utilise pas. Toutes les mutations vont directement à l'API.

**Architecture cible** :

```
Mutation (create/update/delete)
  → Écriture locale (IndexedDB ou localStorage sync_queue)
  → Réponse immédiate à l'UI (optimistic update)
  → Background: si online → POST /sync/push → vider la queue
  → Si offline: queue reste locale jusqu'au retour en ligne
```

**Ce qu'il faut faire** :

**Frontend**
- `syncService.js` (nouveau) :
  - `enqueue(operation)` : ajoute à la sync_queue (`type`, `payload`, `created_at`)
  - `flush()` : envoie toutes les opérations en attente à `POST /sync/push`
  - `pull(since)` : `GET /sync/pull?since=...` → merge avec données locales
- `useNetwork()` hook : écoute `@capacitor/network` ou `navigator.onLine` → déclenche `syncService.flush()` au retour en ligne
- TanStack Query : ajouter `onMutate` (optimistic) + `onError` (rollback) aux hooks critiques
- Indicateur de sync dans le header (SyncIndicator composant déjà présent)

**Priorité** : après Capacitor intégré. Web-only ne nécessite pas de sync offline.

---

## Récapitulatif des priorités

| Priorité | Sprint / Feature | Phase | Effort |
|---|---|---|---|
| 🔴 P1 | S8 — Badges alertes livraisons + Dashboard 3 indicateurs | MVP1 | 4h |
| 🔴 P1 | S9 — Photo profil client + anti-doublon | MVP1 | 3h |
| 🔴 P1 | S10 — Communications 3 déclenchements WhatsApp auto | MVP1 | 4h |
| 🔴 P1 | S11 — Quota factures WhatsApp | MVP1 | 3h |
| 🔴 P1 | S12 — Paramètres devise + unité mesure | MVP1 | 3h |
| 🟡 P2 | 4.1 — i18n fonctionnel (extraction des textes) | 1.5 | 1-2j |
| 🟡 P2 | 4.2 — Permissions Assistant/Membre granulaires | 1.5 | 1j |
| 🟡 P2 | 4.3 — Super Admin crée des admins de bas niveau | 1.5 | 1j |
| 🟡 P2 | 4.4 — Atelier maître gère ses sous-ateliers | 1.5 | 2j |
| 🟢 P3 | 5.1 — Module Caisse (gated + seeder) | 2 | 3j |
| 🟢 P3 | 5.2 — Multi-tenant wildcard subdomains | 2 | 2j |
| 🔵 P4 | 6.1 — Intégration Capacitor (plugins natifs) | 3 | 3j |
| 🔵 P4 | 6.2 — Sync offline-first frontend | 3 | 3j |

---

*Ce document remplace `etat-avancement.md` et `sprints-restants-mvp1.md`.*
*Référence technique : `docs/cahier-des-charges-v1.3.md`*
