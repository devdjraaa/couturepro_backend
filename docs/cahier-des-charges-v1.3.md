# COUTURE PRO

**Application de Gestion d'Atelier de Couture**  
*Destinée aux Couturiers d'Afrique Francophone*

## CAHIER DES CHARGES FONCTIONNEL & TECHNIQUE

**Version 1.3 — Document Finale Phase 1**  
Avril 2026  

*Document Confidentiel — Usage Interne*

---

## JOURNAL DES MODIFICATIONS

| Version | Date       | Modifications |
|---------|------------|----------------|
| V1.0    | Avr. 2026  | Document initial — architecture, abonnements, menus, timer, multi-ateliers |
| V1.1    | Avr. 2026  | Stack React/Laravel/Capacitor, Retool, sécurité 3 couches, WhatsApp, photos, Module Caisse Phase 2, 20 modèles, CGU |
| V1.2    | Avr. 2026  | Correction niveaux, suppression Deno Phase 1, plugin timer natif, jsPDF, onboarding guide, alertes livraisons, export fiche client |
| V1.3    | Avr. 2026  | Points fidélité logique finale (10k/45k/100k), suppression abonnement À Vie, pause compteur bonus, export mesures PDF WhatsApp, photo profil client 4 types + avatars, photo tissu commande tous niveaux local, note interne commande, suppression export CSV global, suppression calculateur prix |

---

## 1. IDENTITÉ ET VISION DU PROJET

### 1.1 Présentation Générale

Couture Pro est une application SaaS de gestion d'atelier de couture destinée aux couturiers et ateliers en Afrique francophone. Elle remplace les carnets papier et les échanges WhatsApp non structurés pour la gestion des clients, mesures, commandes et paiements.

| Paramètre                  | Valeur |
|----------------------------|--------|
| Nom du projet              | Couture Pro |
| Cible                      | Couturiers et ateliers d'Afrique francophone |
| Plateforme                 | Android & iOS (via Capacitor) |
| Langue Phase 1             | Français uniquement |
| Langue Phase 2             | Anglais (internationalisation) |
| Philosophie                | Offline-First — priorité au local, sync différée |
| Backend                    | Laravel + Supabase + Cloudflare R2 (photos VIP) |
| Frontend                   | React JS converti en APK/IPA via Capacitor |
| Admin Panel                | Retool (Phase 1 & 2) branché sur Supabase |

### 1.2 Philosophie Offline-First — Non Négociable

L'application doit fonctionner parfaitement sans internet pendant 30 jours maximum pour le Gérant et 7 jours pour les Assistants. La synchronisation est toujours différée — jamais en temps réel.

> **⚠️ NB : La sync en temps réel n'est JAMAIS utilisée. Toutes les opérations sont d'abord locales puis synchronisées en batch différé.**

---

## 2. ARCHITECTURE TECHNIQUE

### 2.1 Stack Technologique Complète

| Couche                | Technologie                         | Rôle |
|-----------------------|-------------------------------------|------|
| Frontend mobile       | React JS + Capacitor                | Une seule base de code → APK Android + IPA iOS |
| Backend API           | Laravel (PHP)                       | API REST, logique métier, batch sync vers Supabase, gestion abonnements |
| Base locale           | WatermelonDB + LokiJSAdapter (Phase 1) | Stockage offline 100% JS — migration SQLiteAdapter Phase 2 |
| Base Cloud            | Supabase (PostgreSQL + Auth + RLS)  | Source de vérité absolue |
| Photos VIP            | Cloudflare R2                       | Photos album modèles — Premium & Magnat uniquement |
| Génération PDF        | jsPDF + html2canvas                 | 100% local, offline, partage WhatsApp via API Share Capacitor |
| Timer abonnement      | Plugin Capacitor custom             | SystemClock.elapsedRealtime() Android + systemUptime iOS |
| Dashboard Admin       | Retool (Phase 1 & 2)                | Branché Supabase sans développement custom |
| Couche intermédiaire  | Supprimée en Phase 1                | Deno/Redis réintégré en Phase 2 si coûts Supabase mesurables |

> **⚠️ NB : WatermelonDB avec LokiJSAdapter stocke tout en mémoire RAM. Sur téléphones d'entrée de gamme (2 Go RAM), surveiller la consommation mémoire dès que la base dépasse 500 clients. Planifier la migration vers SQLiteAdapter en Phase 2.**

### 2.2 Flux de Synchronisation (Batch Laravel Direct)

- L'utilisateur crée/modifie → WatermelonDB local mis à jour immédiatement
- L'opération est ajoutée à la sync_queue locale
- Lors de la sync → envoi en batch de 20 opérations max directement à Laravel
- Laravel → Supabase — traitement en batch, pas de couche intermédiaire en Phase 1
- Supabase retourne les UUIDs serveur → WatermelonDB local mis à jour
- Les opérations complétées sont supprimées de la sync_queue

> 📝 La couche Deno + Redis sera réintroduite en Phase 2 uniquement si les coûts Supabase deviennent mesurables.

### 2.3 Logique de Synchronisation

- Toutes les données texte : forfait mobile direct — Wi-Fi prioritaire si détecté
- Photos VIP (album modèles) : compression 70% avant envoi, forfait direct, Wi-Fi prioritaire
- Photos profil client et photos tissu commande : stockage local uniquement, JAMAIS sur le Cloud

| Mode            | Comportement pour photos VIP                                | Par défaut |
|-----------------|-------------------------------------------------------------|------------|
| Mode Économique | Seuil 200 Ko — au-delà attend Wi-Fi ou confirmation         |            |
| Mode Équilibre  | Seuil 500 Ko — au-delà attend Wi-Fi ou confirmation         |            |
| Mode Libre      | Toutes les photos partent sur forfait sans limite           | Oui        |

### 2.4 Ce qui Monte sur le Cloud / Ce qui Reste Local

| Données                         | Stockage                      | Remarque |
|---------------------------------|-------------------------------|----------|
| Clients, Mesures, Commandes     | Supabase — delta uniquement   | Compression GZIP + batch unique par synchro |
| Auth, Abonnements, IDs, Points  | Supabase                      | Données critiques — priorité absolue |
| Config modèles vêtements        | Supabase                      | Configuration Gérant — pas les templates supprimés |
| Compteur abonnement bonus       | Supabase + sync locale        | Cloud gère la pause et la reprise automatiquement |
| Photos VIP album modèles        | Cloudflare R2                 | Premium & Magnat uniquement |
| Photos profil client            | Local uniquement              | Tous niveaux — compression auto — jamais Cloud |
| Photos tissu sur commande       | Local uniquement              | Tous niveaux — lié à la commande uniquement |
| Historique des actions          | Local uniquement (8 jours max)| Jamais sur le Cloud |

