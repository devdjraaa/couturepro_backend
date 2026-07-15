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
| 1 | **Codes promo / ambassadeurs** (table dédiée, API validée + rate-limit, panel admin, 1×/tél, expiry) — le `activer-code` actuel ne couvre que l'activation d'abonnement, pas ce système | ⬜ | 1/3 | V1-P1, P153-158 |
| 2 | **Dashboard admin temps réel** — ✅ auto-refresh 30s + bouton Actualiser livrés ; 🟡 restent delta/graphiques avancés | 🟡 | 3 | V1-P92-103 |
| 3 | **Slogan à l'infinitif** « Créer · Gérer · Rayonner » → doit être **impératif** « Créez, Gérez, Rayonnez » | ⚠️ | 1 | SUG-2 |
| 4 | ~~« novafrique » → « novafriq »~~ ✅ **corrigé** | ✅ | 6 | V1-P127, P188 |
| 5 | **Page inscription en anglais** malgré l'indicateur FR (i18n manquant) — l'indicateur lui-même est corrigé | ⚠️ | 1 | V1-P145 |
| 6 | **Menus header vitrine** Solutions / Tarifs / Documentation (déroulants façon Kkiapay) — absents | ⬜ | 6 | V1-P181-184 |
| 7 | **Barre de contact fine** au-dessus du header (tél + WhatsApp +229) — absente | ⬜ | 6 | V1-P180 |
| 8 | **Likes (cœur) + 4 boutons** sur chaque création (❤️/💬/📩/🛒) — absents du profil créateur | ⬜ | 1 | V1-P159-160 |
| 9 | **Badges / Mérites** (6 catégories × 5 niveaux) sur le profil créateur — absents | ⬜ | 1 | V1-P174-176 |
| 10 | **Profil créateur public** : 4 compteurs, pseudo public, date d'inscription intelligente, bouton s'abonner | ⬜ | 1 | V1-P170-173 |
| 11 | **Téléchargement de patrons payants** (bouton payant, récup par code transaction) | ⬜ | 1 | V1-P161-163 |
| 12 | **Connexion sociale** Google / Facebook / Apple | ⬜ | 1 | V1-P150 |
| 13 | **Photos dans les avis** clients | ⬜ | 1 | V1-P137 |
| 14 | **PWA** (manifest.json + service worker + bannière « ajouter à l'écran d'accueil ») | ⬜ | 7 | V1-P186 |
| 15 | **Protection anti-robot** (reCAPTCHA v3 / hCaptcha) sur l'inscription | ⬜ | 6 | V1-P196 |
| 16 | **Veille technique SEO hebdo** (PageSpeed + Search Console + HTTPS + alertes), 2 sites | ⬜ | 2 | V1-P200 |
| 17 | **Migration Cloudflare** (NS Namecheap → SSL/DDoS/DNSSEC) + **Search Console/Bing/sitemap** | ⬜ | 6 | V1-P197, P199 |
| 18 | **Sauvegardes VPS** chiffrées quotidiennes off-site + test de restauration mensuel | ⬜ | 4 | V1-P203 |
| 19 | ~~Push FCM (notifs même app fermée)~~ ✅ **fait** (HTTP v1 + observer) | ✅ | 7 | V1-P42-43, P168 |
| 20 | **Format numéro de reçu** auto-majuscules + tirets automatiques | ⬜ | 1 | V1-P144 |
| 21 | **e-mail `support@gextimo.africa`** erroné (FAQ) → mettre la bonne adresse | ⬜ | 5 | V1-P189 |

> Beaucoup d'autres bugs signalés (⚠️) portent sur du code **existant** (multi-ateliers, OTP, gel de
> compte, WhatsApp) → voir annexes, à **re-tester** dans l'app avant correction.

---

## Tableau de bord

