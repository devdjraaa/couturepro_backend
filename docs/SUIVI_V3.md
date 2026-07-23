# Suivi V3 — NovafriQ & Gextimo

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

- **un seul document**, trois porteurs clairement séparés : le développement (Aquilas /
  Markus), la direction (Pat Dona), et ce qui dépend de tiers ;
- **le fait remplace le déclaratif** : chaque statut ci-dessous est vérifié dans le code
  ou en production à la date indiquée, jamais recopié d'un ancien fichier ;
- **le livré est résumé, pas raconté**. Le détail narratif reste dans les archives. Un
  tracker sert à savoir quoi faire, pas à relire ce qui est fini ;
- **les deux projets sont dans le même tableau** : le site NovafriQ n'apparaissait
  dans aucun suivi alors qu'il a ses propres anomalies en production.

**Légende** — ✅ Fait · 🟡 Partiel · ⬜ À faire · ⚠️ Bug confirmé · 🚧 Bloqué (tiers) ·
ℹ️ Décision, pas une tâche

---

## Tableau de bord

| Domaine | État | Ce qui reste |
|---|---|---|
| **Gextimo — produit** | 🟢 | Reliquat vitrine, quelques re-tests appareil |
| **Gextimo — SEO** | ✅ | Pré-rendu actif : les robots reçoivent le vrai contenu (23/07) |
| **Gextimo — administration** | ✅ | Kit de formulaire unifié sur les 27 écrans (22/07) |
| **NovafriQ — site** | ✅ | Contenu éditable, formulaire de contact réparé |
| **NovafriQ — back-office** | ✅ | API en ligne sur `novafriqapi.novafriq.africa`, HTTPS actif |
| **Infrastructure** | ✅ | Sauvegardes, OTA, veille, files d'attente : sains |
| **Direction (Pat Dona)** | ⬜ | 15 réglages et arbitrages en attente |

---

# §1 — Les trois anomalies de production *(corrigées le 23 juillet)*

Elles avaient en commun d'être **invisibles** : rien ne cassait à l'écran, personne ne
s'en plaignait, et le préjudice courait en silence. C'est précisément pour ça qu'elles
avaient survécu si longtemps.

| ID | Sujet | Statut | Constat |
|---|---|---|---|
| **P1** | **Pré-rendu SEO inerte** | ✅ 23/07 | Vérifié le 23/07 : `curl -A Googlebot` sur l'accueil, `/createurs` et un profil renvoie **le même titre générique** qu'un navigateur. Tout le travail serveur existe et fonctionne (`SeoRenderController` testé en direct), mais **nginx ne route aucun robot vers lui**. Conséquence : Google indexe une page vide de contenu depuis le lancement. **Corrigé le 23/07** : règle nginx posée à la main sur le serveur (le vhost est en `600 root:root`, hors de portée du déploiement automatique). Vérifié en production — Googlebot reçoit le vrai titre, sa description, sa canonique et 5 balises Open Graph, là où un navigateur garde l'application ; les aperçus WhatsApp et Facebook aussi. Trois pièges documentés dans `deploy/README-SEO.md`. Le premier jet a été refusé par nginx et le retour en arrière a fonctionné : le site n'a jamais été interrompu. |
| **P2** | **Formulaire de contact NovafriQ cassé** | ✅ 23/07 | Vérifié le 23/07 dans le paquet servi en production : le formulaire poste vers `formspree.io/f/VOTRE_ID_FORMSPREE` — l'identifiant d'exemple n'a **jamais** été remplacé. Chaque message échoue et retombe sur un lien `mailto` que le visiteur doit cliquer lui-même. **Depuis la mise en ligne, personne ne peut nous écrire depuis le site.** Corrigé par le back-office NovafriQ (§4), qui reçoit les messages en base. |
| **P3** | **Aucun contenu NovafriQ n'est éditable** | ✅ 23/07 | Tout le texte du site — postes ouverts, membres, FAQ, produits, feuille de route — est écrit en dur dans le JSX. Changer une virgule demande un commit et un déploiement. La page Actualités annonce un blog qui n'existe pas. C'est l'objet du chantier §4. |

