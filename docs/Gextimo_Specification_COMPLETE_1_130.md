# Spécification Gextimo — Points 1 à 31 (base du document, transmis pour la première fois)

> Ce contenu constitue le véritable début de la spécification — les points 32 à 128 déjà rédigés le référençaient implicitement (« Continuité directe du document précédent, arrêté au point 31 »), mais son contenu n'avait jamais été transmis jusqu'à présent.

---

## Sprint 01 — Améliorations Studio > Vidéo

### Point 1 — Correction du libellé du bouton d'ajout vidéo
Remplacer le bouton actuel « + Ajouter la vidéo » par « Ajouter la vidéo » (suppression du symbole « + », qui n'apporte aucune information supplémentaire).

### Point 2 — Ajouter une méthode d'importation directe de vidéo
En plus de l'ajout via lien externe (ex. YouTube), permettre l'importation directe d'une vidéo depuis l'appareil de l'utilisateur (téléphone, tablette, ordinateur).

### Point 3 — Nouvelle organisation de l'interface vidéo
Afficher deux actions séparées et visibles : « Ajouter la vidéo » (ajout via URL YouTube ou plateforme compatible) et « Importer la vidéo » (sélection d'un fichier vidéo local). L'utilisateur choisit librement entre une vidéo déjà en ligne ou un fichier présent sur son appareil.

---

## Sprint 02 — Correction Module Outils créatifs

### Point 4 — Correction du bouton Ajouter dans Outils créatifs
Emplacement : Plus (hamburger) → Outils créatifs → Onglet Tout. Remplacer « + Ajouter » par « Ajouter » (suppression du symbole « + »).

### Point 5 — Correction du chargement automatique des onglets
Actuellement, changer d'onglet (ex. Croquis → Fiche technique) modifie l'affichage mais pas le contenu — l'utilisateur doit intervenir manuellement dans un menu déroulant. Chaque onglet (Tout, Moodboard, Croquis, Fiche technique, Patron) doit charger automatiquement son contenu correspondant dès son ouverture, sans manipulation supplémentaire.

### Point 6 — Correction du libellé Niveau dans Patron
Emplacement : Outils créatifs → Patron, après le champ Taille. Le libellé technique actuellement affiché par erreur (« Outils créatifs / Méta / Niveau ») doit être remplacé par « Choisir le niveau du patron » (ou version courte « Choisir le niveau »), pour que l'utilisateur comprenne qu'il doit sélectionner un niveau (Débutant / Intermédiaire / Avancé).

### Point 7 — Réorganisation des onglets Outils créatifs
Nouvel ordre obligatoire des onglets : Moodboard → Croquis → Fiche technique → Patron, pour suivre le processus naturel de création d'un designer (inspiration → dessin → documentation technique → patron).

---

## Sprint 03 — Espace Client : Authentification et inscription

> Note de cohérence à vérifier : cette section nomme systématiquement la plateforme « Gestimo » (sans X) alors que l'ensemble du reste de la spécification (Points 32 et suivants) utilise « Gextimo ». Voir Point 129 pour le traitement de cette incohérence.

### Point 8 — Ajouter plusieurs méthodes de connexion
Emplacement : Header → Espace Client. Permettre la connexion via Google, Facebook, Apple, Microsoft, ou adresse e-mail. *(Voir Point 129bis sur la cohérence à établir avec l'architecture technique du Point 34, qui ne prévoit actuellement que Google.)*

### Point 9 — Ajouter les mêmes options lors de l'inscription
Le système d'inscription doit proposer les mêmes méthodes qu'au Point 8 : Google, Facebook, Apple, Microsoft, e-mail.

### Point 10 — Ajouter l'acceptation obligatoire des conditions
Case obligatoire avant création du compte, texte proposé : *« J'ai lu et j'accepte les Conditions d'utilisation ainsi que la Politique de confidentialité de [marque]. »* Les liens « Conditions d'utilisation » et « Politique de confidentialité » sont cliquables. Le compte ne peut pas être créé sans validation de cette case.

### Point 11 — Ajouter l'inscription Newsletter facultative
Case indépendante, texte proposé : *« Je souhaite recevoir les actualités, nouveautés, conseils, offres et promotions de [marque] par e-mail. »* L'utilisateur peut accepter, refuser, ou modifier son choix plus tard.

### Point 12 — Ajouter un message de confiance
Afficher sous le formulaire d'inscription : *« Vos données personnelles sont traitées de manière strictement confidentielle. Elles sont utilisées uniquement pour le fonctionnement de votre compte et ne sont jamais vendues à des tiers. »*

### Point 13 — Création automatique du compte client
Après validation de l'adresse e-mail, création automatique de l'espace client et accès immédiat au compte.

### Point 14 — Ajouter les informations facultatives du profil
Champs disponibles à compléter plus tard : nom, prénom, photo, téléphone, adresse, ville, pays.

---

## Sprint 04 — Correction Bug Connexion Espace Client

### Point 15 — Correction erreur Server Error
Lors d'une tentative de connexion (par e-mail ou via Google), le système affiche actuellement « Server Error ». Contrôler la configuration d'authentification, la connexion frontend/backend, les fournisseurs Google/e-mail, et la gestion des erreurs. En cas de problème persistant, afficher un message clair : *« Le service est momentanément indisponible. Veuillez réessayer plus tard. »*

---

## Sprint 05 — Sécurisation et récupération du compte

### Point 16 — Ajouter un onboarding première connexion
Lors de la première connexion uniquement, afficher une fenêtre de bienvenue invitant à configurer un moyen de récupération (adresse e-mail de récupération ou numéro de téléphone), avec boutons « Configurer maintenant » / « Plus tard ». Texte proposé : *« Bienvenue sur votre espace client ! Pour renforcer la sécurité de votre compte, nous vous recommandons de configurer un moyen de récupération. Si vous perdez l'accès à votre adresse e-mail principale, vous pourrez récupérer votre compte facilement. »*

### Point 17 — Ajouter les moyens de récupération
Informations possibles : nom, prénom, e-mail secondaire, numéro de téléphone. Les moyens ajoutés doivent être vérifiés par code avant d'être activés.

### Point 18 — Ajouter la récupération d'accès
Sous l'écran de connexion, ajouter les options « Renvoyer le code » et « Je n'ai plus accès à cette adresse e-mail ».

### Point 19 — Récupération par e-mail secondaire
Si un e-mail de récupération existe, afficher : *« Un code de récupération a été envoyé à votre adresse e-mail de récupération. »* Masquage obligatoire de l'adresse affichée (ex. `p******n@gmail.com` — première lettre, dernière lettre avant @, domaine complet visible).

### Point 20 — Récupération par téléphone
Si un téléphone existe, afficher : *« Un code de récupération a été envoyé par SMS au numéro : »* avec uniquement les deux derniers chiffres visibles (ex. `+229 ******45`).

### Point 21 — Aucun moyen de récupération disponible
Si aucun moyen n'est configuré, afficher : *« Aucun moyen de récupération n'est configuré. Veuillez contacter le support afin de vérifier votre identité. »*

### Point 22 — Sécurité des codes
Tous les codes de récupération doivent être uniques, temporaires, invalidés après utilisation. Durée de validité recommandée : 10 à 15 minutes.

---

## Section — Modules de l'espace client (suite à partir du Point 23)

> Note pour le développeur (reprise du document source) : chaque point est une tâche indépendante. Certains modules décrits n'existent pas encore sur la plateforme et doivent être créés de zéro. Pour tout module sans contenu réel à afficher, prévoir un état vide (« empty state ») soigné plutôt qu'une page blanche ou une erreur.

### Point 23 — Système d'inscription et d'activation de compte
Le visiteur saisit son adresse e-mail ou se connecte via Google/Facebook. Un code de confirmation est envoyé automatiquement (par e-mail si inscription par e-mail ; par SMS ou WhatsApp si connexion via Google/Facebook). Dès validation du code, le compte est créé automatiquement et l'espace client s'ouvre, **sans étape de création de mot de passe**. L'utilisateur peut ensuite compléter les champs restants (voir Point 28). Ce même mécanisme de vérification par code doit être réutilisé pour toute modification ultérieure d'e-mail ou de téléphone dans le profil (voir Point 28).

### Point 24 — Module Notification
Créer un module Notification affichant des informations à sens unique (l'utilisateur ne peut pas y répondre) : mises à jour de la plateforme, changement de statut de commande, informations générales du service. Doit être visuellement distinct du module Message (Point 25) — icône et emplacement différents.

### Point 25 — Module Message (conversation client ↔ créateur)
Créer un module permettant une conversation directe entre le client et le créateur. La plateforme achemine les messages sans les consulter, les analyser ni les exploiter à d'autres fins. L'historique de conversation est conservé et reste consultable uniquement par le client et le créateur concernés. Dès qu'un paiement est confirmé, le créateur reçoit automatiquement une notification avec tous les détails de la commande. Le créateur peut confirmer la réception, ce qui déclenche l'envoi automatique d'une confirmation au client.

### Point 26 — Module Avis
Créer un module d'avis obligatoirement rattaché à une commande déjà passée (impossible de laisser un avis libre). Prévoir une note (étoiles) et un champ commentaire, avec possibilité de joindre une photo (facultatif). **Précision importante :** ces avis alimentent également un futur système de certification des comptes créateurs — les données (note, contenu, photo, date, commande associée) doivent être structurées pour être exploitables par ce futur module de certification, même si ce dernier n'est pas à développer maintenant.

### Point 27 — Module Plainte
Créer un module de plainte obligatoirement rattaché à une commande précise. Quand une plainte est déposée, le message est envoyé simultanément au créateur concerné et à l'équipe de la plateforme. Objectif : permettre un futur suivi qualité des créateurs recevant des plaintes récurrentes (structure de données à prévoir, fonctionnalité de suivi non développée pour l'instant).

### Point 28 — Module Profil / Paramètres
Créer un module de gestion des informations personnelles (nom, téléphone, e-mail modifiables). **Règle de sécurité impérative :** toute modification de l'e-mail (ou du téléphone si vérification SMS) doit déclencher l'envoi d'un nouveau code de confirmation, comme à l'inscription (Point 23) — le changement n'est appliqué qu'après validation du code.

### Point 29 — Module Recherche
Créer un module de recherche accessible directement depuis l'espace client, avec un champ unique cherchant simultanément dans trois types de contenus : noms de créateurs, noms de modèles/produits, catégories/tags. Les résultats doivent regrouper ces trois types (sections distinctes dans l'affichage).

### Point 30 — Module Code Promo (à créer entièrement, module inexistant à ce jour)
**Partie A — Champ de saisie pendant la commande :** au moment du paiement, ajouter un champ « Code promo » vérifiant validité/conditions/expiration et appliquant la réduction si valide. Ajouter une icône d'information (i) menant directement vers la page des codes promo disponibles (Partie B).
**Partie B — Page dédiée aux codes promo :** lister tous les codes disponibles avec leurs conditions (réduction, expiration, conditions éventuelles), accessible depuis le footer et depuis l'icône « i » de la Partie A. Prévoir un état vide soigné si aucun code n'est actif (ex. *« Aucun code promo disponible pour le moment, revenez bientôt ! »*), jamais une page blanche ou une erreur.

### Point 31 — Module Commandes (suivi général)
Créer le module central de suivi des commandes du client : liste des commandes en cours et passées, statut par commande (reçue, en confection, expédiée, livrée), détail des articles/prix/délais, possibilité de repasser une commande identique ou similaire. Ce module est directement lié aux modules Message (Point 25), Avis (Point 26) et Plainte (Point 27), tous rattachés à une commande précise.

---

*Fin des points 1 à 31. La suite (Points 32 et au-delà) est déjà rédigée dans les documents précédents.*
# Spécification Gextimo — Suite des points (32 à 114)

> Continuité directe du document précédent (arrêté au point 31).

---

## Section A — Architecture technique (document v3 ingénierie)

### Point 32 — Corriger les conflits de nommage de tables
Les tables `clients` et `commandes` existent déjà en base (pour l'atelier). Pour l'espace client vitrine, créer des tables séparées et distinctes : `gxt_clients` et `gxt_commandes`. "Designer" doit correspondre à `ateliers` de type `designer`. "Article" doit correspondre à `creations_designer` ou `patrons`. Toutes les clés primaires en UUID, migrations compatibles PostgreSQL.

### Point 33 — Réduire le schéma de 14 à 6 tables
Structure retenue : une seule table source (`gxt_evenements`) + deux tables de synthèse recalculées la nuit (`gxt_client_metrics`, `gxt_designer_metrics`).

### Point 34 — Phase 1 : Fondations de l'espace client
Migrations `gxt_clients`, `gxt_consents` (UUID, PostgreSQL). Authentification sans mot de passe : (A) Google via Socialite, (B) email + OTP à 6 chiffres valable 10 minutes via Brevo. Capture UTM/referrer/type d'appareil à la création du compte. Endpoints : `POST vitrine/client/otp/demander`, `POST vitrine/client/otp/verifier`, `GET vitrine/client/social/google`, `GET vitrine/client/me`, `POST vitrine/client/consentement`, `POST vitrine/client/logout`.
*(Voir Point 130 : écart avec les méthodes d'authentification demandées aux Points 8-9, 23.)*

### Point 35 — Phase 2 : Commandes vitrine, notifications, avis/réclamations
Table `gxt_commandes` (référence `GXT-XXXX`, statuts reçue/acceptée/en confection/prête/livrée). Emails automatiques via Brevo à chaque changement de statut (queue). Table `gxt_avis` (visible si statut "livrée") + table `gxt_reclamations` (notifie designer + admin). Réutiliser `vitrine/suivi/{reference}`.

### Point 36 — Phase 3 : Tracking comportemental
Table `gxt_evenements` (ingestion groupée via `POST vitrine/evenements`, événements métier uniquement). Micro-événements → GA4 uniquement, pas en base. GA4/Meta Pixel/Microsoft Clarity ne se chargent qu'après consentement explicite (`analytics_consent`/`marketing_consent`). Table `gxt_recherches_sans_resultat` avec alerte designers.

### Point 37 — Phase 4 : Scoring et segmentation
Commande planifiée nocturne (`gxt:recalculer-metrics`) remplissant `gxt_client_metrics` et `gxt_designer_metrics`. Barème : vue produit +1, consultation >30s +3, wishlist +5, panier +10, achat +20.

### Point 38 — Phase 5 : Dashboard admin et automatisations
Page `/admin/analytique` (vues Globale/Clients/Designers/Tendances, lecture des tables de synthèse uniquement). Automatisations Brevo : panier abandonné 24h, client inactif 30j, commande livrée sans avis 5j, client à risque, nouvel article catégorie favorite, message VIP.

---

## Section B — Brief technique complémentaire

### Point 39 — Mémoire des échanges avec le chatbot
Table `conversations` + table `feedback`. Tableau de bord interne des questions mal traitées. Fiche de suivi condensée par utilisateur. Obligation de conformité : informer dans CGU/politique de confidentialité.

### Point 40 — Analytics comportemental déclaré
Bandeau de consentement granulaire (nécessaire/analytics/marketing), conforme loi béninoise. Collecte après consentement : temps passé, scroll, clics/likes/favoris, créateurs/produits consultés, mots-clés recherchés. Données anonymisées/pseudonymisées.

### Point 41 — Profil utilisateur basique
Champs : nom, prénom, préférences déclarées. Mot de passe hashé (bcrypt/argon2). Optionnels : date de naissance, ville, centres d'intérêt.

### Point 42 — Algorithme de recommandation / "boost"
Scoring basé sur historique/interactions/profil. Système de mise en avant paramétrable, transparent si contenu sponsorisé.

### Point 43 — Veille automatique (opportunités, concours)
Scraping/monitoring sources publiques pour concours et appels à participation. Alertes automatiques. Alimente le SEO via contenu frais.

### Point 44 — Identité visuelle et culturelle locale
Splash screen thématique selon périodes. Messages d'anniversaire discrets. Vérification obligatoire : visuels réellement béninois.

### Point 45 — Accès et confidentialité de l'équipe
Restreindre l'accès aux données sensibles à l'équipe technique restreinte. Charte interne d'accès.

---

## Section C — Corrections et fonctionnalités diverses (Lot n°2)

### Point 46 — Structuration complète du consentement à l'inscription
Case obligatoire (politique de confidentialité + CGU, liens cliquables, non cochée par défaut, bouton désactivé tant que non cochée, champs `privacy_policy_accepted`/`_at`/`_version`). Case facultative newsletter séparée (`newsletter_opt_in`/`_at`), non cochée par défaut, sans effet sur l'inscription. Cases visuellement séparées. Lien de désinscription dans chaque email. Interdiction : newsletter précochée, blocage du service si non cochée.

### Point 47 — Nom de famille forcé en majuscules
Champ "Nom de famille" automatiquement forcé en MAJUSCULES à l'inscription.

### Point 48 — Case d'acceptation des conditions légales manquante
Texte exact : "☐ J'accepte les Conditions Générales d'Utilisation et la Politique de confidentialité.", liens cliquables vers pages correspondantes.

### Point 49 — Bug de traduction : mot anglais résiduel
Message d'erreur téléphone déjà utilisé : retirer le mot anglais entre parenthèses, rester entièrement en français.

### Point 50 — Filigrane sur l'export PDF des mesures clients
Filigrane répété en diagonale (logo Gextimo + `www.gextimo.novafriq.africa`), semi-transparent, motif répété (pas centré unique).

### Point 51 — Pied de page sur l'export PDF des mesures
Chaque page : logo Gextimo (petit format), URL `www.gextimo.novafriq.africa`, numéro `+229 01 91 47 96 28`.

### Point 52 — Branding sur le partage WhatsApp des mesures
Bloc automatique : "Généré par Gextimo" + "www.gextimo.novafriq.africa" + "📞 +229 01 91 47 96 28".

### Point 53 — Bug bloquant : commande groupée
Corriger la condition : ne vérifier que la présence d'au moins deux articles avec prix renseigné (pas de "vêtement" distinct de l'"article").

### Point 54 — Renommer "Article" en "Type de vêtement"
Partout dans l'interface, cohérent avec la terminologie déjà adoptée.

### Point 55 — Réseaux sociaux du profil Designer
Champs Instagram/Facebook = lien complet vers le compte, générant un lien cliquable automatique.

### Point 56 — Type de document sur l'écran de vérification Designer
Champ complémentaire (menu déroulant/carte) précisant le type de document joint.

---

## Section D — Système d'événements dynamiques (document complet, Markus/Aquilas)

### Point 57 — Système d'événements dynamiques complet
Système de célébration d'événements (nationaux, religieux, internes, personnels, marketing), sans recompilation. Non-intrusif, autonomie maximale. 5 familles (fixe, lunaire, propre à Gextimo, utilisateur, marketing/dynamique) — voir document dédié pour la structure de champs complète et les exemples JSON (`id`, `type`, `date`/`regle_calcul`, `titre`, `message`, `animation`, `couleur`, `priorite`, `cible`, `frequence_affichage`, `mode_affichage`). Règle de priorité : personnel > Gextimo > national/religieux > marketing. Fréquence : 1x/jour par événement. Cas limites : pas de date de naissance, conflits multiples, hors ligne, fuseau horaire.

### Point 58 — Ajout du champ date de naissance
Jour et mois uniquement (pas l'année).

### Point 59 — Test de 3-4 jours avant validation
Note de process : tester une première version 3-4 jours avant validation finale.

---

## Section E — Corrections diverses (lot suivant)

### Point 60 — Emails de vérification : adresse et branding
Adresse officielle Gextimo (pas Gmail personnel). Retirer "CouturePro", remplacer par "Gextimo" partout.

### Point 61 — Module Clients : état vide et incitations
Icône professionnelle. Message incitatif : "Ajoutez votre premier client ou importez vos contacts pour commencer." Flèche/cercle clignotant vers "+", message "Importez vos contacts clients pour gagner du temps."

### Point 62 — Importation des contacts : positionnement et confirmation
Remonter les boutons Annuler/Importer (superposition barre Android). Toast "Contacts importés avec succès."

### Point 63 — Création de commande : visibilité client et champ quantité
Nom du client visible étapes 2-4 ("Commande de : [Nom]"). Quantité 1 supprimable directement.

### Point 64 — Sélection du modèle après le client
Ouvrir directement le catalogue après sélection du client. Flèche clignotante vers "+" si catalogue vide.

### Point 65 — Module Catalogue : icônes par type de vêtement
Icône adaptée par type (pantalon, chemise, robe, t-shirt). Mémorisation des nouveaux types créés.

### Point 66 — Classement alphabétique du catalogue
Réorganisation automatique après création de plusieurs modèles.

### Point 67 — Onboarding en 3 étapes obligatoires
Profil → Clients (import/ajout) → Catalogue (modèles), avec flèches indicatives, message final "Vous êtes prêt..."

### Point 68 — Refonte de la procédure de création de commande
Nom client visible, menu déroulant "Choisir un type de vêtement", renommage "Article 1"→"Type de vêtement", nouvel ordre (client→type→infos→mesures), bouton "Éditer les mesures".

### Point 69 — Configuration des mesures par type de vêtement
Libellés définis une seule fois par type de vêtement dans Catalogue, chargés automatiquement à la commande.

### Point 70 — Bug export WhatsApp : libellés de mesures manquants
Intégrer les libellés (Tour de poitrine, Taille, etc.) dans l'export WhatsApp.

### Point 71 — Prénom manquant dans le profil
Ajouter l'affichage du prénom dans Réglages > Profil.

### Point 72 — Points de fidélité liés aux clients
1 client enregistré/importé = 1 point. Message informatif avant import/ajout.

### Point 73 — Bug "Se souvenir de moi"
Préremplir le dernier numéro utilisé si activé ; jamais le mot de passe.

### Point 74 — Bug préférences non appliquées
Devise/unités doivent s'appliquer immédiatement partout après enregistrement.

### Point 75 — Historique des mises à jour
Flèche Paramètres > Mise à jour doit ouvrir un historique des versions (numéro, date, changements), langue de l'utilisateur.

### Point 76 — Bug bouton Conditions d'utilisation
Corriger pour afficher le document au lieu de renvoyer à la connexion.

### Point 77 — Bug Server Error à la création de compte Designer
Vérifier l'erreur serveur/API bloquant la sélection du profil Designer.

### Point 78 — Rappel : texte standard de la case CGU + Politique de confidentialité
Consolidation des Points 46/48 : texte standard, liens cliquables.

### Point 79 — Intégration des documents légaux dans les pages du site
Copier/coller le contenu à jour, mise en forme professionnelle, fond agréable. Signaler tout document manquant.

---

## Section F — Correctifs vitrine + application web

### Point 80 — Logo incorrect sur la page de connexion
Logo officiel Gextimo ("Créer" avec "EZ"), partout sur la vitrine.

### Point 81 — Correction de casse du copyright (résolu)
Règle officielle : "Novafriq", seule la première lettre en majuscule. Afficher "© Gextimo - Novafriq". *(Voir Point 128 : contradiction détectée avec une instruction ultérieure.)*

### Point 82 — Réécriture du texte "Votre espace est sur l'application mobile"
Ton naturel, sans tirets longs. Conserver "Bonjour {Nom Atelier}". Pas d'interdiction suggérée pour le navigateur.

### Point 83 — Refonte des plans d'abonnement
Sélecteur Mensuel/Annuel (Mensuel par défaut). Plan Gratuit ajouté, visible seul au lancement. Plan Master : différences uniquement. Annuel = mensuel + "2 mois offerts". Plan annuel mis en avant visuellement. Corrections texte ("1 membre", "1 sous-atelier"). Nouveau document des plans à utiliser.

### Point 84 — Ordre des rubriques Paramètres (Web vs Mobile)
Ordre Web identique à l'ordre Mobile.

### Point 85 — Bug redirection "Ajouter une ville"
Rediriger vers Paramètres → Atelier → Ville (bon bloc directement).

### Point 86 — Bug scroll sidebar
Le scroll ne doit pas remonter automatiquement après clic sur un élément.

### Point 87 — Bug état actif sidebar
Fond coloré du menu actif fonctionnel sur tous les menus.

### Point 88 — Logo sidebar
Remplacer les ciseaux par le logo officiel Gextimo (statique).

### Point 89 — Bouton Hamburger sidebar
Deux états : développé (menu complet) / réduit (icônes uniquement).

### Point 90 — Profil : formatage Nom/Prénom + nouveaux champs
Nom en MAJUSCULES. Prénom : première lettre majuscule par mot. Ajout champs "Pseudo" et "ID utilisateur".

### Point 91 — Bug avatar : erreur 404
Corriger pour ouvrir le profil (footer sidebar + haut à droite).

### Point 92 — Tickets Support : images et horodatage
Conserver ratio/proportions des images (jamais recadrées). Ajouter date/heure exacte du ticket.

### Point 93 — Revue générale des liens internes
Vérifier qu'aucun bouton ne pointe vers une page inexistante.

*Note de priorisation (Points 80-93) : priorité haute = logo, copyright, plans, bug profil 404, bug scroll, réécriture message mobile ; moyenne = réorganisation paramètres, redirection ville, corrections texte, hamburger, plan Gratuit ; faible = formatage Nom/Prénom, Pseudo, ID utilisateur, heure tickets, visuel plans.*

### Point 94 — Liste exhaustive des 54 pays africains à l'inscription
Compléter avec indicatifs, triée alphabétiquement (liste des pays manquants fournie).

### Point 95 — Affichage amélioré de la sélection de pays
Format drapeau + nom complet + indicatif, triée alphabétiquement, recherche par nom.

### Point 96 — Correction globale du nom de marque (résolu)
Recherche globale : toute occurrence non conforme ("NovaAfriq", "NovAfriq") → "Novafriq" (confirme le Point 81).

### Point 97 — Bug déconnexion + retour à l'accueil
Corriger le bouton déconnexion. Prévoir un retour facile à l'accueil (bouton ou logo).

### Point 98 — Bulle flottante de retour à la vitrine
Widget réduit en coin d'écran (Web Desktop/Tablette), réduit l'espace applicatif sans le fermer, session intacte en arrière-plan.

### Point 99 — Bug synchronisation Web/Mobile : Caisse du jour
Synchronisation automatique des données financières entre appareils, délai très court.

### Point 100 — Bug synchronisation Web/Mobile : catalogues disparus
Vérifier enregistrement en base, synchronisation mobile→serveur, pas de suppression/écrasement.

---

## Section G — Module "Mes Réalisations" (spécification complète, Markus/Aquilas)

### Point 101 — Module "Mes Réalisations" : publication de photos par l'artisan
4 statuts (Brouillon, En attente de modération, Publiée, Refusée) avec badge visible. Case de certification d'auteur obligatoire bloquante. Double sécurité consentement personnes visibles (artisan + modération). Cache local limité à 100 (brouillons + en attente uniquement). Anti-abus : 10 envois/semaine/artisan. Watermark automatique à la publication (pas à l'envoi).

---

## Section H — Notifications, compte à rebours, récupération de compte, commission

### Point 102 — Distinction Notifications / Gextimo Infos
Deux onglets : Notifications (automatiques) et Gextimo Infos (communication descendante équipe→utilisateurs), typologie par message (Annonce, Nouveauté, Astuce, Promotion, Alerte, Événement, Formation, Sécurité).

### Point 103 — Compte à rebours de lancement Gextimo
Bande quotidienne J-30 à J-1 (5 secondes, couleurs rouge/jaune/vert). Écran "Lancement en direct" Jour J avec chrono temps réel. Configuration via panneau admin.

### Point 104 — Amélioration des messages de récupération de compte
Consignes explicites format international (+229...) pour question secrète et OTP. Validation automatique du format si possible.

### Point 105 — Modification du taux de commission affiché
"0%" → "15% de commission sur vos ventes".

### Point 106 — Import intelligent des contacts (anti-doublon)
Analyse avant import : aucun changement / nouveaux contacts / doublons détectés (avec fusion proposée). Récapitulatif final.

### Point 107 — Méthodes d'inscription complémentaires
Ajouter Yahoo et Apple (en plus de Google). Espace newsletter. Captcha classique au lieu de reCAPTCHA.

### Point 108 — Discussion d'architecture pour l'assistant conversationnel
Question ouverte à Markus (pas une spec figée) : architecture progressive V1 (Botpress) → V2 (Qdrant + IA légère) → V3 (données réelles), garde-fous dès le début.

---

## Section I — Derniers signalements

### Point 109 — Bug d'ancrage/scroll sur les boutons du header
Défilement automatique vers la bonne ancre au clic sur les boutons du header.

### Point 110 — Réécriture du message d'erreur du support (ton moins "IA")
Retirer le tiret, ton plus naturel, sens conservé.

### Point 111 — Choix de la structure officielle de l'adresse email support
Trancher entre Option A (`support.gextimo@novafriq.africa`) et Option B (`support@gextimo.novafriq.africa`).

### Point 113 — Audit et standardisation de toutes les adresses email du site
Deux modèles validés (générique `nom@novafriq.africa` ; support selon Point 111). Recherche complète, signalement, validation une par une, remplacement automatique partout.

### Point 114 — Calcul des upgrades d'abonnement (prorata)
Base fixe 31 jours. Crédit = (jours restants/31) × prix actuel. Nouvelle période à partir de la date d'upgrade. Points de fidélité jamais recalculés. Récapitulatif avant paiement.

---

*Fin des points 32 à 114.*
# Spécification Gextimo — Suite des points (115 à 123)

> Continuité directe du document précédent (arrêté au point 114). Traduction fidèle des instructions données oralement, réparties en tâches distinctes et actionnables, à partir des deux documents fournis : le fichier HTML de paramétrage des cookies (`gextimo-cookies.html`) et la charte texte de l'assistant conversationnel Makila AI (`Gextimo_Makila_AI_Charte_texte_v1.docx`).

---

## Section J — Paramètres de cookies (fichier gextimo-cookies.html)

### Point 115 — Vérification de la conformité d'affichage du bandeau et du panneau cookies
Avant toute modification, vérifier que le bandeau compact de consentement et le panneau complet « Personnaliser » (catégories Essentiels / Préférences / Personnalisation / Statistiques / Marketing, avec interrupteurs et description de chaque catégorie) sont actuellement affichés sur le site exactement comme dans la maquette `gextimo-cookies.html` transmise. Si ce n'est pas le cas — présentation différente, catégories manquantes, comportement différent — reparamétrer l'affichage du site pour qu'il corresponde fidèlement à cette maquette (structure, comportement du bandeau, ouverture/fermeture du panneau, actions « Tout accepter » / « Tout refuser » / « Personnaliser »).

### Point 116 — Vérification et amélioration du rendu visuel
Une fois la conformité de structure vérifiée au Point 115, contrôler la qualité du rendu à l'écran (alignement, espacement, lisibilité, comportement responsive sur mobile/tablette/desktop, transitions d'ouverture du panneau, superposition de l'overlay) et l'améliorer si nécessaire, sans modifier la structure ni le design déjà validés.

### Point 117 — Enrichissement du contenu décrivant chaque catégorie de cookies
Le contenu actuellement affiché pour chaque catégorie de cookies (Essentiels, Préférences, Personnalisation, Statistiques, Marketing) est jugé purement informatif et incomplet. Parcourir les données réellement collectées et utilisées par le site (l'ensemble des informations disponibles, pas seulement celles déjà listées dans la maquette) afin de produire une description plus riche et plus précise pour chaque catégorie, reflétant fidèlement ce que Gextimo collecte réellement.

**Contraintes à respecter :**
- Conserver exactement le même design que celui de la maquette (structure, mise en page, interrupteurs, badges).
- Conserver la palette de couleurs actuelle du site (mêmes teintes que celles définies dans la feuille de style : bordeaux/rouge de marque, or, crème).
- Préciser l'emplacement où ce panneau de gestion des cookies doit être accessible sur le site (footer, paramètres du compte, etc.).

---

## Section K — Assistant conversationnel Makila AI (charte texte v1.0)

### Point 118 — Vérification de la conformité de l'identité de Makila AI
Vérifier que le nom de l'assistant (« Makila AI ») et sa signature officielle (« L'intelligence au service de la mode africaine ») sont correctement intégrés partout où ils doivent apparaître : en-tête fixe du chatbot (ligne 1 : Makila AI ; ligne 2 : la signature), bulle d'information du site, message d'accueil du corps du chat. Vérifier que le ton de voix (chaleureux, précis, passionné, accessible) et le registre de vouvoiement définis dans la charte sont respectés dans l'ensemble des textes actuellement en place, et corriger tout écart par rapport aux textes finaux validés dans la charte (accueil, incompréhension, aucun résultat trouvé, redirection vers un humain).

### Point 119 — Vérification du contenu et du fonctionnement du menu du chatbot
Vérifier que les quatre entrées du menu du chatbot (« À propos de Makila AI », « Politique de confidentialité » version courte, « Centre d'aide », « Donner un avis ») sont bien présentes avec les textes exacts définis dans la charte. Vérifier spécifiquement le module « Donner un avis » : s'assurer qu'il permet réellement de recueillir un avis avec les caractéristiques nécessaires (note et/ou commentaire), et corriger si ce n'est pas fonctionnel ou incomplet.

### Point 120 — Vérification des liens cliquables vers les pages légales et complément si nécessaire
Vérifier que tous les liens cliquables en rapport avec la politique de confidentialité (dans le chatbot comme dans le footer et ailleurs sur le site) renvoient bien vers les pages correspondantes correctes. Pour chaque page de destination, vérifier qu'elle contient des informations suffisantes et complètes. Si une page ne dispose pas encore d'un contenu structuré correspondant aux données actuelles de Gextimo, créer un modèle de page adapté et le remplir avec un contenu conforme à la situation réelle de la plateforme.

### Point 121 — Refonte unifiée des pages légales avec navigation en sidebar
Prendre en priorité la page « Politique de confidentialité » et la réorganiser selon un nouveau module de présentation unique : un menu latéral (sidebar) à gauche listant les titres de toutes les sections ou pages liées (politique de confidentialité, conditions générales d'utilisation, mentions légales, politique de cookies, etc.), et une zone de contenu à droite qui affiche le texte correspondant dès qu'un titre est sélectionné dans la sidebar — sans rechargement de page, dans un template unique. Ce même module de présentation doit ensuite être appliqué à l'ensemble des liens du footer relatifs aux données/mentions légales, afin que tous ces contenus partagent la même expérience de navigation. *(Ce point précise et complète le Point 79 déjà formulé sur l'intégration des documents légaux.)*

### Point 122 — Complément du contenu légal manquant, signalé en vert
Étant donné que l'ensemble des données nécessaires à ces pages n'est pas encore finalisé côté marque, compléter les articles ou sections insuffisamment détaillés afin que chaque page légale soit suffisamment riche et conforme, **sans modifier le sens ni l'intention du contenu déjà validé** — il s'agit uniquement de compléter, jamais de remplacer. Toute portion de texte ajoutée en complément doit être mise en évidence visuellement en couleur verte, afin de signaler clairement ce qui a été ajouté par l'IA, pour permettre une relecture et une validation ultérieures par l'équipe si un ajout est jugé injustifié ou à corriger.

### Point 123 — Conformité réglementaire de la conversion des contenus légaux
Avant toute mise en ligne, s'assurer que la conversion et le complément des contenus légaux (Point 122) respectent à la fois le code du numérique en vigueur au Bénin et les standards internationaux applicables en matière de protection des données personnelles.

---

*Fin des points 115 à 123 — transcription fidèle des instructions orales fournies, à partir des documents `gextimo-cookies.html` et `Gextimo_Makila_AI_Charte_texte_v1.docx`.*
# Spécification Gextimo — Suite des points (124 à 125)

> Continuité directe du document précédent (arrêté au point 123). Sujet : vérification du référencement naturel (SEO) et de la lisibilité du contenu par les robots des moteurs de recherche, en particulier pour les pages construites en rendu côté client (JavaScript) — notamment la nouvelle structure en sidebar des pages légales (Point 121).

---

## Section L — SEO et indexabilité par les moteurs de recherche

### Point 124 — Audit de la lisibilité du contenu par les robots d'indexation (Google, Bing)
**Contexte à transmettre au développeur :** un point de vigilance technique existe indépendamment d'un travail SEO déjà réalisé par ailleurs. Quand un moteur de recherche visite une page du site, il envoie un robot d'exploration qui demande la page et regarde ce qu'il reçoit. Si le vrai contenu d'une page se charge uniquement via JavaScript après coup (rendu côté client), le robot risque de ne recevoir qu'une coquille quasi vide — seulement le titre et la description générale du site, sans le texte réel de la page.

**Conséquence si ce n'est pas corrigé :** si Google ne « voit » pas le contenu réel d'une page, il la classe moins bien dans ses résultats de recherche pour les mots-clés correspondants (ex. « politique de confidentialité Gextimo », ou des mots-clés liés à l'activité comme « mode africaine », « marketplace »). Ce risque ne concerne pas uniquement la page de politique de confidentialité : toute page fonctionnant selon le même principe (chargement du contenu via JavaScript) peut être concernée — pages produits, pages créateurs, catégories, accueil.

**Nuance à transmettre également :** ce n'est pas une urgence critique. Google a amélioré sa capacité à exécuter du JavaScript avant indexation, donc ce n'est plus aussi problématique qu'il y a plusieurs années. Mais cette exécution reste plus lente, plus coûteuse pour Google, non garantie à 100 %, et certains outils tiers (aperçus de partage sur certains réseaux sociaux, autres robots moins sophistiqués) ne l'exécutent pas du tout.

**Tâche demandée :** effectuer un audit pour vérifier, page par page (en priorité : la nouvelle structure sidebar des pages légales du Point 121, puis accueil, catégories, pages créateurs, pages produits), si le contenu réel est présent dans le HTML reçu par un robot n'exécutant pas JavaScript, ou s'il n'apparaît qu'après exécution du JavaScript côté client. Documenter les pages concernées par ce problème.

### Point 125 — Correction technique : rendu serveur ou pré-rendu des pages clés
Pour chaque page identifiée au Point 124 comme non lisible par un robot sans exécution JavaScript, mettre en place une solution technique pour que le contenu réel soit présent directement dans le HTML livré, sans dépendre de l'exécution du JavaScript. Deux approches possibles à évaluer par le développeur selon l'architecture existante :
- **Rendu côté serveur (SSR)** : générer le HTML complet, contenu inclus, au moment de la requête serveur.
- **Pré-rendu** : générer à l'avance une version HTML statique et lisible du contenu de chaque page, servie aux robots (et aux utilisateurs) sans attendre l'exécution du JavaScript.

Priorité de traitement : pages légales (structure sidebar du Point 121, à commencer par la politique de confidentialité), puis page d'accueil, pages catégories, pages créateurs et pages produits — ce sont les pages qui comptent le plus pour le référencement naturel d'une marketplace vivant du trafic et de l'acquisition de nouveaux clients.

---

*Fin des points 124 à 125.*
# Spécification Gextimo — Suite des points (126 à 127)

> Continuité directe du document précédent (arrêté au point 125). Sujet : conformité réelle du panneau de gestion des cookies (`gextimo-cookies.html`) — comportement par défaut des cases et persistance effective des choix de l'utilisateur.

---

## Section L — SEO et indexabilité par les moteurs de recherche *(suite)*

### Point 126 — Correction des cases pré-cochées par défaut dans le panneau « Personnaliser »
Dans la maquette actuelle (`gextimo-cookies.html`), les catégories Préférences, Personnalisation, Statistiques et Marketing sont toutes cochées (`checked`) par défaut à l'ouverture du panneau — seule la catégorie Essentiels doit logiquement être cochée et non désactivable, puisqu'elle est indispensable au fonctionnement du site.

**Problème :** un consentement granulaire conforme (loi béninoise sur la protection des données personnelles, cf. Point 40, et principes équivalents type RGPD) doit reposer sur un choix actif de l'utilisateur (opt-in) pour toute catégorie non essentielle, et non sur un consentement présumé par défaut (opt-out). Tel qu'implémenté actuellement, un utilisateur qui clique directement sur « Enregistrer mes choix » sans toucher aux interrupteurs valide implicitement toutes les catégories, y compris Marketing.

**Tâche demandée :** modifier le comportement par défaut du panneau afin que les catégories Préférences, Personnalisation, Statistiques et Marketing soient **décochées** à l'ouverture du panneau (état initial `false`), la catégorie Essentiels restant seule cochée et non modifiable. L'utilisateur doit activer explicitement chaque catégorie qu'il souhaite autoriser. Le bouton « Tout accepter » du bandeau et du panneau continue de fonctionner comme un raccourci pour cocher l'ensemble des catégories en un clic, mais ne doit jamais être le comportement par défaut silencieux.

### Point 127 — Mise en place de la persistance réelle des choix de consentement
Dans l'état actuel du fichier, le bouton « Enregistrer mes choix » du panneau ferme uniquement l'interface (`closePanel()`) sans enregistrer nulle part les choix effectués par l'utilisateur (ni cookie, ni stockage local, ni appel serveur). De même, les boutons « Tout accepter » et « Tout refuser » modifient l'état visuel des interrupteurs (`setAll()`) sans persister ce choix au-delà de la session en cours.

**Problème :** le Point 36 exige que GA4, Meta Pixel et Microsoft Clarity ne se chargent qu'après consentement explicite de l'utilisateur, contrôlé par les valeurs `analytics_consent` et `marketing_consent`. Sans persistance réelle des choix issus du panneau, il n'existe aucun mécanisme technique reliant « ce que l'utilisateur a coché » à « quels scripts tiers ont le droit de se charger ». La règle du Point 36 est donc actuellement impossible à appliquer correctement.

**Tâche demandée :**
- Enregistrer le choix de l'utilisateur pour chaque catégorie (Préférences, Personnalisation, Statistiques, Marketing) dans un cookie ou un stockage dédié, avec une durée de conservation définie et une date d'enregistrement du consentement.
- Faire en sorte que ce choix soit relu à chaque chargement de page, afin que le bandeau ne réapparaisse pas à chaque visite si un choix a déjà été enregistré, et que le panneau « Personnaliser » reflète l'état réellement enregistré à sa réouverture (et non des valeurs par défaut).
- Relier ces valeurs enregistrées aux variables `analytics_consent` / `marketing_consent` utilisées par le Point 36, afin que le chargement de GA4, Meta Pixel et Microsoft Clarity dépende effectivement du consentement réellement donné.
- Prévoir un moyen pour l'utilisateur de revenir modifier ses choix à tout moment (cf. mention déjà présente dans le panneau : « Vous pouvez revenir sur ces choix à tout moment depuis le pied de page... »), en s'assurant que cette fonctionnalité est réellement opérationnelle et pas seulement mentionnée en texte.

---

*Fin des points 126 à 127.*
# Spécification Gextimo — Point 128

> Continuité directe du document précédent (arrêté au point 127). Ce point ne décrit pas une tâche à exécuter directement, mais un conflit entre deux instructions déjà transmises, à trancher avant toute implémentation.

---

## Section M — Point de vigilance : cohérence des instructions

### Point 128 — Conflit à trancher : casse exacte du nom « Novafriq » dans le copyright
**Constat :** deux instructions contradictoires existent sur la casse officielle du nom de marque « Novafriq », toutes deux présentées comme définitives.

- Le **Point 81** (marqué « résolu », décision présentée comme tranchée par la direction) impose : seule la première lettre (N) en majuscule, toutes les autres lettres en minuscules → **« Novafriq »**. Il précise explicitement que des formes comme « NovAfriq » ou « NovaAfriq » sont incorrectes. Le **Point 96** confirme et généralise cette même règle à l'ensemble du projet (recherche globale, remplacement de toute occurrence non conforme).
- Une note reçue séparément (section « Copyright » d'un document de correctifs) demande au contraire d'afficher **« NovAfriq »** (A majuscule après Nova), en la présentant elle aussi comme « la casse officielle de la marque ».

**Problème :** ces deux instructions ne peuvent pas être vraies en même temps. Si le paquet de spécifications est envoyé tel quel, le développeur se retrouve avec deux règles contradictoires sur le même mot, appliquées potentiellement à deux endroits différents du même projet (copyright en pied de page vs reste du code).

**Tâche demandée :** avant l'envoi du paquet complet au développeur, trancher explicitement laquelle des deux casses est la bonne — de préférence en confirmant à nouveau auprès de la direction, puisque le Point 81 indique que la question avait déjà été validée dans ce sens (« Novafriq », un seul N majuscule). Une fois la décision reconfirmée, supprimer ou corriger la mention contradictoire avant de transmettre le paquet, afin qu'une seule règle de casse circule dans l'ensemble de la documentation envoyée au développeur.

**Recommandation :** en l'absence de nouvelle instruction contraire de la direction, retenir la règle du Point 81/96 (« Novafriq », un seul N majuscule) comme référence, puisqu'elle est documentée comme une décision déjà validée en interne — et traiter la mention « NovAfriq » comme une erreur à corriger plutôt que comme une nouvelle décision.

---

*Fin du point 128.*
# Spécification Gextimo — Points 129 à 130

> Continuité directe du document précédent (arrêté au point 128). Ces deux points ne sont pas des tâches à exécuter directement, mais des incohérences détectées entre le contenu des points 1 à 31 (transmis pour la première fois) et le reste de la spécification déjà rédigée (points 32 à 128), à trancher avant l'envoi au développeur.

---

## Section M — Points de vigilance : cohérence des instructions *(suite)*

### Point 129 — Conflit à trancher : « Gestimo » vs « Gextimo »
**Constat :** l'ensemble des points 1 à 22 (Sprints 01 à 05) et 23 à 31 (modules de l'espace client) nomment systématiquement la plateforme **« Gestimo »** (sans X) — y compris dans des textes destinés à être affichés tels quels à l'utilisateur final (ex. Point 10 : *« la Politique de confidentialité de Gestimo »* ; Point 16 : *« Bienvenue sur votre espace client Gestimo ! »*). À l'inverse, l'intégralité des points 32 à 128 utilise systématiquement **« Gextimo »** (avec X), y compris dans le nom de domaine officiel (`www.gextimo.novafriq.africa`, Points 50-52) et les adresses e-mail (`support.gextimo@novafriq.africa`, Point 111).

**Problème :** si le paquet est envoyé tel quel, le développeur risque d'implémenter littéralement des textes visibles par l'utilisateur avec le mauvais nom de marque (« Gestimo » au lieu de « Gextimo »), ce qui serait immédiatement visible et embarrassant une fois en ligne.

**Tâche demandée :** confirmer que « Gestimo » est une erreur de frappe (le X manquant) et non une volonté délibérée de renommage partiel, puis corriger toutes les occurrences des points 1 à 31 en « Gextimo » avant l'envoi du paquet final au développeur.

### Point 130 — Écart à clarifier : méthodes d'authentification prévues par l'architecture technique vs par la spécification fonctionnelle
**Constat :** trois documents de la spécification donnent trois listes différentes de méthodes de connexion pour l'espace client :
- Le **Point 34** (architecture technique, Phase 1 - Fondations) ne prévoit que deux méthodes : Google (via Socialite) et e-mail + OTP.
- Les **Points 8 et 9** (Sprint 03) demandent cinq méthodes : Google, Facebook, Apple, Microsoft, e-mail.
- Le **Point 23** (module d'inscription) ne mentionne explicitement que Google et Facebook (en plus de l'e-mail).

**Problème :** l'architecture technique déjà spécifiée au Point 34 est plus étroite que ce que la spécification fonctionnelle demande. Si le développeur implémente uniquement ce que décrit le Point 34 (Google + e-mail), les connexions Facebook, Apple et Microsoft demandées aux Points 8-9 ne seront pas disponibles — sans que cet écart soit signalé nulle part ailleurs dans le document.

**Tâche demandée :** confirmer la liste définitive des méthodes de connexion réellement souhaitées pour le lancement (les cinq méthodes des Points 8-9, ou un sous-ensemble), puis mettre à jour le Point 34 en conséquence pour que l'architecture technique couvre bien tous les fournisseurs d'authentification retenus (ajout de Facebook Login, Sign in with Apple, et Microsoft Identity/Azure AD si Microsoft est confirmé), avant l'envoi du paquet final au développeur.

---

*Fin des points 129 à 130.*
---

*Document maître assemblé — Points 1 à 130.*
