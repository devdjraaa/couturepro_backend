⛔ **PÉRIMÉ — NE PLUS METTRE À JOUR.** (depuis le 19/07/2026)

✅ **Bascule terminée le 21/07/2026.** Ce fichier ne fait plus foi pour rien.

Les 19 points ont été repris : quatre étaient **déjà faits** (P135 détection pays/devise,
P143 menu Galerie des artisans, P190/P142 footer APDP, P128 police du nom stylisé), les
autres sont désormais suivis dans le **tracker HTML** sous `VIT-4` (header), `VIT-5`
(bouton d'inscription sur mobile) et `VIT-6` (footer et textes).

Le détail de chaque point reste lisible ci-dessous, comme référence — mais l'état
d'avancement, lui, se lit dans le tracker.

---

# Vitrine Gextimo — À faire (frontend)

> Document extrait du suivi général NovAfriq le **16 juillet 2026**. Il regroupe **tout ce qui
> concerne la vitrine web publique** (`gextimo.novafriq.africa`, branche **master**,
> `src/pages/vitrine/`). Ces points ne sont **plus suivis dans le tracker HTML** — ce fichier
> fait foi pour le volet vitrine.
>
> Statuts : ⬜ à faire · 🟡 partiel (existe mais à compléter/corriger).

---

## 1. Header & navigation

### 🟡 P180 — Barre de contact fine au-dessus du header
**PARTIEL — vérifié le 22/07.** Une barre de contact existe (`ContactBar`, VitrineChrome.jsx) mais
autre contenu que la spec : e-mail, fond clair, centrée — au lieu de téléphone **+229 01 91 47 96 28**
+ WhatsApp, fond sombre, alignée à droite. À confirmer avec la direction/Aquilas : évolution
assumée du design, ou reste à faire tel quel spécifié.

### 🟡 P181 — Menu déroulant « Solutions »
**STRUCTURE FAITE — vérifié le 22/07.** `NavDropdown` existe et fonctionne, mais contenu différent :
Créateurs / Espace client / Artisans, au lieu de Application Mobile Artisan/Designer. Choix
probablement plus pertinent pour une vitrine WEB (liens app mobile ont peu de sens ici) — à
confirmer que c'est un choix assumé, pas un oubli.

### 🟡 P182 — Menu déroulant « Tarifs »
**STRUCTURE FAITE — vérifié le 22/07.** Contenu différent : Plans / Boost, au lieu de
Artisans/Designers/Web séparés — probablement une simplification assumée (un seul barème de plans
pour tous types de comptes). À confirmer.

### 🟡 P183-184 — Menu « Documentation » (cartes) + page
**PARTIEL — vérifié le 22/07.** Le menu déroulant existe mais renvoie vers des pages génériques
(Aide/Suivi/Qui sommes-nous), pas vers les 3 cartes spécifiques ci-dessous. Le HTML « déjà conçu,
fourni par la direction » n'est ni dans le dépôt ni accessible pour cette session — à transmettre.
Menu déroulant « Documentation » avec 3 cartes (icône + titre + petite description) :
1. **Créer votre vitrine** — publier ses collections, gérer sa boutique en ligne ;
2. **Tarifs & Commissions** — comprendre les formules Gratuite/Standard/Premium avant de s'inscrire ;
3. **Vos données & sécurité** — ce que Gextimo collecte, droits de l'utilisateur, conformité APDP.

Disposition en colonne façon kkiapay.com, icônes colorées, fond blanc, texte sobre. Chaque carte
renvoie vers la bonne section de la page Documentation. La rubrique amène sur une **page en
cartes** ; chaque carte ouvre le contenu complet correspondant (**déjà conçu — voir le HTML
fourni par la direction**).

### ✅ P132 — Header / retour sur les pages inscription-connexion
**FAIT — vérifié le 22/07.** Header simplifié explicitement tagué « VIT-2 — P132 » dans
VitrineChrome.jsx : logo cliquable vers l'accueil + lien « retour à la vitrine ».

### 🟡 P131 — Bouton « S'inscrire » absent sur mobile
Visible sur desktop mais pas sur la version mobile du header. À ajouter sur mobile aussi.

### 🟡 P194 — Alignement header
Pousser le logo un peu plus vers la **gauche**, et le bouton « Se connecter » un peu plus vers la
**droite**.

### ℹ️ P179 — Rappel de l'existant (pour contexte)
Header actuel : « Comment ça marche », « Créateurs », « Artisans », « Collections », « Suivi »,
« Aide », « Qui sommes-nous », icône mode sombre, sélecteur de devise (XOF), sélecteur de langue
(FR/EN), bouton « S'inscrire », bouton « Se connecter » (rouge).

---

## 2. Footer & pages légales

### ⬜ P138 — Footer « Devenir créateur » : afficher le tarif
**TOUJOURS OUVERT — vérifié le 22/07**, absent du footer. Bloqué sur la référence visuelle de la
direction, non retrouvée dans cette session.

### 🟡 P139 — CGV complètes (marketplace)
Il manque les CGV, indispensables pour une marketplace. Un document existe mais incomplet — une
**nouvelle version plus complète sera transmise** pour remplacer l'ancienne.

### 🟡 P140 — « Mentions légales » → « CGU », page unique
Renommer « Mentions légales » en « CGU », mettre le lien en avant, et faire atterrir sur **une
seule page complète** qui regroupe tout : mentions légales, politique de confidentialité, CGV,
CGU, etc. (page unique plutôt que des liens éparpillés).

### 🟡 P142 — Footer de la page confidentialité
Sur la page politique de confidentialité, footer avec ces liens :
*© 2026 Novafriq — Conditions d'utilisation — Droits d'auteur & Droits des marques — Politique de
confidentialité — Avis relatif aux non-utilisateurs — Conditions Générales de Vente — Politique
d'usage des cookies — Annonces personnalisées.*

### 🟡 P190 — Contenu du footer depuis le doc APDP
Tout le contenu des menus du footer est déjà rédigé dans le **document général de conformité APDP
déjà fourni** — y prendre ce qui est utile et l'insérer au bon endroit.

---

## 3. Contenus & textes

### 🟡 P192 — Nouvelle phrase d'accroche
Remplacer : *« Nouveau — la vitrine Gextimo connecte les créateurs de mode africaine à toute
l'Afrique de l'Ouest. »*
Par : *« **Nouveau — Gextimo, la vitrine qui fait rayonner la mode africaine au-delà des
frontières.** »*

### 🟡 P193 — Phrase à intégrer quelque part sur le site
*« **Gextimo est né en Afrique, pour le monde.** »*

### 🟡 P187 — Texte de la page « S'inscrire »
Corriger titre + texte de la page ouverte au clic sur « S'inscrire » :
*« Votre vitrine de création africaine vous attend. Créez votre espace en 2 minutes, depuis votre
navigateur. Votre espace est accessible depuis le web dès votre inscription. Pour aller plus loin
— gérer vos commandes en mobilité, recevoir des notifications et accéder aux fonctionnalités
avancées — téléchargez l'application mobile. »*

### 🟡 P128 — Police gothique cohérente (nom stylisé)
Plusieurs versions de la police du nom stylisé coexistent. Garder **une seule** : toujours en
minuscules, avec la police « gothique » spéciale — réservée aux usages stylisés du nom, jamais
pour le texte courant.

### 🟡 P135 — Détection pays/devise + confirmation
Détecter automatiquement le pays du visiteur et afficher : *« Vous êtes en [pays], votre devise
sera [devise], et les frais de livraison seront calculés en conséquence. »* avec choix Oui/Non.
Si validé → tous les prix s'affichent dans la devise locale.

### 🟡 P143 — Menu « Galerie des artisans »
Nom du menu pas encore fixé (réflexion en cours côté direction). Le menu doit ouvrir une page à
défilement montrant toutes les photos/réalisations stockées sur la plateforme — pour que les
artisans consultent des photos et reproduisent ce que leurs clients leur montrent.

---

## Notes techniques (repères dans le code)

- Header/footer/nav : `src/pages/vitrine/VitrineChrome.jsx`
- Pages légales : `src/pages/vitrine/VitrineLegalPages.jsx` (routes déjà branchées dans `App.jsx`)
- Accueil/accroche : `src/pages/vitrine/VitrineHome.jsx` (clé i18n `vitrine.promo`)
- Page inscription vitrine : `src/pages/vitrine/InscriptionPage.jsx`
- Devise : `src/pages/vitrine/vitrineCurrency.jsx` (sélecteur existant — P135 = détection auto en plus)
- **Zéro hardcoding** : tout texte passe par `src/lang/fr.json` + `en.json` (règle projet).
- Branche : **master** (jamais d'import `@capacitor/*`/`@capgo/*` sur master ; `npm run build` avant push).
