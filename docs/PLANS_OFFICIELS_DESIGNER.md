# Plans d'abonnement officiels — Designer

> Référence **officielle** transmise par la direction le **16/07/2026** (maquette HTML de la page
> plans Designer). Fait foi pour les prix, quotas et fonctionnalités. Toute config `NiveauConfig`
> + quotas + affichage doit s'y conformer. (Les plans **Artisan** sont à part.)

## Vue d'ensemble

| Plan | Prix mensuel | Prix annuel | Badge |
|---|---|---|---|
| **Gratuit** (Découverte) | 0 FCFA — permanent, sans engagement | — | Découverte |
| **Atelier** (Recommandé) | 2 500 FCFA/mois | 25 000 FCFA/an (**2 083/mois**, 2 mois offerts, éco 5 000) | Recommandé |
| **Studio** (Premium) | 5 000 FCFA/mois | 50 000 FCFA/an (**4 167/mois**, 2 mois offerts, éco 10 000) | Premium |

**Essai Premium** : 14 jours offerts, accès complet → retombe en **Gratuit** si aucune souscription.
**Sans engagement, annulation à tout moment.** Aucune CB / mobile money requis pour démarrer.

---

## Gratuit (Découverte) — 0 FCFA
> **Commission de 15 % sur chaque vente réalisée via la vitrine Gextimo.** Aucune commission avec un
> plan payant actif. **Aucune sauvegarde cloud** — données sur le téléphone uniquement.

- **Vitrine & créations** : compte vitrine automatique · collections illimitées · photos/collection
  illimitées · référentiels de tailles illimités · page publique = **1 suppression / mois**.
- **Clients & mesures** : import contacts illimité · enregistrement clients illimité · **10 clients
  sur mesure** (une commande pour un 11e client distinct exige un plan payant) · encaissement illimité ·
  mesures/cliente illimitées (corrections incluses) · export mesure **client par client** (groupé = payant).
- **Commandes & caisse** : commandes illimitées (pour clients enregistrés) · caisse illimitée.
- **Facturation** : standard illimitée · facture perso avec logo (**limité à 10**).
- **Alertes** : WhatsApp. **Galerie** : consultation illimitée.

## Atelier (Recommandé) — 2 500/mois · 25 000/an
> *Tout le contenu du plan Gratuit, plus :*

- **Créations & collections** : **25 créations** vitrine · **20 patrons/fiches techniques** ·
  lookbook PDF illimité · suppression créations illimitée · **export groupé** collections (PDF) · export groupé patrons (PDF).
- **Clients & commandes** : **75 clients actifs/mois** · **75 commandes/mois** · export groupé des mesures ·
  suivi de production étape par étape.
- **Finance** : caisse complète · facturation illimitée avec logo · devis & bons de commande · rapport PDF mensuel.
- **Équipe** : **1 assistant inclus** · **1 membre inclus** · assistant supp (100/mois) · membre supp (200/mois) ·
  **sauvegarde cloud tous les 3 jours** · badge Designer Pro.
- **Vitrine** : mise en avant dans les nouveautés · stats vitrine complètes.

## Studio (Premium) — 5 000/mois · 50 000/an
> *Tout le contenu du plan Atelier, plus :*

- **Créations & collections** : **50 créations** · **50 patrons** · **50 vidéos** de présentation ·
  annonce de collection · exports groupés PDF.
- **Clients & commandes** : clients **illimités** · commandes **illimitées** · liste d'attente clients ·
  programme de fidélité avancé.
- **Finance** : rapport PDF mensuel global · rapport PDF mensuel par cliente · simulateur de revenus.
- **Équipe & multi-structure** : **3 assistants inclus** · **3 membres inclus** · assistant/membre supp (100/200) ·
  **multi-ateliers** · **multi-boutiques** · **sauvegarde cloud journalière** · priorité de support.
- **Vitrine** : mise en avant prioritaire · badge Studio Vérifié 👑.

## Options supplémentaires (Atelier & Studio uniquement)
| Option | Prix |
|---|---|
| 1 assistant supplémentaire | 100 FCFA / mois |
| 1 membre supplémentaire | 200 FCFA / mois |
| Pack 25 créations supplémentaires (vitrine) | 400 FCFA / mois |

---

## Récapitulatif quotas (pour la config `NiveauConfig` / quotas)

| Quota | Gratuit | Atelier | Studio |
|---|---|---|---|
| Clients sur mesure | 10 | 75 / mois | illimité |
| Commandes | illimité | 75 / mois | illimité |
| Créations vitrine | illimité (photos) | 25 | 50 |
| Patrons / fiches techniques | 0 | 20 | 50 |
| Vidéos | 0 | 0 | 50 |
| Assistants inclus | 0 | 1 | 3 |
| Membres inclus | 0 | 1 | 3 |
| Multi-ateliers / boutiques | non | non | oui |
| Sauvegarde cloud | non | /3 jours | journalière |
| Factures perso avec logo | 10 | illimité | illimité |
| Commission ventes vitrine | 15 % | 0 % | 0 % |

> **Note importante** : ces plans (noms **Gratuit / Atelier / Studio**, prix 2 500 / 5 000) diffèrent
> potentiellement de la config `niveaux_config` actuellement seedée (à auditer — cf. gap analysis).
> Toute modification de `NiveauConfig` touche la **facturation live** et les **abonnements existants**
> → migration à faire avec précaution, jamais en aveugle.
