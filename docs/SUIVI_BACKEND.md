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
| 3 | **C'est le créateur qui modère ses propres avis** (`AvisController::moderer`, route dans le groupe atelier authentifié). Il est juge et partie : il peut rejeter tout avis négatif. | C'est exactement ce que la direction veut supprimer (Pt 29). |
| 4 | **La route de signalement d'un avis est publique, sans authentification ni limitation.** N'importe qui peut faire disparaître un avis validé de la vitrine. | Faille à corriger en priorité (S08C-29). |
| 5 | **L'interface annonce 3 règles de fidélité qui n'existent pas** (parrainage 50 pts, 1 000 XOF = 1 pt, mois actif = 5 pts). Vérifié : zéro occurrence de parrainage dans le backend. | Promesse non tenue visible par les utilisateurs. Voir `SYSTEME_FIDELITE.md`. |
| 6 | **Le socle du workflow photos existe déjà.** Le module « Mes Réalisations » (livré le 18/07) a déjà les statuts, la modération admin, le filigrane à la publication et l'anti-abus. | **Étendre**, ne pas refaire (PHOTO-*). |
| 7 | **L'abonnement aux créateurs est 100 % anonyme** (clé visiteur en stockage local). Vider son cache = perdre ses abonnements. | ⚠️ **Décision** : que fait-on des abonnements anonymes existants lors de la bascule vers un compte ? |

---

## 1. Sprint 02A — Mes vêtements & abonnements

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| S02A-25 | Libellé du bouton d'édition | ℹ️ | **100 % frontend** ↔ `SUIVI_FRONTEND.md#S02A-25`. Rien à faire côté serveur. |
| S02A-26 | Positionnement du bouton | ℹ️ | **100 % frontend** ↔ `SUIVI_FRONTEND.md#S02A-26`. |
| S02A-27 | Quota galerie piloté par l'abonnement | ✅ | **Déjà fait.** `GalerieController` lit `max_photos_vip_par_mois` via `getConfigEffective()`, expose `GET /galerie/quota` (`utilise`/`max`/`restant`/`illimite`), `-1` et `null` = illimité. |
| S02A-27b | Nettoyer les messages d'upsell en dur | 🟡 | `app/Traits/ChecksPlanFeature.php` contient « 5 photos/mois » en dur, et `GalerieController` renvoie `plan_requis => premium_annuel` codé en dur. À dériver de la config réelle. |
| S02A-28 | Configuration centralisée des abonnements | ✅ | **Déjà fait.** `NiveauConfig` (table `niveaux_config`) → `Abonnement::getConfigEffective()` (snapshot + fusion + repli sur `free` si expiré) → `ChecksPlanFeature::planGate()` + `AtelierLimitsService`. 26 points d'appel. |
| S02A-28b | Dette : mapping « feature → plan requis » en dur | 🟡 | `ChecksPlanFeature.php` embarque une table de 16 features en dur en PHP, sans lien avec `niveaux_config`. Si un plan change en base, ce mapping ne suit pas. À déplacer en configuration. |

---

## 2. Sprint 08vC — Avis, paiements, fidélité

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| S08C-29a | Publication automatique des avis | ⬜ | Aujourd'hui l'avis naît en `en_attente` et n'apparaît qu'une fois passé à `valide`. À basculer en publication directe (ou modération **globale** côté admin, pas côté créateur). |
| S08C-29b | Retirer la modération par le créateur | ⚠️ | Route `POST avis/{avis}/moderation` dans le groupe atelier → le créateur valide/rejette ses propres avis. **À supprimer** et, si une modération est souhaitée, la déplacer côté admin (aucune route admin n'existe aujourd'hui). |
| S08C-29c | Sécuriser le signalement d'un avis | ⚠️ | `POST avis/{avis}/signaler` est **public, sans auth ni limitation** : un avis validé passe en `signale` et disparaît instantanément de la vitrine, sans arbitrage ni retour possible. Ajouter authentification + limitation + file de modération. |
| S08C-29d | Autoriser plusieurs avis par utilisateur | 🟡 | Aucune contrainte d'unicité sur le chemin public (l'auteur est un simple champ texte). Sur le chemin espace client, l'unicité est **par commande** (applicative, pas d'index en base). À clarifier avec la direction : la limite voulue est-elle « 1 avis par commande » ? |
| S08C-29e | Lier les avis aux collections | ⬜ | `Avis` n'a pas de `collection_id`. Migration + relation à créer **si** la direction confirme vouloir des avis par collection (voir écart n°2). |
| S08C-30 | Moyens de paiement → FedaPay uniquement | 🟡 | Côté serveur, `mode_paiement` d'une facture accepte **n'importe quelle chaîne** (aucune énumération). Les commandes/caisse ont une énumération figée en base (`especes`, `mobile_money`, `virement`), incohérente avec la facturation. → Exposer **une liste unique et configurable** consommée par le front. ↔ `SUIVI_FRONTEND.md#S08C-30` |
| S08C-31 | Documentation du système de fidélité | ✅ | **Livré** : `docs/SYSTEME_FIDELITE.md` (événements réels, montants par plan, plafonds, conversion, 6 anomalies). |
| S08C-31b | Corriger les anomalies de fidélité | ⬜ | Suite du livrable : les 3 règles annoncées mais inexistantes, le crédit qui ne passe que par la synchro offline, la permission `points.convert` non appliquée, la conversion à 31 jours fixes. **Arbitrage direction requis.** |

