# COUTURE PRO — Guide de Référence Design Frontend
## Identité Visuelle, Composants & Règles de Style

> **Fichier cible :** `docs/frontend_design_guide.md` dans le projet
> **Version :** 1.0 — Référence obligatoire pour tout développement frontend
> **Usage :** Avant de coder un composant ou une page, consulter ce guide. Coller ce fichier dans une conversation Claude avec la demande pour garantir la cohérence visuelle.

---

## 1. Direction Artistique — L'ADN Visuel

### Ton & Personnalité

Couture Pro s'adresse à des couturiers africains francophones. L'interface doit dégager :

- **Chaleur professionnelle** — ni froid/corporate, ni trop enfantin. Un équilibre entre sérieux métier et accessibilité
- **Artisanat moderne** — le couturier est un artisan créatif. L'app doit refléter ce mélange de tradition et modernité
- **Confiance** — l'utilisateur gère son business dessus, il doit sentir que l'outil est fiable et stable
- **Fluidité** — chaque interaction doit être naturelle, sans friction. L'app travaille pour le couturier, pas l'inverse

### Différenciateur mémorable

L'identité visuelle repose sur un concept : **"Le fil et le tissu"**. Des éléments visuels subtils rappellent la couture — courbes douces (comme un fil), textures tissées légères en fond, et une palette qui évoque les pagnes wax et les ateliers africains. Ce n'est pas littéral (pas de ciseaux partout), c'est une atmosphère.

### Ce qu'on NE fait PAS

- Pas de design générique type "SaaS template" blanc/bleu fade
- Pas de fonts génériques (Inter, Roboto, Arial, system-ui seul)
- Pas de gradients violet/rose clichés IA
- Pas de layouts identiques sur chaque page
- Pas de composants qui ressemblent à du Material UI par défaut
- Pas de blanc partout sans texture ni profondeur

---

## 2. Typographie

### Fonts choisies

| Usage | Font | Pourquoi |
|---|---|---|
| **Display / Titres** | **Outfit** | Géométrique mais chaleureuse, arrondie sans être enfantine. Poids variés pour créer de la hiérarchie. Caractère distinctif sans être excentrique |
| **Corps / UI** | **DM Sans** | Lisible, moderne, légèrement arrondie. Complémente parfaitement Outfit. Excellente lisibilité sur mobile même en petite taille |
| **Monospace (codes, chiffres)** | **JetBrains Mono** | Pour les codes d'activation, montants, IDs. Propre et technique |

### Import Google Fonts
```html
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

### Échelle typographique

```css
--font-display: 'Outfit', sans-serif;
--font-body: 'DM Sans', sans-serif;
--font-mono: 'JetBrains Mono', monospace;

/* Titres — Outfit */
--text-h1: 700 1.75rem/1.2 var(--font-display);    /* 28px — titres de page */
--text-h2: 600 1.375rem/1.3 var(--font-display);   /* 22px — sections */
--text-h3: 600 1.125rem/1.4 var(--font-display);   /* 18px — sous-sections */

