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

## Passe UX mobile (feedback client) — corrections appliquées (live reload)

Mise en place du **live reload Capacitor** (WebView → serveur Vite `http://localhost:5173` via
`adb reverse` + `server.url` dans `capacitor.config.json` — **dev-only, NON commité**). Les modifs
de code sont désormais instantanées, **sans rebuild** (seuls les changements natifs res/manifest
nécessitent un rebuild).

- ✅ **Carte abonnement sur l'accueil** : `AbonnementCard` (Dashboard) — plan + statut + jours
  restants, discrète (ambre en essai, danger si ≤3 j), cliquable → `/abonnement`. Données via
  `useAbonnement()` (`GET /abonnement/current`). Vérifié live (« Standard Mensuel · essai · 13 j »).
- ✅ **Langues** : `LANGUES_DISPO` réduit à **FR + EN** (ar/wo commentés — pas de fichiers de
  traduction, tombaient en fallback). `src/contexts/LangContext.jsx`.
- ✅ **Safe-area / status bar** : `viewport-fit=cover` (index.html) + status bar non-overlay avec
  fond rouge assorti au header + icônes claires (`ThemeContext`). Corrige le header qui passait
  « sous le rideau » du téléphone. **Confirmé OK par le client.**
- ✅ **Import contacts** : la permission **`READ_CONTACTS`** manquait dans l'AndroidManifest
  (cause du « ça ne marche pas »). Ajoutée. (nécessite rebuild — fait)
- ✅ **Icône de notification** : `ic_stat_notify` généré depuis `logogextimovectoriel.png` (silhouette
  blanche, 5 densités) + `smallIcon`/`iconColor` sur les notifs locales + meta-data FCM dans le
  manifest. (natif — rebuild fait)

### ✅ Bascule sur TÉLÉPHONE RÉEL + import contacts réparé
- **Émulateur abandonné** (instable : tué par le build Gradle / SIGHUP) au profit d'un **vrai
  téléphone TECNO BF7 (Android 12)** en USB. Émulateur + system-images supprimés (**~7,3 Go libérés**).
  Live reload via `adb reverse tcp:5173` sur USB — marche parfaitement.
- **Safe-area** : sur le vrai téléphone, `env(safe-area-inset-top)=36px` (marche, contrairement à
  l'ému). Repassé en **overlay:true (edge-to-edge)** → status bar sur le header rouge, contenu
  décalé par `.pt-safe`, **plus de vide en haut**. Vérifié à l'écran.
- **Import contacts RÉPARÉ** : 2 causes — (1) `capacitor.plugins.json` vide → **`cap sync`** l'a
  régénéré (plugin `Contacts` enregistré) ; (2) le plugin `@capacitor-community/contacts` exige
  **`WRITE_CONTACTS`** déclaré dans le manifest (pas juste READ) sinon `requestPermissions()` plante.
  Ajouté. → **381 contacts détectés, 368 importables** (vérifié sur le vrai téléphone). ✅
  ⚠️ Note : le plugin est en **v7.2.0** (Capacitor 7) alors que le projet est en Capacitor 8 —
  fonctionne mais à surveiller (envisager une version alignée Capacitor 8).

### 🟠 Décisions en attente (mobile)
- **#12 Push FCM non fonctionnel (DÉCISION)** : les **notifications push ne marchent pas** car il
  n'y a **ni plugin push (`@capacitor/push-notifications`) ni `google-services.json`** (projet
  Firebase). Le code envoie bien un `fcm_token` au backend mais rien ne le génère. → **Nécessite le
  projet Firebase du client** (google-services.json + config). Les notifs **locales** fonctionnent.
- **#13 Safe-area Android 15 (DÉCISION)** : `env(safe-area-inset-*)` renvoie 0 sur le WebView
  Android (testé). La solution non-overlay actuelle suffit sur Android <15 ; pour l'edge-to-edge
  forcé d'Android 15, prévoir le plugin **`@capacitor-community/safe-area`**.
