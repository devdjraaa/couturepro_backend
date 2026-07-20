# Suivi BACKEND — Gextimo

> **Périmètre : la LOGIQUE MÉTIER, quelle que soit la couche** (API, base, services, mais aussi
> les écrans qui appliquent une règle serveur — quota, tarif, statut, reprise d'action, paiement).
> **Aquilas ne prend que la FAÇADE** : apparence, mise en page, animation, retour visuel.
> Critère : *si l'écran doit connaître une règle du serveur pour être juste, il est pour nous.*
>
> Correction du 20/07 : 8 items étaient marqués « 100 % frontend » et renvoyés à Aquilas alors
> qu'ils portaient de la logique (reprise d'action, quotas, tarifs, statuts). Ils reviennent ici.
> Un item qui touche les deux couches porte le **même identifiant** des deux côtés, avec un renvoi `↔`.
>
> **Méthode** : chaque statut est posé après **audit du code réel**, jamais par supposition. Les ✅ et 🟡 citent un fichier comme preuve.
> Sources : les 7 documents de retours de la direction (juillet 2026) + reliquat ouvert des anciens trackers.
> Dernière mise à jour : **19/07/2026**

**Légende** — ✅ Fait · 🟡 Partiel · ⬜ À faire · ⚠️ Bug confirmé · 🔵 Spec à suivre · ℹ️ Info / décision

---

## ⚠️ À LIRE D'ABORD — écarts entre la demande et le code

Ces constats changent le chiffrage. À valider avec la direction avant de lancer les chantiers.

| # | Constat | Conséquence |
|---|---|---|
| 1 | **Les points 27 et 28 sont déjà faits.** L'architecture centralisée d'abonnement existe et fonctionne (`NiveauConfig` → `Abonnement::getConfigEffective()` → `ChecksPlanFeature` → hooks front), ~37 clés de configuration. | Ce n'est **pas un chantier**, seulement de la dette à nettoyer (S02A-27/28). |
| 2 | **Les avis ne sont pas liés aux collections.** Le modèle `Avis` ne porte que `atelier_id` — la notion d'avis par collection n'existe nulle part. | Le Pt 29 tel qu'écrit suppose une fonctionnalité inexistante. **Décision à prendre** : créer le lien, ou traiter les avis au niveau créateur. |
| 3 | ~~**C'est le créateur qui modère ses propres avis**~~ ✅ **CORRIGÉ le 19/07** — validation retirée, publication automatique. | Reste au front à retirer l'écran (la route répond 410 en attendant). |
| 4 | ~~**La route de signalement d'un avis est publique, sans authentification ni limitation**~~ ✅ **CORRIGÉ le 19/07** — le signalement ne dépublie plus rien (compteur + horodatage), limitation de débit ajoutée sur le signalement et sur le dépôt d'avis. | Faille fermée, vérifiée en production. |
| 5 | **L'interface annonce 3 règles de fidélité qui n'existent pas** (parrainage 50 pts, 1 000 XOF = 1 pt, mois actif = 5 pts). Vérifié : zéro occurrence de parrainage dans le backend. | Promesse non tenue visible par les utilisateurs. Voir `SYSTEME_FIDELITE.md`. |
| 6 | **Le socle du workflow photos existe déjà.** Le module « Mes Réalisations » (livré le 18/07) a déjà les statuts, la modération admin, le filigrane à la publication et l'anti-abus. | **Étendre**, ne pas refaire (PHOTO-*). |
| 8 | ~~**Le référentiel de permissions d'équipe n'est appliqué nulle part côté serveur**~~ ✅ **CORRIGÉ le 20/07** — middleware `equipe.permission` sur **54 routes** (clients, mesures, commandes, paiements, vêtements, factures, notifications). Le propriétaire n'est jamais concerné. | Les défauts du rôle « assistant » ont dû être **élargis avant activation** : tels quels ils privaient les 4 assistants en production de la saisie des mesures et de l'avancement des commandes. Restent refusés : suppression clients/commandes, facturation, conversion de points. |
| 7 | ~~**L'abonnement aux créateurs est 100 % anonyme**~~ ✅ **RÉSOLU le 20/07** — l'abonnement est rattaché au compte client (ABO-1). | **Décision close par les faits** : il n'existait **aucun** abonnement anonyme en base (0 ligne en production). Il n'y avait donc rien à migrer. |

---