### 2.5 Sécurité Base de Données

- Row Level Security (RLS) active sur toutes les tables Supabase
- Chaque policy vérifie que auth.uid() est membre du workspace avec le bon rôle
- Un Assistant ne peut jamais modifier ou supprimer un client même en appelant l'API directement
- Index sur atelier_id pour éviter les scans complets de la base
- Chaque entrée porte un tag created_by (ID auteur) et workspace_id (ID atelier)

---

## 3. INSCRIPTION, CONNEXION & ONBOARDING

### 3.1 Formulaire d'Inscription

- Nom de l'Atelier
- Nom & Prénom du gérant
- Numéro de téléphone (Identifiant Pivot — ne peut jamais être modifié sauf procédure de récupération)
- Email (récupération de compte et réception des reçus)
- Mot de passe
- Question secrète + réponse (Couche 3 de sécurité)

Actions automatiques à la validation :
- Génération d'un ID_Atelier unique par le système (invisible)
- Envoi d'un OTP par SMS sur le numéro saisi pour vérification immédiate
- Attribution automatique d'un abonnement cadeau de 14 jours au niveau Premium Mensuel
- Affichage de l'onboarding guide en 3 étapes interactives
- Redirection vers la page de choix d'abonnement (portail web externe)

> **⚠️ NB : Tous les comptes créés sont réels avec 14 jours d'essai complet au niveau Premium Mensuel. Risque à surveiller depuis Retool : patterns de création multiple avec différents numéros sur le même appareil.**

### 3.2 Page de Connexion Unique — Logique Unifiée

| Ce qui est saisi                     | Type détecté     | Logique appliquée |
|--------------------------------------|------------------|-------------------|
| Numéro de téléphone                  | Gérant           | Vérification locale puis Cloud si nécessaire |
| Code type ATELIER123_ASST1           | Assistant ou Membre | Vérification Cloud obligatoire puis cache local activé |
| ID système connu du Gérant           | Gérant           | Accepté — champ Identifiant accepte téléphone OU ID |

- Mémorisation automatique après première connexion réussie pour tous les types d'utilisateurs
- Si le Gérant révoque un accès, la session mémorisée est invalidée à la prochaine synchro
- Liens sous les champs : Mot de passe oublié ? et Numéro inaccessible ?

### 3.3 Récupération de Compte — Numéro Inaccessible

| Étape                    | Action                                      | Objectif |
|--------------------------|---------------------------------------------|----------|
| 1 — Email                | Saisie de l'email d'inscription             | Identifier le compte sans le numéro |
| 2 — Question secrète     | Le système pose la question secrète         | Couche 3 — bloquer les tentatives extérieures |
| 3 — Email d'alerte       | Email envoyé immédiatement — lien de blocage valable 24h | Couche 2 — fenêtre d'opposition |
| 4 — Nouveau numéro       | Saisie du nouveau numéro après 24h sans opposition | Mise à jour sécurisée |
| 5 — OTP                  | OTP à 6 chiffres sur le nouveau numéro      | Couche 1 — vérifier que le numéro appartient au Gérant |
| 6 — Validation           | Nouveau numéro remplace l'ancien dans Supabase | Mise à jour définitive |

> **⚠️ NB : Cas extrêmes (perte email ET numéro simultanément) : traitement manuel par le Super-Admin via procédure SAV interne avec vérification d'identité par appel. Non géré dans l'application — à documenter dans les processus internes.**

---

## 4. HIÉRARCHIE DES COMPTES ET RÔLES

### 4.1 Structure des Rôles

| Rôle                     | Nb max | Droits principaux |
|--------------------------|--------|-------------------|
| Super-Admin (Promoteur)  | 1      | Retool — gestion de tout le parc, abonnements, statistiques globales, SAV |
| Gérant (Admin)           | 1 par atelier | Contrôle total. Crée/supprime les comptes équipe. Seul à voir Abonnement et Notifications système. |
| Assistant                | Selon abonnement | Création clients/commandes/mesures/modèles. Archivage. Pas de suppression ni modification sensible. |
| Membre / Lecteur         | Selon abonnement | Lecture seule : mesures et profils clients uniquement. |

### 4.2 Quota pendant la Période d'Essai de 14 Jours (niveau Premium Mensuel)

| Fonctionnalité               | Valeur pendant l'essai |
|------------------------------|------------------------|
| Assistants                   | 1                      |
| Membres / Lecteurs           | 3                      |
| Clients & Commandes / mois   | 100                    |
| Photos album VIP             | 5                      |
| Facturation WhatsApp         | 25 factures            |
| Points de fidélité           | Actifs — 1 pt par client / commande |

### 4.3 Droits des Assistants

| Action                                      | Gérant | Assistant | Membre |
|---------------------------------------------|--------|-----------|--------|
| Créer un nouveau modèle de vêtement         | Oui    | Oui       | Non    |
| Modifier un modèle existant                 | Oui    | Non       | Non    |
| Supprimer un modèle                         | Oui    | Non       | Non    |
| Archiver un modèle erroné                   | Oui    | Oui       | Non    |
| Ajouter des libellés de mesures             | Oui    | Oui (dans sa session) | Non |
| Modifier les libellés existants             | Oui    | Non       | Non    |
| Enregistrer photo tissu sur commande        | Oui    | Oui       | Non    |
| Exporter mesures client en PDF WhatsApp     | Oui    | Oui       | Non    |
| Enregistrer photo album VIP (si active)     | Oui    | Oui       | Non    |
| Supprimer des photos album VIP              | Oui    | Non       | Non    |

### 4.4 Code de Reprise Assistant — Déblocage après 7 jours

- Code à 6 chiffres uniquement (ex: 44 82 10) — valable 48 heures
- Lié à l'ID unique de l'appareil — ne fonctionne sur aucun autre téléphone
- Connexion internet OBLIGATOIRE pour valider le code
- Cette connexion forcée déclenche automatiquement la synchronisation complète en arrière-plan

> 📝 L'assistant sauvegarde tout son travail de la semaine au moment exact où il débloque son application.

### 4.5 Révocation des Accès

