# Réponse — Suivi des statistiques réseaux sociaux + badge Makila + splash

> Réponse aux trois documents de la direction du 20/07. Rédigée pour être
> transmise telle quelle.

---

## 1. MVP Facebook — FAIT, prêt à collecter dès ce soir

**Faisable, et c'est déjà construit.** Le système est en production côté
plateforme ; il ne manque QUE le jeton Meta (voir « ce qu'il nous faut »).
Dès qu'il est posé, la collecte démarre dans la seconde — la publication de
demain matin sera couverte.

Ce qui tourne :
- **API officielle Meta Graph uniquement** (aucun scraping, aucune publication,
  aucune réponse automatique — lecture seule, conformément à la demande).
- Pour chaque publication : date/heure, format (photo, vidéo, album, lien,
  texte), extrait du message, lien permanent, **portée, impressions, réactions,
  commentaires, partages, clics**.
- **Un relevé horodaté par jour** (6h30) + relevé manuel possible juste après
  une publication. L'historique montre la progression d'un post dans le temps,
  pas seulement son score final.
- **Le rapport demandé** est disponible immédiatement :
  `GET /admin/reseaux/rapport` → top 5 de la période + points communs (heures,
  formats, sujets). Le « sujet » de chaque post s'étiquette en un clic.
- Le jeton n'est jamais réaffiché une fois posé ; une erreur d'expiration est
  visible dans le statut sans rien casser d'autre.

### Ce qu'il nous faut de votre part (une seule fois, ~20 minutes)

1. Un **compte développeur Meta** avec l'e-mail de la structure
   (developers.facebook.com) — le compte doit être **admin de la Page**.
2. Créer une **app de type Business** (nom libre, ex. « Gextimo Insights »).
3. Dans l'app : *Outils* → *Explorateur de l'API Graph* →
   générer un **jeton d'utilisateur** avec les autorisations
   `pages_read_engagement` + `read_insights`, puis l'échanger contre un
   **jeton longue durée**, et récupérer le **jeton de PAGE** (menu
   « Obtenir le token de Page »).
4. Nous transmettre : **l'ID de la Page** + ce **jeton de Page**.
   → On le pose dans l'admin (`PUT /admin/reseaux/facebook`) : la première
   collecte se lance immédiatement et valide le jeton.

**Estimation** : la partie technique est finie (0 jour restant). Un jeton de
Page issu d'un jeton longue durée n'expire pas en usage normal ; s'il tombe,
le statut l'affiche et il suffit d'en reposer un.

## 2. Instagram — même socle, à brancher quand vous voulez

Le compte étant déjà Business et lié à la Page, la **même app Meta** suffit
(autorisation `instagram_basic` + `instagram_manage_insights` en plus). La
table et le service acceptent déjà la plateforme `instagram` sans migration.
**Estimation : ~½ journée** une fois le jeton fourni.

## 3. LinkedIn — à lancer côté structure dès maintenant

La partie qui dépend de nous est courte ; la validation LinkedIn (programme
« Marketing Developer Platform ») prend des semaines et peut être refusée —
c'est **la seule vraie inconnue**. À faire côté structure, dès maintenant :
compte développeur LinkedIn avec l'e-mail de la structure, une app liée à la
Page LinkedIn, et le dossier de demande (usage : « lecture des statistiques de
nos propres publications pour analyse interne »). **Estimation côté technique :
~1 journée après validation LinkedIn.**

## 4. Badge « équipe hors ligne » (Makila) — FAIT

- Badge automatique près de l'icône du chat **entre 18h et 8h, heure de
  Cotonou** (bascule serveur, aucun réglage manuel ; horaires modifiables en
  admin, clé `equipe_horaires`).
- Formulation retenue (la direction nous laissait la main) — elle rassure au
  lieu d'annoncer une absence :
  > **Notre équipe est hors ligne**
  > Makila vous répond tout de suite, 24h/24 — un membre de l'équipe prend le
  > relais dès 8h.
- **Makila reste actif 24h/24**, rien ne se coupe. Hors plage, sa consigne
  change : pour un cas qui exige un humain (litige, réclamation), il note la
  demande, annonce le relais à la reprise, et ne promet jamais de réponse
  humaine immédiate.

## 5. Module splash / messages événementiels — déjà largement livré

Le document recoupe un module existant. État précis :

| Demandé | État |
|---|---|
| Splash logo puis écran événementiel | ✅ livré (thèmes saisonniers + événements) |
| Catalogue d'événements avec dates, cible, visuel, couleur, animation | ✅ livré — 5 familles (fêtes fixes, religieuses mobiles, interne, anniversaires, campagnes datées), édition admin (`vitrine/evenements`) |
| Fin de période = disparition automatique | ✅ (fenêtres datées) |
| Messages personnels hors démarrage (anniversaire, bienvenue…) | ✅ anniversaires en notification discrète + notifications système |
| Image / GIF | ✅ (`image_url` — un GIF est une image) |
| **Petite vidéo** dans le splash | ⬜ seul manque réel — à ajouter si le besoin se confirme |
| **Écran admin graphique** de gestion | ⬜ l'API est prête ; l'interface visuelle revient à Aquilas |

---

*Tout ce qui précède est en production, sauf mention contraire. Aucun scraping,
aucune écriture vers les plateformes, uniquement nos propres pages.*