## 1. Sprint 02A — Mes vêtements & abonnements

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| S02A-25 | Libellé du bouton d'édition | ℹ️ | **Façade → Aquilas.** Aucune règle serveur en jeu. |
| S02A-26 | Positionnement du bouton | ℹ️ | **Façade → Aquilas.** |
| S02A-27 | Quota galerie piloté par l'abonnement | ✅ | **Déjà fait.** `GalerieController` lit `max_photos_vip_par_mois` via `getConfigEffective()`, expose `GET /galerie/quota` (`utilise`/`max`/`restant`/`illimite`), `-1` et `null` = illimité. |
| S02A-27b | Nettoyer les messages d'upsell en dur | ✅ | **FAIT (19/07)** — plan proposé dérivé de la grille active + libellés issus du référentiel ; `planRequisPourLimite()` ajouté pour les quotas numériques. |
| S02A-28 | Configuration centralisée des abonnements | ✅ | **Déjà fait.** `NiveauConfig` (table `niveaux_config`) → `Abonnement::getConfigEffective()` (snapshot + fusion + repli sur `free` si expiré) → `ChecksPlanFeature::planGate()` + `AtelierLimitsService`. 26 points d'appel. |
| S02A-28b | Dette : mapping « feature → plan requis » en dur | ✅ | **FAIT (19/07)** — la table PHP figée est supprimée : le plan requis est le plan ACTIF le moins cher activant la fonctionnalité. 23 clés sans libellé complétées dans `fonctionnalites`. |

---

## 2. Sprint 08vC — Avis, paiements, fidélité

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| S08C-29a | Publication automatique des avis | ✅ | **FAIT (19/07)** — publication immédiate à la soumission. La migration a publié l'avis resté en attente (vérifié en prod : 5 valides, 0 en attente). |
| S08C-29b | Modération : créateur → admin | ✅ | **FAIT (19/07)** — la validation par le créateur est retirée ; la route répond 410 le temps que le front retire l'écran ↔ `SUIVI_FRONTEND.md#S08C-29`. À supprimer ensuite. |
| S08C-29c | Sécuriser le signalement d'un avis | ✅ | **FAIT (19/07)** — le signalement n'affecte plus le statut : il incrémente un compteur + horodate. Limitation de débit ajoutée (10/h) ainsi que sur le dépôt d'avis (5/h). |
| S08C-29d | Autoriser plusieurs avis par utilisateur | ✅ | **Tranché (20/07, décision 2b)** — pas de contrainte d'unicité : chacun peut déposer plusieurs avis, la régulation se fait par la **modération admin a posteriori** + la limitation de débit (5/h). L'unicité par commande reste sur le chemin espace client. |
| S08C-29e | Lier les avis aux collections | ✅ | **FAIT (20/07, décision 1b)** — `collection_id` FACULTATIF sur les avis : un avis vise une collection précise ou le créateur. Les avis existants restent valides ; supprimer une collection ne les détruit pas. La collection doit appartenir au créateur visé (sinon dépôt refusé). |
| S08C-30 | Moyens de paiement → FedaPay uniquement | ✅ | **FAIT (20/07)** — liste unique éditable en admin, FedaPay seul en V1, `GET /moyens-paiement` comme source du front, et le serveur VALIDE enfin le mode reçu. ⚠️ Les anciennes valeurs restent tolérées le temps que le front bascule, sinon la création de factures casserait en production. ↔ `SUIVI_FRONTEND.md#S08C-30` |
| S08C-31 | Documentation du système de fidélité | ✅ | **Livré** : `docs/SYSTEME_FIDELITE.md` (événements réels, montants par plan, plafonds, conversion, 6 anomalies). |
| S08C-31b | Corriger les anomalies de fidélité | 🟡 | **Défauts techniques CORRIGÉS (20/07)** : les 3 règles inexistantes retirées de l'interface, permission `points.convert` appliquée, crédit désormais actif **aussi sur le chemin web** (il ne fonctionnait qu'en synchro hors ligne — idempotence vérifiée), description « Commande validée » → « Commande créée ». **RESTE UNE DÉCISION COMMERCIALE** : le programme est *mathématiquement inatteignable* — 375 points générés depuis le lancement tous ateliers confondus, seuil minimum 10 000, **0 conversion**. Il faut recalibrer les seuils (ou les gains). Voir `SYSTEME_FIDELITE.md`. |

---

## 3. Workflow photos & quota

