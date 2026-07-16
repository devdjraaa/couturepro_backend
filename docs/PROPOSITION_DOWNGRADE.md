# Proposition — Gestion du downgrade d'abonnement (P53-55)

> Proposée à la direction le 16/07/2026, en complément de la spec upgrade
> (`docs/SPEC_UPGRADE_ABONNEMENT.md`). **En attente de décision : A, B, ou A + B.**

## Socle
Un downgrade ne fait **jamais** perdre de valeur payée et ne déclenche **jamais** de
remboursement cash.

## Option A — Downgrade différé à l'échéance (recommandée, standard SaaS)
1. Choix d'un plan inférieur → **rien à payer aujourd'hui**.
2. Le plan actuel reste actif **jusqu'à son échéance** (payé = dû).
3. À l'échéance, bascule automatique sur le plan choisi (nouveau tarif).
4. **Annulable à tout moment** avant l'échéance.

Message type : « Votre plan Studio Annuel reste actif jusqu'au 9 juillet 2027. À cette
date, vous passerez au plan Atelier Mensuel (2 500 F/mois). Annulable à tout moment. »

Zéro calcul, zéro litige, zéro abus, comportement connu de tous (Claude/ChatGPT/Spotify).

## Option B — Downgrade immédiat, crédit converti en TEMPS (jamais en argent)
```
crédit = jours_restants × valeur mensuelle plan actuel ÷ 31        (base 31, comme l'upgrade)
jours offerts sur nouveau plan = crédit ÷ (prix mensuel nouveau ÷ 31)
```
Ex. Studio Annuel, 358 j restants → crédit 48 122 F → 596 jours offerts d'Atelier Mensuel.
Inconvénients : durées très longues, plus dur à expliquer, à encadrer contre les allers-retours.

## Données quand les quotas rétrécissent (les deux options)
**On ne supprime JAMAIS rien.**

| Élément en trop | Comportement |
|---|---|
| Sous-ateliers hors limite | Verrouillés lecture seule, l'utilisateur choisit les actifs, re-upgrade = retour |
| Créations publiées > cap | Restent publiées ; nouvelles publications bloquées au-dessus du cap |
| Patrons > cap | Restent en vente ; création bloquée |
| Assistants/membres en trop | Accès suspendu (choix utilisateur), re-upgrade = réactivés |
| Points de fidélité | Jamais touchés |

## Recommandation
**Option A seule** au lancement ; B ajoutable plus tard sur demande utilisateurs.

## État technique actuel (en attendant la décision)
Le flux de paiement applique la logique upgrade à tout changement de plan : crédit
plafonné au prix du nouveau plan (jamais négatif). Un downgrade immédiat « perd » donc
l'excédent — c'est précisément ce que cette proposition corrige.
