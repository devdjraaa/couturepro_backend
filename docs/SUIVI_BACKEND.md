# Suivi BACKEND — Gextimo

> **Périmètre : backend uniquement** (API, base de données, migrations, services, modération côté serveur, infra).
> Le volet frontend est suivi séparément dans **`SUIVI_FRONTEND.md`** (Aquilas).
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
| 8 | ⚠️ **Le référentiel de permissions d'équipe n'est appliqué NULLE PART côté serveur** (découvert le 20/07). Il est renvoyé au front, qui masque l'interface — mais un membre d'équipe peut appeler n'importe quelle route pro quel que soit son rôle. | Correctif ciblé posé sur la conversion de points (action sensible). Une couche d'autorisation complète touche ~40 routes et risque de bloquer des utilisateurs légitimes : **à décider et tester posément**, pas à chaud. |
| 7 | **L'abonnement aux créateurs est 100 % anonyme** (clé visiteur en stockage local). Vider son cache = perdre ses abonnements. | ⚠️ **Décision** : que fait-on des abonnements anonymes existants lors de la bascule vers un compte ? |

---

## 1. Sprint 02A — Mes vêtements & abonnements

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| S02A-25 | Libellé du bouton d'édition | ℹ️ | **100 % frontend** ↔ `SUIVI_FRONTEND.md#S02A-25`. Rien à faire côté serveur. |
| S02A-26 | Positionnement du bouton | ℹ️ | **100 % frontend** ↔ `SUIVI_FRONTEND.md#S02A-26`. |
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
| S08C-31b | Corriger les anomalies de fidélité | 🟡 | **Partiellement fait (20/07, décision 4a)** — les 3 règles annoncées mais inexistantes sont retirées de l'interface et remplacées par les règles réelles ↔ `SUIVI_FRONTEND.md`. La permission `points.convert` est désormais appliquée. **Restent à arbitrer** : faut-il créditer les points hors synchro offline (aujourd'hui les utilisateurs 100 % web n'en gagnent jamais) et la conversion à 31 jours fixes quel que soit le seuil. |

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
| ABO-4 | Reprise de l'action initiale | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#ABO-4`. |
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
| EC-2 | Ouverture automatique de l'espace client | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#EC-2`. |
| EC-3 | Reprise automatique de l'action | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#EC-3`. |
| EC-4 | Gestion des échecs | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#EC-4`. |
| EC-5 | Couvrir toutes les actions nécessitant un compte | 🟡 | Côté serveur : les favoris n'existent pas en base (purement locaux), l'abonnement est anonyme → tant que ABO-1 n'est pas fait, « reprendre l'action » n'a pas de sens pour ces deux cas. |

---

## 7. Studio Vidéos & Support

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| VID-1 | Lecture intégrée | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#VID-1`. |
| VID-2 | Nombre max de vidéos par plan | ✅ | **FAIT (19/07)** — max_videos 1/3/5 par plan (avant : 50 en dur, et fonctionnalité réservée au Studio). `GET /atelier-videos/quota` pour le compteur. Vérifié en prod. |
| VID-3 | Règles de modification par plan | ✅ | **FAIT (20/07)** — route de modification créée (elle n'existait pas) + corrections plafonnées par plan (0/1/2), journalisées car une suppression efface la ligne. Offre Gratuite : la nouvelle vidéo REMPLACE l'ancienne, sinon l'utilisateur serait bloqué à vie. |
| VID-4 | Import direct de fichier vidéo | ✅ | **FAIT (20/07)** — import de fichier (MP4/MOV/WebM, 100 Mo) en plus du lien YouTube. |
| VID-5 | Validation obligatoire avant publication | ✅ | **FAIT (20/07)** — soumission → en attente → validée/refusée sous 24 h, notifications, file admin avec compte à rebours. Refus = quota restitué. ⚠️ Corrigé au passage : la vitrine n'était **pas filtrée** — une vidéo en attente ou refusée s'affichait publiquement. |
| SUP-1 | Encart d'information dans les tickets | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#SUP-1`. |

---

## 8. Reliquat ouvert (anciens trackers)

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| REL-1 | Déploiement automatique + cache nginx | ✅ | **RÉSOLU (19-20/07)** — trois problèmes distincts empilés : (1) le jeton HTTPS n'avait pas la portée `workflow` → **les 2 dépôts sont passés en SSH** (`github-djraa`), blocage levé ; (2) l'étape « Setup SSH » du CI n'avait ni délai ni tolérance → corrigée ; (3) **la vraie cause** des déploiements perdus : le secret `SSH_HOST` du dépôt backend, réécrit avec l'IP littérale. Déploiement automatique rétabli et vérifié. Le correctif nginx (immutable réservé aux assets hashés) est également poussé. |
| REL-2 | Pré-rendu SEO (pt 125) | ⬜ | Recommandation prête, **à valider avant de toucher la production**. La vitrine est une application monopage : un robot sans JavaScript reçoit une coquille vide. |
| REL-3 | « Mes Réalisations » sur mobile | ⬜ | Cache hors-ligne (100 brouillons/attente) à ajouter côté application native. |
| REL-4 | Flux « Éditer les mesures » (pts 68-69) | ⬜ | Charger les libellés de mesures du type de vêtement dans l'éditeur existant. Mesures **par client** (arbitrage acté). Nécessite un test sur appareil. |
| REL-5 | Relecture juridique des pages légales | ℹ️ | Compléments rédigés et **surlignés en vert** ; relecture par un juriste = tâche équipe (pt 123). |
| REL-6 | Clés externes | ℹ️ | GA4 / Meta Pixel / Clarity + Search Console : **bloqué**, dépend de la direction. |

---

*Ce fichier remplace, pour le volet backend, `SUIVI_NOVAFRIQ.md` et `SUIVI_SPEC_130.md` (dépréciés). Il est évolutif : mettre à jour les statuts au fil des livraisons.*
