# Suivi V3 — NovafriQ, Gextimo, HerboQuiz

> **Document de suivi unique.** Il remplace les cinq trackers qui coexistaient et se
> contredisaient. À partir d'ici, **un seul fichier fait autorité** ; les anciens sont
> conservés en archive et ne doivent plus être mis à jour.
>
> Tracker cochable : `couturepro_frontend/public/suivi-v3.html`
> Dernière refonte : **23 juillet 2026**

---

## Pourquoi cette V3

Cinq fichiers de suivi vivaient en parallèle — `SUIVI_NOVAFRIQ.md`, `SUIVI_BACKEND.md`,
`SUIVI_FRONTEND.md`, `SUIVI_SPEC_130.md`, `VITRINE_TODO_FRONTEND.md` — plus un tracker
HTML. Chacun avait raison à sa date et tort le lendemain.

**Ce n'est pas une gêne théorique, ça a produit une erreur réelle.** Le pré-rendu SEO
est déclaré « livré et vérifié en production le 20/07 » dans `SUIVI_BACKEND.md`, et
« le seul chaînon manquant » dans `SUIVI_FRONTEND.md` le 22/07. **Vérification faite
aujourd'hui : c'est le second qui a raison** — Googlebot reçoit exactement le même
titre générique qu'un navigateur. Le travail existe, il est inerte en production.

Un tracker qui se contredit ne se corrige pas, il se refond.

**Ce qui change en V3 :**

- **un seul document**, trois destinataires clairement séparés : moi, toi, les tiers ;
- **le fait remplace le déclaratif** : chaque statut ci-dessous est vérifié dans le code
  ou en production à la date indiquée, jamais recopié d'un ancien fichier ;
- **le livré est résumé, pas raconté**. Le détail narratif reste dans les archives. Un
  tracker sert à savoir quoi faire, pas à relire ce qui est fini ;
- **les trois projets** sont dans le même tableau. NovafriQ et HerboQuiz existaient hors
  de tout suivi.

**Légende** — ✅ Fait · 🟡 Partiel · ⬜ À faire · ⚠️ Bug confirmé · 🚧 Bloqué (tiers) ·
ℹ️ Décision, pas une tâche

---

## Tableau de bord

| Domaine | État | Ce qui reste |
|---|---|---|
| **Gextimo — produit** | 🟢 | Reliquat vitrine, quelques re-tests appareil |
| **Gextimo — SEO** | ⚠️ | **Le pré-rendu est inerte en production** — priorité 1 |
| **Gextimo — administration** | ✅ | Kit de formulaire unifié sur les 27 écrans (22/07) |
| **NovafriQ — site** | ⚠️ | **Formulaire de contact cassé en production** |
| **NovafriQ — back-office** | 🚧 | Chantier ouvert le 23/07, rien en ligne |
| **HerboQuiz** | 🟡 | Application prête, **contenu et diffusion en attente** |
| **Infrastructure** | ✅ | Sauvegardes, OTA, veille, files d'attente : sains |
| **Direction** | ⬜ | 15 réglages et arbitrages en attente |

---

# §1 — À traiter en premier

Trois anomalies vérifiées aujourd'hui. Elles ont en commun d'être **invisibles** : rien
ne casse à l'écran, personne ne s'en plaint, et le préjudice court en silence.

| ID | Sujet | Statut | Constat |
|---|---|---|---|
| **P1** | **Pré-rendu SEO inerte** | ⚠️ | Vérifié le 23/07 : `curl -A Googlebot` sur l'accueil, `/createurs` et un profil renvoie **le même titre générique** qu'un navigateur. Tout le travail serveur existe et fonctionne (`SeoRenderController` testé en direct), mais **nginx ne route aucun robot vers lui**. Conséquence : Google indexe une page vide de contenu depuis le lancement. Cause connue : le vhost est en `600 root:root`, hors de portée du `sudo` autorisé au déploiement — il faut poser la règle à la main sur le serveur. |
| **P2** | **Formulaire de contact NovafriQ cassé** | ⚠️ | Vérifié le 23/07 dans le paquet servi en production : le formulaire poste vers `formspree.io/f/VOTRE_ID_FORMSPREE` — l'identifiant d'exemple n'a **jamais** été remplacé. Chaque message échoue et retombe sur un lien `mailto` que le visiteur doit cliquer lui-même. **Depuis la mise en ligne, personne ne peut nous écrire depuis le site.** Corrigé par le back-office NovafriQ (§4), qui reçoit les messages en base. |
| **P3** | **Aucun contenu NovafriQ n'est éditable** | ⬜ | Tout le texte du site — postes ouverts, membres, FAQ, produits, feuille de route — est écrit en dur dans le JSX. Changer une virgule demande un commit et un déploiement. La page Actualités annonce un blog qui n'existe pas. C'est l'objet du chantier §4. |

