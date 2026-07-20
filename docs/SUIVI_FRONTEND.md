# Suivi FRONTEND — Gextimo (Aquilas)

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
| 3 | **Aucun mécanisme de « reprise d'action après connexion » n'existe** dans le code (vérifié). | EC-3 est une vraie construction, pas un correctif. C'est le cœur du problème signalé par la direction. |
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
| S02A-28b | Écran admin des plans désynchronisé | 🟡 | `src/pages/admin/PlansPage.jsx` connaît ~19 clés alors que le serveur en expose ~37 : **15 clés récentes** tombent dans « Autres clés » (champ libre), donc éditables à l'aveugle. À resynchroniser (je te fournis la liste). |

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
| PHOTO-1 | Retour qualité **instantané et visuel** | ⬜ | À l'envoi d'une photo, retour immédiat **sans aucun texte à lire** : icône, couleur, courte animation — compréhensible par tout utilisateur, quelle que soit sa langue. Si refusée, l'utilisateur peut reprendre autant de fois qu'il veut, **sans pénalité**. ↔ l'analyse (netteté, luminosité, résolution, cadrage) est côté serveur. |
| PHOTO-3 | Écran de modération admin | 🟡 | Une file de modération existe déjà (`src/pages/admin/AdminRealisationsPage.jsx`, approuver / refuser avec motif). À ajouter : **compte à rebours des 24 h** et action « retoucher légèrement puis valider » (recadrage, ajustements simples) pour les designers peu équipés. |
| PHOTO-4 | Affichage du quota designer | ✅ | ✅ **Fait.** `MesRealisationsPage.jsx` affiche le quota du cycle, la date de remise à zéro, l'alerte à 80 % et le blocage à 0 (bouton désactivé). Tous les seuils viennent du serveur (`/realisations/quota`), le front n'en recalcule aucun.
| PHOTO-7 | Historique / traçabilité | ⬜ | Côté admin, garder l'accès à la **photo originale** même après retouche. |

---

## 4. Module Annonces (Espace Designer) — gros morceau

