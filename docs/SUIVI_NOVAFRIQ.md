# Suivi — Écosystème NovAfriq

> **Fichier de suivi maître.** NovAfriq est la maison-mère ; **Gextimo** est un de ses produits.
> Ce document remplace `roadmap.md` (périmé). Organisé par les **7 blocs** définis par la direction.
> Dernière mise à jour : **13 juillet 2026**.

**Légende des statuts** — ✅ Fait (vérifié dans le code) · 🟡 Partiel / à peaufiner · ⬜ À faire
(absent après recherche) · ⚠️ Bug confirmé (présent mais défectueux, à re-tester) · 🔵 Spec fournie à
suivre telle quelle · ℹ️ Info / décision.

**Sources** : `docs/message.txt` (24 suggestions → `SUG-x`), `docs/# Mise à jour - Corrections et sugg
volet 1.txt` (205 points → `V1-Px`), `cahier-des-charges-v1.3.md`, `JOURNAL_TESTS_EMULATEUR.md`, et
les mémoires projet. **Méthode** : chaque statut est posé après **audit du code réel** (front `src/` +
back Laravel), pas par supposition — l'objectif de la direction est de repérer les **vrais oublis**.

---

## 🔴 À NE PAS RATER — gaps confirmés (les vrais oublis)

Ces éléments sont **absents ou cassés** dans le code après vérification. Ce sont les priorités à
valider par la direction avant de lancer les corrections.

| # | Sujet | Statut | Bloc | Réf |
|---|---|---|---|---|
| 1 | **Codes promo / ambassadeurs** — ✅ **LIVRÉ & testé prod** : table + API rate-limitée (1×/tél, expiry, anti-course), panel admin, saisie app, GEXT-AMB-001→010 seedés (+17 j au restant, P155 vérifié 365→382) ; P156 mode gratuit ✅ aussi | ✅ | 1/3 | V1-P1, P153-158 |
| 2 | ~~Dashboard temps réel~~ ✅ **fait** : fréquence au choix (P95), heure MAJ (P98), **vue multi-ateliers consolidée + comparaison (P100-101)**, cache serveur 60s (P102-103) ; graphiques avancés = plus tard | ✅ | 3 | V1-P92-103 |
| 3 | Slogan du logo à l'impératif « Créez, Gérez, Rayonnez » (le logo login avait « Créer ») | ✅ | 1 | SUG-2 |
| 4 | ~~« novafrique » → « novafriq »~~ ✅ **corrigé** | ✅ | 6 | V1-P127, P188 |
| 5 | ~~Page inscription en anglais malgré l'indicateur FR~~ ✅ **résolu** : audit code — `i18n.js` démarre en `fr` par défaut (`lng: cp_lang \|\| 'fr'`, `fallbackLng: 'fr'`), toutes les clés `auth.inscription.*` présentes et traduites en fr.json ; l'indicateur était déjà corrigé | ✅ | 1 | V1-P145 |
| 6 | **Menus header vitrine** Solutions / Tarifs / Documentation (déroulants façon Kkiapay) — absents | ⬜ | 6 | V1-P181-184 |
| 7 | **Barre de contact fine** au-dessus du header (tél + WhatsApp +229) — absente | ⬜ | 6 | V1-P180 |
| 8 | ~~Likes (cœur) + 4 boutons sur chaque création~~ ✅ **fait** (full-stack) | ✅ | 1 | V1-P159-160 |
| 9 | ~~Badges / Mérites (6 catégories × 5 niveaux)~~ ✅ **fait** (config + service + vitrine) | ✅ | 1 | V1-P174-176 |
| 10 | ~~Profil créateur public (4 compteurs, date intelligente, s'abonner)~~ ✅ **fait** (pseudo = nom public, décidé) | ✅ | 1 | V1-P170-173 |
| 11 | ~~Téléchargement de patrons payants (récup par code)~~ ✅ **fait & testé sandbox** (FedaPay) | ✅ | 1 | V1-P161-163 |
| 12 | ~~Connexion sociale~~ ✅ **Google actif (web + natif)** — Facebook abandonné (décision), Apple différé iOS | ✅ | 1 | V1-P150 |
| 13 | ~~**Photos dans les avis** clients~~ ✅ **fait** (upload ≤3 photos + affichage vitrine) | ✅ | 1 | V1-P137 |
| 14 | **PWA** — ✅ livrée : manifest + SW conservateur (jamais de site périmé) + bannière d'installation (web only, OTA Capgo intact) | ✅ | 7 | V1-P186 |
| 15 | **Protection anti-robot** (reCAPTCHA v3 / hCaptcha) sur l'inscription | ⬜ | 6 | V1-P196 |
| 16 | **Veille technique SEO hebdo** — ✅ livrée : `veille:seo` (PSI mobile+desktop, HTTPS, dispo, alertes, 2 sites), lundi 7h. 🟡 Search Console API (OAuth à configurer) ; e-mail : définir `VEILLE_SEO_EMAIL` | 🟡 | 2 | V1-P200 |
| 17 | **Migration Cloudflare** (NS Namecheap → SSL/DDoS/DNSSEC) + **Search Console/Bing/sitemap** | ⬜ | 6 | V1-P197, P199 |
| 18 | **Sauvegardes VPS** — ✅ quotidiennes locales (pg_dump + storage, 3h30, rotation 7 j, intégrité vérifiée) ; ⬜ off-site (destination à choisir) + chiffrement + test restore mensuel | 🟡 | 4 | V1-P203 |
| 19 | ~~Push FCM (notifs même app fermée)~~ ✅ **fait** (HTTP v1 + observer) | ✅ | 7 | V1-P42-43, P168 |
| 20 | **Format numéro de reçu** — ✅ majuscules + tirets automatiques livrés | ✅ | 1 | V1-P144 |
| 21 | **e-mail `support@gextimo.africa`** erroné (FAQ) → mettre la bonne adresse | ⬜ | 5 | V1-P189 |

> Beaucoup d'autres bugs signalés (⚠️) portent sur du code **existant** (multi-ateliers, OTP, gel de
> compte, WhatsApp) → voir annexes, à **re-tester** dans l'app avant correction.

---

## Tableau de bord

