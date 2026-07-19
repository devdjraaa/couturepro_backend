# Système de points de fidélité — état réel

> **Réponse au Point 31** (Sprint 08vC) — documentation du fonctionnement **actuellement implémenté**.
> Établi par audit du code (pas de mémoire ni de supposition) — 19/07/2026.
> ⚠️ Ce document décrit **ce qui existe**, pas ce qui devrait exister. Les écarts et anomalies sont signalés en fin de document.

---

## 1. Réponse directe aux questions posées

| Le boss demande : les points prennent-ils en compte… | Réponse |
|---|---|
| Les **abonnements** (activation) | ✅ **OUI** |
| Les **renouvellements** d'abonnement | ✅ **OUI** (même mécanisme que l'activation) |
| Les **commandes validées** | 🟡 **PARTIELLEMENT** — les points sont crédités à la **création** de la commande, pas à sa validation/livraison (le libellé est trompeur), et **uniquement via la synchro offline** |
| Les **achats** | ❌ **NON** |
| Les **ventes** | ❌ **NON** |
| Le **parrainage / recommandations** | ❌ **NON** — aucune ligne de code, malgré ce qu'affiche l'interface |
| Les **avis publiés** | ❌ **NON** |
| Les **catégories d'utilisateurs** (designer / artisan / client) | ❌ **NON** — aucune distinction. Les montants varient **selon le plan d'abonnement uniquement** |
| Autre critère | Création de **client** (+ ajustement manuel par un admin) |

**En une phrase** : aujourd'hui, seuls **4 événements** créditent réellement des points — activation/renouvellement d'abonnement, création d'un client, création d'une commande, et ajustement manuel admin.

---

## 2. Où vit le système

| Rôle | Fichier |
|---|---|
| Service central | `app/Services/PointsFideliteService.php` |
| Solde (1 ligne par atelier) | `app/Models/PointsFidelite.php` |
| Historique (immuable) | `app/Models/PointsHistorique.php` |
| API atelier | `app/Http/Controllers/Api/FideliteController.php` |
| API admin | `app/Http/Controllers/Admin/FideliteController.php` |
| Crédit clients / commandes | `app/Services/SyncService.php` (≈ l. 186-221) |
| Crédit activation abonnement | `app/Services/PaymentService.php` (≈ l. 425-434) et `app/Http/Controllers/Api/AbonnementController.php` (≈ l. 297-307) |
| Valeurs par plan | `database/seeders/NiveauxConfigSeeder.php` |
| Écran utilisateur | `couturepro_frontend/src/pages/PointsPage.jsx` |

