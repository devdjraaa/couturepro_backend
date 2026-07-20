# Consolidation globale — tout ce qui a été demandé, rien d'oublié

> Demande direction du 20/07 : centraliser **l'ensemble** des améliorations,
> corrections et évolutions transmises à ce jour, pour confirmation avant la
> suite. Générée le 20/07/2026 par extraction mécanique des suivis —
> **aucune ligne n'a été retirée à la main**, chaque item garde sa source.
>
> Tracker interactif : https://gextimo.novafriq.africa/suivi-v2.html
> (107 items, 77 faits — les 7 documents de retours de juillet + décisions Avis v2)

## Vue d'ensemble

| Source | Total | Faits | Encore ouverts |
|---|---|---|---|
| SUIVI_NOVAFRIQ.md (ancien) | 212 | 156 | 52 (37 partiels, 12 à faire, 3 spec) |
| SUIVI_SPEC_130.md (ancien) | 99 | 44 | 43 (34 partiels, 8 à faire, 1 bug) |
| SUIVI_BACKEND.md (courant) | 75 | 53 | 4 |
| SUIVI_FRONTEND.md (courant) | 50 | 23 | 23 |
| Tracker v2 (courant, source de référence) | 107 | 77 | 28 |

⚠️ Les items des anciens fichiers **recoupent largement** les suivis courants
(les partiels de la spec 130 ont pour la plupart été achevés ensuite). Le
détail ci-dessous permet de le vérifier ligne par ligne — c'est volontairement
redondant : mieux vaut un doublon qu'un oubli.

---

## Ancien suivi général (déprécié) — `docs/SUIVI_NOVAFRIQ.md` (59 lignes ouvertes)

