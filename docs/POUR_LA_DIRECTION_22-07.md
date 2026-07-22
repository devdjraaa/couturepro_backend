# Ce qui a été corrigé — 22 juillet 2026

Bonjour Monsieur le Directeur,

Voici, en clair, l'ensemble des problèmes réglés sur l'application et la vitrine
Gextimo. **Tout ce qui suit est déjà en ligne et vérifié en conditions réelles.**

---

## 1. L'espace client était bloqué — le plus important

Les clients ne pouvaient **plus du tout créer de compte ni passer commande**
depuis la vitrine d'un créateur : dès qu'ils demandaient leur code de connexion,
un message « Erreur serveur » s'affichait. Cela touchait **tous** les clients.

**→ Réglé.** Un client peut désormais créer son compte, se connecter par e-mail
et passer commande à un créateur, du début à la fin. Vérifié de bout en bout.

---

## 2. Le bouton « Booster » ne fonctionnait pas

Quand un créateur voulait mettre son annonce en avant, le clic sur « Payer »
affichait une erreur au lieu d'ouvrir la page de paiement.

**→ Réglé.** Le Boost ouvre bien la page de paiement. La cause était un léger
décalage d'heure entre l'application et le serveur.

---

## 3. Les comptes assistants étaient déconnectés

Chaque fois qu'un assistant rouvrait l'application, il était éjecté et devait se
reconnecter.

**→ Réglé.** Les assistants restent connectés normalement.

---

## 4. Affichage et confort d'utilisation

- **Bande d'annonces** : elle était mal placée et le texte collé en bas —
  recentrée et bien positionnée.
- **Monnaie** : certains écrans affichaient « XOF » et d'autres « FCFA » pour la
  même monnaie — uniformisé, et la devise suit maintenant le pays de l'atelier.
- **Écran blanc** : cliquer sur un élément supprimé ou un lien périmé donnait un
  écran vide — il affiche maintenant un message clair avec un bouton « Retour ».
- **Outils créatifs** : onglets remis dans l'ordre logique (moodboard → croquis
  → fiche technique → patron) et libellés manquants corrigés.
- **Studio** : l'onglet « Annonce » qui faisait doublon a été retiré (il existe
  déjà dans le menu principal).

---

## 5. Application mobile

Une mise à jour avait rendu l'application inutilisable (écran « Une erreur est
survenue »). **Réparée le soir même**, et un garde-fou a été ajouté : désormais,
si une mise à jour est défectueuse, l'application **revient automatiquement** à
la version précédente au lieu de rester bloquée.

---

## 6. Site novafriq.africa

Le travail de Kaifree a été **entièrement récupéré et mis en ligne** sur le bon
dépôt. Le site est à jour et le nom de la structure s'affiche correctement
(« NovafriQ Groupe SAS »).

---

## En résumé

Tout est **en ligne, testé et stable**. Le serveur ne remonte plus aucune
erreur. Concrètement :

- les clients peuvent à nouveau créer un compte et commander,
- les créateurs peuvent booster leurs annonces,
- les assistants restent connectés,
- l'application mobile est protégée contre les mises à jour défectueuses.

En plus des corrections, des **garde-fous automatiques** ont été mis en place
pour détecter ce type de problème avant qu'il n'arrive chez les utilisateurs.
