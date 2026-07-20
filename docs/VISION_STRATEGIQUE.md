# Vision stratégique — directives direction du 20/07/2026

> Source : message « Organisation des prochains sprints et orientations stratégiques ».
> La direction précise elle-même : *« Tous ces éléments ne sont pas destinés à être
> développés immédiatement. »* Ce document fixe ce que la vision **implique dès
> aujourd'hui** dans nos choix techniques, et ce qui attend son heure.
> Rien de ce qui suit ne doit être improvisé sans cadrage — c'est le principe
> « durable, jamais du va-vite ».

---

## 1. Ce qui est DÉJÀ en place (l'existant sert la vision)

| Directive | État actuel |
|---|---|
| Quota publications plan Gratuit (5 / période, reset date anniversaire) | ✅ **En production depuis le 16/07** — `AtelierLimitsService::publicationRefus()`, journal `publications_vitrine`, période = date anniversaire de l'abonnement. Dépublier ne redonne pas de crédit. |
| Facturation par CLIENTS DISTINCTS (10 / période, factures illimitées par client) | ✅ **En production depuis le 16/07** — `factureRefus()` + `clientsFacturesPeriode()`, identité stable par téléphone normalisé. Devis hors quota. |
| VASAT masqué par mot de passe | ✅ **Livré le 20/07** — route `/vasat` (noindex), page blanche, mot de passe TOFU vérifié côté serveur (`POST /vitrine/vasat/acces`, anti force brute). ⚠️ **Première saisie = mot de passe de référence : la direction doit aller le poser en premier.** |
| Config-driven partout | ✅ Le socle exigé par la vision existe : `NiveauConfig` (plans), `VitrineSetting` (réglages), référentiel `fonctionnalites` piloté serveur. Aucune valeur métier en dur. |
| Événements utilisateurs | 🟡 Partiel — `VitrineEvenement` (visites, contacts) + `gxtTracking` (consentement P202) existent ; c'est l'embryon du « moteur d'analyse maison ». |
| Consentement cookies | ✅ P202 — bandeau + préférences, la collecte respecte le choix. |

## 2. Implications IMMÉDIATES sur nos choix techniques (sans rien développer)

Ces règles de conception coûtent zéro maintenant et évitent une refonte plus tard :

1. **Tout événement significatif doit passer par un point unique** (`gxtTracking`
   côté client, `VitrineEvenement` côté serveur). Ne jamais disperser des
   compteurs ad hoc : c'est la matière première du futur moteur d'analyse et de
   l'algorithme de visibilité.
2. **Toute règle métier reste éditable en admin** (jamais en dur) — déjà notre
   règle absolue ; la vision la confirme.
3. **Les identifiants sont stables et neutres** (UUID, clés fonctionnelles) pour
   que les données restent exploitables par des tableaux de bord multi-produits.
4. **Multi-produits** : préfixer ce qui est propre à un produit (Gextimo / VASAT)
   plutôt que de supposer un produit unique. Le tracker v2 et `VitrineSetting`
   savent déjà accueillir plusieurs espaces.

## 3. Ce qui ATTEND un cadrage avant toute ligne de code

| Chantier | Ce qu'il faut avant de commencer |
|---|---|
| **Makila (IA du groupe)** | Choix d'architecture (base de connaissances, modèle, hébergement), budget d'inférence, périmètre v1. C'est un PRODUIT, pas une fonctionnalité — cadrage dédié. |
| **Espace d'admin Makila** | Dépend de l'architecture Makila. |
| **Tableaux de bord groupe (multi-produits)** | La direction dit elle-même que le choix centralisé/indépendant « pourra être arrêté ultérieurement ». |
| **Algorithme de visibilité** | Nécessite du volume de données réel. Le journal d'événements (déjà en place) l'alimentera. |
| **Analyse des réseaux sociaux** | Contraintes d'API externes (Meta, TikTok) : étude de faisabilité + conformité CGU avant tout. |
| **Marketplace verticale (chaussures, beauté…)** | Attend le **CDC promis par la direction** (« je te donne à toi le cdc »). |

## 4. Méthode actée (message du 20/07)

- La direction ne transmettra **plus de correctifs au fil de l'eau** : le sprint
  courant va au bout, elle teste intégralement, puis **un dernier lot global**.
- Prérequis : la **consolidation exhaustive** de tout ce qui a été demandé
  (→ `docs/CONSOLIDATION_GLOBALE.md`) et sa confirmation par la direction.