- ⬜ **6** : Menus header vitrine Solutions / Tarifs / Documentation (déroulants façon Kkiapay) — absents — 6 — V1-P181-184
- ⬜ **7** : Barre de contact fine au-dessus du header (tél + WhatsApp +229) — absente — 6 — V1-P180
- 🟡 **17** : Cloudflare ✅ migré (novafriq.africa actif/protégé ; DNSSEC indispo .africa) + sitemaps/robots ✅ live ; restent proxy phase 2 + soumission Search Console — 6 — V1-P197, P199
- 🟡 **4** : Recommandation / boost — ✅ scoring nocturne + boost payant (sponsorisation) + reco v1 (designers favoris en tête de galerie pour le client connecté) ; reste : reco par catégorie (nécessite catégoriser les créations)
- 🟡 **5** : Veille opportunités/concours — 🟡 externe — Chantier n8n du collègue (hébergement : Oracle Free vs Hetzner, décision direction) — ne pas dupliquer côté app
- ⬜ **P1** : Espace admin : créer codes d'activation/promo — 3
- 🟡 **P21** : Sélecteur de quantité par vêtement — 1 — 🟡 à confirmer
- 🟡 **P28** : Défilement horizontal des catégories — 1
- 🟡 **P46** : Module abonnement peu visible — 1
- 🟡 **P48** : MAJ abonnement sur tous les ateliers — 1
- 🟡 **P50** : Premium annuel assistants/viewers — config DB correcte (assistants=2, membres=5) → re-tester runtime — 1
- 🟡 **P51** : Magnat annuel 7 ateliers — config DB correcte (max_sous_ateliers=7) → re-tester runtime — 1
- ⬜ **P53-55** : Comportement changement/downgrade de plan — 1 — ⬜ à définir
- 🟡 **P58** : Bouton → page abonnements/plan conseillé — 1
- 🟡 **P66-67** : Validation champ téléphone (chiffres + « + ») — 1
- 🟡 **P68-77** : Recherche client cross-ateliers + mesures partagées — 1 — ⬜/🟡
- 🟡 **P82-91** : Module facturation par plan + modèles + preview — 1 — ✅/🟡
- 🟡 **P104-106** : Permissions au changement de plan — snapshot régénéré au sync + à l'activation ; immédiateté UI/P106 à re-tester — 1
- 🟡 **P107** : Transitions d'écran plus fluides — 1
- 🟡 **P109** : Expérience mobile (petits écrans, réseau lent) — 1
- 🟡 **P114** : Tâches longues en arrière-plan — 1
- 🟡 **P115-117** : Offline / file de sync « en attente » — 1 — ✅/🟡
- 🟡 **P118** : Séparation stricte + sync fiable (synthèse) — 1
- 🟡 **P128** : Police gothique cohérente (nom stylisé) — 6
- 🟡 **P129** : Logo ciseaux (login) → logo officiel — 1 — ✅/🟡
- 🟡 **P131** : Bouton « S'inscrire » absent sur mobile — 6 — 🟡 à confirmer
- ⬜ **P132** : Header/retour sur pages inscription-connexion — 6
- 🟡 **P135** : Détection pays/devise + message — 1/6
- 🟡 **P136** : Bandeau cookies + personnalisation — 6 — ✅/🟡
- ⬜ **P138** : Footer « devenir créateur » : tarif — 6
- 🟡 **P139** : CGV complètes (marketplace) — 6
- 🟡 **P140** : Renommer Mentions→CGU, page légale unique — 6
- 🟡 **P142** : Footer page confidentialité (liens légaux) — 6
- 🟡 **P143** : Nom du menu « Galerie des artisans » — 1
- 🟡 **P145** : Indicateur « FR » mais texte EN — audit code OK (i18n défaut/ fallback FR, LangContext synchro i18n via cp_lang, 0 clé FR manquante, 0 EN en dur en auth) ; re-test visuel chef sur l'écran précis — 1 — 🟡 audit
- 🟡 **P149** : Récup via « mot de passe oublié » (OTP e-mail) — 1 — ✅/🟡
- 🟡 **P152** : Bibliothèque photos catégorisée (réf/sexe/occasion…) — 1
- 🔵 **P165** : Paiement 2 phases (mise en relation → commission 15%) — 1 — 🔵 (business)
- 🟡 **P167** : Messages = communications officielles (lecture) — 1
- 🟡 **P178** : Mention « Bientôt / Soon » sur non-prêt — 1
- ⬜ **P180** : Barre de contact fine (tél + WhatsApp) — 6
- ⬜ **P181** : Menu déroulant « Solutions » — 6
- ⬜ **P182** : Menu déroulant « Tarifs » — 6
- ⬜ **P183-184** : Menu « Documentation » (cartes) + page — 6
- 🟡 **P187** : Texte d'accueil page inscription — 6
- ⬜ **P189** : E-mail `support@gextimo.africa` erroné — 5
- 🟡 **P190** : Contenu footer depuis doc APDP — 6
- 🟡 **P192** : Nouvelle phrase d'accroche — 6
- 🟡 **P193** : Phrase « né en Afrique, pour le monde » — 6
- 🟡 **P194** : Logo à gauche / bouton connexion à droite — 6
- 🟡 **P197** : Migration Cloudflare (NS Namecheap) — 6 — ✅ migré ; proxy/SSL phase 2 🟡
- 🟡 **P199** : Search Console + Bing + sitemap.xml — 6 — 🟡 (sitemaps + fichier vérif prêts ; soumission à finir)
- 🟡 **P201** : Alerte inscription + messages bienvenue/retour — 5 — 🟡 (dit fait)
- ⬜ **P203** : Spec sécurité mobile v5 + sauvegardes VPS — 4 — 🔵/⬜
- 🔵 **P204** : Note « Partenaires » (doc maître) — 1
- 🟡 **P205** : Coordination lancement commun — 6 — 🟡 (site novafriq.africa en ligne)
- 🟡 **SUG-1** : Splash screen (favicon → logo → connexion) — 7
- 🟡 **SUG-23** : Mieux valoriser les cartes d'abonnement — 1
- 🟡 **SUG-24** : Revue UX générale (cohérence, sécu Android) — 1

