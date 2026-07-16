# Charte interne d'accès aux données — NOVAFRIQ / Gextimo

**Version 1.0 — 16/07/2026 — Document interne (brief technique, point 7).**
Objet : définir **qui accède à quoi, et pourquoi**, pour les données des utilisateurs de
l'écosystème (Gextimo, vitrine, espace client). S'applique à toute personne disposant d'un accès
technique ou administrateur.

## 1. Classement des données

| Niveau | Données | Exemples |
|---|---|---|
| **N1 — Public** | Contenu publié volontairement | profils créateurs, créations publiées, avis validés |
| **N2 — Interne** | Données d'exploitation agrégées | statistiques anonymisées, tendances, compteurs |
| **N3 — Personnel** | Données identifiantes | nom, e-mail, téléphone, ville, date de naissance |
| **N4 — Sensible** | Comportement individuel + accès techniques | événements de navigation par personne, scores/segments par client, secrets serveur, sauvegardes |

## 2. Qui accède à quoi

| Rôle | N1 | N2 | N3 | N4 | Pourquoi |
|---|---|---|---|---|---|
| **Direction** (Markus) | ✅ | ✅ | ✅ | ✅ | pilotage, responsabilité légale (APDP) |
| **Équipe technique restreinte** (devs) | ✅ | ✅ | ✅ | ✅ | développement, maintenance, incidents |
| **Community manager** | ✅ | ✅ | ❌ | ❌ | contenus/réseaux — jamais de données individuelles |
| **Support** | ✅ | ✅ | ✅ (client concerné uniquement) | ❌ | traiter le ticket de la personne, rien de plus |
| **Partenaires / externes** | ✅ | sur accord direction | ❌ | ❌ | aucun accès direct aux systèmes |

## 3. Règles non négociables

1. **Minimum nécessaire** : on n'accède qu'aux données requises par la tâche en cours.
2. **Pas d'export sauvage** : aucune extraction de données personnelles (CSV, copie d'écran de
   listes clients…) hors des outils prévus, sans accord direction.
3. **Secrets** : mots de passe, clés API, `.env`, clés SSH → jamais dans un chat, un e-mail, un
   dépôt Git ou une capture. Stockage : gestionnaire de mots de passe / `gextimo-secrets` (VPS).
4. **Comptes nominatifs** : pas de compte admin partagé ; chaque accès est individuel (audit log
   admin activé). Révocation immédiate au départ d'un membre.
5. **Consentement** : le tracking comportemental individuel n'existe que pour les utilisateurs
   ayant consenti (bandeau APDP) ; les analyses se font en priorité sur les **agrégats** (N2).
6. **Incident** : toute fuite ou suspicion → prévenir la direction immédiatement, couper l'accès
   concerné, tracer (qui/quoi/quand). La direction évalue l'obligation de notification APDP.

## 4. Mise à jour

La direction tient ce document à jour à chaque changement d'équipe ou de périmètre d'accès.