| Bloc | Domaine | Priorité | État résumé |
|---|---|---|---|
| **1** | Backlog produit Gextimo (requêtes antérieures) | 🔴 | Majorité **faite** ; restent des bugs ⚠️ (multi-ateliers, OTP) + features vitrine/créateur ⬜ |
| **2** | Veille auto (SEO/technique) | 🟡 | ✅ `veille:seo` hebdo livrée (PSI+HTTPS+dispo, 2 sites) ; reste Search Console + destinataire e-mail |
| **3** | Volet APK Admin | 🟢 | Codes promo ✅, dashboard temps réel ✅, gel/dégel ✅ (re-testé), diagnostic ✅ |
| **4** | Gestion VPS | 🟡 | Durcissement ✅ ; sauvegardes quotidiennes locales ✅ ; off-site ⬜ (destination) |
| **5** | Gestion mailing | 🟡 | Archi + queue ✅ ; Brevo/délivrabilité + adresses ⬜ |
| **6** | Gestion NovAfriq (site mère) | 🔴 | Nom « novafriq » ✅, header/SEO/Cloudflare ⬜, lancement commun |
| **7** | Updates Gextimo (release/OTA) | ✅ | Système complet livré ; FCM + PWA ⬜ |

---

## Bloc 1 — Backlog produit Gextimo

### 1.1 Vitrine web (site public gextimo.novafriq.africa)
- ✅ Overscroll/pull-to-refresh bloqués (`src/index.css`) — V1-P195.
- ✅ Bandeau cookies (VitrineChrome) — V1-P136 (personnalisation fine à confirmer).
- ✅ Favicon officiel (notre travail) — V1-P130 ; ✅ logo officiel #5 partout — V1-P126/129.
- ✅ « novafrique » → « novafriq » (footer/Qui sommes-nous, master) — V1-P127/188.
- ✅ Indicateur langue affiche bien la langue **courante** (FR en FR) — V1-P37.
- ⬜ Menus header Solutions/Tarifs/Documentation — V1-P181-184 ; ⬜ barre contact — V1-P180.
- ✅ PWA livrée (manifest + SW + bannière, web only) — V1-P186 ; ⬜ reCAPTCHA — V1-P196.
- 🟡 Pages légales : renommer Mentions→CGU, page unique, CGV complètes, footer confidentialité — V1-P138-142.
- 🟡 Localisation pays/devise auto + message — V1-P135 ; 🟡 phrases d'accroche — V1-P192/193.
- ✅ Bannière profil (photo/GIF/vidéo) — V1-P134 (upload Ma Vitrine + couverture vitrine, QA device OK) ; ✅ profil s'ouvre en haut (P133, master).

### 1.2 App mobile artisan/designer
- ✅ Checkbox CGU obligatoire à l'inscription (`RegisterPage`) — V1-P141/SUG-4.
- ✅ Import de contacts (multi-sélection) — V1-P5-7/SUG-13 (bug « erreur puis import » corrigé : timeout par lot).
- ✅ Dates de livraison passées bloquées (`min=TODAY`) — V1-P18/19.
- ✅ Compteur de caractères tickets (255 / 5000 + blocage) — V1-P29-34.
- ✅ Galerie photos (FeatureGate + quota) — V1-P44/45.
- ✅ Champ Prénom (profil + register) — SUG-12/V1-P152.
- ✅ Multi-items / échéances multiples / commande groupée — V1-P17/20/22/23/25.
- ✅ Slogan du logo corrigé à l'impératif « Créez » (glyphe z) — SUG-2.
- ✅ Double icône œil MDP corrigée (V1-P148) ; ✅ bouton « Se connecter » ne dépasse plus (burger mobile, V1-P191).
- ✅ Champ Quantité éditable (SUG-18) ; ✅ Historique alimenté + au menu (SUG-20) ; ✅ Mesures wizard obsolète (SUG-19) ; ✅ profil client `/`/`Key` non reproduit (SUG-21) ; ✅ recherche clients live (P9).
- ✅ Icône officielle du module « nouveau modèle » — placeholder image générique remplacé par `Shirt` (icône de la nav / action-sheet « Nouveau modèle ») dans le form catalogue (SUG-17, choix boss « A »).
- ⚠️ Reste à vérifier device : bouton « déjà inscrit » (SUG-7). ✅ Bouton se connecter : safe-area (SUG-8).
- ✅ Placeholders adaptés au profil + références béninoises (Sèna/Hounkpatin/Tofa) — SUG-9/10/11.
- 🟡 Export mesures WhatsApp/CSV (WhatsApp oui, CSV à confirmer) — V1-P11/12/61.
- ✅ PDF : pied de page marketing Gextimo (logo, « Créez, Gérez, Rayonnez », site, pitch) +
  coordonnées de l'artisan (tél/e-mail/adresse) sur toutes les factures — SUG-14/15.

### 1.3 Isolation multi-ateliers ✅ (chantier sensible — corrigé)
- ✅ **Bug de mélange corrigé (V1-P62-65)** : le sync tirait TOUS les ateliers du propriétaire dans le
  WatermelonDB local mais les requêtes ne filtraient pas → un sous-atelier voyait les clients/commandes
  du maître. Fix : filtrage local par l'id de l'atelier actif issu du **contexte auth** (`useAuth().atelier?.id`,
  synchrone + re-render au switch) sur `useClients`, `useCommandes`, `useVetements` (+ modèles système gardés)
  et `useCommandeStats`. **Vérifié sur device** : maître 359 clients / sous-atelier « carnet vierge », commandes
  idem, aucun mélange, aucune perte au retour. Données enfant (mesures/paiements) isolées via leur parent.
- 🟡 **Recherche cross-ateliers (P68-71/76) livrée** : toggle « Cet atelier / Tous mes ateliers »
  (comptes multi-ateliers), badge d'atelier d'origine sur chaque client, fiche consultable (infos +
  mesures). App (local) + web (API `scope=tous`, réservé propriétaire).