## Spécification 130 points (dépréciée) — `docs/SUIVI_SPEC_130.md` (43 lignes ouvertes)

- 🟡 **1-3** : Libellé vidéo, import direct, réorg interface vidéo — 🟡 à re-tester
- 🟡 **4-7** : Boutons/onglets Outils créatifs — 🟡 à re-tester
- 🟡 **8-9** : Plusieurs méthodes de connexion/inscription — 🟡 Google + OTP e-mail en place ; autres méthodes = pt 130 (décision)
- ⬜ **11** : Newsletter facultative
- 🟡 **12** : Message de confiance
- 🟡 **15** : Server Error (connexion client) — 🟡 à confirmer (≠ pt 77 déjà réglé)
- 🟡 **16-22** : Onboarding + récupération de compte client — 🟡 espace sans mot de passe (OTP) → récupération inhérente ; onboarding client ⬜
- ⬜ **24** : Notifications client
- ⬜ **25** : Messagerie client ↔ créateur — ⬜ (nouveau module)
- 🟡 **29** : Recherche
- ⬜ **30** : Code promo client
- 🟡 **46** : Consentement structuré à l'inscription
- 🟡 **47** : Nom de famille en majuscules — 🟡 à confirmer
- 🟡 **49** : Mot anglais résiduel — 🟡 à localiser
- 🟡 **50-52** : Filigrane + pied de page PDF mesures, branding WhatsApp
- 🟡 **55** : Réseaux sociaux profil Designer — 🟡 (champs socials en base)
- 🟡 **56** : Type de document (vérification Designer)
- ⬜ **67** : Onboarding 3 étapes
- 🟡 **68** : Refonte flux création commande — 🟡 renommage + client visible + menu déroulant ✅ (18/07) ; « Éditer les mesures » inline = à itérer sur device
- 🟡 **69** : Mesures définies par type de vêtement — 🟡 décidé : mesures PAR CLIENT (pas de refonte modèle) ; `libelles_mesures` par vêtement existe ; chargement auto à la commande = à itérer device
- 🟡 **72** : Fidélité : 1 client = 1 point
- ⬜ **75** : Historique des mises à jour
- 🟡 **78** : Texte standard case CGU + Politique
- 🟡 **80** : Logo page de connexion
- 🟡 **82** : Texte « espace sur l'app mobile »
- 🟡 **84** : Ordre rubriques Paramètres
- 🟡 **85** : Bug « Ajouter une ville »
- 🟡 **86-89** : Sidebar : scroll, état actif, logo, hamburger — 🟡 à re-tester
- 🟡 **90** : Profil : format Nom/Prénom + champs
- 🟡 **91** : Avatar 404
- 🟡 **92** : Tickets : images + horodatage
- 🟡 **93** : Revue des liens internes
- 🟡 **94-95** : 54 pays africains + affichage
- 🟡 **97** : Bug déconnexion
- ⬜ **98** : Bulle flottante retour vitrine
- ⚠️ **99-100** : Sync Web/Mobile : caisse + catalogues — ⚠️ à reproduire sur device
- 🟡 **103** : Compte à rebours de lancement
- 🟡 **104** : Messages de récupération
- 🟡 **106** : Import contacts anti-doublon
- 🟡 **107** : Méthodes d'inscription complémentaires — 🟡 (cf. pt 130)
- 🟡 **109** : Ancrage/scroll boutons header
- 🟡 **123** : Conformité (code du numérique Bénin + RGPD) — 🟡 compléments rédigés selon standards APDP Bénin/RGPD et marqués pour relecture légale (relecture par un juriste = tâche équipe)
- ⬜ **125** : Rendu serveur / pré-rendu des pages clés — ⬜ reco prête (à valider avant prod)

## Suivi backend courant — `docs/SUIVI_BACKEND.md` (8 lignes ouvertes)