- Le Gérant supprime le compte depuis les Paramètres (internet requis)
- Le Cloud invalide immédiatement le jeton d'accès (Token)
- À la prochaine ouverture par l'employé : accès refusé et données locales de l'atelier effacées

---

## 5. MODE MULTI-ATELIERS

### 5.1 Activation et Règles

- Tout utilisateur démarre en mode atelier unique par défaut
- Bouton "Activer le mode Multi-Ateliers" dans Paramètres pour débloquer
- Le premier atelier créé à l'inscription = Atelier Maître à vie
- Maximum 7 ateliers par compte Maître
- Chaque atelier secondaire : 14 jours d'essai gratuit à sa création
- Après 14 jours sans abonnement Multi-Ateliers : accès gelé (données conservées)

### 5.2 Droits du Patron Maître

| Action                                | Atelier Maître         | Ateliers Secondaires |
|---------------------------------------|------------------------|----------------------|
| Voir clients / commandes / mesures    | Lecture + écriture     | Lecture seule        |
| Modifier / Supprimer des données      | Oui                    | Non                  |
| Créer / Supprimer des comptes équipe  | Oui                    | Oui (sécurité)       |
| Voir les statistiques                 | Oui                    | Oui                  |
| Gérer les abonnements                 | Oui (centralisé)       | Non (invisible)      |
| Recevoir les notifications système    | Oui                    | Non                  |

### 5.3 Tableau de Bord Consolidé

- Vue mobile simplifiée : chiffres clés par atelier
- Rapport PDF mensuel automatique par email : comparatif production, performance, CA tous ateliers
- Dashboard Retool : analyse approfondie, gestion globale
- Chargement à la demande : les données d'un atelier secondaire ne se téléchargent que lors du clic

---

## 6. SYSTÈME D'ABONNEMENT ET NIVEAUX

### 6.1 Portail de Paiement

- Paiement via Mobile Money (MTN, Moov, etc.) — zone Bénin / Afrique
- Paiement via Carte Bancaire (VISA, Mastercard) — zone internationale
- Après paiement : l'utilisateur reçoit par email un ID de Transaction unique
- L'ID de Transaction est saisi dans le menu Abonnement pour activer les jours
- Un ID de Transaction utilisé est immédiatement invalide pour tout autre compte

### 6.2 Formules d'Abonnement

> 📝 L'abonnement À Vie est supprimé définitivement. Aucune mention ni logique dans le code.

| Durée        | Jours    | Remarque |
|--------------|----------|----------|
| Mensuel      | 31 jours | Base universelle — 31 jours fixes peu importe le mois calendaire |
| Trimestriel  | 93 jours | 31 x 3 |
| Semestriel   | 186 jours| 31 x 6 |
| Annuel       | 365 jours| Débloque les meilleurs avantages et points |

> 📝 Les abonnements sont cumulables. Les jours s'ajoutent automatiquement au total restant.

### 6.3 Tableau des Niveaux et Droits Complets

**🆕 V1.3 — Tableau corrigé et finalisé**

| Fonctionnalité                | Std Mens | Std Ann | Prem Mens | Prem Ann | Mag Mens | Mag Ann |
|-------------------------------|----------|---------|-----------|----------|----------|---------|
| Assistants                    | 0        | 1       | 1         | 2        | 2        | 3       |
| Membres / Lecteurs            | 0        | 1       | 3         | 5        | 5        | 7       |
| Clients & Commandes/mois      | 50       | 80      | 100       | 150      | 300      | 500     |
| Points par client créé        | 1 pt     | 1 pt    | 1 pt      | 2 pts    | 2 pts    | 3 pts   |
| Points par commande validée   | 1 pt     | 1 pt    | 1 pt      | 2 pts    | 2 pts    | 3 pts   |
| Points à l'activation         | 31 pts   | 365 pts | 31 pts    | 365 pts  | 31 pts   | 365 pts |
| Photos album VIP / mois       | Non      | Non     | 5         | 15       | 15       | 25      |
| Facturation / mois            | Non      | Non     | 25        | Illimité | 50       | Illimité|
| Envoi facture WhatsApp        | Non      | Non     | Oui       | Oui      | Oui      | Oui     |
| Sauvegarde automatique        | Non      | Non     | Non       | Oui      | Oui      | Oui     |
| Module Caisse (Phase 2)       | Non      | Non     | Oui       | Oui      | Oui      | Oui     |
| Photo profil client           | Oui      | Oui     | Oui       | Oui      | Oui      | Oui     |
| Photo tissu sur commande      | Oui      | Oui     | Oui       | Oui      | Oui      | Oui     |
| Export mesures PDF WhatsApp   | Oui      | Oui     | Oui       | Oui      | Oui      | Oui     |

> **⚠️ NB : Photo profil client, photo tissu sur commande et export mesures PDF sont disponibles pour TOUS les niveaux sans exception. Stockage local uniquement pour les photos.**

### 6.4 Expiration du Compte — Comportement

- À expiration : Gérant et Assistants passent automatiquement en rôle Membre (lecture seule)
- Toutes les données restent accessibles en consultation
- Seul le menu Abonnement reste actif pour le renouvellement
- Si l'utilisateur est offline quand l'abonnement expire, le compte se grise pour forcer la connexion
- À la reconnexion, le Cloud synchronise le timer local et met à jour le statut automatiquement

| Délai avant expiration | Message affiché (timer local — sans internet) |
|------------------------|------------------------------------------------|
| 7 jours                | Votre abonnement expire dans 7 jours. Pensez à renouveler. |
| 3 jours                | Plus que 3 jours ! Renouvelez maintenant pour continuer. |
| 1 jour                 | Dernière journée ! Votre accès sera limité demain. |
| 1 heure                | Dans 1 heure, votre application passera en mode consultation uniquement. |

---

## 7. SYSTÈME DE POINTS DE FIDÉLITÉ

**🆕 V1.3 — Logique finale et définitive**

### 7.1 Accumulation des Points

- Chaque jour d'abonnement acheté = 1 point attribué automatiquement. 31 jours = 31 points. 365 jours = 365 points. Toujours.
- Couture Pro utilise un mois fixe de 31 jours — jamais le calendrier civil. Peu importe le mois, 1 mois = 31 jours = 31 points.
- Chaque client créé = points selon le niveau (voir tableau section 6.3)
- Chaque commande validée = points selon le niveau (voir tableau section 6.3)
- Abonnement payé = points d'activation automatiques selon la durée achetée
- Nous suivre sur les réseaux sociaux = points bonus
- Nous noter sur le store = points bonus