---

# §2 — Ce qui m'attend (développement)

## 2.1 Gextimo — reliquat vitrine

| ID | Sujet | Statut | Détail |
|---|---|---|---|
| GX-1 | Routage des robots vers le pré-rendu | ⚠️ | = **P1**. Le seul geste manquant est côté serveur. |
| GX-2 | Bouton « S'inscrire » invisible sous 1024 px | ⬜ | Le lien existe dans le menu déroulant mobile ; le bouton de la barre du haut porte `hidden lg:inline-flex`. Le remonter est un choix visuel à confirmer. |
| GX-3 | Ordre des rubriques Paramètres | ⬜ | Jamais vérifié à l'écran faute de spec assez précise. |
| GX-4 | Mention « Bientôt » sur les modules non prêts | ⬜ | Idem. |
| GX-5 | Recommandation par catégorie | ⬜ | La reco v1 (créateurs favoris en tête) tourne. La version par catégorie suppose de **catégoriser les créations** — chantier de données, pas d'affichage. |
| GX-6 | Facturation : prévisualisation et gabarits avancés | 🟡 | Le module et la normalisation DGI sont livrés. Restent l'aperçu avant émission et les gabarits par atelier. |
| GX-7 | Export des mesures en CSV | 🟡 | Le partage WhatsApp est en place et testé. L'export CSV groupé est livré ; **l'export individuel reste à confirmer à l'écran**. |

## 2.2 Gextimo — à re-tester sur appareil

Ces points sont **codés et probablement bons**. Ils ne sont pas fermés parce qu'ils n'ont
pas été revus sur le téléphone depuis leur correction. La méthode est établie : tester à
travers l'application réelle, pas en relisant le code.

| ID | Sujet | Statut |
|---|---|---|
| QA-5 | Bouton « déjà inscrit » sur l'écran d'inscription | 🟡 |
| QA-6 | Repli de la barre latérale en icônes seules (grand écran) | 🟡 |
| QA-7 | Quotas des plans annuels — assistants, sous-ateliers, cumul d'essai | 🟡 |
| QA-8 | Événements dynamiques : campagne réelle de 3-4 jours | 🟡 |

## 2.3 NovafriQ — back-office de contenu *(chantier ouvert le 23/07)*

Décisions prises avec la direction avant de commencer :

- le back-office est une **route `/admin` du site existant**, chargée seulement quand on
  y va — les visiteurs ne téléchargent rien de plus, il n'y a ni second domaine, ni
  certificat, ni CORS à gérer ;
- **publication immédiate** : le site lit l'API au chargement, avec une copie du contenu
  intégrée au build — si l'API tombe, le site s'affiche quand même avec la dernière
  version connue. Il ne peut pas devenir blanc.

