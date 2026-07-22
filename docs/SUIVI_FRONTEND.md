# Suivi FRONTEND — Gextimo (Aquilas)

> ## ⚠️ Répartition revue le 20/07 — à lire avant de commencer
>
> Le partage n'est pas « backend / frontend » mais **logique métier / façade**.
>
> **Tu prends la FAÇADE** : apparence, mise en page, animation, retour visuel, cohérence graphique.
> **Nous prenons tout écran qui applique une RÈGLE SERVEUR** : quota, tarif, durée, statut,
> reprise d'action, paiement. Un écran qui doit connaître une règle du serveur pour être juste
> n'est pas de la façade, même s'il s'écrit en React.
>
> **Ce qui te revient réellement** : `S02A-25` libellé de bouton · `S02A-26` positionnement ·
> `PHOTO-1` retour visuel instantané à l'envoi d'une photo · `ANN-7` encart d'information ·
> `ANN-8` habillage de la bande défilante (le CSS `.gx-marquee` existe déjà) · `SUP-1` encart
> tickets · `REL-V1` finitions vitrine · l'apparence du lecteur vidéo (`VID-1`).
>
> **Ce qui nous revient et que tu peux ignorer** : tout le module Annonces (`ANN-1` à `ANN-6`,
> `ANN-9`), l'écran admin de modération des avis (`AV2-F4`), les règles vidéo (`VID-3`, `VID-4`,
> `VID-5`), la traçabilité photo (`PHOTO-7`), le cache hors-ligne (`REL-V3`) et le réalignement
> des branches (`REL-V4`).
>
> **Statuts périmés corrigés** : `EC-2`, `EC-3`, `EC-4`, `ABO-4` sont **faits** (mécanisme de
> reprise d'action en place) et `REL-V2` aussi (le pré-rendu SEO est en production depuis
> le 20/07 — un robot reçoit bien le contenu). Ne pas les redévelopper.


> **Périmètre : frontend uniquement** — vitrine publique, application pro (web + mobile), interfaces d'administration, design.
> Le volet backend (API, base de données, services) est pris en charge séparément et suivi dans **`SUIVI_BACKEND.md`**.
> Un item qui touche les deux couches porte le **même identifiant** des deux côtés, avec un renvoi `↔`. Quand tu vois `↔`, l'API correspondante est de mon côté : dis-moi quand tu en as besoin, je te donne le contrat.
>
> **Méthode** : chaque statut a été vérifié dans le code (pas de supposition). Les chemins de fichiers sont donnés pour te faire gagner du temps.
> Dépôt frontend : `couturepro_frontend` — branche `master` = web, branche `android` = master + couche mobile.
> Dernière mise à jour : **19/07/2026**

**Légende** — ✅ Fait · 🟡 Partiel · ⬜ À faire · ⚠️ Bug confirmé · 🔵 Spec à suivre · ℹ️ Info / décision

---

## ⚠️ À LIRE D'ABORD

