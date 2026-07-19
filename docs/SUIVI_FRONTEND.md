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
| S02A-25 | Bouton « Modifier » → « Enregistrer » | ⬜ | `src/components/vetements/VetementForm.jsx` : le bouton affiche `initialData ? 'Modifier' : 'Ajouter'` **en dur**. En édition, l'utilisateur est déjà dans l'écran de modification → le libellé attendu est **Enregistrer**. ⚠️ Ce composant **n'importe pas `useTranslation`** alors que la clé `formulaire.enregistrer` **existe déjà** dans `src/lang/fr.json` : à passer par `t()` au passage. Idem pour l'entrée du menu ⋮ dans `VetementCard.jsx`. **À porter aussi sur la branche `android`** (code identique). |
| S02A-26 | Positionnement du bouton | ⚠️ | Trois problèmes cumulés dans `src/pages/CataloguePage.jsx` : (a) le `BottomSheet` n'a **aucun pied de page collant** → sur une fiche longue le bouton principal défile hors écran ; (b) `PatronManager` est rendu **sous** le formulaire pour un compte designer → les boutons « Annuler / Modifier » se retrouvent **au milieu du panneau** ; (c) `PatronManager` ajoute un second bouton pleine largeur juste en dessous → confusion. **Un seul bouton « Enregistrer » suffit**, placé en pied de page collant. |
| S02A-26b | Doublon d'action « Nouveau » | ⚠️ | Toujours dans `CataloguePage.jsx` : le bouton de l'état vide **et** le bouton flottant déclenchent la même action et s'affichent **en même temps** quand la liste est vide. Le bouton flottant recouvre aussi le menu ⋮ de la dernière carte. |
| S02A-28 | Limites en dur à brancher sur le plan | 🟡 | `MAX_IMAGES = 5` dans `VetementForm.jsx` et `MAX_PHOTOS = 6` dans `MesRealisationsPage.jsx` sont **codés en dur** → à brancher sur `usePlanLimit`. Voir aussi `src/services/abonnementService.js` qui embarque 6 configurations de plan en dur (mode démo) — dérive garantie avec le serveur. |
| S02A-28b | Écran admin des plans désynchronisé | 🟡 | `src/pages/admin/PlansPage.jsx` connaît ~19 clés alors que le serveur en expose ~37 : **15 clés récentes** tombent dans « Autres clés » (champ libre), donc éditables à l'aveugle. À resynchroniser (je te fournis la liste). |

---

## 2. Sprint 08vC — Avis & paiements

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| S08C-29 | Retirer la validation des avis par le créateur | ⬜ | Aujourd'hui le créateur valide/rejette les avis déposés sur son propre profil (il est juge et partie). La direction veut une **publication automatique**. → Retirer l'interface de modération côté créateur. ↔ `SUIVI_BACKEND.md#S08C-29b` (je supprime la route). |
| S08C-30 | Moyens de paiement → **FedaPay uniquement** | ⬜ | `src/pages/FacturationPage.jsx` : la liste `['wave','om','especes','virement','autre']` est **en dur**, avec `wave` par défaut. Pour la V1, n'afficher que **FedaPay**, en gardant la structure évolutive. Libellés dans `src/lang/fr.json`. ℹ️ Il existe une **seconde liste incohérente** (`especes`/`mobile_money`/`virement`) dans les écrans commandes/caisse — on la traite après, ne pas y toucher pour l'instant. ↔ `SUIVI_BACKEND.md#S08C-30` |

---

