# Méthode de test QA — « à travers l'application »

> Principe validé avec l'équipe : on teste **chaque fonctionnalité en passant par l'application réelle
> côté utilisateur**, pas en tapant les endpoints d'API bruts. C'est plus long, mais c'est **efficace** :
> on valide ce que vit *réellement* l'utilisateur (affichage + enregistrement + synchro), pas juste
> qu'une route répond `200`.

## Pourquoi

Un endpoint qui répond correctement ne garantit **pas** que l'écran marche. Beaucoup de bugs
n'apparaissent qu'en passant par les vrais écrans. Exemple concret (mesures) : l'API répondait bien,
mais **côté app** les mesures ne s'affichaient jamais, l'enregistrement écrivait des champs vides, et
créait des doublons. Invisible si on teste seulement l'API.

## Le dispositif

- Application pilotée sur un **téléphone physique réel** (pas d'émulateur), via le protocole de debug
  du navigateur embarqué (CDP).
- L'agent conduit l'app comme un utilisateur : navigation, saisie des formulaires, envoi — puis
  **rapporte chaque résultat**.

## Le protocole, pour chaque fonctionnalité

1. **Agir dans l'UI réelle** : ouvrir l'écran, remplir le formulaire comme un vrai utilisateur, valider.
2. **Vérifier la vérité terrain à 3 niveaux** (ne jamais se fier au seul affichage) :
   - **(a) Écran** : ce que l'utilisateur voit bien s'afficher / se mettre à jour.
   - **(b) Base locale du téléphone** : la donnée est bien écrite en local, avec son état de synchro
     (`en attente` vs `synchronisé`).
   - **(c) Serveur** : la donnée est bien arrivée en base côté serveur (lecture seule).
3. **Tester le cycle hors-ligne ↔ en ligne** :
   - **En ligne** : une modification doit remonter **toute seule** au serveur en quelques secondes.
   - **Hors-ligne (mode avion)** : la modification est gardée **en local** (« en attente »), le serveur
     ne bouge pas.
   - **Retour du réseau** : tout ce qui a été fait hors-ligne **remonte automatiquement** au serveur.
4. **Traçabilité** : on tient à jour la liste des flux déjà testés pour ne **jamais repasser deux fois**
   sur la même route.
5. **Propreté** : on **nettoie** les données de test créées et on **restaure** les valeurs modifiées à
   leur état d'origine.

## Règles de sécurité (rappel)

- Aucune écriture directe en production hors de l'application / de la pipeline.
- Jamais d'email personnel ni de compte réel pour les tests d'envoi — toujours une adresse de test.
- Les commits sont faits par l'agent ; **les push restent faits par le responsable**.

## Périmètre

On applique cette méthode à **toutes les fonctionnalités de l'espace utilisateur**, une par une. C'est
un travail long mais qui donne une **couverture réelle** de l'expérience utilisateur.