---

# §2 — Développement (Aquilas / Markus)

## 2.1 Gextimo — reliquat vitrine

| ID | Sujet | Statut | Détail |
|---|---|---|---|
| GX-1 | Routage des robots vers le pré-rendu | ✅ 23/07 | = **P1**. Règle nginx posée, vérifiée en production. Détail et pièges : `deploy/README-SEO.md`. |
| GX-2 | Bouton « S'inscrire » invisible sous 1024 px | ⬜ | Le lien existe dans le menu déroulant mobile ; le bouton de la barre du haut porte `hidden lg:inline-flex`. Le remonter est un choix visuel à confirmer. |
| GX-3 | Ordre des rubriques Paramètres | ⬜ | Jamais vérifié à l'écran faute de spec assez précise. |
| GX-4 | Mention « Bientôt » sur les modules non prêts | ⬜ | Idem. |
| GX-5 | Recommandation par catégorie | ⬜ | La reco v1 (créateurs favoris en tête) tourne. La version par catégorie suppose de **catégoriser les créations** — chantier de données, pas d'affichage. |
| GX-6 | Facturation : prévisualisation et gabarits avancés | ✅ 23/07 | Aperçu avant émission livré et **vérifié à l'écran** : l'overlay rend le document fidèlement (en-tête atelier, client, tableau, totaux, gabarit). Il a même montré son utilité en affichant un net à payer négatif quand l'acompte dépassait le total. |
| GX-7 | Export des mesures en CSV | ✅ 23/07 | Individuel corrigé : le bouton pointait vers un `<a download>` sans jeton (401) et l'URL venait d'une méthode `async` (`[object Promise]`) → récupération via client authentifié. 4 tests serveur. |
| GX-8 | Bulle Makila « hors ligne » plantée en bas à droite | ✅ 23/07 | Aucun minuteur : elle restait plantée en permanence et chevauchait le bandeau d'install. Désormais auto-disparition (6 s) + croix + mémoire de session. Vérifié à l'écran. |
| GX-9 | Mention « Une solution NovafriQ » absente du widget Makila | ✅ 23/07 | Le pied de page vitrine la portait, pas le widget Makila (présent sur chaque page). Ligne d'attribution ajoutée en bas du chat, cliquable vers novafriq.africa. Textes fr/en + URL éditables (zéro hardcoding). Vérifié à l'écran. |
| GX-10 | L'app avalait TOUS les messages du serveur | ✅ 23/07 | **Trouvé en faisant le QA-7 sur le téléphone.** Les intercepteurs rejettent un objet normalisé `{code, message}` (plus de `.response`) alors que **18 écrans** lisaient encore `err.response.data.message` → toujours vide → message générique. Quotas, motifs de refus et erreurs de validation étaient perdus **partout**. Deux dégâts cachés : le retour visuel des problèmes de qualité (réalisations) ne s'affichait jamais, et deux écrans testaient un `403` toujours faux. Corrigé à la source + 18 écrans. Vérifié contre l'API de production. |