| ID | Sujet | Statut |
|---|---|---|
| NF-1 | `novafriq_backend` — Laravel 13, Sanctum, PostgreSQL, UUID, suppressions douces | 🚧 en cours |
| NF-2 | Reprise **intégrale** du contenu actuel en base — rien ne doit se perdre | ⬜ |
| NF-3 | API publique (contenu, articles, messages) + API d'administration | ⬜ |
| NF-4 | Kit d'administration déclaratif — une ressource se **déclare**, ses écrans en découlent | ⬜ |
| NF-5 | Ressources : produits, membres, postes, partenaires, FAQ, listes, articles, messages, réglages | ⬜ |
| NF-6 | Site public branché sur l'API, avec copie de secours | ⬜ |
| NF-7 | Formulaire de contact réparé → boîte de réception du back-office | ⬜ = **P2** |
| NF-8 | Blog / actualités réel (la page en annonce un qui n'existe pas) | ⬜ |
| NF-9 | Déploiement : base, `.env`, vhost `/api`, CI/CD, certificat | ⬜ |
| NF-10 | Reliquats sur le serveur : `/var/www/novafriq_new` et un `pari-finale.conf` vide dans `sites-enabled` | ⬜ |

**Ce que « façon Filament » veut dire ici.** Ce qui fait Filament n'est pas son
apparence, c'est qu'on **déclare** une ressource — ses champs, sa table, ses règles — et
que les écrans liste / création / édition / suppression en découlent. C'est cela qui est
reproduit, en repartant du kit d'administration Gextimo du 22/07. Ajouter un type de
contenu doit coûter **un fichier de déclaration, pas quatre écrans**. Aucune bibliothèque
tierce : le sur-mesure est un choix, comme sur Gextimo.

⚠️ **Un collaborateur pousse sur `main`** (Kaiffre, 7 commits d'avance au 23/07) et le
dépôt se déploie tout seul. Toujours `git fetch` avant de toucher au site. Le code
d'administration vit dans `src/admin/`, qu'il ne touche pas.

## 2.4 HerboQuiz

Projet à part, avec son propre document de reprise : **`herboquiz_backend/CONTEXTE.md`**.
Ne pas dupliquer ici — seulement l'échéance, parce qu'elle est proche.

| ID | Sujet | Statut |
|---|---|---|
| HQ-1 | Application complète, en ligne sur `herboquiz.novafriq.africa` | ✅ |
| HQ-2 | **Écran d'animation jamais vu en conditions réelles** — les données sont testées, le rendu non | 🟡 |
| HQ-3 | Corriger « Lundi 27 juillet 2026 a 18h00 » (accent manquant, valeur par défaut) | ⬜ |

---

# §3 — Ce qui t'attend (direction)

Ce ne sont **pas** des développements. Tout est en place et paramétrable : ce sont des
saisies et des arbitrages qui se font depuis l'administration, sans développeur. Mode
d'emploi détaillé dans `POUR_LA_DIRECTION.md`.

## Bloquant

| ID | Sujet | Pourquoi ça bloque |
|---|---|---|
| DIR-1 | **Recalibrer le programme de fidélité** (Réglages vitrine → Programme de fidélité) | Le programme est **mathématiquement inatteignable** : les ateliers génèrent trop peu de points face au premier palier, personne ne convertit jamais. Deux leviers : baisser les paliers, ou augmenter les gains. Repère : un palier doit s'atteindre en **semaines** pour un atelier actif, pas en années. |
| DIR-2 | **Saisir l'identité légale** (RCCM, IFU, délibération APDP, dates) | Tant qu'un champ est vide, **toute phrase qui le mentionne disparaît** des 11 pages juridiques. Choix assumé : mieux vaut une mention absente qu'un « à compléter » affiché publiquement. |

## Arbitrages à rendre

| ID | Sujet | Ce qu'il faut trancher |
|---|---|---|
| DIR-12 | **Messagerie client ↔ créateur** (module entier, non commencé) | Qui écrit à qui ? La conversation est-elle rattachée à une commande ? Que se passe-t-il en cas d'abus ? Rien ne peut être chiffré tant que ce n'est pas tranché. |
| DIR-13 | **Code promo côté client** (non commencé) | Qui finance la remise, Gextimo ou le créateur ? Elle porte sur quoi ? Cumulable ? |

## Réglages à faire quand tu veux

| ID | Sujet |
|---|---|
| DIR-3 | Compte à rebours de lancement — **éteint par défaut**, rien ne s'affiche tant qu'il n'est pas activé |
| DIR-4 | Publier le premier message « Gextimo Infos » — l'écran affiche le **nombre de destinataires avant l'envoi** ; à zéro, un critère de ciblage est mal saisi |
| DIR-7 | Vérifier les coordonnées officielles — elles partent dans les PDF et les partages WhatsApp des ateliers |
| DIR-8 | Moyens de paiement proposés en facturation (ne touche ni la caisse ni les commandes) |
| DIR-9 | Modération des avis — cadence, seuil de signalements, motifs graves, mots bannis |
| DIR-10 | Mot de passe VASAT, le jour où le produit sort — tant qu'aucun n'est défini, l'accès reste fermé |
| DIR-11 | **En continu** — entretenir « Quoi de neuf » à chaque livraison. Les mises à jour partent automatiquement, parfois plusieurs fois par jour : sans cette page, les professionnels voient l'application changer sans savoir quoi. Quatre entrées sont déjà rédigées pour donner le ton — écrire pour un utilisateur d'atelier, pas pour un développeur |
| DIR-15 | Fournir les clés externes le jour d'une campagne payante (GA4, Pixel Meta, Clarity, Search Console). **Facultatif** : l'analytique interne tourne déjà et `/admin/analytique` est alimenté |

## HerboQuiz — échéance proche

| ID | Sujet | Échéance |
|---|---|---|
| HQ-D1 | **Diffuser le lien d'inscription** dans le groupe — bloque tout le reste | clôture **samedi 25/07 à 20h** |
| HQ-D2 | **Préparer les questions** (format `question \| réponse`, une par ligne) — la banque est vide | avant **lundi 27/07, 18h** |

---

# §4 — Bloqué sur des tiers

Tracé ici pour mémoire. **Ne pas relister ailleurs** : ces points ne dépendent ni de moi
ni de toi.

| ID | Sujet | Dépend de |
|---|---|---|
| T-1 | Relecture juridique des 11 pages légales | un juriste |
| T-2 | RCCM / IFU / n° APDP | l'immatriculation |
| T-3 | Jeton Meta | le community manager |
| T-4 | Clés GA4 / Pixel Meta / Clarity | une campagne payante à venir |
| T-5 | Search Console API (OAuth) pour l'indexation et les 404 | après T-4 |

---

# §5 — Livré

Résumé volontairement compact. Le détail narratif — cause, piège évité, preuve — reste
dans les archives (§7), qui restent lisibles.

## Gextimo — produit

**Abonnements** : plans Designer alignés sur la maquette officielle, quotas gatés,
essai 14 j réellement complet, upgrade au prorata avec récapitulatif avant paiement,
downgrade à l'échéance annulable, codes promo et ambassadeurs testés en production.

**Créateur et vitrine** : profil public avec compteurs, likes, badges et mérites,
abonnement aux créateurs, patrons payants (FedaPay), bannière recadrable, avis avec
photos, annonces avec boost payant et bande défilante, vidéos avec lecteur intégré
chargé au clic.

**Espace client** : authentification sans mot de passe (OTP), reprise d'action après
connexion, notifications propres au client, « Gextimo Infos » avec diffusion ciblée,
journal des mises à jour, chatbot avec mémoire et IA locale (Makila).

**Multi-ateliers** : isolation corrigée et vérifiée sur appareil, policies rendues
cohérentes avec la propriété réelle, faille IDOR fermée sur les commandes, historique
versionné des mesures, recherche entre ateliers.

**Administration** : les 27 écrans passent par le même kit de formulaire. Plus aucune
fenêtre codée à la main, plus aucune définition de style recopiée. Cinq champs restent
volontairement écrits sur place parce qu'ils ne sont pas des champs de formulaire
ordinaires — ce n'est pas de la dette, c'est le bon choix.

## Infrastructure

**Sauvegardes** : quotidiennes, chiffrées AES-256, déportées sur Backblaze B2, avec test
de restauration mensuel automatique. **Mises à jour** : OTA à chaud fiabilisée après la
panne silencieuse de la 1.0.143 — intégrité vérifiée à trois niveaux indépendants,
l'appareil rapporte succès ou échec. **Deux applications** distinctes (pros et console
interne) cohabitent sur un même téléphone. **Veille** : reprise dans Laravel, quotidienne,
29 recherches ciblées Bénin éditables sans déploiement, tri par Makila fiabilisé,
silence de l'automate devenu un signal d'alerte. **Messagerie** : Brevo authentifié,
9 alias officiels, file d'attente opérationnelle.

**Garde-fous ajoutés au fil des pannes** — chacun est né d'un défaut réel :

- une page importée sans route, ou une route sans lien qui y mène, **casse la
  construction**. C'est le défaut le plus fréquent du projet : fonctionnalité complète,
  aucun chemin pour l'atteindre ;
- une clé de traduction manquante casse la construction, au lieu d'afficher son chemin
  brut à l'utilisateur ;
- une migration de base locale mal formée est détectée avant l'appareil ;
- chaque `sudo` d'un workflow est confronté aux règles réellement autorisées sur le
  serveur.

---

# §6 — Décisions tranchées, à ne pas rouvrir

| Sujet | Décision |
|---|---|
| SMS | **Abandonné.** OTP par e-mail uniquement. |
| Connexion Facebook | **Abandonnée.** Google actif (web et natif), Apple différé. |
| Branches `master` / `android` | **L'écart est voulu.** Mobile n'est pas master. Un correctif web n'est pas porté d'office. Ce n'est ni une dette ni un risque. |
| Downgrade d'abonnement | Option A : le plan inférieur s'applique à l'échéance, rien à payer, annulable avant. |
| Newsletter côté admin | **Écartée sciemment** : 0 inscrit en production. À reprendre quand les inscriptions démarrent. |
| Modèle IA plus gros sur le VPS | **Écarté, mesuré** : 2 processeurs sans carte graphique — un 7B tournerait 2 à 3× plus lentement. Le levier est la connaissance du domaine, pas la taille. |
| « NOVAFRIQ » en majuscules dans les textes légaux | **Laissé tel quel** (92 occurrences). |
| Menus « Solutions » et « Tarifs » de la vitrine | **Laissés tels quels** : la spec supposait des applications et des tarifs par type de compte ; ni l'un ni l'autre n'existe. Le contenu actuel correspond à l'architecture réelle. |

---

# §7 — Règles de travail

Elles ne sont pas décoratives : chacune vient d'un incident.

1. **Zéro codage en dur.** Aucun texte, seuil ou tarif dans le code. i18n pour les
   libellés, base de données pour tout ce qui se règle. « Éditable en base » ne suffit
   pas — si ça demande une console sur le serveur, ce n'est éditable **par personne**.
2. **Pas d'emoji dans le code.** Icônes `lucide-react`.
3. **Une construction verte ne prouve rien sur le comportement.** Après une modification
   par script, vérifier que le remplacement a bien eu lieu avant de committer.
4. **Vérifier plutôt qu'affirmer.** Tester à travers l'interface réelle, pas en relisant
   le code. Un statut « fait » non vérifié vaut moins que « à vérifier ».
5. **Jamais de suppression en masse en production.** Cibler par identifiant. Un incident
   a déjà eu lieu (11 inscrits effacés par une boucle de nettoyage, récupérés grâce à la
   suppression douce).
6. **`npm run build` sur `master` avant de pousser** — la CI construit `master`.
7. **Deux comptes GitHub** : pousser en SSH via les alias `github-kaido` (Kaido0427) et
   `github-djraa` (devdjraaa). Ne jamais les mélanger.
8. **Un réglage ne se déplie pas dans une page de consultation** : fenêtre modale. Les
   listes longues passent en onglets avec compteurs.
9. **Commits en français**, expliquant le **pourquoi** et pas seulement le quoi.

---

# §8 — Archives

Ces fichiers **ne font plus autorité** et ne doivent plus être mis à jour. Ils gardent le
détail narratif de chaque correction — cause racine, piège évité, preuve — qui n'a pas sa
place dans un tracker mais qui vaut d'être conservé.

| Fichier | Ce qu'il contient | Depuis |
|---|---|---|
| `SUIVI_NOVAFRIQ.md` | Suivi maître par blocs, 205 points du volet 1, 24 suggestions | périmé le 19/07 |
| `SUIVI_BACKEND.md` | Détail de chaque correction serveur, avec preuve | archivé le 23/07 |
| `SUIVI_FRONTEND.md` | Répartition façade / logique métier avec Aquilas | archivé le 23/07 |
| `SUIVI_SPEC_130.md` | Spécification de 130 points | **statuts périmés** — plusieurs 🟡 ont été fermés depuis par les campagnes QA |
| `VITRINE_TODO_FRONTEND.md` | 19 points vitrine, tous repris ou fermés | périmé le 21/07 |
| `public/suivi-v2.html` | Tracker cochable précédent | remplacé par `suivi-v3.html` |

⚠️ **`SUIVI_SPEC_130.md` est le plus trompeur** : ses statuts datent du 20/07 et une
bonne partie de ses 🟡 a été vérifiée close depuis. Ne pas s'y fier sans recouper.

---

*Statuts posés après vérification dans le code ou en production. Ce qui n'a pas été
vérifié est écrit comme tel.*