---

## 3. Workflow photos & quota

> ℹ️ Le socle existe : `Realisation` (4 statuts), `WatermarkService` (filigrane à la publication), modération admin, anti-abus 10/semaine. On **étend**.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| PHOTO-1 | Contrôle qualité automatique | ⬜ | Analyse à l'envoi : netteté/flou, luminosité, résolution minimale, cadrage. Aucune analyse d'image aujourd'hui. Le retour visuel est frontend ↔ `SUIVI_FRONTEND.md#PHOTO-1`. |
| PHOTO-2 | Workflow Artisan (publication immédiate) | 🟡 | Aujourd'hui **toute** réalisation passe en modération. À différencier : artisan = publication directe après contrôle auto + modération a posteriori (échantillonnage/signalement). |
| PHOTO-3 | Workflow Designer (fenêtre admin 24 h) | 🟡 | La modération admin existe ; manquent la **fenêtre de 24 h**, le compte à rebours et l'action « retoucher légèrement puis valider ». |
| PHOTO-4 | Quota designer (compteur de solde) | ⬜ | Compteur = **solde restant**, décrémenté **à l'envoi**, réattribué (+1) si refus définitif ou suppression avant publication ; **jamais** réattribué après publication. Blocage à 0, alerte à 80 %, upgrade appliqué immédiatement. |
| PHOTO-5 | Cycle de renouvellement | ⬜ | Reset unique pour tous : **le 22 de chaque mois à 00h00 (heure de Cotonou)**, nouveau cycle le 23. Tâche planifiée + exposition de la date du prochain reset. |
| PHOTO-6 | Statuts de suivi | 🟡 | `Realisation` a déjà 4 statuts. À compléter : `contrôle auto en cours`, `validée auto`, `refusée auto`. |
| PHOTO-7 | API de modération admin | 🟡 | File + compteurs existent. À ajouter : compte à rebours 24 h, retouche, et **archivage de l'original** même après retouche (traçabilité / droits d'auteur). ↔ `SUIVI_FRONTEND.md#PHOTO-7` |

---

## 4. Module Annonces (Espace Designer)