> 📝 Le but des points est de pousser le couturier à utiliser l'application régulièrement — pas juste de payer et d'oublier.

### 7.2 Conversion des Points en Abonnement

Un utilisateur ne peut convertir ses points QUE pour obtenir le même niveau d'abonnement que celui qu'il possède déjà.

| Seuil de points requis | Abonnement gagné               | Niveau requis pour convertir |
|------------------------|--------------------------------|------------------------------|
| 10 000 points          | 31 jours du pack Starter       | Starter (Mensuel ou Annuel)  |
| 45 000 points          | 31 jours du pack Premium       | Premium (Mensuel ou Annuel)  |
| 100 000 points         | 31 jours du pack Magnat        | Magnat (Mensuel ou Annuel)   |

> 📝 Ces seuils sont volontairement élevés pour que les points soient un objectif à long terme créant de la fidélité sur la durée.

### 7.3 Logique de Pause et Reprise — Abonnement Bonus

**🆕 V1.3 — Gestion intelligente du compteur d'abonnement**

- Quand un abonnement bonus est gagné par échange de points, le compteur de l'abonnement en cours se met en PAUSE
- L'abonnement bonus s'écoule en premier jusqu'à épuisement complet
- Une fois le bonus épuisé, l'abonnement principal reprend exactement là où il s'était arrêté
- C'est le Cloud Supabase qui gère cette logique de pause/reprise automatiquement
- Si le Gérant est offline pendant la transition, à la reconnexion le Cloud recalibre le timer local
- Le Gérant reçoit une alerte locale lui demandant de se connecter pour mise à jour du compteur
- Si l'abonnement se termine pendant qu'il est offline, le compte se grise pour forcer la connexion

> **⚠️ NB : Le Cloud synchronise le timer avec le local à chaque reconnexion. Le timer local seul ne peut jamais décider du statut final.**

### 7.4 Interface du Menu Points de Fidélité

- Menu indépendant dans le hamburger — séparé du menu Abonnement
- Tableau de bord de progression avec barre visuelle vers le seuil d'échange
- Notification automatique quand le seuil est atteint
- Historique des points : sources, dates, montants
- Bouton de conversion visible uniquement quand le seuil est atteint

---

## 8. SÉCURITÉ DU TIMER D'ABONNEMENT

### 8.1 Mécanisme Anti-Fraude

| Élément                    | Solution technique |
|----------------------------|---------------------|
| Timer local Android        | Plugin Capacitor custom → SystemClock.elapsedRealtime() — indépendant de la date système |
| Timer local iOS            | Plugin Capacitor custom → ProcessInfo.processInfo.systemUptime |
| Stockage local             | EncryptedSharedPreferences (Android) / Keychain (iOS) |
| Source de vérité absolue   | Cloud Supabase — le Cloud gagne TOUJOURS en cas de conflit |
| Récupération après désinstallation | Reconnexion → Cloud renvoie les jours restants exacts |

> **⚠️ NB : Plugin Capacitor custom obligatoire — SystemClock.elapsedRealtime() n'est pas accessible depuis JavaScript dans une WebView Capacitor. Un plugin natif custom est requis — 1 à 2 jours de développement natif à planifier dès le début du projet.**

---

## 9. ARCHITECTURE DES MENUS (Navigation Drawer)

### 9.1 Structure du Menu Hamburger

| Menu                | Fonction                                          | Accès |
|---------------------|---------------------------------------------------|-------|
| Clients             | Liste, recherche, ajout, archivage, profil avec photo/avatar | Tous |
| Commandes           | Suivi tenues, dates livraison, statut, photo tissu, note interne | Tous |
| Vêtements           | Catalogue — 20 templates + personnalisés          | Tous |
| Mesures             | Fiches techniques configurables par vêtement      | Tous |
| Photos (VIP)        | Album photo modèles pour présenter les confections | Premium & Magnat |
| Points de Fidélité  | Tableau de bord progression, conversion, notifications | Selon niveau |
| Abonnement          | Activation ID transaction, jours restants, statut, bonus | Gérant uniquement |
| Historique          | Journal des actions (3 à 8 jours, local uniquement) | Gérant (tous), Employé (son atelier) |
| Notifications       | Messages système : promos, mises à jour, alertes synchro | Gérant uniquement |
| Paramètres          | Profil, équipe, communications, langue, thème, sauvegarde, À propos | Gérant (complet), Employés (limité) |
| FAQ                 | Questions fréquentes d'aide à l'utilisation       | Tous |
| Nous Contacter      | WhatsApp, Facebook, YouTube, LinkedIn, Twitter    | Tous |

### 9.2 Dashboard Résumé — Écran Principal

À l'ouverture de l'app, 3 indicateurs clés s'affichent immédiatement calculés localement depuis WatermelonDB. Temps de calcul cible : moins de 50ms.

| Indicateur              | Description                                      | Couleur d'alerte |
|-------------------------|--------------------------------------------------|------------------|
| Commandes en retard     | Nombre de commandes dont la date de livraison est dépassée | Rouge si > 0 |
| Livraisons dans 48h     | Commandes à livrer dans les 2 prochains jours    | Orange si > 0 |
| Commandes en cours      | Total des commandes non livrées                  | Neutre           |

> 📝 L'indicateur financier (argent attendu) sera ajouté en Phase 2 avec le Module Caisse.

### 9.3 Menu CLIENTS — Profils et Photos

**🆕 V1.3 — 4 types de profils client avec avatars préinstallés**

| Type de profil | Avatars préinstallés                     | Photo personnelle possible |
|----------------|------------------------------------------|----------------------------|
| Homme          | 7 avatars avec variations de couleur de peau | Oui |
| Femme          | 7 avatars avec variations de couleur de peau | Oui |
| Enfant         | 7 avatars avec variations de couleur de peau | Oui |
| Mixte          | Aucun avatar — champ vide par défaut     | Oui |

Logique de photo de profil client :
- Option 1 : Choisir un avatar pré-installé (comme les emojis WhatsApp avec variations de couleur de peau)
- Option 2 : Uploader une photo depuis la galerie du téléphone
- Option 3 : Prendre une photo directement avec l'appareil photo
- Photo compressée automatiquement avant stockage
- Stockage local uniquement — jamais sur le Cloud ni dans la galerie du téléphone
- Disponible pour TOUS les niveaux d'abonnement sans exception
- Non obligatoire — un client peut n'avoir aucune photo ni avatar