- ✅ **Bug multi-ateliers policies (P72-77)** : les policies Client/Commande/Mesure/Vetement
  limitaient le **propriétaire à son atelier maître** → 403 en ouvrant/éditant/supprimant toute donnée
  d'un **sous-atelier** (ou d'une fiche cross-atelier). Corrigé via `ownsAtelier()` (le proprio possède
  l'entité si l'atelier est l'un des SIENS). Vérifié en prod : view/update/delete/archive sous-atelier OK,
  cross-propriétaire toujours refusé.
- ✅ **Historique versionné des mesures (P74)** : chaque changement de mesures fige une version
  (date, atelier, auteur, n°) via `Mesure::saved` → table `mesure_versions` (comparaison fiable des
  `champs`, pas de faux positif JSON). Endpoint `GET /clients/{id}/mesures/historique` + visualiseur
  « Historique » sur la fiche mesures (BottomSheet, lecture seule en ligne). Vérifié en prod.
- ✅ **Sécurité + backend commande cross-atelier (P72-73/75)** : correctif IDOR — `client_id`/`vetement_id`
  des commandes (simples + groupées) scopés aux ateliers du propriétaire via
  `ResolvesAtelier::ateliersAutorises()` (avant : n'importe quel client/vêtement d'un autre propriétaire
  était référençable). Effet voulu : le backend accepte désormais une commande pour un client d'un
  **autre de mes ateliers** sans ressaisie. Reste ⬜ : parcours front « créer commande depuis la fiche
  cross-atelier » (P72-73 UX) ✅ **livré** : sélecteur de client cross-atelier (toggle « Tous mes
  ateliers » + badge d'origine) dans la création de commande + fiche client agrégeant ses commandes
  par client_id + badge d'atelier d'origine. Vérifié device. Reste ⬜ : partage explicite (P77).

- 🐛→✅ **Bugs de sync CRITIQUES (trouvés en testant P72-73, pré-lancement)** : aucune commande
  créée **offline** ne se synchronisait au serveur. Cause : `commandes.date_commande` absent du
  schéma WatermelonDB → INSERT NULL → NOT NULL violation qui **bloquait toute la file de sync**
  (WatermelonDB retente le lot entier). Corrigé (`DEFAULT CURRENT_DATE`). Idem `commandes.vetement_id`
  était NOT NULL alors que l'UI autorise les commandes sans vêtement → rendu nullable. **Vérifié
  device** : commande fraîche `status:created`, date auto. **Audit sync complet** des 9 tables
  synchronisées : seule `commandes` avait le piège (date_commande) ; toutes les autres colonnes
  NOT NULL sont fournies (schéma local, défaut DB, SyncService, ou requises à la saisie). Sync saine.

### 1.4 Auth / OTP / inscription ⚠️
- ✅ Flux OTP + récupération (`OtpPage`, `ProprietaireAuthController`, `RecuperationController`),
  normalisation téléphone (migration dédiée) — V1-P66/67 (front à confirmer).
- ✅ SMS abandonné (décision chef) : OTP **e-mail uniquement** ; bouton « Renvoyer l'OTP » (V1-P147) ; OTP sur e-mail
  ✅ e-mail fictif débloqué : « E-mail incorrect ? Corrigez-le » sur la page OTP (tél + mdp → correction + renvoi, V1-P123). Format récup OK (V1-P124). ✅ Double œil corrigé (P148).
- ✅ Connexion sociale : Google actif (web + natif) ; Facebook abandonné, Apple différé — V1-P150.

### 1.5 Abonnements / plans
- ✅ **Plans Designer alignés sur la maquette officielle** (16/07/2026) : Gratuit 0 / Atelier 2 500·25 000 /
  Studio 5 000·50 000 (label Master→Studio, `cle` inchangée), quotas conformes — cf. `docs/PLANS_OFFICIELS_DESIGNER.md`.
- ✅ **Quotas gatés & vérifiés** : clients, commandes, créations vitrine, **patrons (nombre — 20/50, corrigé)**,
  assistants, membres, sous-ateliers/multi-ateliers, caisse, facture perso, devis, sponsorisation, photos VIP.
- Onglets Mensuel/Annuel (vitrine `PremiumPage`) — SUG-22/V1-P22 (app `AbonnementTab` à confirmer).
- ✅ Blocage par plan + `FeatureGate` (Caisse, etc.) — V1-P56.
- ✅ **Essai 14 j « accès complet » réel** (16/07/2026) : l'essai servait le plan artisan `standard_mensuel`
  à tout le monde → un designer en essai n'avait ni patrons ni quotas designer. Corrigé aux 4 points de
  création (inscription, sous-atelier, reset CLI, prolongation admin) via `NiveauConfig::cleEssaiPour()`
  (designer → Studio) + sous-atelier hérite du type + migration de rattrapage prod (1 compte migré).
- ❓ **2 questions envoyées au boss** (plan Gratuit) : créations vitrine 5 ou illimité ? factures 10/mois
  au total (actuel) vs standard illimité + 10 avec logo (maquette — demande un compteur séparé) ?

#### ⏳ Fonctionnalités promises dans les plans — À CONSTRUIRE (« Bientôt » — P178)
> Vendues sur les cartes officielles mais **absentes du code** (audit 16/07/2026). Choix direction :
> les tracer ici en « Bientôt » et les construire ensuite. Le **gating des quotas**, lui, est déjà en place.

| # | Fonctionnalité | Plan | Réf tracker |
|---|---|---|---|
| PL-1 | **Lookbook PDF** (catalogue de collection en PDF) | Atelier+ | s30t1 |
| PL-2 | **Export groupé** (collections / patrons / mesures en lot) — l'export **individuel** existe déjà | Atelier+ | s30t2 |
| PL-3 | **Rapport PDF mensuel** (global + par cliente) | Atelier / Studio | s30t3 |
| PL-4 | **Liste d'attente clients** | Studio | s30t4 |
| PL-5 | **Simulateur de revenus** | Studio | s30t5 |
| PL-6 | **Annonce de collection** | Studio | s30t6 |
| PL-7 | **Vidéos de présentation** (jusqu'à 50) | Studio | s30t7 |
| PL-8 | **Badge « Designer Pro »** (distinct du badge Vérifié qui existe) | Atelier | s30t8 |
| PL-9 | **Programme de fidélité avancé** (le basique existe) | Studio | s30t9 |
| PL-10 | **Sauvegarde cloud par atelier cadencée** (/3 j Atelier, journalière Studio) — flag posé, backup réel à faire | Atelier / Studio | s30t10 |

- ⚠️ Bugs quotas plans à re-tester : Premium annuel assistants/viewers (P50), Magnat annuel 7 ateliers
  mais 3 max (P51), cumul essai après souscription (P47), MAJ tous ateliers (P48).
- ⬜/🟡 Comportement changement de plan / downgrade ateliers en trop (verrouillés/archivés ?) — V1-P53-55.
- 🟡 Rendre le module abonnement plus visible + valoriser les cartes — V1-P46, SUG-23.

### 1.6 Facturation par plan
- ✅ Module facturation `FeatureGate('facturation')` + gabarits standard/personnalisé + normalisation
  DGI/e-MECeF + habillage + QR (notre travail) — V1-P82-91.
- 🟡 Prévisualisation, modèles avancés Premium, réglages par atelier — à compléter (P86-91).

### 1.7 Profil créateur public + interactions ⬜
- ✅ Liens réseaux sociaux (migration `add_socials_to_ateliers`) — V1-P177 ; ✅ photo/logo profil — V1-P169.
- ⬜ Likes cœur + 4 boutons par création — V1-P159/160 ; ⬜ badges/mérites — V1-P174-176 ;
  ⬜ 4 compteurs + pseudo public + date intelligente + bouton s'abonner — V1-P170-173 ;
  ⬜ téléchargement patrons payants — V1-P161-163 ; ⬜ photos dans avis — V1-P137.

### 1.8 Codes promo / ambassadeurs ✅ (livré & testé prod)
- ✅ **Système complet (V1-P153-155, P157-158)** : tables `codes_promo` + `code_promo_utilisations`
  (unique code+téléphone), `POST /codes-promo/utiliser` (throttle 5/min, message générique
  anti-énumération, transaction + verrous anti-course), les jours s'AJOUTENT au restant (P155 —
  vérifié en prod : 365→382 j, puis nettoyé), panel admin `/admin/codes-promo` (création, compteur
  d'utilisations par code, activer/désactiver), saisie dédiée dans Réglages → Abonnement,
  10 codes ambassadeurs GEXT-AMB-001→010 seedés (+17 j, sans expiration). Import contacts non gated (P157).
- ⬜ P156 : expiration → repli mode gratuit lecture seule (chantier produit à part).
  `abonnement/activer-code` existe mais ≠ codes promo/ambassadeurs. Voir aussi Bloc 3.

---

## Bloc 2 — Veille auto (SEO / technique) 🟡 — *livrée, 2 compléments à configurer*

✅ **Livré** : commande `veille:seo` planifiée **chaque lundi 07:00** (2 sites séparés) —
PageSpeed Insights mobile+desktop, validité/expiration du certificat HTTPS (alerte < 14 j),
disponibilité HTTP, rapport `storage/app/veille/DATE.txt` + e-mail avec préfixe ⚠/✓.
✅ **Au passage : cron scheduler installé** (`/etc/cron.d/gextimo-scheduler`) — le scheduler Laravel
ne tournait **pas du tout** en prod (purge paiements, notifs d'expiration, bonus fidélité inactifs).

**Premier rapport (15/07/2026)** — trouvailles :
- 🔴 **novafriq.africa ne résout pas (pas de DNS)** — le site mère est injoignable (cf. Bloc 6).
- ✅ gextimo.novafriq.africa : HTTP 200, certificat OK (60 j).

**À configurer (direction)** :
- ⬜ `PSI_API_KEY` (clé Google Cloud gratuite, sinon quota PSI anonyme → 429).
- ⬜ `VEILLE_SEO_EMAIL` (destinataire du rapport hebdo).
- ⬜ Search Console API (OAuth) pour l'indexation/404/sitemap — après P199 (Bloc 6).
Hors périmètre (travail humain) : contenu, backlinks, réseaux sociaux.

---

## Bloc 3 — Volet APK Admin (Gextimo Admin)

- ✅ App admin (flavor Android « admin » interne, appId distinct) + panel web admin complet (ateliers, plans, transactions,
  tickets, signalements, bannière, audit).
- ✅ Gel/dégel de compte (`Admin/AtelierController`, `AtelierDetailPage`). ✅ V1-P2 corrigé : le gate
  (`useSubscriptionGate`) prend en compte `atelier.statut` (le gel admin ne touchait que ce champ, pas
  l'abonnement → le compte gelé gardait l'accès) → **mur plein écran** (portail, couvre FAB/nav), message
  FR adapté (gelé → contacter support ; expiré → abonnement), `/support` accessible pour les tickets.
  ✅ V1-P119 (dégel restaure essai/actif). ✅ V1-P120 (page détail atelier admin blanche corrigée →
  la réactivation/dégel est de nouveau accessible). ⚠️ Reste V1-P121-123.
- ⬜ **Espace création de codes** d'activation/promo côté admin — V1-P1.
- ⬜ **Dashboard admin temps réel** (auto-refresh configurable, bouton rafraîchir, delta, filtre atelier,
  perf multi-ateliers) — V1-P92-103.
- ✅ Outil de diagnostic admin (queue/jobs échoués, base, stockage, dernières erreurs de log) — V1-P110-111.

---

## Bloc 4 — Gestion VPS

- ✅ Durcissement sécurité (ufw, fail2ban, patch kernel + reboot), worker queue systemd `gextimo-queue`,
  déploiement release NOPASSWD (`/usr/local/sbin/gextimo-deploy`).
- ⬜ **Sauvegardes chiffrées quotidiennes off-site + test de restauration mensuel** — V1-P203 (prioritaire).
- ⬜ Accès SSH par clé uniquement, monitoring d'uptime, garde-fou « wipe distant » non déclenchable
  depuis le serveur seul — V1-P203.

---

## Bloc 5 — Gestion mailing

- ✅ Architecture e-mails NovAfriq (boîte 10 Go + aliases + forwarders Gmail) documentée ; worker queue
  OTP/notifs opérationnel (Gmail SMTP app-password).
- ⬜/🟡 **Brevo** : domaine `novafriq.africa` validé, adresse d'envoi, délivrabilité (pas en spam) — V1-P198.
- ⬜ Adresses officielles (remplacer `support@gextimo.africa` erroné) — V1-P189.

---

## Bloc 6 — Gestion NovAfriq (site mère novafriq.africa)

- ✅ Cohérence du nom « **novafriq** » (footer + Qui sommes-nous corrigés sur master) — V1-P127/188.
- ⬜ **Migration Cloudflare** (NS Namecheap → SSL/DDoS/DNSSEC — compte déjà créé) — V1-P197.
- ⬜ **Search Console + Bing + sitemap.xml** (novafriq.africa) — V1-P199.
- ⬜ Menus header vitrine + barre contact (partagés avec Bloc 1) — V1-P180-184.
- ℹ️ Coordination **lancement commun** : Gextimo prêt, NovAfriq attend la vitrine finalisée — V1-P205.

---

## Bloc 7 — Updates Gextimo (release / OTA)

- ✅ **Système complet livré (notre travail)** : OTA à chaud (Capgo self-host, directUpdate), version-gate
  natif + popup changelog + snooze 7j, **release auto au push** (hook pre-push → `release.sh`, NOPASSWD),
  APK debug signée stable, distribution vitrine (`Gextimo-v1.0.apk` = dernière). Voir `scripts/RELEASE.md`.
- ⬜ **Push FCM** (notifs même app fermée) — V1-P42/43/168 (notif locale déjà en place).
- ⬜ **PWA** (installable navigateur) — V1-P186.
- 🟡 Splash animé (favicon → logo → connexion) — SUG-1 (splash système par défaut actuellement).

---

## Annexe A — Détail volet 1 (V1-P1 → V1-P205)

> Statut audité dans le code. « à re-tester » = code présent mais bug signalé par la direction.

| ID | Sujet | Bloc | Statut |
|---|---|---|---|
| P1 | Espace admin : créer codes d'activation/promo | 3 | ⬜ |
| P2 | Compte gelé : message **FR** « Compte suspendu » + compte réellement **bloqué** (mur), CTA support (pas abonnement), tickets accessibles — vérifié device | 3 | ✅ |
| P3 | Icône profil cliquable → paramètres du profil | 1 | ✅ |
| P4 | Bouton « Retour » Android ferme les modales (= Annuler) avant de naviguer — pile de handlers (BottomSheet/Modal), vérifié device | 1 | ✅ |
| — | **Bug i18n accueil** (`abonnement.statut.undefined`) : entête API `X-Atelier-Id` restait sur un ancien atelier → requêtes serveur mal ciblées. Fix : re-sync au reload + garde-fou badge. Vérifié device | 1 | ✅ |
| P5 | Accès aux contacts du téléphone | 1 | ✅ |
| P6 | Import avec confirmation (jamais auto) | 1 | ✅ |
| P7 | Bouton « Importer des contacts » (multi) | 1 | ✅ |
| P8 | Clients « 0 commande » erroné | 1 | ✅ |
| P9 | Recherche clients live (filtre au fil de la frappe) — vérifié device | 1 | ✅ |
| P10 | Clic avatar client → modifier | 1 | ✅ (QA device 1.0.57 : sheet « Modifier le client » s'ouvre) |
| P11 | Export mesures depuis la fiche | 1 | ✅ |
| P12 | Export WhatsApp / CSV | 1 | ✅ (PDF+partage natif WhatsApp + **export CSV** BOM UTF-8, bouton fiche client, OTA 1.0.60) |
| P13 | Ajout commande sans animation (noAnimation sur les pages de saisie) | 1 | ✅ |
| P14 | Acompte > total → bloc auto (commande simple **et** groupée) | 1 | ✅ |
| P15 | Bloc différence calculée (`+ acompte−total`) + motif obligatoire | 1 | ✅ |
| P16 | Acompte ne dépasse pas total sans motif (bloqué UI + serveur : `StoreCommandeRequest` + `CommandeGroupeController`) | 1 | ✅ |
| P17 | Bouton « + » ajouter un vêtement | 1 | ✅ |
| P18 | Dates de livraison passées grisées | 1 | ✅ |
| P19 | Interdire validation date passée | 1 | ✅ |
| P20 | Plusieurs exemplaires même vêtement | 1 | ✅ |
| P21 | Sélecteur de quantité par vêtement | 1 | 🟡 à confirmer |
| P22 | Plusieurs catégories, une seule facture | 1 | ✅ |
| P23 | Échéances multiples + note | 1 | ✅ |
| P24 | Photo du tissu **par article** en commande groupée (grande zone, multipart, backend + app) — vérifié device | 1 | ✅ |
| P25 | Mode « commande groupée » | 1 | ✅ |
| P26 | Tri auto par date livraison | 1 | ✅ (groupes retard/aujourd'hui/demain/semaine/plus tard) |
| P27 | Urgentes en premier | 1 | ✅ (urgentes triées en tête dans chaque groupe de date) |
| P28 | Défilement horizontal des catégories | 1 | 🟡 |
| P29-34 | Compteur caractères tickets (255/5000 + blocage) | 1 | ✅ |
| P35 | Timeout uploads étendu à 120 s (multipart) | 1 | ✅ |
| P36 | Tickets avec photos : plus de timeout (timeout multipart 120s + **compression client ~1600px/<1Mo** avant upload, créer & répondre) | 1 | ✅ |
| P37 | Langue affiche EN au lieu de FR | 1 | ✅ |
| P38 | Gamification : chaîne vérifiée (attribution au sync, config 1-2 pts/action tous plans, solde test 367 pts accumulés) — fonctionne | 1 | ✅ |
| P39 | Visibilité des gains de points par action | 1 | ✅ (watcher global `usePointsToast` : toast « +X pts » + notif locale **une seule fois** quand le solde augmente ; points rafraîchis après sync — sobre, pas de bruit) |
| P40 | Meilleur affichage des points | 1 | ✅ (QA device : carte points riche — solde, badge Bronze, progression vers Argent, historique) |
| P41 | Badge auto au changement de niveau | 1 | ✅ (QA device : badge de niveau + barre de progression affichés) |
| P42 | Notifs commande même app fermée | 7 | ✅ |
| P43 | Notifs seulement à l'ouverture | 7 | ✅ |
| P44 | Galerie photos annoncée absente | 1 | ✅ (existe) |
| P45 | Espace galerie jamais activé | 1 | ✅ |
| P46 | Module abonnement peu visible | 1 | 🟡 |
| P47 | Cumul temps restant essai + abonnement (prolonge depuis l'expiration si statut essai) | 1 | ✅ |
| P48 | MAJ abonnement sur tous les ateliers | 1 | 🟡 |
| P49 | Points + bienvenue après souscription | 1 | ✅ (notif « Bienvenue sur le plan … » à l'activation : points crédités + instructions du plan) |
| P50 | Premium annuel assistants/viewers — **config DB correcte** (assistants=2, membres=5) → re-tester runtime | 1 | 🟡 |
| P51 | Magnat annuel 7 ateliers — **config DB correcte** (max_sous_ateliers=7) → re-tester runtime | 1 | 🟡 |
| P52 | Magnat mensuel : points OK | 1 | ✅ |
| P53-55 | Comportement changement/downgrade de plan | 1 | ⬜ à définir |
| P56 | Caisse : message plan insuffisant | 1 | ✅ |
| P57 | Bouton « voir les plans » → ouvre l'onglet Abonnement | 1 | ✅ |
| P58 | Bouton → page abonnements/plan conseillé | 1 | 🟡 |
| P59 | Consulter mesures enregistrées | 1 | ✅ |
| P60 | Modifier les mesures | 1 | ✅ |
| P61 | Export mesures WhatsApp/CSV | 1 | ✅ (idem P12 — PDF/WhatsApp + CSV) |
| P62-65 | Isolation stricte des ateliers — **corrigé & vérifié device** (filtre local par atelier actif sur clients/commandes/catalogue) | 1 | ✅ |
| P66-67 | Validation champ téléphone (chiffres + « + ») | 1 | 🟡 |
| P68-77 | Recherche client cross-ateliers + mesures partagées | 1 | ⬜/🟡 |
| P78-81 | WhatsApp preuve de paiement : PDF auto + partage natif (déjà OK) + toast « génération en cours » (P81) | 1 | ✅ |
| P82-91 | Module facturation par plan + modèles + preview | 1 | ✅/🟡 |
| P92-95 | Dashboard admin : auto-refresh 30s + bouton Actualiser | 3 | ✅ |
| P96-103 | Dashboard : bouton refresh (P96), rechargement ciblé react-query (P97), vue multi-ateliers + comparaison (P100-101), cache 60s (P102-103) | 3 | ✅ (graphiques avancés P99 = plus tard) |
| P104-106 | Permissions au changement de plan — snapshot régénéré au sync + à l'activation ; immédiateté UI/P106 à re-tester | 1 | 🟡 |
| P107 | Transitions d'écran plus fluides | 1 | 🟡 |
| P108 | Skeleton loaders | 1 | ✅ |
| P109 | Expérience mobile (petits écrans, réseau lent) | 1 | 🟡 |
| P110-111 | Logs + outil de diagnostic admin | 3 | ✅ (page **Diagnostic** admin : queue/jobs échoués, base+taille, stockage, modules, **dernières erreurs de log** ; refresh 30s. **A déjà payé** : a débusqué ① push FCM cassés en prod (fcm.json illisible par le worker → chown, fixé+vérifié), ② inscriptions doublon tel → 500 (normalisation avant unique, fixé+testé prod 422), ③ 401 rendus en 500 « Route [login] » (redirectGuestsTo null, fixé+testé prod)) |
| P112-113 | Erreurs techniques jamais brutes (messages i18n, 500 masqué) | 1 | ✅ |
| P114 | Tâches longues en arrière-plan | 1 | 🟡 |
| P115-117 | Offline / file de sync « en attente » | 1 | ✅/🟡 |
| P118 | Séparation stricte + sync fiable (synthèse) | 1 | 🟡 |
| P119 | Dégel restaure essai/actif valide | 3 | ✅ (**re-testé prod 16/07** : essai valide → gel → dégel → statut « essai » restauré ; essai expiré → « expire », correct) |
| P124 | Récup : format tél normalisé (espaces strippés à l'écriture + lookup) | 3 | ✅ |
| P120 | Page détail atelier admin **blanche** pour comptes non-actifs — **corrigé** (`UNITE_OPTIONS` hors scope → ReferenceError ; la réactivation/dégel se fait sur cette page) | 3 | ✅ |
| P123 | E-mail fictif : « E-mail incorrect ? Corrigez-le » sur la page OTP (tél + mdp → renvoi) | 3 | ✅ |
| P121-122 | Tél dé-enregistré ; compte expiré non réutilisable (voulu, P122) | 3 | ✅/ℹ️ (P121 : changement de numéro via récupération = OTP e-mail obligatoire (étapes 3-4) + refus si numéro déjà pris par un autre compte (durci 16/07)) |
| P125 | Point après « gextimo » sur l'accueil — retiré | 1 | ✅ |
| P126 | Logo officiel #5 partout | 1/6 | ✅ |
| P127 | « novafrique » → « novafriq » | 6 | ✅ |
| P128 | Police gothique cohérente (nom stylisé) | 6 | 🟡 |
| P129 | Logo ciseaux (login) → logo officiel | 1 | ✅/🟡 |
| P130 | Favicon violet → logo officiel | 1 | ✅ |
| P131 | Bouton « S'inscrire » absent sur mobile | 6 | 🟡 à confirmer |
| P132 | Header/retour sur pages inscription-connexion | 6 | ⬜ |
| P133 | Profil créateur s'ouvre en haut (scrollTo(0,0) au changement de slug, master) | 1 | ✅ |
| P134 | Bannière profil : photo/GIF/vidéo | 1 | ✅ (full-stack : `ateliers.banniere_path/type`, upload image≤4Mo/vidéo≤15Mo dans Ma Vitrine, affichée en couverture du profil vitrine ; sinon dégradé) |
| P135 | Détection pays/devise + message | 1/6 | 🟡 |
| P136 | Bandeau cookies + personnalisation | 6 | ✅/🟡 |
| P137 | Photos dans les avis | 1 | ✅ (full-stack : `avis.photos` json, upload public ≤3 dans `AvisForm`, miniatures cliquables dans le profil vitrine) |
| P138 | Footer « devenir créateur » : tarif | 6 | ⬜ |
| P139 | CGV complètes (marketplace) | 6 | 🟡 |
| P140 | Renommer Mentions→CGU, page légale unique | 6 | 🟡 |
| P141 | Checkbox CGU à l'inscription | 1 | ✅ |
| P142 | Footer page confidentialité (liens légaux) | 6 | 🟡 |
| P143 | Nom du menu « Galerie des artisans » | 1 | 🟡 |
| P144 | Code d'activation : majuscules + tirets automatiques (XXXX-XXXX-XXXX) | 1 | ✅ |
| P145 | Indicateur « FR » mais texte EN — **audit code OK** (i18n défaut/ fallback FR, LangContext synchro i18n via cp_lang, 0 clé FR manquante, 0 EN en dur en auth) ; re-test visuel chef sur l'écran précis | 1 | 🟡 audit |
| P147 | Login non vérifié → redirige vers l'OTP (renvoi + saisie), fini le blocage | 1 | ✅ |
| P146 | OTP par e-mail **et** SMS — **ABANDONNÉ (décision chef)** : pas de SMS, l'OTP reste **e-mail uniquement**. Rien à faire. | 1 | ❌ abandonné |
| P157 | Import contacts gratuit pour tous — vérifié : aucun verrou de plan (front ni back) | 1 | ✅ |
| P148 | Une seule icône « œil » mot de passe (native masquée) | 1 | ✅ |
| P149 | Récup via « mot de passe oublié » (OTP e-mail) | 1 | ✅/🟡 |
| P150 | Connexion sociale | 1 | ✅ (**Google actif web + natif** — web testé jusqu'à l'écran Google, natif via plugin Credential Manager APK ≥ 1.0.10 + OTA 1.0.58 ; **Facebook abandonné (décision boss : Google suffit)**, Apple différé iOS) |
| P151 | Renommer catalogue « Modèles Courants » | 1 | ✅ (titre + menu = « Modèles Courants », i18n fr/en) |
| P152 | Bibliothèque photos catégorisée (réf/sexe/occasion…) | 1 | 🟡 |
| P153-155, P157-158 | Codes promo + ambassadeurs — livré & testé prod (API sécurisée, panel admin, app, GEXT-AMB seedés) | 1/3 | ✅ |
| P156 | Expiration → repli **mode gratuit** : plus de mur, bannière « Renouveler », données visibles, quotas/features free (getConfigEffective) — **vérifié device** | 1 | ✅ |
| P159-160 | Likes cœur + 4 boutons par création | 1 | ✅ (full-stack déployé : like ❤️ public anonyme + rangée 4 boutons ❤️/💬/📩/🛒 sur chaque création dans `CreateurProfilPage`. Nuance restante : 💬 renvoie à la section avis (avec **photos** ✅ P137) — les avis restent **par créateur**, pas par création) |
| P161-163 | Téléchargement patrons payants + récup code | 1 | ✅ **full-stack** (backend : tables patrons/patron_achats, `PaymentService::initiatePatron` FedaPay **sandbox** + webhook, `PatronController` CRUD upload privé, endpoints acheter/statut/télécharger par code ; **acheteur vitrine** : bouton Télécharger + modale achat → paiement + page reçu `/patrons/recu/:code` + menu `/patrons/recuperer` (footer) ; **créateur** : `PatronManager` dans le catalogue (mise en vente fichier+prix). Gate plan `patrons_payants` défaut inclus. **À vérifier** : parcours paiement sandbox de bout en bout sur données réelles) |
| P164 | Formulaire « passer commande » en 3 étapes | 1 | ✅ (DevisModal refait en 3 étapes : coordonnées → détails (modèle/type/taille/particularités) → récapitulatif ; envoi créateur + WhatsApp) |
| P165 | Paiement 2 phases (mise en relation → commission 15%) | 1 | 🔵 (business) |
| P166 | Tickets = canal support créateur | 1 | ✅ |
| P167 | Messages = communications officielles (lecture) | 1 | 🟡 |
| P168 | Notifications temps réel + purge 30j | 1/7 | ✅ |
| P169 | Photo de profil créateur | 1 | ✅ |
| P170 | Nom/prénom internes, pseudo public | 1 | ✅ **décidé boss** : on garde le **nom public** (nom d'atelier), pas de pseudo séparé. Clos. |
| P171 | 4 compteurs publics (abonnés/avis/publi/commandes) | 1 | ✅ (full-stack : les 4 exposés backend + affichés dans la carte profil vitrine) |
| P172 | Date d'inscription intelligente | 1 | ✅ (full-stack : `inscrit_depuis` jours/mois/ans, affiché sous la ville) |
| P173 | Bouton « S'abonner / Enregistrer » | 1 | ✅ (full-stack : toggle anonyme backend + bouton S'abonner/Abonné câblé + compteur abonnés live) |
| P174-176 | Espace Mérites (6 catégories × niveaux) | 1 | ✅ (full-stack : `config/merites.php` + `MeritesService` + section Mérites vitrine, niveau atteint + niveaux non obtenus grisés. Noms P175 **validés boss** ; niv5 vues = « Éclatant ») |
| P177 | Liens réseaux sociaux du profil | 1 | ✅ (full-stack : 6 réseaux instagram/facebook/site_web + linkedin/youtube/tiktok, exposés + affichés en pastilles) |
| P178 | Mention « Bientôt / Soon » sur non-prêt | 1 | 🟡 |
| P179 | Rappel du header existant | 6 | ℹ️ |
| P180 | Barre de contact fine (tél + WhatsApp) | 6 | ⬜ |
| P181 | Menu déroulant « Solutions » | 6 | ⬜ |
| P182 | Menu déroulant « Tarifs » | 6 | ⬜ |
| P183-184 | Menu « Documentation » (cartes) + page | 6 | ⬜ |
| P185 | APK sur le site + guide d'installation | 7 | ✅ (bouton « Télécharger » vitrine câblé → /Gextimo.apk ; **rsync CI n'efface plus l'APK** — fix --exclude 16/07) |
| P186 | PWA (manifest + SW + bannière) | 7 | ✅ **déjà fait** (audit code) : `public/manifest.webmanifest` complet (icônes 192/512/maskable), `public/sw.js` (SW conservateur : navigations réseau-d'abord, assets hashés cache-d'abord, API non interceptée), `src/utils/pwa.js` (register web-only + `beforeinstallprompt` + `promptInstall`), `PwaInstallBanner` rendu dans `main.jsx`, i18n `pwa.*`. Web uniquement (désactivé dans Capacitor pour ne pas gêner l'OTA) |
| P187 | Texte d'accueil page inscription | 6 | 🟡 |
| P188 | Remplacer « novafrique » (Qui sommes-nous, footer) | 6 | ✅ |
| P189 | E-mail `support@gextimo.africa` erroné | 5 | ⬜ |
| P190 | Contenu footer depuis doc APDP | 6 | 🟡 |
| P191 | Bouton « Se connecter » dépasse sur mobile — burger mobile, ne dépasse plus | 6 | ✅ |
| P192 | Nouvelle phrase d'accroche | 6 | 🟡 |
| P193 | Phrase « né en Afrique, pour le monde » | 6 | 🟡 |
| P194 | Logo à gauche / bouton connexion à droite | 6 | 🟡 |
| P195 | Bloquer overscroll/pull-to-refresh (desktop) | 6 | ✅ |
| P196 | Anti-robot (reCAPTCHA v3 / hCaptcha) | 6 | ⬜ |
| P197 | Migration Cloudflare (NS Namecheap) | 6 | ⬜ |
| P198 | Config Brevo (domaine, from, spam) | 5 | ⬜/🟡 |
| P199 | Search Console + Bing + sitemap.xml | 6 | ⬜ |
| P200 | Veille technique SEO hebdo (2 sites) | 2 | ⬜ |
| P201 | Alerte inscription + messages bienvenue/retour | 5 | 🟡 (dit fait) |
| P202 | Spec « espace client » (à suivre telle quelle) | 1 | 🔵 |
| P203 | Spec sécurité mobile v5 + sauvegardes VPS | 4 | 🔵/⬜ |
| P204 | Note « Partenaires » (doc maître) | 1 | 🔵 |
| P205 | Coordination lancement commun | 6 | ℹ️ |

---

## Annexe B — Détail suggestions (SUG-1 → SUG-24, `message.txt`)

| ID | Sujet | Bloc | Statut |
|---|---|---|---|
| SUG-1 | Splash screen (favicon → logo → connexion) | 7 | 🟡 |
| SUG-2 | Slogan à l'impératif « Créez, Gérez, Rayonnez » : image `logoforlogin.png` corrigée (glyphe z) + alt | 1 | ✅ |
| SUG-3 | Indicateur de langue (FR/EN) | 1 | ✅ |
| SUG-4 | Checkbox CGU obligatoire à l'inscription | 1 | ✅ |
| SUG-5 | Lien CGU ouvre le site web (page /cgu publiée par l'équipe) | 1 | ✅ |
| SUG-6 | Lien CGU → page publique /cgu en externe (formulaire intact) | 1 | ✅ |
| SUG-7 | « Déjà inscrit ? Se connecter » → /login sur l'écran d'inscription (i18n FR/EN) — présent (code vérifié) | 1 | ✅ |
| SUG-8 | Bouton « Se connecter » : safe-area bas (ne colle plus la barre système) | 1 | ✅ |
| SUG-9 | Placeholders atelier adaptés au type (artisan/designer) | 1 | ✅ |
| SUG-10 | Références béninoises (Sèna, Hounkpatin, Tofa) partout | 1 | ✅ |
| SUG-11 | Placeholders Nom/Prénom béninois (i18n, plus de dur) | 1 | ✅ |
| SUG-12 | Champ Prénom dans le profil | 1 | ✅ |
| SUG-13 | Import contacts : timeout 60 s par lot — fini l'« erreur »' fantôme | 1 | ✅ |
| SUG-14 | PDF : pied de page marketing Gextimo (logo, slogan, site, pitch) | 1 | ✅ |
| SUG-15 | Coordonnées artisan (tél/e-mail/adresse) sur toutes les factures | 1 | ✅ |
| SUG-16 | Export WhatsApp mesures : message construit localement (offline-first) | 1 | ✅ |
| SUG-17 | Icône du module « Nouveau modèle » — placeholder image générique → `Shirt` (icône officielle du module) dans VetementForm | 1 | ✅ |
| SUG-18 | Champ Quantité éditable (effaçable, borné 1–999, select au focus) | 1 | ✅ |
| SUG-19 | Mesures : plus d'étape Mesures dans le wizard commande (obsolète) ; mesures client OK | 1 | ✅ |
| SUG-20 | Historique alimenté (logAction branché app) + entrée menu ajoutée — vérifié device | 1 | ✅ |
| SUG-21 | Profil client `/`/`Key` : non reproduit (form + mesures propres) | 1 | ✅ |
| SUG-22 | Abonnements en 2 onglets (Mensuel/Annuel) | 1 | ✅ |
| SUG-23 | Mieux valoriser les cartes d'abonnement | 1 | 🟡 |
| SUG-24 | Revue UX générale (cohérence, sécu Android) | 1 | 🟡 |

---

## Annexe C — Journal « déjà fait » (session en cours)

Travaux récents (front branche `android` + back), avec les points qu'ils couvrent :
- **Système MAJ / release** : OTA Capgo + version-gate + popup changelog + snooze, release auto au push
  (hook pre-push, NOPASSWD), download APK via navigateur système → **Bloc 7**.
- **Branding** : logo officiel + favicon (icône app flavor gextimo sur blanc) → **V1-P126/129/130**.
- **Vitrine/app** : fix routage `/catalogue` → catalogue (plus Mes Réglages), refonte Paramètres en
  menu-liste, KPIs remontés, cloche notif, fix clavier IME, sponso countdown/typage paiement,
  compteur « 0 cmd » retiré → **V1-P8**, lien plans **V1-P56-58** (partiel).
- **Facturation DGI/e-MECeF** : normalisation + habillage + QR + import direct → **V1-P82-91**.
- **Sécurité VPS** : durcissement ufw/fail2ban, patch kernel, worker queue → **Bloc 4**.
- **Mailing** : architecture NovAfriq + queue OTP/notifs → **Bloc 5**.

---

## Annexe D — Specs maîtres à suivre telles quelles

- 🔵 **V1-P202** — Spécification « espace client » (auth Google/OTP sans mot de passe, consentement,
  suivi commandes + e-mails par étape, avis/réclamations, tracking comportemental, scoring/segmentation,
  Meta Pixel + Clarity, dashboard admin). *Document fourni — à suivre tel quel.*
- 🔵 **V1-P203** — Spécification sécurité & anti-fraude mobile (v5) : ne jamais faire confiance au
  device, validation serveur, score de confiance non décisionnaire, quarantaine progressive, plan
  d'incident + **sauvegardes quotidiennes chiffrées off-site** (voir Bloc 4). *Document fourni.*
- 🔵 **V1-P204** — Note « Partenaires » (document maître Partenaires). *À suivre selon le doc.*

---

*Ce fichier remplace `roadmap.md`. Il est évolutif : mettre à jour les statuts au fil des corrections.*