> Existant : un onglet « Annonce » minimal dans le Studio (`src/pages/StudioPage.jsx`) = un menu déroulant de collection + une zone de texte + un bouton. Environ 20 % du besoin.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| ANN-1 | Formulaire complet | ⬜ | Titre, message, **image facultative** (bannière), **date de début** (calendrier), **durée** (sélecteur d'incrémentation). |
| ANN-2 | Durée 1 → 10 jours | ⬜ | Le designer choisit **un nombre de jours**, jamais une date de fin (calculée par le serveur). Publication **gratuite** quelle que soit la durée. |
| ANN-3 | Une annonce par jour + message d'information | ⬜ | Après publication, création bloquée jusqu'au lendemain. Encart (icône i) : « Chaque designer peut publier une seule annonce par jour. Pour augmenter la visibilité de votre annonce, utilisez la fonctionnalité Boost. » |
| ANN-4 | Historique sous le formulaire | ⬜ | Liste des annonces publiées avec leur statut : **En cours / Terminée / Expirée / Boostée**. |
| ANN-5 | Bouton « Boost » + fenêtre modale | ⬜ | Bouton **discret** sur chaque annonce encore active. Modale : date de début (calendrier) + durée **1 / 3 / 7 jours**. Le boost peut démarrer plus tard, tant que l'annonce est active. |
| ANN-6 | Tarif automatique | ⬜ | **1 j = 100 F · 3 j = 200 F · 7 j = 300 F**, affiché automatiquement selon la durée, **champ non modifiable**. Puis tunnel de paiement existant. |
| ANN-7 | Information sur le boost | ⬜ | Encart (icône i) dans la modale : « Pendant toute la durée du Boost, votre annonce sera diffusée trois fois par jour afin d'augmenter sa visibilité. » |
| ANN-8 | **Bande d'annonces défilante** | ⬜ | En **haut de l'application**, légère et élégante (esprit bandeau d'information TV), sans gêner l'expérience. → Réutiliser `.gx-marquee` (`src/index.css`), déjà en place pour les partenaires. ↔ j'expose le flux des annonces actives. |
| ANN-9 | Gestion de l'image + Aperçu | ⬜ | **Avec image** : bannière affichée, message en dessous. **Sans image** : texte seul, centré dans la bande. Bouton **« Aperçu »** avant publication pour vérifier le rendu final (texte, cadrage, lisibilité). |

---

## 5. Abonnement aux créateurs

> ⚠️ Aujourd'hui le bouton « S'abonner » fonctionne **sans aucun compte** : l'abonnement est stocké sous une clé visiteur en stockage local. Vider son cache = tout perdre. Il n'a donc aucune valeur réelle.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| ABO-1 | Vérifier la session au clic | ✅ | ✅ **Fait.** ⚠️ C'était devenu une **régression** : le serveur exigeait déjà un compte (401) mais le front envoyait une clé anonyme et `postJson` aplatissait toute erreur sur `null` — chaque clic « Suivre » échouait en silence. `postDetaille` rend le statut, l'affichage optimiste est annulé sur refus, et un 401 déclenche la connexion.
| ABO-2 | Inscription simplifiée à la volée | ⬜ | Le module s'ouvre **tout seul** (l'utilisateur ne doit pas le chercher). Seule information obligatoire : **l'adresse e-mail**. ⚠️ Si l'utilisateur ferme ou abandonne avant validation : **aucun compte incomplet, aucun abonnement enregistré**. ℹ️ Le socle existe déjà côté serveur (code e-mail + Google). |
| ABO-4 | Reprendre l'abonnement automatiquement | ✅ | ✅ **Fait.** L'abonnement visé est rejoué automatiquement après connexion, puis l'utilisateur est ramené au profil du créateur.
| ABO-5 | Case de consentement notifications | ⬜ | S'abonner et accepter les notifications sont **deux consentements séparés** : case dédiée, indépendante de l'abonnement. Exigence APDP / Code du numérique béninois. |
| ABO-6 | Messages des règles métier | ⬜ | « Vous êtes déjà abonné à ce créateur » si doublon. Un créateur ne peut pas s'abonner à lui-même. |
| ABO-7 | Espace client : mes abonnements | ⬜ | Liste des créateurs suivis + **désabonnement** à tout moment. |

---

## 6. Parcours Espace Client — **priorité haute**

> C'est le blocage signalé par la direction : l'authentification marche, mais **le parcours s'arrête là**. Chaque module fonctionne isolément, ils ne sont pas **chaînés**.

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| EC-1 | Session persistante | ✅ | Vérifié : jeton stocké, session restaurée au chargement, purge si invalide. **Le problème n'est pas là.** |
| EC-2 | Ouvrir l'espace client automatiquement | 🟡 | Après authentification, l'espace doit se charger **sans action supplémentaire**. |
| EC-3 | **Reprise automatique de l'action initiale** | ✅ | ✅ **Fait.** `src/pages/vitrine/actionEnAttente.js` : intention mémorisée (type + payload + page de retour) en `sessionStorage`, valable 30 min, rejouée une seule fois après connexion. L'écran de connexion **dit pourquoi** il s'affiche. Générique : une nouvelle action n'a qu'un type à déclarer.
| EC-4 | Gestion des échecs | ✅ | ✅ **Fait.** Succès et échec de la reprise sont annoncés. ⚠️ Au passage : le composant `<Toaster />` n'était **monté nulle part** — les 41 appels `toast.success`/`toast.error` de toute l'application ne produisaient rien. Corrigé dans `main.jsx`.
| EC-5 | Couvrir **toutes** les actions | ⬜ | Ne pas corriger que l'abonnement : tester le même enchaînement pour les favoris et toute action nécessitant un compte, pour que le défaut ne réapparaisse pas ailleurs. |

---

## 7. Studio — Vidéos

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| VID-1 | **Lecture intégrée** (embed) | ⬜ | Aujourd'hui un lien YouTube renvoie **vers YouTube** (simple lien sortant, dans `src/pages/vitrine/CreateurProfilPage.jsx` et `src/pages/StudioPage.jsx`). Attendu, à la manière de Notion : vidéo **intégrée dans une carte**, lecture **sans quitter Gextimo**, avec lecture/pause, barre de progression, muet, volume, plein écran, + bouton pour ouvrir sur YouTube. **Cartes de taille uniforme**, affichage en **grille**. |
| VID-2 | Compteur visible | ✅ | ✅ **Fait.** Compteur servi par `GET /atelier-videos/quota` (plafond du plan, illimité géré, corrections restantes du mois). Le « /50 » en dur affiché à tout le monde a disparu.
| VID-3 | Règles de modification | ⬜ | Gratuit : une seule vidéo, une nouvelle **remplace** l'ancienne, aucune correction/suppression mensuelle. Atelier : **1** correction/suppression par mois. Studio : **2**. Compteur mensuel affiché. ↔ la route de modification n'existe pas encore côté serveur. |
| VID-4 | Import direct de vidéo | ⬜ | Deux entrées possibles : **lien YouTube** ou **import d'un fichier** (tous les créateurs n'ont pas de chaîne). Mode d'affichage adapté selon la source. |
| VID-5 | Statut « en attente de validation » | 🟡 | 🟡 **Partiel.** Statut de modération affiché par vidéo côté créateur (en validation / refusée, motif en infobulle). **Restant : l'écran de validation côté admin** — aucune page admin vidéos n'existe côté front.