> ℹ️ Le socle existe : `Realisation` (4 statuts), `WatermarkService` (filigrane à la publication), modération admin, anti-abus 10/semaine. On **étend**.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| PHOTO-1 | Contrôle qualité automatique | ✅ | **FAIT (20/07)** — analyse synchrone à l'envoi (résolution, luminosité, netteté, cadrage), retour en CODES traduits en icônes côté interface. Photo refusée = non conservée, reprise illimitée sans pénalité. ⚠️ **La netteté est en AVERTISSEMENT, pas en blocage** : mesuré, une photo nette réaliste score ~46 quand un damier flouté dépasse 3000 — un seuil absolu non calibré rejetterait des photos légitimes. Seuils éditables en admin (`controle_photo`) ; passer `nettete_bloquante` à vrai après calibrage sur de vraies photos. |
| PHOTO-2 | Workflow Artisan (publication immédiate) | ✅ | **FAIT (20/07)** — artisan : publication immédiate après contrôle auto (filigrane appliqué au passage), contrôle humain a posteriori. Évite le goulot d'étranglement sur un volume élevé. |
| PHOTO-3 | Workflow Designer (fenêtre admin 24 h) | ✅ | **FAIT (20/07)** — designer : validation humaine explicite, fenêtre de 24 h avec compte à rebours et repérage des dossiers en retard. |
| PHOTO-4 | Quota designer (compteur de solde) | ✅ | **FAIT (20/07)** — quota par cycle (5/10/20), solde **déduit des faits** (pas de compteur stocké, donc pas de dérive) : décrément à l'envoi, réattribution automatique si refus ou suppression avant publication. Alerte à 80 %, blocage à 0 avec message + plan supérieur. |
| PHOTO-5 | Cycle de renouvellement | ✅ | **FAIT (20/07)** — reset le 22 de chaque mois à 00h00 heure de Cotonou, date du prochain renouvellement exposée. Testé sur 7 dates (bascule à minuit pile, passage d'année). |
| PHOTO-6 | Statuts de suivi | ✅ | **FAIT (20/07)** — les statuts « contrôle auto en cours / validée auto » se sont révélés inutiles : l'analyse est SYNCHRONE (quelques dizaines de ms), il n'y a donc pas d'état intermédiaire à représenter. Le verdict est rendu dans la réponse à l'envoi. |
| PHOTO-7 | API de modération admin | ✅ | **FAIT (20/07)** — compte à rebours 24 h, retouche légère avant validation, **original toujours archivé** (traçabilité / droits d'auteur) ; le filigrane s'applique à la version retouchée s'il y en a une. |

---

## 4. Module Annonces (Espace Designer)

> ℹ️ Existant : une « annonce de collection » minimale (2 colonnes greffées sur `collections`, message seul, publication immédiate, une annonce **écrase** la précédente). ~20 % du besoin.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| ANN-1 | Table `annonces` dédiée | ✅ | **FAIT (19/07)** — table `annonces` dédiée + modèle. L'ancienne « annonce de collection » écrasait la précédente : l'historique est désormais possible. |
| ANN-2 | Durée 1→10 jours + date de fin calculée | ✅ | **FAIT (19/07)** — durée 1→10 jours, date de fin calculée côté serveur (durée inclusive : 1 jour = le jour même), publication gratuite. |
| ANN-3 | Limite d'une annonce par jour | ✅ | **FAIT (19/07)** — une annonce par jour et par atelier (jour calendaire, heure de Cotonou), avec le message d'information renvoyant vers le Boost. |
| ANN-4 | Historique + statuts | ✅ | **FAIT (19/07)** — historique complet + statuts DÉDUITS des dates (programmee/en_cours/boostee/terminee), donc jamais désynchronisés. 8/8 cas testés. |
| ANN-5 | Boost — modèle et planification | ✅ | **FAIT (19/07)** — planification du Boost (date de début libre tant que l'annonce est diffusée) + durées 1/3/7 jours issues de la configuration. |
| ANN-6 | Boost — paiement | ✅ | **FAIT (19/07)** — `POST /annonces/{annonce}/boost` : tunnel FedaPay réutilisé, prix lu côté SERVEUR (le client ne peut pas l'imposer). 5/5 garde-fous testés. Activation branchée dans `activate()` — sans ce dispatch un Boost payé n'aurait jamais démarré. |
| ANN-7 | Diffusion 3× par jour pendant le boost | ✅ | **FAIT (19/07)** — `diffusions_par_jour` (3) exposé dans la config + les annonces boostées sont marquées et remontées en tête du flux public. La rotation d'affichage est côté front ↔ `SUIVI_FRONTEND.md#ANN-7`. |
| ANN-8 | Flux public des annonces actives | ✅ | **FAIT (19/07)** — `GET /vitrine/annonces` : annonces en cours, boostées d'abord. Vérifié en prod. ↔ `SUIVI_FRONTEND.md#ANN-8` |
| ANN-9 | Upload de la bannière | ✅ | **FAIT (19/07)** — upload et retrait de la bannière (avec/sans image géré côté rendu). |
| ANN-10 | Modération des annonces | ✅ | **FAIT (20/07, décision direction)** — publication LIBRE (le designer ne doit pas attendre pour communiquer) + modération **a posteriori** en cas de contenu inapproprié. Même modèle que les avis : un signalement n'enlève rien, il alimente la file admin ; masquage avec motif obligatoire + notification au designer ; **masquage réversible** pour qu'un signalement abusif ne soit jamais définitif. |

---

## 5. Abonnement aux créateurs

> ⚠️ Aujourd'hui **anonyme** : table `atelier_abonnes` = `atelier_id` + clé visiteur (stockage local). Aucun lien avec un compte, aucune notification envoyée, **aucune limitation de débit**.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| ABO-1 | Exiger un compte pour s'abonner | ✅ | **FAIT (19/07)** — abonnement rattaché au compte ; la route renvoie `401 auth_requise` sans compte, signal exploité par le front pour ouvrir l'inscription. Vérifié en prod. |
| ABO-2 | Inscription à la volée | ✅ | Le socle existe : inscription par e-mail avec code (OTP) + Google, limitation de débit correcte. Rien à construire côté serveur. ↔ front pour l'enchaînement. |
| ABO-3 | Session maintenue après inscription | ✅ | Jeton émis à la validation, session persistée. Vérifié. |
| ABO-4 | Reprise de l'action initiale | ✅ | **DÉJÀ FAIT — statut périmé corrigé le 20/07.** Le module `src/pages/vitrine/actionEnAttente.js` existe : `memoriserAction` (posé depuis `CreateurProfilPage`) → `lireAction`/`consommerAction` (consommés dans `EspaceClientPage`). Trois actions couvertes : commander, laisser un avis, suivre un créateur. |
| ABO-5 | Consentement notifications distinct | ✅ | **FAIT (19/07)** — `notifications_optin` distinct de l'abonnement (exigence APDP). |
| ABO-6 | Règles anti-abus | ✅ | **FAIT (19/07)** — auto-abonnement bloqué ; unicité (atelier, client) en base. |
| ABO-7 | Liste + désabonnement depuis l'espace client | ✅ | **FAIT (19/07)** — `GET /vitrine/client/abonnements` (mes créateurs suivis) + désabonnement via le toggle. |
| ABO-8 | Traçabilité | ✅ | **FAIT (19/07)** — désabonnement conservé (`actif` + `desabonne_at`) ; le compteur public ne compte que les abonnements actifs. |
| ABO-9 | ⚠️ Migration des abonnements anonymes | ✅ | **FAIT (20/07, décision 3a)** — remise à plat : les abonnements anonymes sont supprimés (1 ligne, **sauvegardée** dans `~/backup-abonnes-anonymes-*.json` sur le VPS avant suppression). Les compteurs repartent de zéro sur une base fiable. |

---

## 6. Parcours Espace Client

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| EC-1 | Session créée et persistante | ✅ | Jeton avec capacité dédiée, restauré au chargement, purge automatique si invalide. **Le backend n'est pas en cause** dans le blocage constaté. |
| EC-2 | Ouverture automatique de l'espace client | ✅ | **DÉJÀ FAIT — vérifié le 20/07** : `CreateurProfilPage.jsx:353` redirige vers `/espace-client` quand l'action demande un compte. |
| EC-3 | Reprise automatique de l'action | ✅ | **DÉJÀ FAIT** — même mécanisme qu'ABO-4, l'action mémorisée est rejouée après connexion. |
| EC-4 | Gestion des échecs | ✅ | **DÉJÀ FAIT** — chaque branche de reprise gère son échec (`reprise_echec`), l'action n'est consommée qu'en cas de succès. |
| EC-5 | Couvrir toutes les actions nécessitant un compte | ✅ | Côté serveur, tout est en place : l'abonnement est rattaché au compte (ABO-1) et le parcours de reprise est couvert côté front ↔ `SUIVI_FRONTEND.md#EC-5`. Les favoris restent volontairement locaux (aucune table) — ils n'engagent rien et ne nécessitent pas de compte.

---

## 7. Studio Vidéos & Support

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| VID-1 | Lecture intégrée | 🟡 | **Partagé.** L'analyse du lien (fournisseur, extraction de l'identifiant, refus d'une URL non reconnue) est de la logique → **nous**. L'apparence du lecteur → Aquilas. |
| VID-2 | Nombre max de vidéos par plan | ✅ | **FAIT (19/07)** — max_videos 1/3/5 par plan (avant : 50 en dur, et fonctionnalité réservée au Studio). `GET /atelier-videos/quota` pour le compteur. Vérifié en prod. |
| VID-3 | Règles de modification par plan | ✅ | **FAIT (20/07)** — route de modification créée (elle n'existait pas) + corrections plafonnées par plan (0/1/2), journalisées car une suppression efface la ligne. Offre Gratuite : la nouvelle vidéo REMPLACE l'ancienne, sinon l'utilisateur serait bloqué à vie. |
| VID-4 | Import direct de fichier vidéo | ✅ | **FAIT (20/07)** — import de fichier (MP4/MOV/WebM, 100 Mo) en plus du lien YouTube. |
| VID-5 | Validation obligatoire avant publication | ✅ | **FAIT (20/07)** — soumission → en attente → validée/refusée sous 24 h, notifications, file admin avec compte à rebours. Refus = quota restitué. ⚠️ Corrigé au passage : la vitrine n'était **pas filtrée** — une vidéo en attente ou refusée s'affichait publiquement. |
| SUP-1 | Encart d'information dans les tickets | ℹ️ | **Façade → Aquilas.** Texte fixe, aucune règle serveur. |

---

## 9. Avis v2 — décisions direction du 20/07 (avis par modèle)

> Document « décisions arrêtées » reçu le 20/07 au soir. Livré le jour même.
> La piste « avis par collection » du 19/07 est **abandonnée** ; les 5 avis
> historiques (anonymes, niveau créateur) sont conservés tels quels.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| AV2-BUG | Avis sans texte (correctif prioritaire) | ✅ | Double faute : `texte` facultatif côté serveur ET erreurs avalées par un `catch` muet côté front. Trois champs obligatoires des deux côtés, messages précis. |
| AV2-1 | Avis rattaché au modèle | ✅ | `avis.vetement_id` (FK nullable), migration `2026_07_20_150000_avis_par_modele`. |
| AV2-2 | Un avis par compte et par modèle | ✅ | Index unique `(gxt_client_id, vetement_id)` + contrôle applicatif avec message clair. |
| AV2-3 | Compte obligatoire | ✅ | `storePourModele` exige un `GxtClient` (401 `auth_requise`) ; l'ancienne route anonyme répond 410. |
| AV2-7 | Signalement motivé | ✅ | Table `avis_signalements` (une empreinte = une voix), seuil **configurable**, motif grave = `revue_prioritaire` immédiate. **Jamais de dépublication automatique** — revue humaine. |
| AV2-8 | Anti-spam | ✅ | Plafond d'avis/jour par compte + refus du même texte normalisé (30 j). Seuils dans `moderation_avis`. |
| AV2-9 | « Achat vérifié » | ✅ | Colonne `achat_verifie` prête, logique différée (décision direction). |
| AV2-11 | Photos validées avant publication | ✅ | `photos_statut` (en_attente/validees/refusees) ; le public ne voit que `validees` ; photos existantes réputées validées. |
| AV2-12 | Mots bannis | ✅ | Liste **éditable en admin** (démarre vide, comme demandé), frontières de mots (pas de faux positifs sur mots contenus), avis publié mais marqué prioritaire — pas bloqué. |
| AV2-ADM | File de modération | ✅ | Priorités en tête, filtre photos, compteurs, `GET/PUT admin/vitrine/moderation-avis`, « rétablir » purge les signalements individuels. |

---

## 8. Reliquat ouvert (anciens trackers)

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| REL-1 | Déploiement automatique + cache nginx | ✅ | **RÉSOLU (19-20/07)** — trois problèmes distincts empilés : (1) le jeton HTTPS n'avait pas la portée `workflow` → **les 2 dépôts sont passés en SSH** (`github-djraa`), blocage levé ; (2) l'étape « Setup SSH » du CI n'avait ni délai ni tolérance → corrigée ; (3) **la vraie cause** des déploiements perdus : le secret `SSH_HOST` du dépôt backend, réécrit avec l'IP littérale. Déploiement automatique rétabli et vérifié. Le correctif nginx (immutable réservé aux assets hashés) est également poussé. |
| REL-2 | Pré-rendu SEO (pt 125) | ✅ | **Livré et vérifié en prod le 20/07** (feu vert donné). Nginx route les User-Agents de robots vers `GET /api/vitrine/seo/render` : titre PAR PAGE, description, canonique, Open Graph, et le contenu réel (liste des créateurs sur `/` et `/createurs`, bio/note/créations sur un profil). Humains : SPA inchangée, vérifié. Titres/descriptions éditables en admin (`seo_pages`). ⚠️ Trouvaille : l'étape « Deploy Nginx » du CI n'a jamais pu modifier ce vhost (sudo silencieusement refusés) — c'est ce qui protégeait les blocs SSL certbot ; la copie du VPS est l'autoritaire, miroir documenté dans le dépôt.
| REL-3 | « Mes Réalisations » sur mobile | ⬜ | Cache hors-ligne (100 brouillons/attente) à ajouter côté application native. |
| REL-4 | Flux « Éditer les mesures » (pts 68-69) | ✅ | **CLOS le 20/07 — testé sur appareil.** L'étiquette « test appareil » cachait le vrai problème : la colonne `libelles_mesures` avait été **supprimée en avril** (le modèle Eloquent la référençait encore). Colonne rétablie. **Test de bout en bout sur le téléphone, APK à jour** : suggestions rapides alimentées par la colonne (10 libellés), ajout d'un champ, saisie, enregistrement, réaffichage, puis réouverture en édition pré-remplie — tout fonctionne. Donnée de test nettoyée. Au passage, `max:5` figé des Requests corrigé (aurait plafonné un plan Studio à 5 photos). ⚠️ **Défaut trouvé pendant ce test et corrigé** : un enregistrement de mesures VIDÉ restait un objet truthy → l'écran affichait « Aucune mesure enregistrée » nu, sans invitation à saisir, avec export CSV et partage WhatsApp portant sur rien. Corrigé sur master **et android** ; chaîne codée en dur passée en i18n au passage. |
| REL-5 | Relecture juridique des pages légales | ℹ️ | Compléments rédigés et **surlignés en vert** ; relecture par un juriste = tâche équipe (pt 123). |
| REL-7 | Identité légale (RCCM, IFU, APDP, dates) | ✅ | **Trouvé et corrigé le 20/07 en parcourant les pages.** Les onze pages juridiques affichaient EN PRODUCTION des gabarits : « [NUMÉRO RCCM : à compléter après immatriculation] », « [NUMÉRO IFU : …] », « [DATE, à compléter] », délibération APDP — 12 occurrences FR + 12 EN. Sur des mentions légales, un crochet « à compléter » donne à lire que la société n'est pas immatriculée. Ces valeurs vivent désormais dans `VitrineSetting::identiteLegale()`, vides par défaut, éditables en admin (`PUT /admin/vitrine/identite-legale`, lecture publique `GET /vitrine/identite-legale`) ; **toute ligne citant une valeur absente disparaît** au lieu d'afficher un gabarit. Vérifié en prod : 0 gabarit sur les 11 pages. ⚠️ `/confidentialite` est stockée en base (personnalisée en back-office) et échappait à la résolution i18n — d'où `assainirHtmlLegal`, qui nettoie aussi tout texte collé demain en admin. **À FAIRE PAR LA DIRECTION** : saisir RCCM, IFU et n° de délibération APDP le jour de l'immatriculation — sans redéploiement ni développeur. |
| REL-6 | Clés externes | ℹ️ | GA4 / Meta Pixel / Clarity + Search Console : **bloqué**, dépend de la direction. |
| REL-8 | Deux APK : identité des saveurs | ✅ | **CLOS le 20/07 — les deux applications cohabitent, vérifié sur appareil.** Les deux saveurs portaient le **même `applicationId`** : installer la console interne REMPLAÇAIT l'app des pros. Quatre défauts empilés, tous corrigés : (1) le `sed` non ancré de `scripts/build-android.sh` réécrivait les **trois** `applicationId` du `build.gradle` → ancré sur `defaultConfig` ; (2) le script ne restaurait que `capacitor.config.json`, laissant `build.gradle` muté → sauvegarde + restauration ; (3) il recopiait aussi nom, icônes et couleur de fond dans `src/main/res`, **dossier partagé**, sans restauration — après un build admin, l'app des pros serait sortie avec l'icône rouge et le nom « Gextimo Admin ». La saveur admin a désormais ses **17 ressources propres** (comme gextimo), les étapes 4c/4d/4e du script sont supprimées, et `src/main/res` n'est plus touché : vérifié après DEUX builds successifs, le dépôt ressort intact ; (4) `google-services.json` ne déclarait qu'un paquet → **la direction a enregistré `com.couturepro.admin` dans Firebase**, l'identifiant distinct est rétabli. Vérifié dans les binaires : `com.couturepro.admin` / « Gextimo Admin » et `com.couturepro.app` / « Gextimo », installés côte à côte. |
| REL-10 | OTA servie par application | ✅ | **Trouvé en testant REL-8 sur appareil le 20/07.** L'APK de la console admin, pourtant construit avec la bonne cible (vérifié **dans le binaire** : `APP_TARGET` vaut « admin »), affichait l'écran de connexion des **professionnels** — et vider le cache n'y changeait rien. Cause : `/api/app/updates` servait un bundle **unique**. La console, lancée avec `autoUpdate` + `directUpdate`, téléchargeait le bundle des pros et **se faisait remplacer par lui à chaque démarrage**. La configuration devient une table indexée par identifiant de paquet ; une application absente ne reçoit **aucune** OTA (cas sûr). Deux pièges couverts par un test : `config()` lit la notation à points comme des niveaux imbriqués alors qu'un identifiant de paquet en contient (table indexée à la main), et le plugin envoie `app_id` ou `appId` selon sa version (les deux acceptés). Vérifié en production : pros → bundle 1.0.90, admin → `{}`, application inconnue → `{}`. Publier une OTA admin = renseigner `ADMIN_OTA_VERSION` / `ADMIN_OTA_URL`. |
| SUG-1 | **Écran de démarrage « favicon → logo → connexion »** | ✅ | **Livré le 20/07.** Le splash NATIF est une image figée qu'Android pose en fond de fenêtre : ni animable ni datable — c'est lui que la direction trouvait fade, et on ne peut rien en faire. `SplashDemarrage` prend le relais dès que la WebView est prête : l'anneau de la marque se trace, le point apparaît, le mot monte, puis l'écran s'efface vers l'application. Une fois par session, passable au clic ou au clavier, neutralisé si l'utilisateur a demandé moins d'animations. ⚠️ Au passage : **l'habillage saisonnier existait côté serveur depuis juillet mais AUCUN écran ne l'appelait** — les routes étaient mortes ; il est maintenant branché. Et `public/favicon.svg` était un glyphe **violet** resté d'un gabarit : comme les navigateurs préfèrent le SVG au `.ico`, c'est lui qui s'affichait dans les onglets. Remplacé par la marque. |
| REL-11 | **Lien de téléchargement du site périmé** | ✅ | **Trouvé le 20/07 sur question de la direction.** Le site pointe vers `/Gextimo.apk`, mais `scripts/release.sh` ne rafraîchissait qu'un **autre** fichier, `Gextimo-v1.0.apk`. Quiconque téléchargeait depuis la vitrine récupérait un APK du **16 juillet**, sans aucun correctif des quatre jours suivants. Le script publie désormais sous les deux noms (l'ancien est conservé, des liens ou QR codes peuvent le référencer) et le lien a été rafraîchi immédiatement. Le déploiement, lui, excluait déjà `Gextimo*.apk` du rsync : il n'écrasait rien. |
| REL-12 | **Réglages sans écran pour les atteindre** | ✅ | **Trois jeux de valeurs vivaient côté serveur sans interface** : périodes saisonnières et identité légale n'avaient qu'une route d'ÉCRITURE (on pouvait écraser à l'aveugle, jamais relire), la modération des avis n'était reliée à rien. La direction devait passer par un développeur pour changer une date d'entrée en vigueur, activer un habillage de Noël ou bannir un mot. Les lectures manquantes sont ajoutées (`GET /admin/vitrine/splash-themes`, `GET /admin/vitrine/identite-legale`) et l'écran `/admin/reglages-vitrine` regroupe les trois. |

---

## Ce qui NOUS revient encore (logique métier) — établi le 20/07 après audit du code

> Le socle serveur de ces chantiers est **déjà livré**. Ce qui manque, c'est l'écran qui applique
> la règle : quota, tarif, statut, durée. Ce n'est pas de la façade, Aquilas n'a pas à le porter.

| ID | Sujet | Statut | Ce qui existe déjà côté serveur / ce qui manque |
|---|---|---|---|
| ANN-F | **Module Annonces — écran créateur** | ✅ | **Le backend est COMPLET** : `GET annonces/quota`, `GET/POST annonces`, `PUT annonces/{id}`, `POST annonces/{id}/image`, `POST annonces/{id}/boost`, plus la modération admin et la diffusion vitrine. **Manque l'écran** : formulaire, durée en jours (la date de fin est calculée par le serveur), blocage 1 annonce/jour, historique avec statuts, modale de boost et **tarif automatique 1j=100 / 3j=200 / 7j=300 F**. Tout cela applique des règles serveur → **pour nous**. Restent à Aquilas : l'encart d'information (ANN-7) et l'habillage de la bande défilante (ANN-8, le CSS `.gx-marquee` existe déjà). |
| AV2-F4 | **Écran admin de modération des avis** | ✅ | Backend prêt (`GET/PUT admin/vitrine/moderation-avis` : seuils, motifs graves, mots bannis). L'écran est absent : la direction ne peut donc pas régler la modération sans passer par un développeur. **Pour nous** (CRUD de réglages). |
| VID-3 | Règles de modification des vidéos | ⬜ | Quotas par plan côté serveur ; l'écran doit refléter la règle (Gratuit : une seule vidéo, la nouvelle remplace l'ancienne ; corrections mensuelles comptées). **Pour nous.** |
| VID-4 | Import direct d'un fichier vidéo | ⬜ | Deux entrées : lien ou fichier. Envoi, validation, stockage → **pour nous**. |
| VID-5 | Statut « en attente de validation » | 🟡 | Partiel : le statut s'affiche côté créateur. Manque la boucle complète de modération. **Pour nous.** |
| PHOTO-7 | Traçabilité de la photo d'origine | ⬜ | Garder l'accès à l'original après retouche, côté admin. Stockage + exposition → **pour nous.** |
| REL-3 | Cache hors-ligne « Mes Réalisations » | ⬜ | 100 brouillons/en attente côté natif (WatermelonDB). **Pour nous.** Dépend de REL-V4. |
| REL-V4 | **Réalignement des branches** | ⚠️ | `master` et `android` ont divergé de plusieurs centaines de commits. Bloque REL-3 et toute reprise sereine du natif. **Pour nous**, et c'est le préalable technique le plus lourd. |

### Ce qui reste à Aquilas (façade pure)
`S02A-25` libellé de bouton · `S02A-26` positionnement · `PHOTO-1` retour visuel instantané ·
`ANN-7` encart d'information · `ANN-8` habillage de la bande défilante · `SUP-1` encart tickets ·
`REL-V1` finitions vitrine · l'apparence du lecteur vidéo (`VID-1`).

### Bloqué sur des tiers
`REL-5` relecture par un juriste · `REL-6` clés GA4 / Meta / Clarity (direction) ·
identité légale RCCM / IFU / APDP (immatriculation) · jeton Meta (CM) · mot de passe VASAT.
| REL-9 | Règles absolues enfreintes : hardcoding & emoji | ✅ | **Balayage systématique du 20/07.** (1) **ZÉRO HARDCODING** : 24 libellés français écrits en clair dans le JSX, invisibles pour la version anglaise — détail de commande (prix total, déjà encaissé, reste à payer, frise entière), réglages (format facture, logo, pied de page, TVA), équipe, formulaires, quota, sidebar, écran d'erreur. **7 d'entre eux avaient DÉJÀ une clé de traduction ailleurs** : le code les redoublait en clair (défaut récurrent du projet — deux sources pour la même vérité). Clés réutilisées, pas dupliquées. (2) **PAS D'EMOJI** : 40 emoji retirés de l'interface, remplacés par des icônes lucide avec `aria-hidden` ; les notations reçoivent un `aria-label` « n/5 » qu'un lecteur d'écran peut annoncer (une suite ★☆ ne se lisait pas). (3) **THÈME** : 11 fonds de palette Tailwind claire (`bg-green-50`…) qui restaient des taches blanches en thème sombre, dont le composant partagé `Badge` — alignés sur les jetons sémantiques, qui sont bien redéfinis en sombre. Les paliers de fidélité gardent leurs teintes propres avec une variante `dark:`. Vérifié sur les 17 pages de l'app en production. **Porté sur android**, où l'on a découvert que le second constructeur de message WhatsApp (unifié sur master le 20/07) y vivait toujours : il figeait l'unité « cm », la locale `fr-FR` et un message d'erreur français. |

---

*Ce fichier remplace, pour le volet backend, `SUIVI_NOVAFRIQ.md` et `SUIVI_SPEC_130.md` (dépréciés). Il est évolutif : mettre à jour les statuts au fil des livraisons.*
