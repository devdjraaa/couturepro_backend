# Journal de test — Application mobile Gextimo (émulateur Android)

> Tests réalisés **depuis l'interface** sur émulateur Android (`gextimo_test`, Android 14),
> APK debug construite depuis la branche `android`, backend réel VPS
> (`https://gextimoapi.novafriq.africa/api`). Mirroring live via `scrcpy`.
>
> Méthode : on avance écran par écran depuis l'UI ; à chaque incohérence → correction
> immédiate + note ici, puis on poursuit.

## Comptes de test
- Propriétaire (créé via API, format `+22990000099` sans espace) : `+22990000099` / `TestGextimo2026!`
  — ⚠️ format téléphone sans espace, ne matche pas la saisie UI (voir bug #4).
- Email de test fourni par le client : `mebag61642@kinws.com` (jetable) — pour le parcours
  d'inscription complet depuis l'UI.

---

## Bugs trouvés & corrigés

### ✅ #1 — Login : `NotificationSysteme` type `connexion` invalide (500)
- **Symptôme** : `POST /auth/login` → HTTP 500.
- **Cause** : à la connexion, création d'une `NotificationSysteme` avec `type => 'connexion'`,
  or la contrainte CHECK PostgreSQL n'autorise que
  `promo|mise_a_jour|alerte_sync|alerte_abonnement|info|alerte_archive`.
- **Fix** : `type => 'info'` dans `ProprietaireAuthController::login()`.
- **Commit** : `10032ba` (déployé, login OK — vérifié 200).

### ✅ #2 — Wording « atelier » dans connexion / inscription (i18n)
- **Symptôme** : écrans d'auth parlent encore d'« atelier » alors que l'app gère aussi les Designers.
- **Fix** : neutralisé FR/EN — `sous_titre_login`, `acceder_atelier`, `sous_titre_register`,
  `nom_atelier` → wording neutre (« espace », « structure (atelier / marque) »).
- **Fichiers** : `src/lang/fr.json`, `src/lang/en.json` (frontend). Rebuild + APK réinstallée, vérifié à l'écran.

### ✅ #3 — `GET /auth/me` : `max(uuid) does not exist` (500)
- **Symptôme** : après login, `GET /auth/me` → HTTP 500 → l'app renvoie sur `/login`
  (impossible d'entrer dans l'espace).
- **Cause** : `Atelier::abonnement()` utilisait `->latestOfMany('timestamp_debut')`. `ofMany`
  ajoute un tie-breaker `MAX(id)` sur la clé primaire ; `abonnements.id` étant un **UUID**,
  PostgreSQL n'a pas de `max(uuid)` (MySQL/SQLite tolèrent, pas Postgres).
- **Fix** : `abonnements.atelier_id` est `UNIQUE` (un seul abonnement/atelier) → `latestOfMany`
  inutile. Remplacé par `->latest('timestamp_debut')` (tri simple, pas d'agrégat).
- **Fichier** : `app/Models/Atelier.php`. À déployer via pipeline.

---

## Points à surveiller (non bloquants)

### ⚠️ #4 — Téléphone stocké/recherché avec espace
- Le `PhoneInput` compose `"+229 90000099"` (indicatif + espace + numéro). Le backend
  stocke et recherche le téléphone **tel quel** (aucune normalisation). Risque de fragilité :
  OTP SMS, liens WhatsApp, e-MECeF attendent souvent un numéro compact `+22990000099`.
- **À décider** : normaliser (retirer espaces) à l'inscription ET à la connexion côté backend,
  ou à l'émission côté frontend. À valider avec le client avant de toucher (impacte les comptes existants).

---

## Progression du parcours UI (depuis l'inscription)

- [ ] Inscription (email `mebag61642@kinws.com`) → OTP → onboarding → bienvenue
- [ ] Connexion propriétaire
- [ ] Dashboard
- [ ] Clients (+ import contacts)
- [ ] Créations / Outils créatifs (croquis, fiches, patrons, moodboards)
- [ ] Commandes / suivi
- [ ] Facturation
- [ ] Paramètres (type de compte, profil atelier/designer)
- [ ] Ma Vitrine (Designer)