## 3. Workflow photos

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| PHOTO-1 | Retour qualité **instantané et visuel** | ⬜ | À l'envoi d'une photo, retour immédiat **sans aucun texte à lire** : icône, couleur, courte animation — compréhensible par tout utilisateur, quelle que soit sa langue. Si refusée, l'utilisateur peut reprendre autant de fois qu'il veut, **sans pénalité**. ↔ l'analyse (netteté, luminosité, résolution, cadrage) est côté serveur. |
| PHOTO-3 | Écran de modération admin | 🟡 | Une file de modération existe déjà (`src/pages/admin/AdminRealisationsPage.jsx`, approuver / refuser avec motif). À ajouter : **compte à rebours des 24 h** et action « retoucher légèrement puis valider » (recadrage, ajustements simples) pour les designers peu équipés. |
| PHOTO-4 | Affichage du quota designer | ⬜ | Afficher en permanence le **solde restant** et la **date du prochain renouvellement**. Alerte à **80 %** avec incitation à monter en gamme. À 0 : bouton d'ajout **inactif** + message « Vous avez atteint votre limite de photos pour ce mois. Renouvellement le [date]. » + bouton **« Passer à l'offre supérieure »**. |
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
| ABO-1 | Vérifier la session au clic | ⬜ | Au clic sur « S'abonner » : si une session visiteur active existe → on enregistre directement. Sinon → ouverture **automatique** du module d'inscription/connexion. ↔ je passe la route en authentifiée. |
| ABO-2 | Inscription simplifiée à la volée | ⬜ | Le module s'ouvre **tout seul** (l'utilisateur ne doit pas le chercher). Seule information obligatoire : **l'adresse e-mail**. ⚠️ Si l'utilisateur ferme ou abandonne avant validation : **aucun compte incomplet, aucun abonnement enregistré**. ℹ️ Le socle existe déjà côté serveur (code e-mail + Google). |
| ABO-4 | Reprendre l'abonnement automatiquement | ⬜ | Après création du compte / connexion, l'abonnement initialement visé est exécuté **automatiquement** — l'utilisateur ne doit **pas** recliquer sur « S'abonner ». |
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
| EC-3 | **Reprise automatique de l'action initiale** | ⬜ | **Le vrai chantier.** Mémoriser l'action demandée avant l'interruption par la connexion (s'abonner, mettre en favori…), puis l'exécuter automatiquement après connexion, avec **message de confirmation**. ⚠️ Vérifié : **aucun mécanisme de ce type n'existe** dans le code. Le seul embryon est un paramètre d'URL `?commander=` qui **pré-ouvre** un formulaire — ce n'est ni générique ni une exécution. À concevoir comme un mécanisme **générique** réutilisable. |
| EC-4 | Gestion des échecs | ⬜ | Si la reprise échoue (ressource supprimée entre-temps, erreur réseau…), afficher un **message clair** plutôt qu'un blocage silencieux. |
| EC-5 | Couvrir **toutes** les actions | ⬜ | Ne pas corriger que l'abonnement : tester le même enchaînement pour les favoris et toute action nécessitant un compte, pour que le défaut ne réapparaisse pas ailleurs. |

---

## 7. Studio — Vidéos

| ID | Sujet | Statut | Détail / où |
|---|---|---|---|
| VID-1 | **Lecture intégrée** (embed) | ⬜ | Aujourd'hui un lien YouTube renvoie **vers YouTube** (simple lien sortant, dans `src/pages/vitrine/CreateurProfilPage.jsx` et `src/pages/StudioPage.jsx`). Attendu, à la manière de Notion : vidéo **intégrée dans une carte**, lecture **sans quitter Gextimo**, avec lecture/pause, barre de progression, muet, volume, plein écran, + bouton pour ouvrir sur YouTube. **Cartes de taille uniforme**, affichage en **grille**. |
| VID-2 | Compteur visible | ⬜ | Afficher `0/1`, `2/3`, `5/5` selon le plan (Gratuit **1**, Atelier **3**, Studio **5**). ↔ j'expose la limite via la configuration de plan (aujourd'hui 50 en dur côté serveur, identique pour tous). |
| VID-3 | Règles de modification | ⬜ | Gratuit : une seule vidéo, une nouvelle **remplace** l'ancienne, aucune correction/suppression mensuelle. Atelier : **1** correction/suppression par mois. Studio : **2**. Compteur mensuel affiché. ↔ la route de modification n'existe pas encore côté serveur. |
| VID-4 | Import direct de vidéo | ⬜ | Deux entrées possibles : **lien YouTube** ou **import d'un fichier** (tous les créateurs n'ont pas de chaîne). Mode d'affichage adapté selon la source. |
| VID-5 | Statut « en attente de validation » | ⬜ | Aucune vidéo publiée immédiatement : soumission → **en attente** → vérification → publication ou refus (délai max **24 h**). En cas de refus, le **quota est restitué**. Prévoir l'affichage du statut côté créateur + l'écran de validation côté admin. |

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

---

## Conventions à respecter

- **Zéro texte en dur** : tout passe par les fichiers de langue (`src/lang/fr.json` / `en.json`).
- **Pas d'emoji dans le code** : utiliser les icônes `lucide-react`.
- **Aucune limite numérique en dur** : passer par `usePlanFeature` / `usePlanLimit`.
- **Toujours lancer `npm run build` sur `master` avant de pousser** (la CI construit `master`).
- Les dépendances mobiles (`@capacitor/*`) ne doivent **jamais** arriver sur `master`.

*Des questions sur un point ? Le contrat d'API correspondant est de mon côté — demande-le avant de commencer, ça évitera les allers-retours.*