- ⚠️ **S08C-30** : Moyens de paiement → FedaPay uniquement — ✅ — FAIT (20/07) — liste unique éditable en admin, FedaPay seul en V1, `GET /moyens-paiement` comme source du front, et le serveur VALIDE enfin le mode reçu. ⚠️ Les anciennes valeurs restent tolérées le temps que le front bascule, sinon la création de factures casserait en production. ↔ `SUIVI_FRONTEND.md#S08C-30`
- 🟡 **S08C-31b** : Corriger les anomalies de fidélité — Défauts techniques CORRIGÉS (20/07) : les 3 règles inexistantes retirées de l'interface, permission `points.convert` appliquée, crédit désormais actif aussi sur le chemin web (il ne fonctionnait qu'en synchro hors ligne — idempotence vérifiée), description « Commande validée » → « Commande créée ». RESTE UNE DÉCISION COMMERCIALE : le programme est *mathématique
- ⚠️ **PHOTO-1** : Contrôle qualité automatique — ✅ — FAIT (20/07) — analyse synchrone à l'envoi (résolution, luminosité, netteté, cadrage), retour en CODES traduits en icônes côté interface. Photo refusée = non conservée, reprise illimitée sans pénalité. ⚠️ La netteté est en AVERTISSEMENT, pas en blocage : mesuré, une photo nette réaliste score ~46 quand un damier flouté dépasse 3000 — un seuil absolu non calibré r
- ⚠️ **ABO-9** : ⚠️ Migration des abonnements anonymes — ✅ — FAIT (20/07, décision 3a) — remise à plat : les abonnements anonymes sont supprimés (1 ligne, sauvegardée dans `~/backup-abonnes-anonymes-*.json` sur le VPS avant suppression). Les compteurs repartent de zéro sur une base fiable.
- ⚠️ **VID-5** : Validation obligatoire avant publication — ✅ — FAIT (20/07) — soumission → en attente → validée/refusée sous 24 h, notifications, file admin avec compte à rebours. Refus = quota restitué. ⚠️ Corrigé au passage : la vitrine n'était pas filtrée — une vidéo en attente ou refusée s'affichait publiquement.
- ⬜ **REL-2** : Pré-rendu SEO (pt 125) — Recommandation prête, à valider avant de toucher la production. La vitrine est une application monopage : un robot sans JavaScript reçoit une coquille vide.
- ⬜ **REL-3** : « Mes Réalisations » sur mobile — Cache hors-ligne (100 brouillons/attente) à ajouter côté application native.
- 🟡 **REL-4** : Flux « Éditer les mesures » (pts 68-69) — Débloqué le 20/07 — l'étiquette « test appareil » cachait le vrai problème : la colonne `libelles_mesures` avait été supprimée en avril (le modèle Eloquent la référençait encore). Colonne rétablie, création/édition d'un modèle acceptent la liste, éditeur front + panneau `MesuresInline` en commande (fusion, jamais d'écrasement). Reste : le test sur appareil

## Suivi frontend courant (Aquilas) — `docs/SUIVI_FRONTEND.md` (27 lignes ouvertes)

- ⚠️ **S02A-25** : Bouton « Modifier » → « Enregistrer » — ✅ — ✅ Fait (web). Libellé « Enregistrer » en édition, via `t()` (`useTranslation` ajouté). ⚠️ Le portage `android` N'A PAS été fait : les branches ont divergé de 247 commits côté master et 288 côté android — c'est un chantier de réalignement (fusion + build APK + test appareil), pas un portage de fichier. Tracé en REL-V4.
- ⚠️ **S02A-28** : Limites en dur à brancher sur le plan — ✅ — ✅ Fait. Limites issues du plan : `max_photos_vetement` (via `usePlanLimit`) et `max_photos_realisation` (via `/realisations/quota`), avec l'ancienne valeur en repli. ⚠️ Découvert au passage : le serveur n'imposait aucune limite sur les photos de modèle — le front s'arrêtait à 5, l'API en acceptait autant qu'on voulait. Plafond appliqué côté serveur à la 
- ⬜ **PHOTO-1** : Retour qualité instantané et visuel — À l'envoi d'une photo, retour immédiat sans aucun texte à lire : icône, couleur, courte animation — compréhensible par tout utilisateur, quelle que soit sa langue. Si refusée, l'utilisateur peut reprendre autant de fois qu'il veut, sans pénalité. ↔ l'analyse (netteté, luminosité, résolution, cadrage) est côté serveur.
- 🟡 **PHOTO-3** : Écran de modération admin — Une file de modération existe déjà (`src/pages/admin/AdminRealisationsPage.jsx`, approuver / refuser avec motif). À ajouter : compte à rebours des 24 h et action « retoucher légèrement puis valider » (recadrage, ajustements simples) pour les designers peu équipés.
- ⬜ **PHOTO-7** : Historique / traçabilité — Côté admin, garder l'accès à la photo originale même après retouche.
- ⬜ **ANN-1** : Formulaire complet — Titre, message, image facultative (bannière), date de début (calendrier), durée (sélecteur d'incrémentation).
- ⬜ **ANN-2** : Durée 1 → 10 jours — Le designer choisit un nombre de jours, jamais une date de fin (calculée par le serveur). Publication gratuite quelle que soit la durée.
- ⬜ **ANN-3** : Une annonce par jour + message d'information — Après publication, création bloquée jusqu'au lendemain. Encart (icône i) : « Chaque designer peut publier une seule annonce par jour. Pour augmenter la visibilité de votre annonce, utilisez la fonctionnalité Boost. »
- ⬜ **ANN-4** : Historique sous le formulaire — Liste des annonces publiées avec leur statut : En cours / Terminée / Expirée / Boostée.
- ⬜ **ANN-5** : Bouton « Boost » + fenêtre modale — Bouton discret sur chaque annonce encore active. Modale : date de début (calendrier) + durée 1 / 3 / 7 jours. Le boost peut démarrer plus tard, tant que l'annonce est active.
- ⬜ **ANN-6** : Tarif automatique — 1 j = 100 F · 3 j = 200 F · 7 j = 300 F, affiché automatiquement selon la durée, champ non modifiable. Puis tunnel de paiement existant.
- ⬜ **ANN-7** : Information sur le boost — Encart (icône i) dans la modale : « Pendant toute la durée du Boost, votre annonce sera diffusée trois fois par jour afin d'augmenter sa visibilité. »
- ⬜ **ANN-8** : Bande d'annonces défilante — En haut de l'application, légère et élégante (esprit bandeau d'information TV), sans gêner l'expérience. → Réutiliser `.gx-marquee` (`src/index.css`), déjà en place pour les partenaires. ↔ j'expose le flux des annonces actives.
- ⬜ **ANN-9** : Gestion de l'image + Aperçu — Avec image : bannière affichée, message en dessous. Sans image : texte seul, centré dans la bande. Bouton « Aperçu » avant publication pour vérifier le rendu final (texte, cadrage, lisibilité).
- ⚠️ **ABO-1** : Vérifier la session au clic — ✅ — ✅ Fait. ⚠️ C'était devenu une régression : le serveur exigeait déjà un compte (401) mais le front envoyait une clé anonyme et `postJson` aplatissait toute erreur sur `null` — chaque clic « Suivre » échouait en silence. `postDetaille` rend le statut, l'affichage optimiste est annulé sur refus, et un 401 déclenche la connexion.
- ⬜ **ABO-2** : Inscription simplifiée à la volée — Le module s'ouvre tout seul (l'utilisateur ne doit pas le chercher). Seule information obligatoire : l'adresse e-mail. ⚠️ Si l'utilisateur ferme ou abandonne avant validation : aucun compte incomplet, aucun abonnement enregistré. ℹ️ Le socle existe déjà côté serveur (code e-mail + Google).
- ⚠️ **EC-4** : Gestion des échecs — ✅ — ✅ Fait. Succès et échec de la reprise sont annoncés. ⚠️ Au passage : le composant `<Toaster />` n'était monté nulle part — les 41 appels `toast.success`/`toast.error` de toute l'application ne produisaient rien. Corrigé dans `main.jsx`.
- ⬜ **VID-1** : Lecture intégrée (embed) — Aujourd'hui un lien YouTube renvoie vers YouTube (simple lien sortant, dans `src/pages/vitrine/CreateurProfilPage.jsx` et `src/pages/StudioPage.jsx`). Attendu, à la manière de Notion : vidéo intégrée dans une carte, lecture sans quitter Gextimo, avec lecture/pause, barre de progression, muet, volume, plein écran, + bouton pour ouvrir sur YouTube. Cartes de taille uniform
- ⬜ **VID-3** : Règles de modification — Gratuit : une seule vidéo, une nouvelle remplace l'ancienne, aucune correction/suppression mensuelle. Atelier : 1 correction/suppression par mois. Studio : 2. Compteur mensuel affiché. ↔ la route de modification n'existe pas encore côté serveur.
- ⬜ **VID-4** : Import direct de vidéo — Deux entrées possibles : lien YouTube ou import d'un fichier (tous les créateurs n'ont pas de chaîne). Mode d'affichage adapté selon la source.
- 🟡 **VID-5** : Statut « en attente de validation » — 🟡 Partiel. Statut de modération affiché par vidéo côté créateur (en validation / refusée, motif en infobulle). Restant : l'écran de validation côté admin — aucune page admin vidéos n'existe côté front.
- ⬜ **SUP-1** : Encart d'information dans les tickets — Dans `src/pages/SupportPage.jsx`, ajouter un encart permanent (icône « i » ou bannière) : « Pour vos réclamations, suggestions d'amélioration, remarques, demandes d'assistance ou toute autre requête, veuillez créer un ticket afin de nous en informer. Notre équipe vous répondra dans les meilleurs délais. » ⚠️ Aujourd'hui le seul texte pédagogique est dans l'é
- ⬜ **AV2-F4** : UI admin de modération — Pour toi : écran des réglages (`GET/PUT admin/vitrine/moderation-avis` : seuils, motifs graves, mots bannis) + file photos en attente (`GET admin/avis?filtre=photos`, `POST admin/avis/{id}/photos` action valider/refuser). L'API est prête et documentée.
- ⬜ **REL-V1** : Points vitrine ouverts — Reprendre le contenu encore ouvert de `VITRINE_TODO_FRONTEND.md` (barre de contact, header/footer, textes web). Ce fichier est déprécié : les points actifs sont à basculer ici au fil de l'eau.
- ⬜ **REL-V2** : Pré-rendu SEO — La vitrine est une application monopage : un robot sans JavaScript reçoit une coquille vide et le même titre sur toutes les pages. Recommandation prête (métadonnées par page + pré-rendu). ⚠️ À valider avant de toucher la production.
- ⬜ **REL-V3** : « Mes Réalisations » sur mobile — Ajouter le cache hors-ligne (100 brouillons/en attente) côté application native, branche `android`.
- ⚠️ **REL-V4** : Branche `android` désalignée — Constaté le 20/07 : `master` a 247 commits que `android` n'a pas, et `android` en a 288 en propre. L'application mobile ne reçoit donc plus les correctifs web depuis longtemps, et « porter un correctif » n'a plus de sens fichier par fichier. C'est un chantier de réalignement à planifier (fusion, résolution de conflits, build APK, test sur appareil) — à ne pas improvi

---

## Ce qu'il reste à faire pour la confirmation à la direction

1. Chaque ligne 🟡/⬜ des anciens fichiers doit être pointée : *fait depuis* /
   *repris dans le tracker v2* / *à ajouter*. Le recoupement mécanique garantit
   qu'aucune n'a disparu ; le tri fin se fait ligne à ligne.
2. Une fois le tri terminé, réponse à la direction : « tout est pris en compte »,
   avec ce fichier en preuve.