/* Corps — DM Sans */
--text-body: 400 0.9375rem/1.5 var(--font-body);   /* 15px — corps principal */
--text-body-sm: 400 0.8125rem/1.5 var(--font-body); /* 13px — secondaire */
--text-label: 500 0.8125rem/1 var(--font-body);     /* 13px — labels, badges */
--text-caption: 400 0.75rem/1.4 var(--font-body);   /* 12px — captions, hints */
```

### Règles
- Les titres de page utilisent toujours `font-display` (Outfit)
- Le corps de texte et les éléments d'interface utilisent `font-body` (DM Sans)
- Les montants en XOF, codes de transaction et IDs utilisent `font-mono`
- Jamais de texte plus petit que 12px
- Le `letter-spacing` sur les titres Outfit : `-0.01em` pour resserrer légèrement

---

## 3. Palette de Couleurs

### Principe : Wax moderne

La palette s'inspire des tons chauds des pagnes wax africains, contrebalancés par des tons froids professionnels. Le résultat : chaleureux mais crédible.

```css
:root {
  /* ── Primaire — Indigo profond ── */
  --color-primary-50: #EEF2FF;
  --color-primary-100: #E0E7FF;
  --color-primary-200: #C7D2FE;
  --color-primary-300: #A5B4FC;
  --color-primary-400: #818CF8;
  --color-primary: #6366F1;           /* Indigo vibrant — boutons, liens, actions */
  --color-primary-600: #4F46E5;
  --color-primary-700: #4338CA;

  /* ── Accent — Ambre doré ── */
  --color-accent-50: #FFFBEB;
  --color-accent-100: #FEF3C7;
  --color-accent-200: #FDE68A;
  --color-accent: #F59E0B;            /* Or chaud — highlights, badges, récompenses */
  --color-accent-600: #D97706;

  /* ── Terracotta — Touch africaine ── */
  --color-terra: #C2410C;             /* Accent secondaire — alertes chaudes, accents */
  --color-terra-light: #EA580C;
  --color-terra-50: #FFF7ED;

  /* ── Sémantiques ── */
  --color-success: #059669;           /* Vert émeraude — pas le vert "bootstrap" */
  --color-warning: #D97706;
  --color-danger: #DC2626;
  --color-info: #6366F1;

  /* ── Surfaces — Thème clair ── */
  --color-bg-app: #FAFAF9;           /* Fond principal — légèrement chaud, pas blanc pur */
  --color-bg-card: #FFFFFF;
  --color-bg-elevated: #FFFFFF;
  --color-bg-subtle: #F5F5F4;        /* Fond secondaire — stone-100 */
  --color-bg-inset: #E7E5E4;         /* Fond enfoncé — inputs, zones désactivées */

  /* ── Texte ── */
  --color-text-primary: #1C1917;      /* Stone-900 — presque noir, chaud */
  --color-text-secondary: #57534E;    /* Stone-600 */
  --color-text-tertiary: #A8A29E;     /* Stone-400 */
  --color-text-inverse: #FAFAF9;

  /* ── Bordures ── */
  --color-border: #E7E5E4;           /* Stone-200 — subtile */
  --color-border-strong: #D6D3D1;    /* Stone-300 — séparations marquées */
}

/* ── Thème sombre ── */
[data-theme="dark"] {
  --color-bg-app: #1C1917;
  --color-bg-card: #292524;
  --color-bg-elevated: #44403C;
  --color-bg-subtle: #292524;
  --color-bg-inset: #1C1917;
  --color-text-primary: #FAFAF9;
  --color-text-secondary: #D6D3D1;
  --color-text-tertiary: #78716C;
  --color-border: #44403C;
  --color-border-strong: #57534E;
}
```

### Règles d'application
- **Fond de l'app** → `bg-app` (légèrement chaud, jamais blanc pur `#FFF`)
- **Cartes** → `bg-card` avec `shadow-sm` et `border` subtile
- **Actions principales** → `primary` (indigo) — boutons, FAB, liens
- **Récompenses, points, badges premium** → `accent` (ambre doré)
- **Alertes chaudes, retards** → `terra` (terracotta)
- **Succès** → `success` (émeraude)
- **Texte principal** → jamais du noir pur `#000`, toujours `text-primary` (stone-900 chaud)

---

## 4. Textures & Profondeur

### Fond de l'app — Texture tissée subtile

Un pattern SVG très discret en fond rappelant le tissage. Visible uniquement en regardant attentivement.

```css
.app-background {
  background-color: var(--color-bg-app);
  background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20v20H0z' fill='none'/%3E%3Cpath d='M0 10h20M10 0v20' stroke='%23e7e5e4' stroke-width='0.3'/%3E%3C/svg%3E");
  background-size: 20px 20px;
}

[data-theme="dark"] .app-background {
  background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h20v20H0z' fill='none'/%3E%3Cpath d='M0 10h20M10 0v20' stroke='%2344403C' stroke-width='0.3'/%3E%3C/svg%3E");
}
```

### Ombres — Douces et chaudes

Pas d'ombres grises froides. Les ombres ont une légère teinte chaude.

```css
--shadow-xs: 0 1px 2px rgba(28, 25, 23, 0.04);
--shadow-sm: 0 1px 3px rgba(28, 25, 23, 0.06), 0 1px 2px rgba(28, 25, 23, 0.04);
--shadow-md: 0 4px 8px rgba(28, 25, 23, 0.08), 0 2px 4px rgba(28, 25, 23, 0.04);
--shadow-lg: 0 12px 24px rgba(28, 25, 23, 0.10), 0 4px 8px rgba(28, 25, 23, 0.05);
--shadow-xl: 0 20px 40px rgba(28, 25, 23, 0.12);
```

