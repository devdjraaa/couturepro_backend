# Spec — Calcul des upgrades d'abonnement (crédit prorata)

> Spécification fonctionnelle transmise par la direction le **16/07/2026**. À implémenter telle
> quelle ; cette logique vaut pour **tous les futurs plans** de l'application.

## 1. Principe général
Un utilisateur avec un abonnement **actif** qui passe à un plan **supérieur** (upgrade) ne perd
jamais la valeur financière du temps restant : le système calcule un **crédit** correspondant aux
jours restants, déduit du prix du nouveau plan. L'utilisateur **ne paie que la différence**.

## 2. Méthode de calcul — base fixe 31 jours
Le prorata utilise **toujours une base de 31 jours**, quel que soit le mois réel (28/29/30/31).

```
crédit = (jours_restants / 31) × prix_du_plan_actuel
montant_à_payer = prix_nouveau_plan − crédit
```

Exemple : plan actuel 12 000 F, nouveau 30 000 F, commencé le 10/07, upgrade le 20/07
→ 10 j consommés, 21 j restants → crédit = (21/31) × 12 000 = 8 129 F → à payer : **21 871 F**.

> Note d'implémentation : pour un plan **annuel**, la valeur mensuelle de référence est
> `prix_mensuel_equivalent_xof` (le crédit = jours_restants × prix_mensuel_equivalent / 31),
> ce qui respecte la base 31 uniformément. Montant à payer plancher : 0 (jamais négatif).

## 3. Nouvelle période
Après paiement de l'upgrade :
- le nouveau plan **démarre immédiatement** ;
- l'échéance est recalculée **de date à date** depuis la date d'upgrade (20/07 → 20/08 ;
  15/08 → 15/09 ; etc.), **jamais** en nombre fixe de jours calendaires.

## 4. Points de fidélité
Les points attribués à l'achat initial sont **définitivement acquis** : jamais recalculés,
retirés, remboursés ni déduits. L'upgrade ne touche que la partie financière.

## 5. Récapitulatif à afficher AVANT paiement
Plan actuel · Plan choisi · Prix du nouveau plan · **Crédit prorata** (en négatif) ·
**Montant à payer aujourd'hui** · Nouvelle date d'expiration · mention « actif immédiatement
après le paiement ».

```
Plan actuel : Basic
Nouveau plan : Premium
Prix du Premium :        30 000 FCFA
Crédit restant :        −  8 129 FCFA
À payer aujourd'hui :    21 871 FCFA
Nouvelle échéance :      20 août 2026
```

## 6. Cas particuliers à gérer
- Upgrade le **dernier jour** (crédit très faible) ;
- Upgrade le **jour même** de la souscription (crédit presque total) ;
- **Plusieurs upgrades** dans le même mois ;
- Aucun calcul ne dépend du nombre réel de jours du mois (base 31 toujours).

## 7. Objectif
Système d'upgrade professionnel, équitable, transparent (à la manière des grandes plateformes
SaaS) : aucune valeur perdue, paiement de la seule différence, bénéfices immédiats.