- **#14 Bouton offline/online (sync WiFi) « disparu »** : `NetworkStatusButton` est dans `Header.jsx`,
  mais le **Dashboard** utilise `noMobileHeader` → le header mobile (donc le bouton) n'y apparaît pas.
  À rebrancher (ex. l'exposer dans le hero du dashboard). *(à traiter)*

### ⚙️ Environnement de test — leçons (voir mémoire [[reference_mobile_test_setup]])
- **Émulateur tué par le build Gradle** (pic CPU/RAM) ou par la fin du shell (SIGHUP). Solutions :
  lancer l'ému **détaché** (`nohup setsid … </dev/null`) et **ne pas builder pendant qu'il tourne**
  (stopper le daemon Gradle : `./gradlew --stop`). Reset sans reboot : tuer qemu + `rm -rf
  /run/user/1000/avd/running/*`. Toujours utiliser le **adb du SDK** (v36), pas `/usr/bin/adb` (v34).

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

### ✅ #11 — CRITIQUE : contrainte CHECK `notifications_systeme.type` désynchronisée (500 en cascade)
- **Symptôme** : `POST /commandes` → **500** (`new row ... violates check constraint
  notifications_systeme_type_check`, type `commande_cree`). Découvert en testant la création de
  commande depuis l'UI (wizard 4 étapes).
- **Ampleur** : la contrainte n'autorise que `promo|mise_a_jour|alerte_sync|alerte_abonnement|info|
  alerte_archive`, mais le code émet **10 types non autorisés** : `commande_cree`, `client_cree`,
  `statut_commande`, `points_convertis`, `sponsorisation`, `abonnement_active`, `atelier_verrouille`,
  `bienvenue_plan`, `bonus_admin`, `conversion`. → **500 sur** : créer commande, créer client,
  changer étape commande, convertir points fidélité, sponsoriser, verrouiller atelier, activer
  abonnement, bonus admin… (même famille que #1 `connexion`).
- **Rectificatif #6/modal** : le modal « Nouveau client » qui restait ouvert **n'était pas** un
  artefact CDP — c'était **ce 500** (`client_cree`) : le client était créé (commit avant la notif)
  mais la requête renvoyait 500 → modal non fermé. Sera réglé par ce fix.
- **Fix** : migration `2026_07_03_000001_relax_notifications_systeme_type_constraint.php` qui
  **supprime la contrainte CHECK** (le `type` est un enum applicatif backend, jamais une saisie
  utilisateur → validé au niveau applicatif, pas en base ; évite que ça recasse au prochain type).
  `down()` restaure la contrainte avec la liste complète. **À POUSSER pour déployer.**
- *Alternative si le client préfère garder une validation DB* : étendre la contrainte à la liste
  complète des 16 types au lieu de la supprimer (même migration, remplacer `up()` par le contenu de
  `down()`).

### ✅ #5 — Onglet Paramètres « facture » non traduit
- **Symptôme** : dans Paramètres, un onglet affiche la clé brute `parametres.onglets.facture`.
- **Cause** : clé i18n manquante (`facture`) dans `parametres.onglets` (FR + EN).
- **Fix** : ajout `"facture": "Facturation"` (FR) / `"Invoicing"` (EN).
- **Fichiers** : `src/lang/fr.json`, `src/lang/en.json`.

### ✅ #6 — Onglet Commandes « annulées » non traduit
- **Symptôme** : onglet affiche la clé brute `commandes.onglets.annulees`.
- **Fix** : ajout `"annulees": "Annulées"` (FR) / `"Cancelled"` (EN) dans `commandes.onglets`.
- **Fichiers** : `src/lang/fr.json`, `src/lang/en.json`.
- **Scan i18n global** : lancé un scan de toutes les clés `t('...')` statiques vs `fr.json`.
  Seuls #5 et #6 étaient de vraies clés manquantes. 4 autres (`dashboard.subtitle.urgentes`,
  `.en_cours`, `caisse.debiteur`, `admin.dashboard.link_tickets_sub`) sont des **faux positifs**
  (clés pluralisées i18next `_one`/`_other`, présentes). → i18n statique globalement propre.

### ✅ #8 — Sidebar : item « Catalogue » affiche la clé `nav.atelier`
- **Symptôme** : dans le menu, un item (→ `/catalogue`) affiche `nav.atelier` brut.
- **Cause** : `Sidebar.jsx` utilisait `key: 'atelier'` (rendu via `t(\`nav.${navKey}\`)`) alors que la
  clé i18n est `nav.catalogue`.
- **Fix** : `key: 'atelier'` → `key: 'catalogue'`. Fichier `src/components/layout/Sidebar.jsx`.

### ✅ #9 — Outils créatifs : état vide = écran blanc
- **Symptôme** : sur Outils créatifs sans contenu, grand écran **noir vide**, aucun message.
- **Cause** : `<EmptyState message={...} />` — or `EmptyState` n'a **pas** de prop `message`
  (il attend `icon`/`title`/`description`) → rend un `<div>` vide.
- **Fix** : `<EmptyState icon={ImagePlus} title={t('outils_creatifs.aucun')}
  description={t('outils_creatifs.aucun_sous')} />` + nouvelle clé `aucun_sous` (FR/EN).
  Vérifié : aucun autre `EmptyState message=` dans le code.

### 🟠 #7 — Inscription : `nom_atelier` ignoré (DÉCISION requise)
- **Symptôme** : l'atelier créé s'appelle « Atelier de {prénom} » (ex. « Atelier de Test »)
  alors que l'utilisateur a saisi un **Nom de structure** (ex. « Atelier Test Claude »).
- **Cause** : `ProprietaireAuthController::verifierOtp()` fait `'nom' => 'Atelier de ' . $prenom`
  (en dur → viole aussi « ZÉRO hardcoding »). Le champ `nom_atelier` envoyé par le front n'est
  **ni validé (`InscriptionRequest`) ni stocké ni utilisé** ; `OtpToken` n'a pas de colonne pour le
  transporter entre l'inscription et la vérification OTP (l'atelier est créé au moment de l'OTP).
- **Décision à prendre** (non implémenté seul) :
  - **Option A (reco)** : colonne nullable `nom_atelier_souhaite` sur `proprietaires`, remplie à
    l'inscription, utilisée à la création de l'atelier ; fallback « Atelier de {prénom} ». (petite migration)
  - **Option B** : retirer le champ « Nom de structure » de l'inscription (le nom se règle ensuite
    dans Paramètres › Atelier). Aucun changement backend.