### Coins arrondis — Généreux mais pas bulle

```css
--radius-sm: 0.5rem;     /* 8px — inputs, badges */
--radius-md: 0.75rem;    /* 12px — cartes, boutons */
--radius-lg: 1rem;       /* 16px — modals, bottom sheets */
--radius-xl: 1.5rem;     /* 24px — FAB, avatars */
--radius-full: 9999px;   /* Cercles */
```

---

## 5. Composants — Signatures Visuelles

> Chaque composant a son identité dans Couture Pro. Pas de composants génériques interchangeables avec n'importe quelle app.

### Boutons

```
Primaire :    bg-primary, text-white, radius-md, shadow-sm, font-display font-600
              Hover : bg-primary-600, shadow-md, scale légère (1.02)
              Active : bg-primary-700, scale 0.98

Secondaire :  bg-transparent, border border-primary, text-primary
              Hover : bg-primary-50

Ghost :       bg-transparent, text-secondary
              Hover : bg-subtle

Danger :      bg-danger, text-white
              Utiliser uniquement pour les suppressions définitives

FAB :         bg-primary, text-white, rounded-full, shadow-lg
              Taille : 56x56px, icône 24px
              Position : fixed bottom-right, au-dessus de la bottom nav
```

### Cartes

```
Structure :   bg-card, border border-border, radius-md, shadow-xs
              Padding : 16px mobile, 20px desktop
              Hover desktop : shadow-sm, translateY(-1px)

Client card : Avatar à gauche (48px), nom en font-display 600, téléphone en text-secondary
              Badge type_profil en haut à droite (couleur accent par type)

Commande card : Bande latérale gauche 3px colorée par statut
                en_cours = primary, livre = success, annule = danger, en retard = terra
```

### Inputs

```
Style :       bg-inset, border border-border, radius-sm, padding 12px 16px
              Focus : border-primary, ring-2 ring-primary-200, bg-card
              Label au-dessus en text-label (DM Sans 500)
              Placeholder en text-tertiary

Recherche :   Icône Search à gauche intégrée dans l'input
              Fond bg-subtle au repos, bg-card au focus
              Radius-full pour la barre de recherche principale
```

### Bottom Navigation

```
Structure :   bg-card, border-top border-border, shadow-lg inversé
              Hauteur : 64px + safe-area-bottom
              4 onglets : icône 24px + label 11px dessous

Actif :       Icône + label en color-primary
              Pastille ronde 4px sous l'icône active (indicator dot)
              Transition douce 200ms

Inactif :     Icône + label en text-tertiary

Badge :       Pastille rouge 18px en haut à droite de l'icône
              Font-mono 11px bold, blanc sur danger
```

### Bottom Sheet

```
Structure :   bg-card, radius-lg en haut uniquement
              Handle : barre 40x4px en bg-inset centrée, radius-full, margin-top 8px
              Backdrop : noir 40% opacity
              Animation : slide-up 300ms ease-out

Contenu :     Padding 20px, max-height 85vh
              Scroll interne si contenu dépasse
```

### Avatars

```
Tailles :     xs=32px, sm=40px, md=48px, lg=64px, xl=96px
Photo :       object-cover, radius-full
Avatar pré :  Image bundlée, radius-full
Initiales :   bg-primary-100, text-primary-700, font-display 600
              2 lettres max (première lettre nom + prénom)
Fallback :    Icône User en text-tertiary
```

### StatusBadge

```
en_cours :    bg-primary-50, text-primary-700, border-primary-200
livre :       bg-emerald-50, text-emerald-700, border-emerald-200
annule :      bg-red-50, text-red-700, border-red-200
essai :       bg-amber-50, text-amber-700, border-amber-200
expire :      bg-stone-100, text-stone-600, border-stone-200
actif :       bg-emerald-50, text-emerald-700

Style :       inline-flex, radius-full, padding 4px 12px
              Font : text-label (DM Sans 500, 13px)
              Dot coloré 6px à gauche du texte
```

---

## 6. Animations & Micro-interactions

### Transitions de base
```css
--transition-fast: 150ms ease;
--transition-base: 200ms ease;
--transition-slow: 300ms ease-out;
--transition-spring: 300ms cubic-bezier(0.34, 1.56, 0.64, 1);  /* Rebond léger */
```