Autres fonctionnalités :
- Recherche prédictive : actualisation dès la 2e lettre saisie
- Affichage automatique des 5 derniers clients à l'ouverture
- Champs : Type de profil, Photo/Avatar (optionnel), Nom, Prénom, Numéro de téléphone (optionnel)
- Anti-doublon : blocage uniquement si Nom ET Prénom existent déjà simultanément
- Assistants : peuvent ajouter ou archiver — suppression définitive réservée au Gérant

### 9.4 Menu COMMANDES

- Onglet "En cours" : liste infinie (scroll), recherche par nom ou type de vêtement
- Onglet "Livré" : affichage restreint aux 5 dernières commandes livrées
- Ajout : pop-up sélection client → choix vêtement → quantité → prix (optionnel/indicatif)
- Badge rouge sur l'icône Commandes si livraison en retard ou dans les 48h
- Rappels WhatsApp configurables selon les dates (section 9.8)

**Photo du tissu sur commande :**
- Gérant ou Assistant peut prendre une photo du tissu et l'attacher à la commande
- Stockage local uniquement — jamais dans la galerie, jamais sur le Cloud
- Disponible pour TOUS les niveaux d'abonnement
- Compressée automatiquement avant stockage
- Disparaît si la commande est supprimée
- Objectif : preuve en cas de litige client sur la matière ou la couleur du tissu

> 📝 Note interne sur commande : champ visible uniquement par Gérant et Assistants, jamais envoyé au client. Exemples : tissu apporté par le client, broderie complexe, client difficile, prévoir 2 jours de plus.

### 9.5 Menu VÊTEMENTS

- 20 templates prédéfinis chargés à la première connexion (voir Annexe B)
- Le Gérant peut renommer, modifier les mesures, ajouter ou supprimer des templates
- L'Assistant peut créer de nouveaux modèles, archiver des modèles erronés
- Tout changement du Gérant est sauvegardé dans sa base Cloud personnelle

### 9.6 Menu MESURES

- Mesures liées au type de vêtement — chaque vêtement a sa propre fiche
- Libellés standards par défaut modifiables par le Gérant
- L'Assistant peut ajouter des libellés dans sa session — pas modifier les existants
- Recherche 100% locale — FTS sur WatermelonDB, debounce 300ms, limite 50 résultats, cible moins de 100ms sur 10 000 clients

### 9.7 Export Mesures Client — PDF WhatsApp

**🆕 V1.3 — Disponible pour tous les niveaux — Gérant ET Assistant**

- Depuis la fiche client, bouton "Exporter les mesures en PDF"
- Génération locale via jsPDF + html2canvas — aucune connexion requise
- Contenu : informations client (nom, prénom, téléphone, type de profil), toutes les mesures par type de vêtement, nom de l'atelier, date d'export
- Partage direct via WhatsApp, SMS ou enregistrement local — via API Share Capacitor
- Gérant ET Assistant peuvent exporter et envoyer — action tracée dans l'Historique
- Export fiche par fiche uniquement — pas d'export global de la base clients

> **⚠️ NB : L'export CSV global de la liste clients est supprimé définitivement. Trop risqué pour la sécurité des données de l'atelier.**

### 9.8 Communications WhatsApp — Module Configurable

Tous les messages WhatsApp partent uniquement depuis le téléphone du Gérant. L'app prépare le message et ouvre WhatsApp avec numéro et texte pré-remplis. Le Gérant appuie sur Envoyer ou annule librement.

| Module             | Description                                 | Message type                               | Défaut |
|--------------------|---------------------------------------------|--------------------------------------------|--------|
| Confirmation commande | Envoie la facture PDF à la création        | Votre commande a été enregistrée chez [Nom Atelier]. Montant : [X] FCFA. Livraison prévue le [Date]. | ON |
| Rappel livraison J-2 | Message 2 jours avant la date de livraison | Bonjour [Nom client], votre tenue sera prête dans 2 jours chez [Nom Atelier]. Merci. | ON |
| Commande prête      | Message quand la commande est marquée livrée | Bonjour [Nom client], votre tenue est prête ! Vous pouvez passer la récupérer chez [Nom Atelier]. | ON |

### 9.9 Alertes Visuelles Livraisons

| Déclencheur                     | Action                                      | Visuel |
|---------------------------------|---------------------------------------------|--------|
| Livraison en retard (date dépassée) | Badge rouge sur l'icône Commandes          | Badge + notification locale |
| Livraison dans 48h              | Badge orange sur l'icône Commandes         | Badge + notification locale |
| Livraison dans 24h              | Notification locale push (même app fermée) | Notification système |

> 📝 Implémentation via Capacitor Local Notifications plugin. Fonctionne offline et avec l'app fermée.

### 9.10 Onboarding Guide — 3 Étapes Interactives

Affiche uniquement à la première connexion. Le couturier manipule l'app réelle, pas une simulation.

| Étape | Action demandée                          | Ce que ça démontre |
|-------|------------------------------------------|--------------------|
| 1/3   | Créez votre premier client               | La saisie du profil client et des mesures |
| 2/3   | Ajoutez une commande pour ce client      | Le flux complet commande → livraison |
| 3/3   | Configurez vos rappels WhatsApp          | L'automatisation des communications |

### 9.11 Menu PARAMÈTRES — Structure Complète

**Profil & Atelier**
- Modifier le nom de l'atelier
- Modifier le mot de passe
- Choisir un avatar pour le profil du Gérant

**Gestion de l'Équipe**
- Créer Assistant (max selon abonnement) — internet obligatoire
- Créer Membre/Lecteur (max selon abonnement) — internet obligatoire
- Supprimer un compte équipe (révocation immédiate via Cloud)

**Communications**
- Confirmation commande : ON / OFF
- Rappel livraison J-2 : ON / OFF
- Commande prête : ON / OFF

**Sauvegarde & Restauration**
- Sauvegarde automatique : TOUJOURS ACTIVE — non désactivable
- Statut affiché en permanence + date et heure de la dernière sauvegarde
- Bouton "Sauvegarder maintenant" : force une sauvegarde immédiate
- Bouton "Restaurer la sauvegarde" : récupère les données Cloud — écrase les données locales

**Préférences**
- Langue : Français / Anglais
- Devise : choix de la devise locale
- Unité de mesure : cm, pouces, etc.
- Thème : Clair / Sombre
- Synchronisation photos VIP : Mode Économique / Mode Équilibre / Mode Libre (Libre par défaut)