| # | Constat | Ce que ça change pour toi |
|---|---|---|
| 1 | **L'architecture des abonnements est déjà centralisée et fonctionne.** Tu as les hooks `usePlanFeature(cle)` (booléens) et `usePlanLimit(cle, count)` (limites numériques, `null`/`-1` = illimité), plus le composant `FeatureGate`. | **Ne recode aucune limite en dur** : branche-toi sur ces hooks. Voir `src/hooks/usePlanFeature.js` et `src/components/abonnement/FeatureGate.jsx`. |
| 2 | **Le CSS de bande défilante existe déjà** : `.gx-marquee` dans `src/index.css` (défilement continu, pause au survol, désactivé si l'utilisateur réduit les animations). Utilisé aujourd'hui par `src/pages/vitrine/PartenairesBanner.jsx`. | Réutilisable **tel quel** pour la bande d'annonces (ANN-8). |
| 3 | ~~Aucun mécanisme de reprise d'action après connexion~~ **FAUX — corrigé le 20/07.** `src/pages/vitrine/actionEnAttente.js` existe et couvre trois actions (commander, laisser un avis, suivre un créateur). | EC-2/EC-3/EC-4 sont **faits**. Ne pas les redévelopper. |
| 4 | Certains correctifs doivent être portés **sur les deux branches** (`master` **et** `android`). | Signalé au cas par cas ci-dessous. |

---

## 1. Sprint 02A — Module « Mes vêtements »

> Le module s'appelle en réalité **« Modèles courants »** dans le code (route `/catalogue`), pas « Mes vêtements ».

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| S02A-25 | Bouton « Modifier » → « Enregistrer » | ✅ | ✅ **Fait (web).** Libellé « Enregistrer » en édition, via `t()` (`useTranslation` ajouté). ⚠️ Le portage `android` N'A PAS été fait : les branches ont divergé de 247 commits côté master et 288 côté android — c'est un chantier de réalignement (fusion + build APK + test appareil), pas un portage de fichier. Tracé en REL-V4.
| S02A-26 | Positionnement du bouton | ✅ | ✅ **Fait.** Un seul bouton d'action, en pied de panneau collant (`sticky bottom-0`, `safe-area-inset-bottom`) dans `VetementForm.jsx`.
| S02A-26b | Doublon d'action « Nouveau » | ✅ | ✅ **Fait.** Le bouton flottant n'est rendu que si `mesModeles.length > 0` : quand la liste est vide, seul l'état vide porte l'invitation.
| S02A-28 | Limites en dur à brancher sur le plan | ✅ | ✅ **Fait.** Limites issues du plan : `max_photos_vetement` (via `usePlanLimit`) et `max_photos_realisation` (via `/realisations/quota`), avec l'ancienne valeur en repli. ⚠️ Découvert au passage : **le serveur n'imposait aucune limite** sur les photos de modèle — le front s'arrêtait à 5, l'API en acceptait autant qu'on voulait. Plafond appliqué côté serveur à la création et à la modification. `VetementForm` portait aussi 6 libellés français en dur, passés par `t()`.
| S02A-28b | Écran admin des plans désynchronisé | ✅ | ✅ **Fait.** L'écran ne porte plus de liste de clés en dur : les réglages avancés sont rendus depuis `GET /admin/fonctionnalites` (libellé, description, type, unité). Une clé ajoutée par une future migration apparaît automatiquement, avec le bon type de champ. Les clés orphelines (présentes en config, absentes du référentiel) restent visibles et supprimables plutôt que masquées.

---

## 2. Sprint 08vC — Avis & paiements

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| S08C-29 | Retirer la validation des avis par le créateur | ✅ | ✅ **Fait.** `MaVitrinePage.jsx` : boutons Valider/Rejeter retirés, `avisService.moderate` supprimé. Le panneau ne liste plus que les avis **signalés**, en lecture seule, avec la mention que Gextimo les examine.
| S08C-30 | Moyens de paiement → **FedaPay uniquement** | ✅ | ✅ **Fait.** Nouveau hook `useMoyensPaiement` → `GET /moyens-paiement` (liste + défaut, éditable en admin). Les anciens libellés (`wave`, `om`…) restent traduits : les factures déjà émises portent ces valeurs en base. ℹ️ La liste caisse/commandes n'a **pas** été touchée — elle enregistre comment le client a payé sur place ; la basculer sur FedaPay supprimerait l'encaissement en espèces.

---

## 3. Workflow photos

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| PHOTO-1 | Retour qualité **instantané et visuel** | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** `VerdictQualite.jsx` : icône + couleur + animation, AUCUN texte affiché (accessible via `title`/`aria-label` pour lecteur d'écran). Un pictogramme par cause (agrandir/recadrer/soleil/mise au point), se referme seul (6 s si bloquant, 3,5 s sinon), pastille verte dédiée si la photo passe. Sans pénalité confirmé côté serveur : le quota hebdomadaire ne compte que `soumis_at` (soumission formelle), jamais `ajouterPhoto` — reprendre une photo refusée est gratuit autant de fois que nécessaire. |
| PHOTO-3 | Écran de modération admin | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** `AdminRealisationsPage.jsx` : compte à rebours des 24 h (rouge si dépassé, orange sous 6 h), réévalué chaque minute sans recharger. Action « retoucher » (recadrage/ajustements sur canvas) livrée en plus de approuver/refuser, pour les designers peu équipés. |
| PHOTO-4 | Affichage du quota designer | ✅ | ✅ **Fait.** `MesRealisationsPage.jsx` affiche le quota du cycle, la date de remise à zéro, l'alerte à 80 % et le blocage à 0 (bouton désactivé). Tous les seuils viennent du serveur (`/realisations/quota`), le front n'en recalcule aucun.
| PHOTO-7 | Historique / traçabilité | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** L'original n'est JAMAIS écrasé : la retouche s'enregistre dans `retouche_path`/`retouche_url`, distinct de `path`. Toute nouvelle retouche repart de l'ORIGINAL (jamais d'une retouche précédente) pour éviter un empilement de corrections qui dégraderait la photo à chaque passage. Publication : la version retouchée s'affiche si elle existe, sinon l'original — jamais de blocage silencieux si le filigrane échoue. |

---

## 4. Module Annonces (Espace Designer) — gros morceau

> Existant : un onglet « Annonce » minimal dans le Studio (`src/pages/StudioPage.jsx`) = un menu déroulant de collection + une zone de texte + un bouton. Environ 20 % du besoin.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| ANN-1 | Formulaire complet | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Formulaire complet (titre, message, date de début, durée, image) — `AnnoncesPage.jsx`, routé `/annonces`, accessible via `NAV_GROUPS` (barre latérale **et** menu mobile, qui la réutilise), réservé aux comptes designer. |
| ANN-2 | Durée 1 → 10 jours | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Durée choisie en NOMBRE DE JOURS, la date de fin étant calculée par le serveur. ⚠️ Le sélecteur listait « 1 à 10 » **en dur** alors que le serveur expose ses bornes : une limite modifiée en configuration n'aurait rien changé à l'écran. Bornes désormais renvoyées par `GET /annonces` et suivies par l'écran. |
| ANN-3 | Une annonce par jour + message d'information | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Une annonce par jour. Le quota est annoncé **avant** le formulaire plutôt que de laisser saisir pour refuser à l'envoi, avec la date de réouverture et le renvoi vers le Boost. |
| ANN-4 | Historique sous le formulaire | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Historique sous le formulaire : image, titre, message, période, pastille de statut (programmée / en cours / boostée / terminée / masquée), actions Boost, retrait d'image et suppression. |
| ANN-5 | Bouton « Boost » + fenêtre modale | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Bouton « Boost » et fenêtre modale, proposés uniquement sur une annonce encore diffusable. |
| ANN-6 | Tarif automatique | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Tarif issu de la configuration serveur, affiché en lecture seule et jamais recalculé : un montant bricolé côté client serait rejeté, et un changement de prix en admin s'applique sans redéploiement. |
| ANN-7 | Information sur le boost | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Information sur le Boost présentée dans la modale ; la date de début est bornée à la fenêtre de diffusion, calculée en heure de Cotonou — proposer le jour UTC faisait rejeter le clic près de minuit. |
| ANN-8 | **Bande d'annonces défilante** | ✅ | ✅ **Fait (22/07).** `BandeAnnonces.jsx`, réutilise `.gx-marquee`. Elle vivait d'abord sur le tableau de bord PRO : un créateur payait un Boost pour être vu… par ses concurrents dans leur espace de travail, et jamais par le public censé acheter chez lui. Déplacée sur la vitrine, à sa vraie place. ⚠️ Elle **disparaissait au premier défilement** — une annonce boostée, donc payée, n'était visible que le temps du premier écran, alors que c'est la visibilité qui est vendue. Fusionnée avec la barre de navigation dans un **unique bloc collant** : les rendre collantes séparément les aurait fait se chevaucher. L'ordre vient du serveur (boostées d'abord) — le trier côté écran reviendrait à décider localement de ce qui a été payé. |
| ANN-9 | Gestion de l'image + Aperçu | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Image avec aperçu immédiat (`URL.createObjectURL`) et retrait. L'envoi se fait **après** la création, le serveur rangeant le fichier sous l'identifiant de l'annonce ; si l'image échoue, l'annonce existe quand même et on le dit sans faire croire à un échec complet. |

---

## 5. Abonnement aux créateurs

> ⚠️ Aujourd'hui le bouton « S'abonner » fonctionne **sans aucun compte** : l'abonnement est stocké sous une clé visiteur en stockage local. Vider son cache = tout perdre. Il n'a donc aucune valeur réelle.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| ABO-1 | Vérifier la session au clic | ✅ | ✅ **Fait.** ⚠️ C'était devenu une **régression** : le serveur exigeait déjà un compte (401) mais le front envoyait une clé anonyme et `postJson` aplatissait toute erreur sur `null` — chaque clic « Suivre » échouait en silence. `postDetaille` rend le statut, l'affichage optimiste est annulé sur refus, et un 401 déclenche la connexion.
| ABO-2 | Inscription simplifiée à la volée | ⬜ | Le module s'ouvre **tout seul** (l'utilisateur ne doit pas le chercher). Seule information obligatoire : **l'adresse e-mail**. ⚠️ Si l'utilisateur ferme ou abandonne avant validation : **aucun compte incomplet, aucun abonnement enregistré**. ℹ️ Le socle existe déjà côté serveur (code e-mail + Google). |
| ABO-4 | Reprendre l'abonnement automatiquement | ✅ | ✅ **Fait.** L'abonnement visé est rejoué automatiquement après connexion, puis l'utilisateur est ramené au profil du créateur.
| ABO-5 | Case de consentement notifications | ✅ | ✅ **Fait.** Le consentement aux notifications est distinct de l'abonnement et modifiable **à tout moment** depuis l'espace client — il ne pouvait auparavant se régler qu'à la souscription, sans retour arrière possible (nouvel endpoint `PATCH /vitrine/client/abonnements/{id}`).
| ABO-6 | Messages des règles métier | ✅ | ✅ **Fait.** Le message renvoyé par le serveur (auto-abonnement, déjà abonné) est affiché à l'utilisateur au lieu d'être avalé.
| ABO-7 | Espace client : mes abonnements | ✅ | ✅ **Fait.** Section « Mes créateurs suivis » dans l'espace client : liste, lien vers le profil, désabonnement et bascule des notifications. Les abonnements n'étaient visibles **nulle part** — on suivait sans jamais pouvoir consulter ni se désabonner autrement qu'en retournant sur le profil.

---

## 6. Parcours Espace Client — **priorité haute**

> C'est le blocage signalé par la direction : l'authentification marche, mais **le parcours s'arrête là**. Chaque module fonctionne isolément, ils ne sont pas **chaînés**.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| EC-1 | Session persistante | ✅ | Vérifié : jeton stocké, session restaurée au chargement, purge si invalide. **Le problème n'est pas là.** |
| EC-2 | Ouvrir l'espace client automatiquement | ✅ | ✅ **Fait.** Après authentification, l'espace client s'affiche directement. Si une action était en attente, elle est rejouée d'abord, puis l'utilisateur est ramené à la page d'où il venait.
| EC-3 | **Reprise automatique de l'action initiale** | ✅ | ✅ **Fait.** `src/pages/vitrine/actionEnAttente.js` : intention mémorisée (type + payload + page de retour) en `sessionStorage`, valable 30 min, rejouée une seule fois après connexion. L'écran de connexion **dit pourquoi** il s'affiche. Générique : une nouvelle action n'a qu'un type à déclarer.
| EC-4 | Gestion des échecs | ✅ | ✅ **Fait.** Succès et échec de la reprise sont annoncés. ⚠️ Au passage : le composant `<Toaster />` n'était **monté nulle part** — les 41 appels `toast.success`/`toast.error` de toute l'application ne produisaient rien. Corrigé dans `main.jsx`.
| EC-5 | Couvrir **toutes** les actions | ✅ | ✅ **Fait.** Enchaînement vérifié sur **toutes** les actions nécessitant un compte : *suivre un créateur* (rejeu de l'action) et *commander* (intention mémorisée **en plus** du paramètre d'URL — le paramètre seul ne survivait pas à un aller-retour hors du site). La reprise se déclenche aussi pour un utilisateur **déjà connecté**, cas où aucun écran de connexion ne venait la déclencher : l'intention serait restée en attente indéfiniment. *Liker*, *laisser un avis* et *acheter un patron* restent anonymes — aucun compte requis, donc rien à enchaîner.

---

## 7. Studio — Vidéos

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| VID-1 | **Lecture intégrée** (embed) | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Lecteur intégré livré : `src/components/video/CarteVideo.jsx` (iframe chargée **au clic seulement** — six lecteurs posés d'emblée alourdissent la page), cadre 16/9 uniforme, vignette, fichiers importés lus par `<video>`. Analyse des liens dans `src/utils/videoEmbed.js` : YouTube (watch, court, Shorts, live), Vimeo, Dailymotion, avec refus propre d'une URL non reconnue. Monté sur le studio **et** sur le profil public du créateur. |
| VID-2 | Compteur visible | ✅ | ✅ **Fait.** Compteur servi par `GET /atelier-videos/quota` (plafond du plan, illimité géré, corrections restantes du mois). Le « /50 » en dur affiché à tout le monde a disparu.
| VID-3 | Règles de modification | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Quota mensuel de corrections piloté par le plan (`AtelierVideoController`, « plus rien en dur »), route `PUT /atelier-videos/{id}` en place. |
| VID-4 | Import direct de vidéo | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Import direct de fichier : `AtelierVideo::SOURCE_FICHIER`, colonne `fichier_path`, formulaire du studio (13 occurrences fichier/upload). |
| VID-5 | Statut « en attente de validation » | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Statut « en attente de validation » : `STATUT_EN_ATTENTE` imposé à la création — aucune publication immédiate — avec `STATUT_PUBLIEE` / `STATUT_REFUSEE`, `motif_refus` et délai annoncé au créateur. |

---

## 8. Support

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| SUP-1 | Encart d'information dans les tickets | ✅ | ✅ **Fait — vérifié sur appareil le 22/07.** Encart permanent dans `SupportPage.jsx`, HORS du bloc d'état vide — le texte pédagogique vivait avant uniquement là, donc disparaissait dès le premier ticket créé, précisément pour ceux qui utilisent réellement le support. Testé avec des tickets déjà existants : l'encart reste affiché. |

---

## 8bis. Avis v2 — décisions direction du 20/07 (avis par modèle)

> J'ai livré la partie mécanique le 20/07 (Aquilas absent). Reste le parti pris visuel.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| AV2-F1 | Formulaire par modèle | ✅ | Choix du modèle **obligatoire** + validation des trois champs + erreurs affichées (le `catch` muet avalait tout). |
| AV2-F2 | Connexion avec rejeu | ✅ | Sans compte, l'avis saisi (texte compris) est mémorisé, la connexion s'ouvre en expliquant pourquoi, l'avis est **publié automatiquement** après connexion, retour au profil. Photos non conservées (fichiers) : le message le dit, sans promettre une édition qui n'existe pas. |
| AV2-F3 | Signalement motivé | ✅ | 4 motifs en un clic (contenu illégal / insulte / discrimination / autre) — motif grave = revue prioritaire immédiate côté admin. |
| AV2-F4 | UI admin de modération | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** `ModerationPage.jsx`, 3 onglets (Avis/Annonces/Vidéos), routé `/admin/moderation` et lié dans la barre latérale admin. Motif obligatoire à chaque retrait — repris tel quel dans l'avis envoyé au créateur. C'est le pendant du correctif des signalements : on ne sanctionne plus automatiquement (faille du 19/07 corrigée), il fallait donc pouvoir sanctionner à la main. |

---

## 9. Reliquat vitrine

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| REL-V1 | Points vitrine ouverts | ⬜ | Reprendre le contenu encore ouvert de `VITRINE_TODO_FRONTEND.md` (barre de contact, header/footer, textes web). Ce fichier est **déprécié** : les points actifs sont à basculer ici au fil de l'eau. |
| REL-V2 | Pré-rendu SEO | 🟡 | **Plus avancé que marqué — vérifié le 22/07.** Les métadonnées PAR PAGE existent déjà et fonctionnent : `usePageMeta.js` (title/description/image/canonical/robots, JS-render-pass) câblé sur **16 pages** de la vitrine, y compris le profil créateur avec ses vraies données (nom, bio, logo). Un rendu HTML statique pour réseaux sociaux existe aussi : `GET /api/og/createurs/<built-in function id>` (VitrineController::ogCreateur) — titre/description/image/données structurées schema.org LocalBusiness, avec redirection immédiate vers la vraie page. ⚠️ **Ce qui manque encore, et pourquoi je ne l'ai pas fait** : rien n'achemine un robot SANS JavaScript vers ce rendu — il faudrait détecter les bots par user-agent au niveau nginx et les rediriger vers `/api/og/createurs/{id}` avant qu'ils n'atteignent la coquille SPA. Le fichier nginx du site (`/etc/nginx/sites-available/gextimo_frontend.conf`) est en `600 root:root` : je n'ai accès qu'au script de déploiement whitelisté, pas à ce fichier. Configuration prête, à appliquer par qui a la main sur le serveur (voir message). |
| REL-V3 | « Mes Réalisations » sur mobile | ✅ | ✅ **Fait — vérifié dans le code le 22/07.** Couvert par `realisationsCache.js` (cache traversant, cent entrées, priorité aux brouillons et dossiers en attente) **et** par la route et l'entrée de menu ajoutées le 22/07 — la page était jusque-là importée sans être routée. |
| REL-V4 | Branches `master` et `android` distinctes | ℹ️ | **Décision, pas dette (22/07).** L'écart entre les deux branches est **voulu** : « c'est normal, mobile est différent de master », « ne cherche pas à vouloir rendre les deux identiques ». Un correctif web n'est donc **pas porté d'office** sur mobile. Exemple du jour : l'arc du splash a été relevé au pixel sur le logo côté web, alors qu'android garde sa version allégée pour la fluidité de la WebView — « android laisse, c'est déjà bon comme ça ». Deux versions du même écran, chacune juste dans son contexte. Ne plus compter cet écart comme un risque. |
| REL-V5 | Composant `<Toaster />` jamais monté | ✅ | Constaté le 20/07 : `react-hot-toast` était installé et appelé **41 fois** dans l'application (caisse, commandes, clients, paramètres, studio…), mais le composant `<Toaster />` n'était rendu **nulle part**. Aucune confirmation d'enregistrement ni message d'erreur n'a jamais été visible. Corrigé dans `src/main.jsx`, avec les jetons de thème pour le mode sombre. |

---

## Conventions à respecter

- **Zéro texte en dur** : tout passe par les fichiers de langue (`src/lang/fr.json` / `en.json`).
- **Pas d'emoji dans le code** : utiliser les icônes `lucide-react`.
- **Aucune limite numérique en dur** : passer par `usePlanFeature` / `usePlanLimit`.
- **Toujours lancer `npm run build` sur `master` avant de pousser** (la CI construit `master`).
- Les dépendances mobiles (`@capacitor/*`) ne doivent **jamais** arriver sur `master`.

*Des questions sur un point ? Le contrat d'API correspondant est de mon côté — demande-le avant de commencer, ça évitera les allers-retours.*