### Règles d'animation
- **Page enter** → fade-in + slide-up 20px, durée 300ms, stagger 50ms entre les éléments
- **Page détail** → slide-in depuis la droite 100%, durée 300ms
- **Retour** → slide-out vers la droite
- **Cartes dans une liste** → stagger reveal au premier chargement (50ms entre chaque carte, max 8 cartes animées)
- **FAB** → scale spring quand il apparaît, pulse subtil quand idle si action urgente
- **Bottom sheet** → slide-up spring, backdrop fade-in
- **Suppression** → slide-out gauche + fade, hauteur collapse
- **Toast** → slide-in depuis le haut, auto-dismiss 3s avec barre de progression
- **Skeleton** → shimmer de gauche à droite, gradient animé

### Ne PAS animer
- Le contenu textuel au scroll (pas de reveal au scroll pour le contenu principal)
- Les changements de valeur dans les formulaires
- La navigation bottom bar (changement d'onglet instantané)

---

## 7. Layouts de Page

### Dashboard
```
Mobile :
┌─────────────────────┐
│ Header : "Bonjour,  │
│ [Prénom]" + avatar   │
├─────────────────────┤
│ 3 cartes indicateurs │  ← Scroll horizontal si nécessaire
│ [Retard] [48h] [Cours]│
├─────────────────────┤
│ Raccourcis rapides   │  ← Grille 2x2 : + Client, + Commande, Mesures, Vêtements
├─────────────────────┤
│ 5 derniers clients   │  ← Liste compacte avec avatars
└─────────────────────┘

Desktop :
┌────┬────────────────────────────────┐
│    │ Header + résumé abonnement    │
│ S  ├──────────┬─────────────────────┤
│ I  │ 3 cartes │ Raccourcis 2x2     │
│ D  │ indicat. │                     │
│ E  ├──────────┴─────────────────────┤
│ B  │ Derniers clients + commandes  │
│ A  │ en 2 colonnes                 │
│ R  │                               │
└────┴────────────────────────────────┘
```

### Liste (Clients, Commandes, etc.)
```
Mobile :
┌─────────────────────┐
│ SearchBar pleine larg│
├─────────────────────┤
│ [TabBar si onglets] │
├─────────────────────┤
│ Card 1              │
│ Card 2              │
│ Card 3              │  ← Scroll infini
│ ...                 │
├─────────────────────┤
│ [FAB +]         ○   │  ← En bas à droite
└─────────────────────┘

Desktop :
Grille 2-3 colonnes de cartes, recherche en haut à gauche, bouton ajouter en haut à droite (pas de FAB)
```

### Détail (Client, Commande)
```
Mobile :
┌─────────────────────┐
│ ← Retour   [Actions]│  ← Header avec titre tronqué
├─────────────────────┤
│ Section héro        │  ← Avatar + nom + infos clés
├─────────────────────┤
│ Onglets / Sections  │  ← Mesures, Commandes, Infos
├─────────────────────┤
│ Contenu scrollable  │
└─────────────────────┘
```

---

## 8. Iconographie — Lucide React

### Icônes par feature

| Feature | Icône Lucide | Usage |
|---|---|---|
| Dashboard | `Home` | Bottom nav |
| Clients | `Users` | Bottom nav, menus |
| Commandes | `Scissors` | Bottom nav — évoque la couture |
| Vêtements | `Shirt` | Menu, sélection |
| Mesures | `Ruler` | Menu, formulaires |
| Abonnement | `Crown` | Menu — évoque premium |
| Points | `Star` | Menu, badges |
| Équipe | `UserPlus` | Menu |
| Photos VIP | `Camera` | Menu |
| Notifications | `Bell` | Menu, badges |
| Paramètres | `Settings` | Menu |
| Recherche | `Search` | SearchBar |
| Ajouter | `Plus` | FAB, boutons |
| Retour | `ArrowLeft` | Header |
| Menu Plus | `MoreHorizontal` ou `Menu` | Bottom nav 4e onglet |

### Règles
- Taille standard : 20px dans l'UI, 24px dans la bottom nav
- `strokeWidth={1.5}` pour un rendu plus élégant (défaut Lucide = 2, trop épais)
- Couleur : hérite du texte parent (`currentColor`)

---

## 9. États Spéciaux

### État vide (EmptyState)
```
Illustration simple SVG (pas de photo stock) + titre en font-display + description courte + CTA
Exemples :
- Clients vide → "Votre carnet est vierge" + "Ajoutez votre premier client"
- Commandes vide → "Aucune commande en cours" + "Créez une commande"
```

### État chargement (Skeleton)
```
Même forme que le composant final (carte, liste, avatar) mais en bg-inset avec shimmer animé.
Jamais de spinner plein écran seul. Le skeleton donne la structure de la page pendant le chargement.
```

### État erreur
```
Inline dans la zone concernée (pas de page d'erreur plein écran pour les erreurs API).
Icône AlertTriangle + message + bouton "Réessayer"
Couleur : text-danger pour l'icône, text-secondary pour le message
```

### État offline
```
Bandeau fin en haut de l'écran : bg-amber-50, text-amber-700, icône WifiOff
"Mode hors ligne — vos données seront synchronisées à la reconnexion"
Disparaît avec slide-up quand la connexion revient.
```

---

## 10. Responsive — Comportements par Breakpoint

| Élément | Mobile (<768px) | Tablette (768-1024) | Desktop (>1024) |
|---|---|---|---|
| Navigation | Bottom nav 4 onglets | Sidebar rétractable (icônes seules) | Sidebar fixe (icônes + labels) |
| Cartes | Pleine largeur, stack vertical | Grille 2 colonnes | Grille 2-3 colonnes |
| Modals/Actions | Bottom sheet | Bottom sheet | Modal centrée |
| FAB | Visible, fixed | Visible, fixed | Remplacé par bouton dans le header |
| Recherche | Pleine largeur sous le header | Pleine largeur | 400px max, alignée à gauche |
| Tableaux | Cartes empilées (jamais de table HTML) | Table si peu de colonnes, sinon cartes | Table complète |
| Formulaires | Pleine largeur, un champ par ligne | 2 colonnes si logique | 2-3 colonnes, sections groupées |

### Règle absolue
> Sur mobile, **jamais de table HTML**. Les données tabulaires sont toujours affichées sous forme de cartes empilées. Les tables sont réservées au desktop uniquement pour les vues avec beaucoup de colonnes.

---

## 11. Checklist Avant de Coder un Composant

Avant de commencer, vérifier :

- [ ] Les fonts Outfit (titres) et DM Sans (corps) sont utilisées — jamais Inter, Roboto ou Arial
- [ ] Les couleurs viennent des variables CSS — aucun hex hardcodé
- [ ] Le fond de l'app est `bg-app` (stone-50 chaud) — pas blanc pur
- [ ] Les ombres sont chaudes (teinte stone) — pas grises froides
- [ ] Les touch targets font au minimum 44x44px
- [ ] Le texte passe par `t('cle')` — aucun texte en dur
- [ ] Les montants XOF utilisent `font-mono` et `formatCurrency()`
- [ ] Les squelettes sont prévus pour l'état chargement
- [ ] L'état vide est prévu avec illustration et CTA
- [ ] Le composant fonctionne en thème sombre
- [ ] Sur mobile, les modals sont des bottom sheets
- [ ] Les cartes de liste sont swipeable (archiver/supprimer)
- [ ] Les transitions sont fluides (200-300ms, ease ou spring)
- [ ] Pas de hover-only — toute interaction a un équivalent touch
- [ ] Le `strokeWidth` des icônes Lucide est à 1.5

---

## 12. Anti-patterns — Ce qui est INTERDIT

| Interdit | Faire à la place |
|---|---|
| `style={{ color: '#xxx' }}` | Classes Tailwind avec variables CSS |
| Texte en dur `"Ajouter un client"` | `t('clients.ajouter')` |
| Font Inter, Roboto, Arial | Outfit (titres) + DM Sans (corps) |
| Fond blanc pur `#FFFFFF` pour l'app | `bg-app` (stone-50 chaud) |
| Spinner centré seul comme chargement | Skeleton de la forme du contenu attendu |
| Table HTML sur mobile | Cartes empilées |
| Modal centrée sur mobile | Bottom sheet |
| Bouton < 44px | Minimum 44x44px touch target |
| Ombre grise froide | Ombres teintées stone |
| Icônes strokeWidth 2 | strokeWidth 1.5 |
| Gradient violet/rose IA | Palette indigo + ambre + terracotta |
| Layout identique chaque page | Varier : héro, grille, list, dashboard |
