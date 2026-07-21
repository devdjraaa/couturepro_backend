# Récupérer l'ID et le jeton de la Page Facebook

Il faut **deux informations** : l'identifiant de la Page, et un jeton d'accès de Page.
Comptez 15 minutes. Vous devez être **administrateur de la Page**.

> ⚠️ Un jeton, c'est un mot de passe. Ne le collez jamais dans une conversation de
> groupe, un e-mail ou une capture d'écran.

---

## Avant de commencer : avec quel compte ?

Le jeton reste **lié au compte qui le crée**. Si vous le générez avec un compte
personnel, la collecte s'arrête le jour où ce compte change de mot de passe ou perd
l'accès à la Page.

Si vous avez un compte au nom de la structure, utilisez celui-là.

---

## Étape 1 — Créer l'application (une seule fois)

1. Ouvrez **developers.facebook.com** et connectez-vous.
2. En haut à droite : **Mes applications** → **Créer une application**.
3. Un questionnaire vous demande ce que vous voulez faire. Choisissez l'option qui
   parle de **données professionnelles / entreprise** (le libellé exact change
   régulièrement, mais c'est toujours l'option « entreprise »).
4. Donnez un nom, par exemple **Gextimo Insights**. Le nom n'a aucune importance.
5. Validez.

Cette application ne sera **jamais publiée** et ne sera visible de personne. Elle sert
uniquement à fabriquer le jeton.

---

## Étape 2 — Ouvrir l'explorateur

Dans le menu du haut : **Outils** → **Explorateur de l'API Graph**.

Un panneau apparaît à droite avec :

- un champ **Application Meta** → sélectionnez celle que vous venez de créer ;
- un champ **Jeton d'accès utilisateur ou de Page** ;
- une zone **Autorisations**.

---

## Étape 3 — Demander les bonnes autorisations

Dans la zone **Autorisations**, ajoutez ces trois lignes (tapez-les, elles
s'autocomplètent) :

```
pages_show_list
pages_read_engagement
read_insights
```

Puis cliquez sur **Générer un jeton d'accès**. Facebook ouvre une fenêtre de
confirmation : acceptez, et **cochez bien votre Page** dans la liste proposée.

---

## Étape 4 — ⚠️ L'ordre qui compte

C'est ici que la plupart des gens se trompent, et c'est pour ça que leur jeton
meurt au bout de deux mois.

**Il faut allonger le jeton AVANT d'en tirer celui de la Page, pas l'inverse.**

1. Copiez le jeton affiché (c'est encore un jeton **utilisateur**).
2. Allez dans **Outils** → **Débogueur de jeton d'accès**.
3. Collez-le, cliquez sur **Déboguer**, puis sur le bouton
   **Étendre le jeton d'accès** en bas.
4. Copiez le **nouveau** jeton obtenu.
5. Revenez dans l'**Explorateur de l'API Graph**, collez ce nouveau jeton dans le
   champ du jeton.
6. Ouvrez alors le menu déroulant du champ et choisissez
   **Obtenir le jeton de Page**, puis votre Page.

Le jeton qui s'affiche maintenant est le bon : **il n'expire pas**.

---

## Étape 5 — Récupérer l'ID de la Page

Toujours dans l'explorateur, avec le jeton de Page sélectionné :

1. Dans la barre de requête, tapez : `me?fields=id,name`
2. Cliquez sur **Envoyer**.

La réponse ressemble à ceci :

```json
{
  "id": "123456789012345",
  "name": "Gextimo"
}
```

- `id` → c'est l'**ID de la Page** (une longue suite de chiffres)
- `name` → vérifiez que c'est bien **notre** Page et pas une autre

---

## Étape 6 — Vérifier que le jeton est bon

Toujours dans l'explorateur, tapez :

```
me/insights?metric=page_impressions&period=day
```

Si des chiffres s'affichent, tout est en ordre. Si vous voyez une erreur de
permission, reprenez l'**étape 3** : une autorisation manque.

---

## Étape 7 — Enregistrer

Dans l'administration Gextimo : **Réseaux sociaux** → collez l'**ID de la Page** et
le **jeton**, puis enregistrez.

La collecte démarre immédiatement, et l'écran vous dit tout de suite si le jeton est
accepté — vous n'avez pas à attendre le lendemain pour le savoir.

---

## Ce que ce jeton permet, et ce qu'il ne permet pas

**Il permet** de lire, chaque jour et pour chaque publication : la portée, les
impressions, les réactions, les commentaires, les partages et les clics.

**Il ne permet pas** de publier, de répondre, de supprimer, ni de lire des messages
privés. Les autorisations demandées sont en lecture seule : même utilisé
volontairement, ce jeton ne peut rien écrire sur la Page.

---

## Si ça coince

| Ce que vous voyez | Ce qu'il faut faire |
|---|---|
| « (#200) Permissions error » | Une autorisation manque — refaites l'étape 3 |
| Le jeton expire au bout de 2 mois | L'ordre de l'étape 4 n'a pas été respecté |
| « Cannot parse access token » | Le jeton a été copié incomplet — recopiez-le en entier |
| Aucune Page proposée | Le compte utilisé n'est pas administrateur de la Page |