### ⚠️ #4 — Téléphone stocké/recherché avec espace
- Le `PhoneInput` compose `"+229 90000099"` (indicatif + espace + numéro). Le backend
  stocke et recherche le téléphone **tel quel** (aucune normalisation). Risque de fragilité :
  OTP SMS, liens WhatsApp, e-MECeF attendent souvent un numéro compact `+22990000099`.
- **À décider** : normaliser (retirer espaces) à l'inscription ET à la connexion côté backend,
  ou à l'émission côté frontend. À valider avec le client avant de toucher (impacte les comptes existants).

### 🎨 #10 — Design : barres d'onglets qui débordent (onglets coupés)
- **Symptôme** : sur **Paramètres** (8 onglets) et **Outils créatifs** (5 onglets), la barre
  d'onglets déborde horizontalement (Paramètres : 918px pour 412px de large) ; le dernier onglet
  est **coupé au bord droit** sans aucun indicateur de scroll. L'utilisateur croit qu'un onglet
  « se cache ». (La page elle-même ne déborde pas — `pageOverflow=0` partout, base responsive OK.)
- **C'est de la refonte visuelle (rôle dev front)** → **DÉCISION/design à trancher** :
  fondu (gradient) en bord droit + snap-scroll, OU menu déroulant sur mobile, OU onglets sur 2 lignes.
  Non implémenté ici (choix de design).
- **Bon point** : le reste des écrans audités (Ma Vitrine, Dashboard, Clients, Commandes, Catalogue,
  Facturation, Caisse, Fidélité, Équipe, Notifications) n'a **pas** de débordement de page.

---

## ⏸️ État à la pause (reprise plus tard, depuis l'interface)