> ℹ️ Existant : une « annonce de collection » minimale (2 colonnes greffées sur `collections`, message seul, publication immédiate, une annonce **écrase** la précédente). ~20 % du besoin.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| ANN-1 | Table `annonces` dédiée | ⬜ | Entité propre : titre, message, image, date de début, durée, statut, atelier. Aujourd'hui il n'y a **pas de table** — juste `annonce_message` + `annonce_at` sur `collections`. |
| ANN-2 | Durée 1→10 jours + date de fin calculée | ⬜ | Le designer choisit un nombre de jours ; la date de fin est calculée côté serveur. Publication gratuite. |
| ANN-3 | Limite d'une annonce par jour | ⬜ | Aucune limitation aujourd'hui (ni contrôle de date ni limitation de débit) : un designer peut publier en boucle. |
| ANN-4 | Historique + statuts | ⬜ | Statuts : en cours / terminée / expirée / boostée. Impossible aujourd'hui : la mise à jour **détruit** l'annonce précédente. Route de liste à créer. |
| ANN-5 | Boost — modèle et planification | ⬜ | Date de début (immédiate ou différée, tant que l'annonce est active) + durée 1 / 3 / 7 jours. |
| ANN-6 | Boost — paiement | ⬜ | 100 / 200 / 300 FCFA. Réutiliser le tunnel de paiement existant (FedaPay), ajouter un type « boost annonce ». |
| ANN-7 | Diffusion 3× par jour pendant le boost | ⬜ | Logique de diffusion + exposition à la vitrine. |
| ANN-8 | Flux public des annonces actives | ⬜ | Aucune route ne liste les annonces de **tous** les ateliers — indispensable pour la bande défilante. ↔ `SUIVI_FRONTEND.md#ANN-8` |
| ANN-9 | Upload de la bannière | ⬜ | Stockage image + exposition. ↔ `SUIVI_FRONTEND.md#ANN-9` |
| ANN-10 | Modération des annonces | ⬜ | Aucun statut de validation ni route admin aujourd'hui. À arbitrer : modère-t-on les annonces ? |

---

## 5. Abonnement aux créateurs

> ⚠️ Aujourd'hui **anonyme** : table `atelier_abonnes` = `atelier_id` + clé visiteur (stockage local). Aucun lien avec un compte, aucune notification envoyée, **aucune limitation de débit**.

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| ABO-1 | Exiger un compte pour s'abonner | ⬜ | Passer la route en authentifiée et rattacher l'abonnement au client (`gxt_client_id`). ↔ `SUIVI_FRONTEND.md#ABO-1` |
| ABO-2 | Inscription à la volée | ✅ | Le socle existe : inscription par e-mail avec code (OTP) + Google, limitation de débit correcte. Rien à construire côté serveur. ↔ front pour l'enchaînement. |
| ABO-3 | Session maintenue après inscription | ✅ | Jeton émis à la validation, session persistée. Vérifié. |
| ABO-4 | Reprise de l'action initiale | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#ABO-4`. |
| ABO-5 | Consentement notifications distinct | ⬜ | Aucune colonne de préférence. À ajouter, séparé de l'abonnement (exigence APDP / Code du numérique). |
| ABO-6 | Règles anti-abus | ⬜ | À ajouter : interdiction de s'abonner à soi-même (absent), abonnement compté **seulement après validation de l'e-mail**, limitation de débit (absente). L'unicité par (atelier, visiteur) existe déjà. |
| ABO-7 | Liste + désabonnement depuis l'espace client | ⬜ | Aucune route ne renvoie les abonnements d'un client. |
| ABO-8 | Traçabilité | 🟡 | La table a déjà `atelier_id` + horodatage. Manquent le lien compte, le statut actif/inactif et l'exploitation statistique. |
| ABO-9 | ⚠️ Migration des abonnements anonymes | ⬜ | **Décision direction requise** : on rattache les anciennes clés visiteur à un compte, ou on repart de zéro ? |

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
| VID-2 | Nombre max de vidéos par plan | ⬜ | Aujourd'hui **50 en dur** dans le contrôleur, identique pour tous les plans. À remplacer par une clé de configuration : Gratuit **1**, Atelier **3**, Studio **5**. |
| VID-3 | Règles de modification par plan | ⬜ | Le contrôleur n'expose **pas de route de modification** (seulement créer/supprimer) → le « compteur de corrections mensuelles » est impossible en l'état. À créer : modification + compteur (Gratuit : remplacement auto ; Atelier : 1/mois ; Studio : 2/mois). |
| VID-4 | Import direct de fichier vidéo | ⬜ | Aujourd'hui **URL uniquement** (aucune vérification que c'est bien YouTube). Stockage + traitement à prévoir. |
| VID-5 | Validation obligatoire avant publication | ⬜ | **Aucune modération** : publication immédiate. À créer : statut « en attente », notification admin, délai 24 h, refus → **quota restitué**. |
| SUP-1 | Encart d'information dans les tickets | ℹ️ | **Frontend** ↔ `SUIVI_FRONTEND.md#SUP-1`. |

---

## 8. Reliquat ouvert (anciens trackers)

| ID | Sujet | Statut | Détail / preuve |
|---|---|---|---|
| REL-1 | Correctif cache nginx (`immutable`) | 🟡 | Correctif prêt, mais le fichier de workflow n'est pas poussable : **le jeton du dépôt n'a pas la portée `workflow`**. Contournement en place (version dans l'URL du favicon). **Action direction requise.** |
| REL-2 | Pré-rendu SEO (pt 125) | ⬜ | Recommandation prête, **à valider avant de toucher la production**. La vitrine est une application monopage : un robot sans JavaScript reçoit une coquille vide. |
| REL-3 | « Mes Réalisations » sur mobile | ⬜ | Cache hors-ligne (100 brouillons/attente) à ajouter côté application native. |
| REL-4 | Flux « Éditer les mesures » (pts 68-69) | ⬜ | Charger les libellés de mesures du type de vêtement dans l'éditeur existant. Mesures **par client** (arbitrage acté). Nécessite un test sur appareil. |
| REL-5 | Relecture juridique des pages légales | ℹ️ | Compléments rédigés et **surlignés en vert** ; relecture par un juriste = tâche équipe (pt 123). |
| REL-6 | Clés externes | ℹ️ | GA4 / Meta Pixel / Clarity + Search Console : **bloqué**, dépend de la direction. |

---

*Ce fichier remplace, pour le volet backend, `SUIVI_NOVAFRIQ.md` et `SUIVI_SPEC_130.md` (dépréciés). Il est évolutif : mettre à jour les statuts au fil des livraisons.*