---

## 8. Support

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| SUP-1 | Encart d'information dans les tickets | ⬜ | Dans `src/pages/SupportPage.jsx`, ajouter un encart permanent (icône « i » ou bannière) : « Pour vos réclamations, suggestions d'amélioration, remarques, demandes d'assistance ou toute autre requête, veuillez créer un ticket afin de nous en informer. Notre équipe vous répondra dans les meilleurs délais. » ⚠️ Aujourd'hui le seul texte pédagogique est dans l'état vide, et il **disparaît dès le premier ticket créé**. |

---

## 9. Reliquat vitrine

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| REL-V1 | Points vitrine ouverts | ⬜ | Reprendre le contenu encore ouvert de `VITRINE_TODO_FRONTEND.md` (barre de contact, header/footer, textes web). Ce fichier est **déprécié** : les points actifs sont à basculer ici au fil de l'eau. |
| REL-V2 | Pré-rendu SEO | ⬜ | La vitrine est une application monopage : un robot sans JavaScript reçoit une **coquille vide** et le **même titre** sur toutes les pages. Recommandation prête (métadonnées par page + pré-rendu). ⚠️ **À valider avant de toucher la production.** |
| REL-V3 | « Mes Réalisations » sur mobile | ⬜ | Ajouter le cache hors-ligne (100 brouillons/en attente) côté application native, branche `android`. |
| REL-V4 | **Branche `android` désalignée** | ⚠️ | Constaté le 20/07 : `master` a **247 commits** que `android` n'a pas, et `android` en a **288** en propre. L'application mobile ne reçoit donc plus les correctifs web depuis longtemps, et « porter un correctif » n'a plus de sens fichier par fichier. C'est un **chantier de réalignement** à planifier (fusion, résolution de conflits, build APK, test sur appareil) — à ne pas improviser au milieu d'une autre tâche. Bloque de fait REL-V3 et le portage de S02A-25. |
| REL-V5 | Composant `<Toaster />` jamais monté | ✅ | Constaté le 20/07 : `react-hot-toast` était installé et appelé **41 fois** dans l'application (caisse, commandes, clients, paramètres, studio…), mais le composant `<Toaster />` n'était rendu **nulle part**. Aucune confirmation d'enregistrement ni message d'erreur n'a jamais été visible. Corrigé dans `src/main.jsx`, avec les jetons de thème pour le mode sombre. |

---

## Conventions à respecter

- **Zéro texte en dur** : tout passe par les fichiers de langue (`src/lang/fr.json` / `en.json`).
- **Pas d'emoji dans le code** : utiliser les icônes `lucide-react`.
- **Aucune limite numérique en dur** : passer par `usePlanFeature` / `usePlanLimit`.
- **Toujours lancer `npm run build` sur `master` avant de pousser** (la CI construit `master`).
- Les dépendances mobiles (`@capacitor/*`) ne doivent **jamais** arriver sur `master`.

*Des questions sur un point ? Le contrat d'API correspondant est de mon côté — demande-le avant de commencer, ça évitera les allers-retours.*