- **Blocage émulateur** : qemu boote (~30 s) puis **meurt tout seul** sous charge, quelle que soit la
  config (testé `-memory`, `-wipe-data`, `-no-snapshot`, GPU swiftshader). Log : *« cannot unmap ptr in
  protected range »* → **problème KVM au niveau hôte** après trop de cycles de boot. **Correctif : redémarrer
  la machine** (libère l'état KVM) avant de reprendre le test UI live.
- **Cause d'instabilité RÉSOLUE en cours de route** : conflit de versions adb — `/usr/bin/adb` (v34 debian,
  1ᵉʳ dans le PATH) vs `~/Android/Sdk/platform-tools/adb` (v36 attendu par l'émulateur) → *« adb protocol
  fault »*. **Toujours utiliser le adb du SDK** : `~/Android/Sdk/platform-tools/adb` (cf. [[reference_mobile_test_setup]]).
- **Santé fonctionnelle confirmée par sweep API** (compte de test, 23 endpoints Designer) : **0 erreur 500**.
  Tout à 200 sauf `mesures` 405 (route nichée `clients/{id}/mesures`) et `caisse/*`, `galerie` 403 (gates de
  plan/permission = normal pour un compte designer/Free). Les 2 anciens 500 (#1, #3) sont bien à 200.
- **Écritures API testées** : `POST creations-designer` ✅, `POST vetements` ✅. (`POST commandes` : à refaire
  proprement, `client_id` était vide dans mon test batch — la liste `/clients` renvoie une enveloppe `data`.)
- **Reste à tester DEPUIS L'UI à la reprise** : création commande (+ étapes de suivi), mesures client,
  facturation (devis/facture/reçu/DGI), création d'une œuvre dans Outils créatifs (+ vérifier l'état vide
  corrigé #9), import contacts, fidélité, notifications. Puis re-vérifier visuellement #5/#6/#8/#9 avec la
  nouvelle APK (déjà buildée : `android/app/build/outputs/apk/debug/app-debug.apk`).
- **Données de test créées sur la prod** (à nettoyer quand fini) : compte `+229 90000088` /
  `mebag61642@kinws.com`, client « Awa Traore », création « Croquis test Claude », vêtement « Boubou test Claude ».

## Stratégie de branches (commits — les push sont faits par le client)

- **Backend `couturepro_backend` (master)** : bugs #1 (`10032ba`, déjà poussé/déployé) et #3
  (`b511e57`, à pousser). master = branche principale, rien à porter ailleurs.
- **Frontend `couturepro_frontend`** : les correctifs i18n/facturation sont **généraux** → présents
  sur les deux branches :
  - `android` : commit `6d21eca` (i18n neutre + dédup FacturationPage — ce dernier était un artefact
    propre à `android`).
  - `master` : commit `7cf23b3` (cherry-pick de `6d21eca` ; seul l'i18n s'applique, master n'avait pas
    le doublon FacturationPage). → rien de perdu entre les deux branches.

## Progression du parcours UI (depuis l'inscription)

- [x] **Inscription** (email `mebag61642@kinws.com`, tél `+229 90000088`) → OTP (récupéré VPS) →
  onboarding (4 slides) → `/bienvenue` (3 étapes) → `/parametres` → **Dashboard** ✅ bout en bout
- [x] Onboarding + écran de bienvenue (nouvelles pages S7) — OK
- [x] Dashboard — charge les vraies données (checklist 1/4, caisse, à faire)
- [x] **Clients** — page + état vide OK ; **création client OK** (Awa Traore créé, visible après reload).
  Note : `handleCreate` ferme bien le modal (`setShowSheet(false)`) + invalide la liste sur succès ;
  le modal resté ouvert observé via CDP = artefact de pilotage, pas un bug (un vrai tap ferme).
- [x] Bouton **« Importer depuis les contacts »** (S7) présent dans le modal Nouveau client.
- [x] **Commandes** — page + état vide OK (après fix #6).
- [x] **Paramètres › Type de compte** (S7) — l'écran s'affiche (cartes Artisan/Designer + Confirmer).
- [ ] Bascule Type de compte Artisan→Designer (à exécuter) + Ma Vitrine (Designer)
- [ ] Créations / Outils créatifs (croquis, fiches, patrons, moodboards)
- [ ] Facturation
- [ ] Connexion propriétaire (re-login UI avec ce compte)

### ✅ Rebuild APK fait + vérifs live
APK rebuild + réinstallée. Vérifié à l'écran : **#9 état vide Outils créatifs** (icône + titre +
sous-titre au lieu de l'écran blanc) ✅, **#6 onglet « Annulées »** ✅, liste créations OK.

### ✅ Couverture fonctionnelle (après fix #11 déployé) — 0 erreur 500
Flux d'écriture validés (API réelle, même endpoints que l'UI) :
- **Commandes** : `POST /commandes` ✅ (wizard UI 4 étapes rend + valide bien ; Suivant `disabled`
  tant que le modèle n'est pas choisi = bonne validation), `POST commandes/{id}/etape` ✅ (étape→coupe).
- **Clients** `POST /clients` ✅, **Mesures** `POST /mesures` ✅ (champs sur-mesure), **Vêtements**
  `POST /vetements` ✅, **Créations designer** `POST /creations-designer` ✅.
- **Facturation** `POST /factures` ✅ (FAC-2026-001, type facture, lignes, acompte).
- **Profil atelier** `PUT /parametres/atelier` ✅ (nom/spécialité/bio/ville) — requiert `nom`.
- **Fidélité** `POST /fidelite/convertir` → 422 métier « solde insuffisant » (pas de crash) ✅.
- **Type de compte** artisan→designer ✅ (persisté).

### Compte de test créé depuis l'UI (à nettoyer)
- Tél `+229 90000088` · email `mebag61642@kinws.com` · atelier « Atelier Test Claude » ·
  mdp `TestGextimo2026!` · réponse secrète « bleu ».

## Session — différenciation artisan/designer, galerie, vitrine in-app, rebuild

### ✅ Livré + testé live
- **Différenciation artisan ↔ designer** (front + back) : inscription stocke le type
  (`proprietaire.type_atelier` → `atelier.type`), `useAccountType` + `DesignerRoute` filtrent la nav
  (Ma Vitrine / outils créatifs = designer ; galerie = les deux). Back commit `600d757`. Testé live.
- **Polish UI mobile** : header rouge réorganisé (switcher sous-ateliers sur 2ᵉ ligne),
  Commandes en **onglets** (dont Essayage, statut `essai`) au lieu du kanban horizontal coupé,
  onglet **« Plus »** (menu complet filtré type/rôle) + FAB **« + »** enrichi (commande simple/groupée,
  client, modèle, encaisser). Portés master.
- **Galerie** : multi-sélection + **compression client** (`compressImage`, corrige le 422 silencieux
  des photos > 5 Mo), bouton **supprimer toujours visible** (tactile, + confirm), **tuto 3 slides**
  affiché une seule fois (flag `gx_galerie_tuto_seen`). Testé par l'utilisateur (« c'est bon »).
- **« Voir ma vitrine » in-app** : `@capacitor/browser` (Custom Tab) au lieu du navigateur externe.
  → **rebuild APK** (Java 21 + `ANDROID_HOME`), réinstallée, `hasBrowser: true` vérifié en live (CDP).
- **master aligné + poussé** : port fichier-par-fichier (deps mobiles hors master), rebase sur
  l'entrant `5befab2` (skeletons), build OK → poussé (`5de016e`).

### ✅ Bug plan gratuit → 500 FedaPay — corrigé, déployé ET vérifié live
- `PaymentService::initiate` court-circuite tout plan à prix 0 → paiement `completed` + activation
  directe, sans passer par FedaPay. `/paiements/initier` expose `statut`. Front : le plan gratuit est
  **retiré des options d'abonnement** (attribué à la création uniquement) + le hook confirme sans
  redirection. Backend `73bae5f`, front `0959c6f`/master `aaab56e`. **Déployé** (VPS : code servi =
  origin/master, route/config recachées).
- **Vérifié sur le téléphone** (compte de test designer, Master Annuel actif) : section Abonnement →
  **aucun plan « Free » dans la liste** ✅ ; plus d'onglet « Type de compte » dans Paramètres ✅ ;
  nav designer complète (Ma Vitrine / Outils créatifs / Mes ateliers) ✅.

### ✅ QA on-device complémentaire (compte de test, données restaurées)
- **Paramètres › Préférences** : changement d'unité de mesure (cm → pouces) → Enregistrer →
  **persistance serveur vérifiée après reload** → remis à cm. Validé de bout en bout. ✅
- **Archives** : rend correctement l'état vide (« Aucun élément archivé »). ✅

### ✅ SEO vitrine
- Client-side déjà complet (JSON-LD LocalBusiness, OG/Twitter par designer). OG statiques d'`index.html`
  pointent désormais le **vrai domaine** `gextimo.novafriq.africa`. Restent (pour l'équipe) : image
  `og-cover.png` 1200×630 à fournir + SSR/prerender pour les previews des robots sociaux.

### ⏳ QA restant (sensible = à faire avec l'utilisateur)
- Équipe (ajout membre → **email**), Récupération flux complet (→ **email**), Avis/devis (données
  entrantes), Paramètres › sécurité (mot de passe) + réglages de facture, push FCM,
  Sponsorisation (paiement), vitrine publique.