| GX-11 | Tarifs : essai offert invisible, libellés obscurs, bande transparente | ✅ 23/07 | Retours du chef. **L'essai offert n'était annoncé nulle part** alors que tout nouveau compte reçoit d'emblée un plan **payant** 14 jours — encart ajouté, et la durée (écrite en dur à 3 endroits) vient désormais du back-office et alimente aussi la page, 4 tests verrouillent le lien. « illimité créations » : le mot était interpolé à la place du **nombre** → phrases dédiées. Libellés renommés (« Photos VIP » → « 15 photos mises en avant par mois », « Module Caisse » → « Caisse : encaissements et suivi du solde »). Un seul nom pour le plan gratuit (la FAQ disait « Free »). Carte bancaire → **mobile money via FedaPay**. Et la **bande d'annonces était transparente** (fond à 6 % d'opacité, collante : tout défilait au travers) → fond plein, vérifié à l'écran. |

## 2.2 Gextimo — à re-tester sur appareil

Ces points sont **codés et probablement bons**. Ils ne sont pas fermés parce qu'ils n'ont
pas été revus sur le téléphone depuis leur correction. La méthode est établie : tester à
travers l'application réelle, pas en relisant le code.

| ID | Sujet | Statut |
|---|---|---|
| QA-5 | Bouton « déjà inscrit » sur l'écran d'inscription | ✅ 23/07 — vérifié sur le téléphone : « Déjà inscrit ? Se connecter » présent, mène bien à la connexion |
| QA-6 | Repli de la barre latérale en icônes seules (grand écran) | ✅ 23/07 — vérifié à l'écran : 256 px → 64 px, 18 libellés → icônes seules, préférence conservée au rechargement |
| QA-7 | Quotas des plans annuels — assistants, sous-ateliers, cumul d'essai | ✅ 23/07 — compte réellement en plan annuel : quotas **appliqués** (4ᵉ membre refusé 403). 2 défauts trouvés et corrigés (voir GX-10 + bouton d'ajout proposé à 3/3) |
| QA-8 | Événements dynamiques : campagne réelle de 3-4 jours | 🟣 **bloqué** — fonction de la *vitrine*, le téléphone n'y change rien ; et **aucune campagne n'est configurée** (l'API renvoie une liste vide). Il faut une campagne programmée par la direction, puis 3-4 jours d'observation |

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
| NF-1 | `novafriq_backend` — Laravel 13, Sanctum, PostgreSQL, UUID, suppressions douces | ✅ 23/07 |
| NF-2 | Reprise **intégrale** du contenu actuel en base — rien ne doit se perdre | ✅ 23/07 |
| NF-3 | API publique (contenu, articles, messages) + API d'administration | ✅ 23/07 |
| NF-4 | Kit d'administration déclaratif — une ressource se **déclare**, ses écrans en découlent | ✅ 23/07 |
| NF-5 | Ressources déclarées : produits, membres, postes, partenaires, FAQ, 8 listes, articles, rubriques, messages, comptes | ✅ 23/07 |
| NF-6 | Site public branché sur l'API, avec copie de secours | ✅ 23/07 — 10 pages sur 10 |
| NF-7 | Formulaire de contact réparé → boîte de réception du back-office | ✅ 23/07 *(en attente de déploiement)* |
| NF-8 | Blog / actualités réel (la page en annonce un qui n'existe pas) | ✅ 23/07 |
| NF-9 | Déploiement : base, `.env`, vhost `/api`, CI/CD, certificat | ✅ 23/07 — **API en ligne** |
| NF-10 | Reliquats sur le serveur : `/var/www/novafriq_new` et un `pari-finale.conf` vide dans `sites-enabled` | ⬜ |

**Ce que « façon Filament » veut dire ici.** Ce qui fait Filament n'est pas son
apparence, c'est qu'on **déclare** une ressource — ses champs, sa table, ses règles — et
que les écrans liste / création / édition / suppression en découlent. C'est cela qui est
reproduit, en repartant du kit d'administration Gextimo du 22/07. Ajouter un type de
contenu doit coûter **un fichier de déclaration, pas quatre écrans**. Aucune bibliothèque
tierce : le sur-mesure est un choix, comme sur Gextimo.

**Reprise du contenu — vérifiée, pas supposée (NF-2).** 182 réglages, 45 éléments de
liste, 3 produits, 4 membres, 4 postes, 9 questions, 3 rubriques d'articles. La reprise a
été **confrontée automatiquement au code source** : sur 152 chaînes longues extraites du
JSX, toutes sont retrouvées en base sauf trois oublis réels, comblés dans la foulée — la
page « introuvable », les libellés du formulaire de contact, et l'indice du champ message.
Le reste des écarts signalés étaient des tracés SVG et des coupures de mon extraction.

**API — 21 tests, sous PostgreSQL (NF-3).** Le site reçoit tout son contenu en **un
seul appel**, mis en cache et purgé à chaque écriture : sans cette purge, l'éditeur
verrait le back-office confirmer sa modification pendant que le site sert encore
l'ancienne, et il la referait en croyant s'être trompé.

La route publique d'écriture — le formulaire de contact — porte trois garde-fous, chacun
contre un abus différent : cadence limitée, champ piège pour les robots, et empreinte du
contenu pour qu'un visiteur impatient qui clique trois fois ne produise pas trois lignes.
C'est la classe de faille déjà rencontrée deux fois sur Gextimo.

Les tests tournent **sous PostgreSQL, jamais SQLite** : le code utilise `ilike`, qui
n'existe pas ailleurs, et un test vert sous un autre moteur donnerait une fausse
assurance — le piège exact déjà payé sur Gextimo avec MySQL en développement.

Deux vérifications valent d'être citées, parce qu'elles visent des bugs déjà vécus :
la **suppression est confrontée à la base**, pas à son code de retour (une route mal
déclarée a déjà répondu « supprimé » sans rien supprimer) ; et le **cloisonnement des
rôles est testé côté serveur**, pas en constatant qu'un bouton est masqué.

**Le kit tient sa promesse, et c'est vérifiable (NF-4).** Tout `src/admin/ressources.jsx`
est de la déclaration — il n'y a **aucun écran par ressource** dans le dossier. La
fenêtre modale impose hauteur plafonnée, en-tête et pied fixes, corps qui défile : le
défaut corrigé sur Gextimo ne peut plus être reproduit en passant par le kit.

Le back-office pèse **41 ko à part** et n'est chargé qu'à l'ouverture de `/admin` : un
visiteur du site public n'en télécharge pas une ligne. C'est la contrepartie du choix de
le loger dans le site plutôt que sur un domaine séparé.

**Vu à l'écran le 23/07, dans un navigateur réel.** Connexion par le formulaire,
19 entrées de menu, les dix listes affichent le bon nombre de lignes, les écrans vides
disent ce qu'ils sont, et la fenêtre d'édition a son **haut atteignable** (158 px du bord),
sa hauteur plafonnée et son corps qui défile — le défaut de Gextimo, vérifié absent.

⚠️ **Et c'est là qu'un défaut invisible autrement a été trouvé** : les fenêtres modales
n'avaient **ni bordure ni fond**. Rendues dans un portail attaché au `body`, elles vivent
hors du conteneur où étaient déclarées les variables de couleur, qui ne cascadaient donc
pas jusqu'à elles. La construction passait, les tests passaient, l'API répondait. Il
fallait ouvrir la fenêtre pour le voir. Corrigé et re-vérifié à l'écran.

Côté site, les neuf pages ont été confrontées au DOM : chaque liste affiche le compte
attendu (5 piliers, 6 engagements, 9 services, 4 membres, 9 questions…) et **aucune clé
technique ne fuit à l'écran**.

Trois écarts **volontaires**, à ne pas prendre pour des oublis :

- le texte « en attendant l'ouverture du blog » est remplacé, puisque le blog existera ;
- les partenaires ne sont **pas** semés : le site annonce que les emplacements « seront
  complétés au fil des partenariats officialisés ». Inventer des lignes serait mentir ;
- le semis **ne remplace jamais** une valeur déjà modifiée en administration. Il pose la
  définition du réglage et ne renseigne la valeur qu'à la création — sans quoi chaque
  déploiement effacerait le travail de l'éditeur, sans erreur et sans témoin.

⚠️ **Un collaborateur pousse sur `main`** (Kaiffre, 7 commits d'avance au 23/07) et le
dépôt se déploie tout seul. Toujours `git fetch` avant de toucher au site. Le code
d'administration vit dans `src/admin/`, qu'il ne touche pas.


---

# §3 — Direction (Pat Dona)

Ce ne sont **pas** des développements. Tout est en place et paramétrable : ce sont des
saisies et des arbitrages que **Pat Dona** fait lui-même depuis l'administration, sans
développeur. Mode
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


---

# §4 — Bloqué sur des tiers

Tracé ici pour mémoire. **Ne pas relister ailleurs** : ces points ne dépendent ni du
développement ni de la direction.

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
