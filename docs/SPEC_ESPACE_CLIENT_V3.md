# SPÉCIFICATION ESPACE CLIENT — v3 (optimisée pour Gextimo)
**Confidentiel — NOVAFRIQ GROUPE.** Version d'ingénierie de la spec v2.0 (Juin 2026), **adaptée au code réel**
et **optimisée** (moins de tables, moins de charge, conforme APDP). À implémenter par phases.

> Cette v3 **remplace la v2 pour l'implémentation**. Elle garde 100 % des fonctionnalités voulues par la
> direction, mais corrige ce qui casserait l'existant et allège l'architecture.

---

## 0. Corrections obligatoires vs v2 (sinon ça casse l'app)

| v2 (spec direction) | Réalité du code | v3 (ce qu'on fait) |
|---|---|---|
| `CREATE TABLE clients` | **`clients` existe déjà** (clients d'atelier, mesures) | **`gxt_clients`** (client final vitrine, distinct) |
| `CREATE TABLE commandes` | **`commandes` existe déjà** (commandes d'atelier) | **`gxt_commandes`** (commande vitrine client→designer) |
| `designers(id)` | pas de table `designers` | = **`ateliers`** de `type='designer'` (PK UUID) |
| `articles(id)` | pas de table `articles` | = **`creations_designer`** ou **`patrons`** |
| `BIGINT AUTO_INCREMENT` | **PK = UUID partout** | **UUID** (`$table->uuid('id')->primary()`) |
| SQL MySQL (`ENUM`, `NULLABLE`) | **prod = PostgreSQL** | **migrations Laravel portables** |
| nouvelle table d'events | **`vitrine_evenements` existe déjà** | on **étend** l'existant, pas de doublon |

## 1. Optimisations (mieux + efficace)

1. **6 tables au lieu de 14.** Les 8 tables de scoring (interest, engagement, RFM, CLV, preferences, trust,
   article_performance, prediction) sont des **agrégats dérivés**. On ne les stocke pas séparément (écritures
   multipliées à chaque clic) → **1 source de vérité** (`gxt_evenements`) + **2 tables de synthèse**
   (`gxt_client_metrics`, `gxt_designer_metrics`) **recalculées par un job planifié la nuit**.
2. **GA4 = firehose, base = décisions.** Micro-événements (scroll, temps de page) → **GA4 seulement**.
   En base : **uniquement les événements métier** (vue produit, panier, wishlist, achat, recherche sans
   résultat, avis, réclamation). Envoi **groupé** côté client via `navigator.sendBeacon` (1 requête / lot,
   pas 1 / clic).
3. **Consentement = interrupteur réel.** GA4 / Meta Pixel / Clarity ne se chargent **qu'après** accord
   `analytics_consent` / `marketing_consent` (obligatoire APDP). Sinon : aucun script tiers.
4. **Réutilisation maximale de l'existant** : Socialite+Google (déjà configuré), OTP (`SendOtpEmail`,
   `ProprietaireAuthController`), **Brevo** (déjà en prod), routes `vitrine/*`, `vitrine/suivi/{reference}`,
   `vitrine_evenements`. On **branche**, on ne réécrit pas.

## 2. Schéma cible (Postgres, UUID)

**Tables NOUVELLES (6)** :
- `gxt_clients` — client final vitrine (auth Google/OTP, UTM, appareil). PK UUID.
- `gxt_consents` — consentements APDP tracés (cookie/marketing/analytics/perso + version + ip_hash).
- `gxt_commandes` — commande vitrine `client → atelier(designer)`, statuts `recue…livree/retournee`, réf `GXT-XXXX`.
- `gxt_avis` — avis (statut=livree) + `gxt_reclamations` (fil horodaté designer+admin). *(2 petites tables ou 1 polymorphe — décidé en Phase 2)*
- `gxt_evenements` — événements **métier** liés au client (ou anonyme via `session_id`).
- `gxt_client_metrics` + `gxt_designer_metrics` — **synthèses recalculées** (scores, segment, RFM, CLV / trust, perf, prédiction).

**Réutilisées** : `ateliers` (=designers), `creations_designer` & `patrons` (=articles), `atelier_abonnes`
(=following), `creation_likes`, `vitrine_evenements`.

## 3. Phases de livraison

### Phase 1 — Fondations espace client *(backbone)*
- Migrations `gxt_clients`, `gxt_consents` (UUID, Postgres).
- Modèle `GxtClient` + guard/auth Sanctum dédié (token client, séparé des proprietaires).
- **Auth sans mot de passe** : (A) Google via Socialite (réutilise `SocialAuthController`), (B) e-mail +
  **OTP Brevo 6 chiffres/10 min** (réutilise le pattern `SendOtpEmail`).
- Capture UTM / referrer / appareil à la création.
- Endpoints : `POST vitrine/client/otp/demander`, `…/otp/verifier`, `GET vitrine/client/social/google`,
  `GET vitrine/client/me`, `POST vitrine/client/consentement`, `POST vitrine/client/logout`.

### Phase 2 — Commandes vitrine + notifications + avis/réclamations
- `gxt_commandes` (réf `GXT-XXXX`, statuts) + workflow de transition (côté designer).
- **E-mails Brevo par statut** (recue/acceptee/en_confection/prete/livree) — jobs sur la queue.
- `gxt_avis` (bouton visible si `livree`) + `gxt_reclamations` (notifie designer + admin).
- Réutilise `vitrine/suivi/{reference}` pour le suivi public.

### Phase 3 — Tracking comportemental (métier + GA4/Meta/Clarity)
- `gxt_evenements` : ingestion **groupée** (`POST vitrine/evenements` en lot), événements métier only.
- GA4 (events perso), **Meta Pixel**, **Microsoft Clarity** côté front — **chargés sous consentement**.
- `gxt_recherches_sans_resultat` (compteur) + alerte designers si seuil.

### Phase 4 — Scoring & segmentation *(jobs planifiés)*
- Commande artisan `gxt:recalculer-metrics` (scheduler nuit) → remplit `gxt_client_metrics`
  (interest par catégorie, engagement+segment froid/tiède/chaud/vip, RFM, CLV, churn, préférences)
  et `gxt_designer_metrics` (trust score, perf article, revenu prédit).
- Barèmes exacts de la v2 conservés (vue +1, temps>30s +3, wishlist +5, panier +10, achat +20 ; etc.).

### Phase 5 — Dashboard admin + automatisations Brevo
- `/admin/analytique` (accès NOVAFRIQ) : vues Globale / Clients / Designers / Tendances (lecture des 2
  tables de synthèse — 0 calcul lourd à l'affichage).
- Automatisations Brevo (panier abandonné 24 h, inactif 30 j, sans avis 5 j, churn élevé, nouvel article
  catégorie favorite, VIP) — déclencheurs côté app + templates Brevo.

## 4. Stack (inchangé vs v2, tout gratuit)
Socialite (Google) · Brevo (OTP + emails + automatisations, 300/j) · GA4 · Meta Pixel · Microsoft Clarity ·
Laravel + Capacitor + **PostgreSQL** (existants).

---
*Fin v3 — document d'ingénierie interne. Confidentiel NOVAFRIQ GROUPE.*