| Bloc | Domaine | Priorité | État résumé |
|---|---|---|---|
| **1** | Backlog produit Gextimo (requêtes antérieures) | 🔴 | Majorité **faite** ; restent des bugs ⚠️ (multi-ateliers, OTP) + features vitrine/créateur ⬜ |
| **2** | Veille auto (SEO/technique) | 🔴 urgent | ⬜ à construire (script + cron hebdo, 2 sites) |
| **3** | Volet APK Admin | 🟡 | App admin existe ; codes promo + dashboard temps réel ⬜ ; gel/dégel ⚠️ |
| **4** | Gestion VPS | 🟡 | Durcissement ✅ ; **sauvegardes** ⬜ (prioritaire) |
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
- ⬜ PWA — V1-P186 ; ⬜ reCAPTCHA — V1-P196.
- 🟡 Pages légales : renommer Mentions→CGU, page unique, CGV complètes, footer confidentialité — V1-P138-142.
- 🟡 Localisation pays/devise auto + message — V1-P135 ; 🟡 phrases d'accroche — V1-P192/193.
- ⬜ Bannière profil (photo/GIF/vidéo) — V1-P134 ; ✅ profil s'ouvre en haut (P133, master).

### 1.2 App mobile artisan/designer
- ✅ Checkbox CGU obligatoire à l'inscription (`RegisterPage`) — V1-P141/SUG-4.
- ✅ Import de contacts (multi-sélection) — V1-P5-7/SUG-13 (⚠️ bug « erreur puis import » à corriger).
- ✅ Dates de livraison passées bloquées (`min=TODAY`) — V1-P18/19.
- ✅ Compteur de caractères tickets (255 / 5000 + blocage) — V1-P29-34.
- ✅ Galerie photos (FeatureGate + quota) — V1-P44/45.
- ✅ Champ Prénom (profil + register) — SUG-12/V1-P152.
- ✅ Multi-items / échéances multiples / commande groupée — V1-P17/20/22/23/25.
- ⚠️ Slogan à l'infinitif — SUG-2.
- ✅ Double icône œil MDP corrigée (V1-P148) ; ✅ bouton « Se connecter » ne dépasse plus (burger mobile, V1-P191).
- ✅ Champ Quantité éditable (SUG-18) ; ✅ Historique alimenté + au menu (SUG-20) ; ✅ Mesures wizard obsolète (SUG-19) ; ✅ profil client `/`/`Key` non reproduit (SUG-21) ; ✅ recherche clients live (P9).
- ⚠️ Reste à vérifier device : icône module « nouveau modèle » (SUG-17),
  bouton « déjà inscrit » (SUG-7), bouton se connecter trop bas (SUG-8).
- ⬜ Placeholders adaptés au profil + **références béninoises** — SUG-9/10/11.
- 🟡 Export mesures WhatsApp/CSV (WhatsApp oui, CSV à confirmer) — V1-P11/12/61 ; 🟡 PDF pied de page
  marketing Gextimo (logo/slogan/site) — SUG-14/15, V1-P14.

### 1.3 Isolation multi-ateliers ✅ (chantier sensible — corrigé)
- ✅ **Bug de mélange corrigé (V1-P62-65)** : le sync tirait TOUS les ateliers du propriétaire dans le
  WatermelonDB local mais les requêtes ne filtraient pas → un sous-atelier voyait les clients/commandes
  du maître. Fix : filtrage local par l'id de l'atelier actif issu du **contexte auth** (`useAuth().atelier?.id`,
  synchrone + re-render au switch) sur `useClients`, `useCommandes`, `useVetements` (+ modèles système gardés)
  et `useCommandeStats`. **Vérifié sur device** : maître 359 clients / sous-atelier « carnet vierge », commandes
  idem, aucun mélange, aucune perte au retour. Données enfant (mesures/paiements) isolées via leur parent.
- ⬜ Recherche cross-ateliers intelligente (P68-77). ⚠️ Web/master (hooks service) : scoping serveur à confirmer.

### 1.4 Auth / OTP / inscription ⚠️
- ✅ Flux OTP + récupération (`OtpPage`, `ProprietaireAuthController`, `RecuperationController`),
  normalisation téléphone (migration dédiée) — V1-P66/67 (front à confirmer).
- ⚠️ À corriger : OTP par e-mail **et** SMS + bouton « Renvoyer l'OTP » (V1-P146/147), OTP sur e-mail
  fictif = compte bloqué (V1-P123), format espace indicatif en récupération (V1-P124). ✅ Double œil corrigé (P148).
- ⬜ Connexion sociale Google/FB/Apple — V1-P150.

### 1.5 Abonnements / plans
- ✅ Onglets Mensuel/Annuel (vitrine `PremiumPage`) — SUG-22/V1-P22 (app `AbonnementTab` à confirmer).
- ✅ Blocage par plan + `FeatureGate` (Caisse, etc.) — V1-P56.
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

