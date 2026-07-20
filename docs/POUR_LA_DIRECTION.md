# Ce qui vous attend dans l'administration

Tout ce qui suit est **paramétrable par vous, sans développeur et sans redéploiement**.
Chaque réglage prend effet immédiatement.

Adresse : **https://gextimo.novafriq.africa/admin**

---

## En un coup d'œil

| # | À faire | Où | Urgence |
|---|---|---|---|
| 1 | Recalibrer le programme de fidélité | Réglages vitrine | **Bloquant** |
| 2 | Renseigner l'identité légale (RCCM, IFU, APDP) | Réglages vitrine | **Bloquant** |
| 3 | Régler le compte à rebours de lancement | Réglages vitrine | Avant le 22 août |
| 4 | Publier le premier message « Gextimo Infos » | Gextimo Infos | Quand vous voulez |
| 5 | Vérifier les coordonnées officielles | Réglages vitrine | Rapide |
| 6 | Décider des moyens de paiement | Réglages vitrine | Rapide |
| 7 | Ajuster la modération des avis | Réglages vitrine | Rapide |
| 8 | Définir le mot de passe VASAT | Réglages vitrine | Quand le produit sort |
| 9 | Entretenir « Quoi de neuf » | Réglages vitrine | En continu |

---

## 1. Programme de fidélité — le seul point vraiment bloquant

**Écran** : Réglages vitrine → *Programme de fidélité — paliers*

Le programme est **inatteignable en l'état**. Les ateliers génèrent très peu de points
comparé au premier palier : personne ne l'atteint, donc **personne ne convertit**, donc le
programme n'existe que sur le papier.

Côté technique tout est réglé (les règles annoncées qui n'existaient pas ont été retirées,
le crédit fonctionne désormais aussi hors synchronisation). **Il ne reste qu'un arbitrage
commercial : quel effort voulez-vous récompenser, et à partir de quand ?**

Deux façons d'y arriver, au choix :

- **baisser les paliers** pour qu'ils correspondent à ce que les ateliers génèrent
  réellement aujourd'hui ;
- **augmenter les gains** par action (une commande créée, un client ajouté) pour que les
  paliers actuels deviennent atteignables.

> Un repère utile : un palier doit pouvoir être atteint par un atelier actif **en quelques
> semaines**, pas en plusieurs années. Un seuil hors de portée est pire que pas de
> programme du tout — il donne une promesse que rien ne tient.

Détail complet des règles en vigueur : `docs/SYSTEME_FIDELITE.md`.

---

## 2. Identité légale

**Écran** : Réglages vitrine → *Identité légale*

Champs : RCCM, IFU, numéro de délibération APDP, date d'entrée en vigueur, date de mise à
jour.

**Tant qu'un champ est vide, toute phrase qui le mentionne disparaît des pages légales.**
C'est volontaire : mieux vaut une mention absente qu'une mention affichant un blanc ou un
« à compléter », qui laisserait croire que la société n'est pas immatriculée.

Ces valeurs alimentent automatiquement les 11 pages juridiques (mentions légales, CGU, CGV,
confidentialité, cookies…).

---

## 3. Compte à rebours de lancement

**Écran** : Réglages vitrine → *Compte à rebours de lancement*

**Il est éteint par défaut** — rien ne s'affiche tant que vous ne l'activez pas.

Deux affichages, réglés ensemble :

- une **bande discrète** en haut des pages, à partir du nombre de jours que vous fixez
  (30 par défaut) ;
- un **chrono plein écran** le jour J, si vous le souhaitez.

Les deux sont refermables par le visiteur, et **se masquent d'eux-mêmes une fois la date
passée** : vous n'avez pas à repasser ici le lendemain pour éteindre l'annonce.

Vous pouvez écrire `{{jours}}`, `{{heures}}`, `{{minutes}}` ou `{{secondes}}` dans vos
textes : ils seront remplacés par le décompte réel.

> Ce compte à rebours n'est pas réservé au 22 août. Changez la date et les textes, et il
> resservira pour n'importe quelle annonce.

---

## 4. Gextimo Infos — parler aux professionnels

**Écran** : Gextimo Infos (menu de gauche)