**Mode Multi-Ateliers**
- Bouton "Activer le mode Multi-Ateliers"
- Gestion des ateliers secondaires

**À Propos**
- Centre d'aide
- Conditions Générales d'Utilisation (CGU)
- Politique de Confidentialité

### 9.12 Menu HISTORIQUE

- Journal de bord : chaque action enregistrée avec auteur et date
- Opérations tracées : ajouts, modifications, archivages, suppressions, exports PDF mesures
- Durée : 3 à 8 jours, stockage local uniquement — jamais sur le Cloud

---

## 10. MODULE DE FACTURATION ET MODULE CAISSE

### 10.1 Stack PDF — Définitive

- Génération 100% locale — jsPDF + html2canvas — sans connexion internet
- Partage direct vers WhatsApp, SMS, email ou enregistrement local via API Share Capacitor

> **⚠️ NB : Le PDF est généré localement et envoyé directement vers WhatsApp sans passer par email ni par le Cloud. Règle technique non négociable.**

### 10.2 Bon de Commande Client

- Nom du client, date de commande, date de livraison prévue
- Type(s) de vêtement commandé(s) et quantité
- Montant total, acompte versé, montant restant dû
- Statut : Payé / Partiellement payé / Non payé

| Niveau                | Quota mensuel de factures |
|-----------------------|---------------------------|
| Standard Mensuel & Annuel | Non disponible |
| Premium Mensuel       | 25 factures / mois        |
| Premium Annuel        | Illimitées                |
| Magnat Mensuel        | 50 factures / mois        |
| Magnat Annuel         | Illimitées                |

### 10.3 Module Caisse — Phase 2

- Suivi acomptes, montant restant dû, historique versements par client
- Tableau de bord mensuel : total encaissé, total en attente, commandes soldées vs en cours
- Indicateur financier sur le dashboard principal (4e indicateur — Phase 2 uniquement)

> 📝 En Phase 1, le champ Prix est indicatif uniquement. Le suivi financier complet arrive en Phase 2.

---

## 11. DASHBOARD SUPER-ADMIN

### 11.1 Retool — Phase 1 & 2

- Interface web accessible depuis n'importe quel ordinateur
- Branché directement sur Supabase — aucun développement spécifique
- Liste de tous les comptes ateliers : Actif, Expiré, En période d'essai
- Surveillance des patterns suspects : créations multiples sur le même appareil
- Validation et association des ID de transaction aux comptes
- Statistiques globales : ateliers actifs, revenus, taux de conversion, rétention
- Envoi de notifications promotionnelles et notes de mises à jour

> 📝 Retool sera remplacé par un Dashboard Web custom en Phase 4.

---

## 12. RÉCAPITULATIF TECHNIQUE POUR LE DÉVELOPPEUR

### 12.1 Règles Absolues — Non Négociables

- JAMAIS de sync en temps réel — tout est différé et en batch
- Le Cloud est TOUJOURS la source de vérité absolue
- La création de comptes équipe nécessite TOUJOURS une connexion internet
- Les photos VIP ne montent JAMAIS sur Supabase — uniquement Cloudflare R2
- Les photos profil client et photos tissu restent TOUJOURS en local — jamais Cloud ni galerie
- L'historique ne monte JAMAIS sur le Cloud — stockage local uniquement
- Le timer utilise un plugin Capacitor custom — JAMAIS la date système ni JavaScript pur
- Les tables Supabase ont RLS activée sur 100% des entrées
- La sauvegarde automatique est TOUJOURS active — non désactivable
- Les messages WhatsApp et factures partent UNIQUEMENT depuis le téléphone du Gérant
- Le PDF est généré localement via jsPDF + html2canvas — JAMAIS via email ni Cloud
- L'export CSV global de la liste clients est SUPPRIMÉ — export fiche par fiche uniquement
- L'abonnement À Vie est SUPPRIMÉ — aucune mention ni logique dans le code
- Le calculateur de prix indicatif est SUPPRIMÉ — aucune mention dans le code

### 12.2 Structure Base de Données

| Table                  | Champs clés                                      | Notes |
|------------------------|--------------------------------------------------|-------|
| proprietaires          | uid, telephone, email, question_secrete, reponse_secrete, atelier_ids[] | Compte Maître — 1 à 7 ateliers |
| ateliers               | atelier_id, proprietaire_uid, nom, abonnement_statut, niveau, essai_expire_at | Unité centrale |
| users                  | user_id, atelier_id, role, created_by, device_id | Assistants et Membres |
| clients                | client_id, workspace_id, created_by, nom, prenom, telephone, type_profil, photo_local_path | RLS sur workspace_id — photo stockée localement |
| commandes              | commande_id, workspace_id, client_id, vetement_id, statut, prix, acompte, date_livraison, note_interne, photo_tissu_local_path | RLS sur workspace_id |
| mesures                | mesure_id, client_id, vetement_id, champs_json, created_by | Liées au vêtement |
| vetements              | vetement_id, workspace_id, nom, libelles_json, is_archived | Templates + personnalisés |
| abonnements            | atelier_id, jours_restants, timestamp_debut, statut, niveau, bonus_jours_restants, bonus_actif | bonus_actif = true quand le bonus s'écoule — principal en pause |
| points_fidelite        | atelier_id, solde_pts, historique_json           | Géré côté Cloud |
| communications_config  | atelier_id, confirmation_on, rappel_j2_on, commande_prete_on | Préférences WhatsApp |
| sync_queue             | op_id, type, payload, created_at, synced         | Table locale WatermelonDB uniquement |

### 12.3 Performance Cible

| Indicateur                                  | Valeur cible |
|---------------------------------------------|--------------|
| Recherche client (10 000 clients)           | < 100 ms     |
| Calcul dashboard résumé (local)             | < 50 ms      |
| Ouverture de l'app (cache local présent)    | < 2 secondes |
| Génération PDF local (jsPDF)                | < 3 secondes |
| Taille de synchro mensuelle par atelier     | < 10 Mo      |
| Taille du batch de sync                     | 20 opérations max |
| Durée max sans sync (Gérant)                | 30 jours     |
| Durée max sans sync (Assistant)             | 7 jours      |
| Compression photos avant envoi              | 70% — qualité visuelle conservée |

---

## 13. ROADMAP DES PHASES

