⛔ **PÉRIMÉ — NE PLUS METTRE À JOUR.** (depuis le 19/07/2026)
Le suivi frontend est désormais centralisé dans **`SUIVI_FRONTEND.md`** (Aquilas), ligne `REL-V1`.
*Ce fichier reste la source des points vitrine encore ouverts, à basculer au fil de l'eau.*

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

### ⬜ P180 — Barre de contact fine au-dessus du header
Ajouter, juste au-dessus du header, une fine barre de contact : fond sombre, alignée à droite,
sur une seule ligne, avec une icône téléphone suivie du numéro (**+229 01 91 47 96 28**), un
séparateur vertical, puis une icône WhatsApp avec le même numéro. Style sobre, hauteur fine
(~36-40 px), texte blanc.

### ⬜ P181 — Menu déroulant « Solutions »
Entre « Comment ça marche » et « Créateurs », avec : **Application Mobile Artisan** et
**Application Mobile Designer** (icône à gauche, couleurs rouge/noir Gextimo).
S'inspirer du menu déroulant « Solutions » de **kkiapay.com**.

### ⬜ P182 — Menu déroulant « Tarifs »
Avec : **Tarifs Artisans**, **Tarifs Designers**, **Tarifs Web** — même style que le menu Solutions.

### ⬜ P183-184 — Menu « Documentation » (cartes) + page
Menu déroulant « Documentation » avec 3 cartes (icône + titre + petite description) :
1. **Créer votre vitrine** — publier ses collections, gérer sa boutique en ligne ;
2. **Tarifs & Commissions** — comprendre les formules Gratuite/Standard/Premium avant de s'inscrire ;
3. **Vos données & sécurité** — ce que Gextimo collecte, droits de l'utilisateur, conformité APDP.

Disposition en colonne façon kkiapay.com, icônes colorées, fond blanc, texte sobre. Chaque carte
renvoie vers la bonne section de la page Documentation. La rubrique amène sur une **page en
cartes** ; chaque carte ouvre le contenu complet correspondant (**déjà conçu — voir le HTML
fourni par la direction**).

### ⬜ P132 — Header / retour sur les pages inscription-connexion
Aucun moyen de revenir en arrière depuis inscription/connexion. Le header doit être présent sur
toutes les pages — retour via clic sur le logo (→ accueil) ou icône « accueil » visible partout.

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
Il manque le tarif dans la section « Devenir créateur » du footer (une référence visuelle a été
envoyée séparément par la direction).

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