### 1.8 Codes promo / ambassadeurs ⬜
- ⬜ Système complet (table, API validée + rate-limit, panel admin, 1×/tél, expiry auto) — V1-P153-158.
  `abonnement/activer-code` existe mais ≠ codes promo/ambassadeurs. Voir aussi Bloc 3.

---

## Bloc 2 — Veille auto (SEO / technique) ⬜ — *urgent (V1-P200)*

À construire : **script + cron hebdo**, **2 sites séparés** (novafriq.africa + gextimo.novafriq.africa).
1. **Performance & Core Web Vitals** via API PageSpeed Insights (mobile + desktop).
2. **Indexation & crawl** via API Google Search Console (pages non indexées, 404, sitemap).
3. **Validité HTTPS** (expiration certificat).
4. **Alertes** (e-mail/Discord) sous un seuil à définir ou erreur critique.
Hors périmètre (travail humain) : contenu, backlinks, réseaux sociaux.
*Implémentation après validation de ce fichier (voir Bloc 6 pour Search Console/sitemap prérequis).*

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
- ⬜ Outil de diagnostic admin (bugs, syncs échouées, lenteurs) — V1-P110-111.

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
| P10 | Clic avatar client → modifier | 1 | 🟡 |
| P11 | Export mesures depuis la fiche | 1 | ✅ |
| P12 | Export WhatsApp / CSV | 1 | 🟡 (CSV ?) |
| P13 | Ajout commande sans animation (noAnimation sur les pages de saisie) | 1 | ✅ |
| P14 | Acompte > total → bloc auto | 1 | ⬜ |
| P15 | Bloc différence calculée + raison obligatoire | 1 | ⬜ |
| P16 | Acompte ne dépasse pas total sans raison | 1 | ⬜ |
| P17 | Bouton « + » ajouter un vêtement | 1 | ✅ |
| P18 | Dates de livraison passées grisées | 1 | ✅ |
| P19 | Interdire validation date passée | 1 | ✅ |
| P20 | Plusieurs exemplaires même vêtement | 1 | ✅ |
| P21 | Sélecteur de quantité par vêtement | 1 | 🟡 à confirmer |
| P22 | Plusieurs catégories, une seule facture | 1 | ✅ |
| P23 | Échéances multiples + note | 1 | ✅ |
| P24 | Photo du tissu **par article** en commande groupée (grande zone, multipart, backend + app) — vérifié device | 1 | ✅ |
| P25 | Mode « commande groupée » | 1 | ✅ |
| P26 | Tri auto par date livraison | 1 | 🟡 |
| P27 | Urgentes en premier | 1 | 🟡 |
| P28 | Défilement horizontal des catégories | 1 | 🟡 |
| P29-34 | Compteur caractères tickets (255/5000 + blocage) | 1 | ✅ |
| P35 | Timeout 15000 ms envoi ticket | 1 | ⚠️ |
| P36 | Tickets avec photos → timeout | 1 | ⚠️ |
| P37 | Langue affiche EN au lieu de FR | 1 | ✅ |
| P38 | Gamification défaillante | 1 | ⚠️ à re-tester |
| P39 | Points de fidélité peu visibles | 1 | ⚠️ |
| P40 | Meilleur affichage des points | 1 | 🟡 |
| P41 | Badge auto au changement de niveau | 1 | 🟡 |
| P42 | Notifs commande même app fermée | 7 | ✅ |
| P43 | Notifs seulement à l'ouverture | 7 | ✅ |
| P44 | Galerie photos annoncée absente | 1 | ✅ (existe) |
| P45 | Espace galerie jamais activé | 1 | ✅ |
| P46 | Module abonnement peu visible | 1 | 🟡 |
| P47 | Cumul temps restant essai + abonnement (prolonge depuis l'expiration si statut essai) | 1 | ✅ |
| P48 | MAJ abonnement sur tous les ateliers | 1 | 🟡 |
| P49 | Points + bienvenue après souscription | 1 | 🟡 |
| P50 | Premium annuel assistants/viewers — **config DB correcte** (assistants=2, membres=5) → re-tester runtime | 1 | 🟡 |
| P51 | Magnat annuel 7 ateliers — **config DB correcte** (max_sous_ateliers=7) → re-tester runtime | 1 | 🟡 |
| P52 | Magnat mensuel : points OK | 1 | ✅ |
| P53-55 | Comportement changement/downgrade de plan | 1 | ⬜ à définir |
| P56 | Caisse : message plan insuffisant | 1 | ✅ |
| P57 | Bouton « voir les plans » → ouvre l'onglet Abonnement | 1 | ✅ |
| P58 | Bouton → page abonnements/plan conseillé | 1 | 🟡 |
| P59 | Consulter mesures enregistrées | 1 | ✅ |
| P60 | Modifier les mesures | 1 | ✅ |
| P61 | Export mesures WhatsApp/CSV | 1 | 🟡 |
| P62-65 | Isolation stricte des ateliers — **corrigé & vérifié device** (filtre local par atelier actif sur clients/commandes/catalogue) | 1 | ✅ |
| P66-67 | Validation champ téléphone (chiffres + « + ») | 1 | 🟡 |
| P68-77 | Recherche client cross-ateliers + mesures partagées | 1 | ⬜/🟡 |
| P78-81 | WhatsApp preuve de paiement (PDF/image attaché) | 1 | ⚠️ |
| P82-91 | Module facturation par plan + modèles + preview | 1 | ✅/🟡 |
| P92-95 | Dashboard admin : auto-refresh 30s + bouton Actualiser | 3 | ✅ |
| P96-103 | Dashboard admin : delta/graphiques/filtres avancés | 3 | ⬜ |
| P104-106 | Permissions au changement de plan — snapshot régénéré au sync + à l'activation ; immédiateté UI/P106 à re-tester | 1 | 🟡 |
| P107 | Transitions d'écran plus fluides | 1 | 🟡 |
| P108 | Skeleton loaders | 1 | ✅ |
| P109 | Expérience mobile (petits écrans, réseau lent) | 1 | 🟡 |
| P110-111 | Logs + outil de diagnostic admin | 3 | ⬜ |
| P112-113 | Erreurs techniques jamais brutes (messages i18n, 500 masqué) | 1 | ✅ |
| P114 | Tâches longues en arrière-plan | 1 | 🟡 |
| P115-117 | Offline / file de sync « en attente » | 1 | ✅/🟡 |
| P118 | Séparation stricte + sync fiable (synthèse) | 1 | 🟡 |
| P119 | Dégel restaure essai/actif valide (ne force plus « expire ») | 3 | ✅ |
| P124 | Récup : format tél normalisé (espaces strippés à l'écriture + lookup) | 3 | ✅ |
| P120 | Page détail atelier admin **blanche** pour comptes non-actifs — **corrigé** (`UNITE_OPTIONS` hors scope → ReferenceError ; la réactivation/dégel se fait sur cette page) | 3 | ✅ |
| P121-123 | Tél dé-enregistré (vérif), OTP e-mail fictif → compte bloqué | 3 | ⚠️ |
| P125 | Point après « gextimo » sur l'accueil — retiré | 1 | ✅ |
| P126 | Logo officiel #5 partout | 1/6 | ✅ |
| P127 | « novafrique » → « novafriq » | 6 | ✅ |
| P128 | Police gothique cohérente (nom stylisé) | 6 | 🟡 |
| P129 | Logo ciseaux (login) → logo officiel | 1 | ✅/🟡 |
| P130 | Favicon violet → logo officiel | 1 | ✅ |
| P131 | Bouton « S'inscrire » absent sur mobile | 6 | 🟡 à confirmer |
| P132 | Header/retour sur pages inscription-connexion | 6 | ⬜ |
| P133 | Profil créateur s'ouvre en haut (scrollTo(0,0) au changement de slug, master) | 1 | ✅ |
| P134 | Bannière profil : photo/GIF/vidéo | 1 | ⬜ |
| P135 | Détection pays/devise + message | 1/6 | 🟡 |
| P136 | Bandeau cookies + personnalisation | 6 | ✅/🟡 |
| P137 | Photos dans les avis | 1 | ⬜ |
| P138 | Footer « devenir créateur » : tarif | 6 | ⬜ |
| P139 | CGV complètes (marketplace) | 6 | 🟡 |
| P140 | Renommer Mentions→CGU, page légale unique | 6 | 🟡 |
| P141 | Checkbox CGU à l'inscription | 1 | ✅ |
| P142 | Footer page confidentialité (liens légaux) | 6 | 🟡 |
| P143 | Nom du menu « Galerie des artisans » | 1 | 🟡 |
| P144 | Format numéro reçu (majuscules + tirets auto) | 1 | ⬜ |
| P145 | Indicateur « FR » mais texte EN | 1 | ⚠️ |
| P147 | Login non vérifié → redirige vers l'OTP (renvoi + saisie), fini le blocage | 1 | ✅ |
| P146 | OTP par e-mail **et** SMS (fiabilité de livraison) | 1 | ⚠️ infra |
| P148 | Une seule icône « œil » mot de passe (native masquée) | 1 | ✅ |
| P149 | Récup via « mot de passe oublié » (OTP e-mail) | 1 | ✅/🟡 |
| P150 | Connexion Google/Facebook/Apple | 1 | ⬜ |
| P151 | Renommer catalogue « Modèles Courants » | 1 | 🟡 |
| P152 | Bibliothèque photos catégorisée (réf/sexe/occasion…) | 1 | 🟡 |
| P153-158 | Codes promo + ambassadeurs (système complet) | 1/3 | ⬜ |
| P159-160 | Likes cœur + 4 boutons par création | 1 | ⬜ |
| P161-163 | Téléchargement patrons payants + récup code | 1 | ⬜ |
| P164 | Formulaire « passer commande » en 3 étapes | 1 | 🟡 (devis existe) |
| P165 | Paiement 2 phases (mise en relation → commission 15%) | 1 | 🔵 (business) |
| P166 | Tickets = canal support créateur | 1 | ✅ |
| P167 | Messages = communications officielles (lecture) | 1 | 🟡 |
| P168 | Notifications temps réel + purge 30j | 1/7 | ✅ |
| P169 | Photo de profil créateur | 1 | ✅ |
| P170 | Nom/prénom internes, pseudo public | 1 | ⬜/🟡 |
| P171 | 4 compteurs publics (abonnés/avis/publi/commandes) | 1 | ⬜ |
| P172 | Date d'inscription intelligente | 1 | ⬜ |
| P173 | Bouton « S'abonner / Enregistrer » | 1 | ⬜ |
| P174-176 | Espace Mérites (6 catégories × niveaux) | 1 | ⬜ |
| P177 | Liens réseaux sociaux du profil | 1 | ✅ |
| P178 | Mention « Bientôt / Soon » sur non-prêt | 1 | 🟡 |
| P179 | Rappel du header existant | 6 | ℹ️ |
| P180 | Barre de contact fine (tél + WhatsApp) | 6 | ⬜ |
| P181 | Menu déroulant « Solutions » | 6 | ⬜ |
| P182 | Menu déroulant « Tarifs » | 6 | ⬜ |
| P183-184 | Menu « Documentation » (cartes) + page | 6 | ⬜ |
| P185 | APK sur le site + guide d'installation | 7 | ✅ (guide à confirmer) |
| P186 | PWA (manifest + SW + bannière) | 7 | ⬜ |
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
| SUG-2 | Slogan à l'impératif (alt corrigé ; **visuel dans l'image `logoforlogin.png` à refaire côté design**) | 1 | 🟡 |
| SUG-3 | Indicateur de langue (FR/EN) | 1 | ✅ |
| SUG-4 | Checkbox CGU obligatoire à l'inscription | 1 | ✅ |
| SUG-5 | Lien CGU ouvre le site web | 1 | 🟡 |
| SUG-6 | Bug : clic CGU renvoie à la connexion | 1 | ⚠️ |
| SUG-7 | Bouton « Déjà inscrit ? Se connecter » | 1 | ⚠️ |
| SUG-8 | Bouton « Se connecter » trop bas | 1 | ⚠️ |
| SUG-9 | Placeholders selon le profil (artisan/designer) | 1 | ⬜ |
| SUG-10 | Références béninoises (noms) | 1 | ⬜ |
| SUG-11 | Placeholders Nom/Prénom béninois | 1 | ⬜ |
| SUG-12 | Champ Prénom dans le profil | 1 | ✅ |
| SUG-13 | Bug import contacts (erreur puis import) | 1 | ⚠️ |
| SUG-14 | PDF : pied de page marketing (logo/slogan/site) | 1 | 🟡 |
| SUG-15 | Coordonnées artisan dans les exports | 1 | 🟡 |
| SUG-16 | Export WhatsApp avec toutes les mesures | 1 | ⚠️ |
| SUG-17 | Icône du module « Nouveau modèle » | 1 | ⚠️ |
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