| Phase         | Contenu | Priorité |
|---------------|---------|----------|
| Phase 1 — MVP | CRUD Clients/Commandes/Mesures, Auth 3 couches, Abonnement 14j essai (niveau Premium Mensuel), Offline-First, Sync batch Laravel direct, Rappels WhatsApp configurables, 20 templates vêtements, Dashboard résumé 3 indicateurs, Onboarding guide 3 étapes, Alertes livraisons locales, Export mesures PDF WhatsApp, Photo profil client (4 types + avatars), Photo tissu commande (local), Note interne commande, Plugin timer Capacitor custom, Points fidélité (accumulation + seuils conversion) | Critique |
| Phase 2       | Module Caisse, Multi-Ateliers, Facturation WhatsApp illimitée, Migration WatermelonDB vers SQLiteAdapter, Points fidélité (conversion + pause abonnement bonus), Couche Deno/Redis si coûts Supabase mesurables | Haute |
| Phase 3       | Photos VIP Cloudflare R2, Rapport PDF mensuel multi-ateliers, Optimisations sync avancées | Moyenne |
| Phase 4       | Dashboard Web custom (remplacement Retool), Anglais, nouvelles fonctionnalités | Basse |

---

## ANNEXE A — TEXTES LÉGAUX

*A finaliser avec un juriste avant lancement. Les textes ci-dessous constituent une base de travail.*

### A.1 Conditions Générales d'Utilisation (CGU)

**Dernière mise à jour : [DATE DE LANCEMENT OFFICIEL]**

**Article 1 — Acceptation des conditions**
> En utilisant notre application, vous acceptez d'être lié par ces conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser l'application.

**Article 2 — Utilisation de l'application**
> Vous vous engagez à utiliser l'application conformément à toutes les lois et réglementations applicables. Vous ne devez pas utiliser l'application à des fins illégales ou interdites par ces conditions.

**Article 3 — Création de compte**
> Pour accéder à certaines fonctionnalités de l'application, vous devez créer un compte. Vous êtes responsable de la confidentialité de vos identifiants de connexion et de toutes les activités effectuées sous votre compte. Vous devez nous informer immédiatement de toute utilisation non autorisée de votre compte.

**Article 4 — Abonnements**
> L'application propose des plans d'abonnement. Les conditions spécifiques de chaque plan, y compris les tarifs et les avantages, sont décrites dans l'application. Les abonnements ne sont pas remboursables sauf cas de force majeure dûment constaté. En cas de non-renouvellement, certaines fonctionnalités deviennent inaccessibles mais vos données sont conservées pendant 30 jours avant suppression définitive. La période d'essai de 14 jours est automatiquement attribuée à l'inscription avec les droits du niveau Premium Mensuel.

**Article 5 — Propriété intellectuelle**
> Tous les contenus, y compris mais sans s'y limiter, les textes, images, logos et designs de l'application, sont la propriété de Couture Pro ou de ses concédants de licence et sont protégés par les lois sur le droit d'auteur. Vous n'êtes pas autorisé à reproduire, distribuer, ou créer des œuvres dérivées sans autorisation préalable.

**Article 6 — Responsabilité**
> L'application est fournie telle quelle et Couture Pro ne garantit pas son fonctionnement ininterrompu ou exempt d'erreurs. Vous reconnaissez que l'utilisation de l'application est à vos propres risques. Couture Pro ne pourra être tenu responsable des dommages directs ou indirects résultant de l'utilisation de l'application.

**Article 7 — Données personnelles**
> Nous respectons votre vie privée. La collecte et l'utilisation de vos données personnelles sont régies par notre Politique de confidentialité. En utilisant l'application, vous consentez à la collecte et à l'utilisation de vos données personnelles conformément à cette politique.

**Article 8 — Modifications des conditions**
> Couture Pro se réserve le droit de modifier ces conditions d'utilisation à tout moment. Nous vous informerons de toute modification en publiant la version mise à jour dans l'application. Votre utilisation continue de l'application après une modification constitue votre acceptation des nouvelles conditions.

**Article 9 — Résiliation**
> Nous nous réservons le droit de suspendre ou de résilier votre accès à l'application, sans préavis, en cas de violation des présentes conditions. En cas de résiliation, vos données sont conservées 30 jours avant suppression définitive.

**Article 10 — Loi applicable**
> Ces conditions d'utilisation sont régies par les lois du Bénin. En cas de litige, vous consentez à vous soumettre à la juridiction exclusive des tribunaux du Bénin.

**Article 10 bis — Procédure de récupération d'urgence**
> En cas de perte simultanée de l'accès à votre numéro de téléphone et à votre adresse email d'inscription, Couture Pro met à disposition une procédure de récupération manuelle via son service client. Cette procédure nécessite une vérification d'identité par appel téléphonique avec présentation de justificatifs. Couture Pro se réserve le droit de refuser toute demande ne satisfaisant pas aux critères de vérification d'identité. Contactez le service client au +229 01 66 55 29 92.

**Article 11 — Acceptation de ces conditions**
> En utilisant notre application, vous reconnaissez avoir lu et compris ces conditions d'utilisation et acceptez leurs termes.

### A.2 Politique de Confidentialité

**Dernière mise à jour : [DATE DE LANCEMENT OFFICIEL]**

**Article 1 — Introduction**
> Cette politique de confidentialité décrit comment Couture Pro collecte, utilise, partage et protège les informations personnelles de nos utilisateurs.

**Article 2 — Informations que nous collectons**
> — Informations d'inscription : Nom de l'atelier, nom du propriétaire, indicatif pays, numéro de téléphone (identifiant pivot), adresse e-mail et mot de passe.
> — Données de mesure : Mesures de vos clients, catégories de mesure et types de vêtements.
> — Informations sur les commandes : Détails des commandes, montant, statut et autres informations associées.
> — Données de paiement : Informations pour le traitement des abonnements. Nous ne stockons pas vos informations de carte de crédit.
> — Images : Photos de profil client et photos de tissu stockées localement sur votre appareil uniquement. Photos de modèles (album VIP) stockées sur Cloudflare R2 pour les abonnements Premium et Magnat.
> — Données locales de l'appareil pour le fonctionnement hors-ligne.

**Article 3 — Utilisation des informations**
> Nous utilisons vos informations pour créer et gérer votre compte, fournir et améliorer nos services, gérer vos commandes et abonnements, communiquer avec vous, envoyer des mises à jour et promotions, et analyser l'utilisation pour améliorer notre service.