Le solde est rattaché à **`atelier_id`** (contrainte d'unicité). Les clients finaux de la vitrine (`GxtClient`) n'ont **aucun** système de points.

---

## 3. Les événements qui créditent réellement

| Événement | Implémenté | Montant | Type en base |
|---|---|---|---|
| Activation / renouvellement d'abonnement | ✅ | `pts_activation` → **31** (mensuel) ou **365** (annuel) | `abonnement_activation` / `activation` |
| Création d'un **client** | ✅ *(via synchro offline uniquement)* | `pts_par_client` → **1 à 3** selon le plan | `client_cree` |
| Création d'une **commande** | ✅ *(via synchro offline uniquement)* | `pts_par_commande` → **1 à 3** selon le plan | `commande_validee` |
| Ajustement manuel par un admin | ✅ | libre (entier ≠ 0) | `bonus_admin` |
| Conversion en bonus (débit) | ✅ | `- seuil_conversion_pts` | `conversion` |
| Partage sur réseau social | ❌ *(type déclaré en base, jamais écrit)* | — | `reseau_social` |
| Note sur le store | ❌ *(type déclaré en base, jamais écrit)* | — | `note_store` |
| Parrainage | ❌ *(aucun code)* | — | — |
| Avis publié | ❌ | — | — |
| Achat / vente / patron | ❌ | — | — |

---

## 4. Montants exacts par plan

Source : `database/seeders/NiveauxConfigSeeder.php`

| Plan | pts / client | pts / commande | pts activation | seuil de conversion | fidélité avancée |
|---|---|---|---|---|---|
| **free** (Gratuit) | 1 | 1 | 31 | 10 000 | non |
| **atelier_mensuel** | 1 | 1 | 31 | 45 000 | non |
| **atelier_annuel** | 1 | 1 | 365 | 45 000 | non |
| **master_mensuel** (Studio) | 2 | 2 | 31 | 100 000 | **oui** |
| **master_annuel** (Studio) | 2 | 2 | 365 | 100 000 | **oui** |
| *standard_mensuel* (legacy) | 1 | 1 | 31 | 10 000 | — |
| *standard_annuel* (legacy) | 1 | 1 | 365 | 10 000 | — |
| *premium_mensuel* (legacy) | 1 | 1 | 31 | 45 000 | — |
| *premium_annuel* (legacy) | 2 | 2 | 365 | 45 000 | — |
| *magnat_mensuel* (legacy) | 2 | 2 | 31 | 100 000 | — |
| *magnat_annuel* (legacy) | 3 | 3 | 365 | 100 000 | — |

*Les plans en italique sont désactivés (`is_actif = false`), conservés pour l'historique.*
Valeurs par défaut si un plan ne définit rien : `pts_par_client` = 1, `pts_par_commande` = 1, `pts_activation` = 31, `seuil_conversion_pts` = 10 000.

---

## 5. Règles de calcul, plafonds, limitations

- **Aucun plafond de gain.** Pas de limite journalière, mensuelle ni annuelle. Pas de rate limit sur l'accumulation.
- **Anti-doublon** : un même client ou une même commande ne peut être crédité qu'une seule fois (contrôle sur `reference_id`).
- **Les points ne sont jamais crédités depuis l'app web classique** pour les clients et les commandes : le crédit ne se déclenche que dans le flux de **synchronisation offline** (`POST /sync/push`). Un utilisateur qui travaille uniquement en web ne gagne donc **jamais** de points sur ses clients et commandes.
- Les valeurs négatives sont acceptées par le service interne (le solde pourrait théoriquement passer sous zéro par ce chemin) ; l'interface admin, elle, refuse un solde négatif.
- **Aucune distinction designer / artisan / client** : seul le plan d'abonnement fait varier les montants.

---

## 6. Utilisation des points (conversion)

Le **seul** usage possible aujourd'hui est la conversion en jours de bonus d'abonnement.

Conditions cumulatives :
1. l'atelier a un abonnement,
2. le plan définit un `seuil_conversion_pts` > 0,
3. le solde est **supérieur ou égal** au seuil,
4. aucun bonus n'est déjà actif.

Effet : on débite **exactement le seuil** (pas tout le solde) et l'atelier reçoit **31 jours de bonus**.

⚠️ **Le gain est toujours de 31 jours, quel que soit le seuil ou le montant converti.** Le libellé côté admin annonce pourtant « nombre de points nécessaires pour obtenir **1 jour** de bonus » — c'est incohérent avec le comportement réel.

---

## 7. Paliers (plan Studio uniquement)

| Palier | Seuil (cumul des points gagnés) |
|---|---|
| Bronze | 0 |
| Argent | 5 000 |
| Or | 20 000 |
| Platine | 50 000 |

- Calculés sur le **cumul des points positifs** (jamais diminué par les conversions).
- Seuils **codés en dur**, non modifiables depuis l'admin.
- **Aucune récompense n'est attachée à un palier** : c'est purement décoratif (barre de progression).

---

## 8. ⚠️ Anomalies et écarts constatés (à arbitrer)

Ces points nécessitent une décision de la direction :

1. **L'interface annonce des règles qui n'existent pas.** Le fichier de traduction affiche :
   - « 1 000 XOF payé = 1 point »
   - « Parrainage d'un atelier = 50 points »
   - « Chaque mois actif = 5 points »

   **Aucune de ces trois règles n'est implémentée** (vérifié : zéro occurrence de parrainage dans tout le backend). Il faut soit **les implémenter**, soit **retirer ces textes**. En l'état, c'est une promesse non tenue aux utilisateurs.

2. **Clients et commandes ne créditent qu'en mode offline.** Les utilisateurs 100 % web ne gagnent rien. À corriger si le comportement attendu est « toujours créditer ».

3. **Libellé trompeur** : le type s'appelle `commande_validee` et l'interface dit « chaque commande livrée vous rapporte des points », mais le crédit se fait à la **création** de la commande.

4. **La permission `points.convert` n'est pas appliquée.** Elle est déclarée dans le référentiel de permissions d'équipe, mais la route de conversion ne la vérifie pas : **n'importe quel membre d'équipe** authentifié peut convertir les points de l'atelier.

5. **Conversion à durée fixe** (31 jours) alors que le seuil varie de 10 000 à 100 000 selon le plan : un plan Studio paie 10× plus cher le même bonus qu'un plan Gratuit.

6. **Deux types de points déclarés mais morts** : `reseau_social` et `note_store` existent dans le schéma mais ne sont jamais écrits.

---

## 9. Recommandation

Avant d'ajouter de nouvelles règles (parrainage, achats, avis…), il faudrait d'abord **aligner l'existant** :
- décider si on implémente les 3 règles annoncées ou si on retire les textes (point 1 — c'est le plus urgent, car visible par les utilisateurs) ;
- faire créditer les points sur le chemin web classique, pas uniquement offline (point 2) ;
- appliquer la permission `points.convert` (point 4) ;
- rendre la conversion proportionnelle, ou aligner le libellé (point 5).

Ces corrections sont chiffrées et suivies dans `SUIVI_BACKEND.md` sous l'identifiant **S08C-31**.