C'est votre canal éditorial vers les ateliers : annonces, nouveautés, astuces, promotions,
alertes, événements, formations, messages de sécurité. **À ne pas confondre avec les
notifications**, qui signalent à un atelier un événement sur SON activité.

Pour chaque message vous choisissez :

- une **catégorie** (elle donne la couleur et l'icône) ;
- des **destinataires** : tout le monde, ou seulement un type de compte (artisan /
  designer), une formule d'abonnement, une ville, ou des ateliers précis ;
- une **date de publication** et une **date de retrait** (laissez vide pour « tout de
  suite » et « jamais ») ;
- un **épinglage** si le message doit rester en tête.

**Avant d'envoyer, l'écran vous indique combien d'ateliers recevront le message.** Si ce
nombre est à zéro, c'est qu'une valeur de ciblage est mal saisie — une ville mal
orthographiée, par exemple. Vérifiez avant de valider.

---

## 5. Coordonnées officielles

**Écran** : Réglages vitrine → *Coordonnées officielles*

Marque, site web, téléphone. Ces valeurs apparaissent **dans les documents PDF et les
partages WhatsApp** envoyés par les ateliers. Un changement de numéro se fait ici.

---

## 6. Moyens de paiement

**Écran** : Réglages vitrine → *Moyens de paiement*

Ce que les professionnels peuvent proposer **sur leurs devis et factures**.

> ⚠️ Cela ne concerne **ni la caisse ni les commandes**, qui enregistrent comment le client
> a réellement payé sur place — espèces comprises. Les deux listes sont volontairement
> séparées : les confondre supprimerait l'encaissement en espèces, qui est le cas
> majoritaire des ateliers.

---

## 7. Modération des avis

**Écran** : Réglages vitrine → *Modération des avis*

- nombre maximum d'avis par jour et par personne ;
- nombre de signalements à partir duquel un avis est retiré automatiquement ;
- motifs considérés comme graves ;
- mots bannis (un par ligne).

---

## 8. Accès VASAT

**Écran** : Réglages vitrine → *Accès VASAT*

Le produit est masqué et protégé par un mot de passe. **Le mot de passe n'est jamais
réaffiché** : laissez le champ vide pour conserver l'actuel, remplissez-le pour le changer.
Tant qu'aucun mot de passe n'est défini, l'accès reste fermé.

---

## 9. « Quoi de neuf » — à entretenir dans la durée

**Écran** : Réglages vitrine → *Journal des mises à jour*

Les mises à jour partent **automatiquement**, plusieurs fois par jour parfois. Sans cette
page, les professionnels voient l'application changer sans savoir ce qui a bougé.

Les quatre premières entrées sont déjà écrites, à titre d'exemple du ton attendu.

> **Écrivez pour un utilisateur d'atelier, pas pour un développeur.** « Vos réalisations
> restent consultables sans connexion » plutôt que « ajout d'un cache WatermelonDB ».

---

## Autres écrans utiles

| Écran | À quoi il sert |
|---|---|
| **Analytique** | Ce que les visiteurs cherchent, et surtout **ce qu'ils cherchent sans trouver** — la liste la plus utile pour décider quoi ajouter au catalogue. |
| **Réalisations** | File de modération des photos, avec le temps restant sur les 24 h. Vous pouvez redresser, recadrer ou éclaircir une photo avant publication plutôt que de la refuser ; l'original est toujours conservé. |
| **Signalements** | Contenus signalés par les visiteurs. |
| **Pages légales** | Le texte des pages juridiques (l'identité légale, elle, se règle au point 2). |
| **Plans / Offres** | Formules d'abonnement et fonctionnalités incluses. |
| **Bannière** | Message d'accueil de la vitrine. |
| **Codes promo** | Codes et ambassadeurs. |

---

## Ce qui n'est pas encore décidé

Trois sujets attendent un arbitrage de votre part avant tout développement :

1. **Messagerie client ↔ créateur** — module entier à cadrer : qui écrit à qui, que se
   passe-t-il en cas d'abus, la conversation est-elle liée à une commande ?
2. **Code promo côté client** — qui finance la remise (Gextimo ou le créateur), sur quoi
   elle porte, cumulable ou non.
3. **Paliers de fidélité** — le point 1 ci-dessus.

Les deux premiers ne sont pas commencés : dites-nous ce que vous voulez et nous chiffrerons.