**Article 4 — Partage des informations**
> Nous ne vendons ni ne louons vos informations personnelles à des tiers. Nous pouvons partager vos informations uniquement avec des prestataires de services tiers qui nous aident à exploiter l'application, pour respecter la loi ou répondre à des demandes légales, et pour protéger nos droits et ceux de nos utilisateurs.

**Article 5 — Protection des données**
> Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos informations personnelles. Les données sont chiffrées en transit et au repos. Aucune méthode de transmission sur Internet n'est totalement sécurisée.

**Article 6 — Vos droits**
> Vous avez le droit d'accéder à vos informations personnelles, de demander leur correction ou suppression, de retirer votre consentement à l'utilisation de vos données, et de déposer une plainte auprès de l'autorité de protection des données.

**Article 7 — Modifications de cette politique**
> Nous nous réservons le droit de modifier cette politique à tout moment. Nous vous informerons de toute modification en publiant la version mise à jour dans l'application.

**Article 8 — Stockage local et préférences applicatives**
> Notre application utilise le stockage local de votre appareil pour sauvegarder vos préférences et assurer le fonctionnement hors-ligne. Ces données restent sur votre appareil et ne sont partagées avec nos serveurs que lors des synchronisations.

**Article 9 — Conservation des données**
> Nous conservons vos informations personnelles aussi longtemps que nécessaire. En cas de résiliation de compte, vos données sont conservées 30 jours avant suppression définitive et sécurisée.

**Article 10 — Contact**
> Si vous avez des questions concernant cette politique, contactez-nous : Téléphone : +229 01 66 55 29 92

**Article 11 — Acceptation de cette politique**
> En utilisant notre application, vous reconnaissez avoir lu et compris cette politique de confidentialité et acceptez ses termes.

---

## ANNEXE B — 20 MODÈLES DE VÊTEMENTS ET MESURES STANDARD

**🆕 V1.3 — Templates prédéfinis chargés à la première connexion — modifiables par le Gérant**

| #  | Modèle                          | Mesures standard à renseigner |
|----|--------------------------------|-------------------------------|
| 1  | Boubou traditionnel homme      | Longueur totale, Tour de poitrine, Tour de cou, Longueur manche, Tour de poignet, Épaule à épaule, Tour de taille |
| 2  | Chemise africaine (col mao/brodée) | Tour de poitrine, Tour de cou, Longueur totale, Longueur manche, Tour de poignet, Épaule à épaule |
| 3  | Pantalon africain (avec/sans broderie) | Tour de taille, Tour de bassin, Longueur totale, Hauteur entrejambe, Tour de cuisse, Tour de bas |
| 4  | Agbada (grande tenue 3 pièces) | Longueur agbada, Tour de poitrine, Tour de cou, Longueur manche, Tour de poignet, Largeur épaule, Longueur tunique intérieure |
| 5  | Dashiki (haut coloré)           | Longueur totale, Tour de poitrine, Tour de cou, Tour de taille, Épaule à épaule, Longueur manche |
| 6  | Tunique longue homme (style sénégalais) | Longueur totale, Tour de poitrine, Tour de cou, Tour de taille, Épaule à épaule, Longueur manche |
| 7  | Robe africaine simple (femme)   | Longueur totale, Tour de poitrine, Tour de taille, Tour de hanche, Tour de bras, Tour de cou, Carrure épaule |
| 8  | Robe pagne stylisée (avec ceinture ou évasée) | Longueur robe, Tour de poitrine, Tour de taille, Tour de hanche, Longueur manche, Tour de bras, Hauteur taille |
| 9  | Jupe pagne droite ou évasée     | Tour de taille, Tour de hanche, Longueur jupe, Hauteur taille, Tour de bas |
| 10 | Taille haute femme (ensemble pagne) | Tour de taille, Tour de hanche, Longueur pantalon/jupe, Tour de cuisse, Tour de bas |
| 11 | Chemisier femme africain        | Tour de poitrine, Tour de taille, Longueur totale, Longueur manche, Tour de bras, Épaule à épaule |
| 12 | Tenue enfant africaine (mixte)  | Tour de poitrine, Tour de taille, Longueur haut, Longueur bas, Tour de bras, Tour de cuisse |
| 13 | Chemise manche longue (homme)   | Tour de poitrine, Tour de cou, Longueur manche, Longueur totale, Tour de poignet, Épaule à épaule |
| 14 | Pantalon classique (costume)    | Tour de taille, Tour de hanche, Longueur totale, Hauteur entrejambe, Tour de cuisse, Tour de bas |
| 15 | T-shirt (col rond ou col V)     | Tour de poitrine, Tour de cou, Longueur totale, Épaule à épaule, Longueur manche |
| 16 | Veste de costume                | Tour de poitrine, Tour de taille, Tour de cou, Longueur totale, Longueur manche, Épaule à épaule, Tour de poignet |
| 17 | Robe droite européenne          | Tour de poitrine, Tour de taille, Tour de hanche, Longueur robe, Carrure épaule, Tour de bras |
| 18 | Jupe crayon (femme)             | Tour de taille, Tour de hanche, Longueur jupe, Hauteur taille, Tour de bas |
| 19 | Short (homme/femme)             | Tour de taille, Tour de hanche, Longueur totale, Tour de cuisse, Tour de bas |
| 20 | Chemisier moderne (femme)       | Tour de poitrine, Tour de taille, Longueur totale, Longueur manche, Épaule à épaule, Tour de bras |

---

## ANNEXE C — FONCTIONNALITÉS RÉSERVÉES AUX VERSIONS FUTURES

| Fonctionnalité                     | Description                                           | Priorité future |
|------------------------------------|-------------------------------------------------------|-----------------|
| Gestion de stock tissu/fil         | Tracer si le pagne apporté par le client est suffisant, gérer les chutes à rendre | Phase 3 ou 4 |
| Module Caisse complet              | Suivi acomptes, dettes, tableau de bord financier     | Phase 2 |
| Catalogue inspiration              | Flux d'images type mini Pinterest intégré             | Phase 4 |
| Rappel SMS automatique             | En complément du WhatsApp, envoi SMS pour les clients sans WhatsApp | Phase 3 |
| Facturation universelle            | Facture disponible pour tous les niveaux d'abonnement | À étudier |

---

*— Fin du Document —*

**Couture Pro — CDCF Version 1.3 — Avril 2026 — Document Confidentiel**