# COUTURE PRO — Blueprint Frontend React.js
## Architecture Complète — Structure, Services, Hooks, Contextes & Pages

> **Fichier cible :** `docs/frontend_blueprint.md` dans le projet
> **Version :** 1.0 — Architecture frontend web responsive mobile-first, prête pour Capacitor
> **Branche :** `main` (web) — une branche `capacitor` viendra wrapper le tout plus tard
> **Usage :** Coller ce fichier dans une conversation Claude et demander :
> *"Génère la structure complète du projet frontend React.js basée sur ce blueprint."*

---

## 1. Philosophie & Principes Directeurs

- **Yarn uniquement** — le projet est initialisé avec Yarn. Ne jamais utiliser `npm`. Toutes les commandes : `yarn add`, `yarn dev`, `yarn build`
- **Mobile-first = App native** — sur mobile, ça doit ressembler à une vraie app native (bottom nav, transitions, touch targets 44px min, pas de hover-only). Objectif : zéro retouche design quand on passera sur Capacitor
- **Offline-ready dès le design** — chaque écran fonctionne visuellement sans données serveur (états vides, squelettes, indicateurs sync)
- **i18n partout** — aucun texte en dur. Tout passe par des clés `t('cle')`. Phase 1 = français, structure prête pour anglais Phase 2
- **Zéro style hardcodé** — tout via Tailwind + variables CSS dans `index.css`. Aucun `style={{}}` inline
- **Données mockées** — les services retournent du faux réaliste. Le backend n'est pas prêt, le dev frontend avance en parallèle. Le jour J, on branche les vrais endpoints dans `api.js`
- **Structure miroir du backend** — mêmes noms d'entités que les tables backend (clients, commandes, mesures, etc.)

---

## 2. Stack Technique

| Outil | Rôle |
|---|---|
| React 19+ | Framework UI |
| Vite 6+ | Bundler + dev server |
| Yarn 4+ | Gestionnaire de paquets — **seul autorisé** |
| React Router 7+ | Routing SPA |
| TanStack Query 5+ | Cache serveur, mutations, invalidation, états de chargement |
| Axios | Client HTTP centralisé dans `api.js` |
| i18next + react-i18next | Internationalisation |
| Tailwind CSS 4+ | Styling utilitaire |
| Lucide React | Icônes cohérentes |
| React Hot Toast | Notifications toast |
| date-fns | Manipulation de dates (léger, tree-shakable) |
| clsx | Composition conditionnelle de classes CSS |
| React Hook Form + Zod | Formulaires + validation |

### Installation complète
```bash
# Dépendances principales
yarn add react-router-dom @tanstack/react-query axios i18next react-i18next \
  lucide-react react-hot-toast date-fns clsx react-hook-form @hookform/resolvers zod

# Dev
yarn add -D @tanstack/react-query-devtools
```

---

## 3. Ordre de Création — Couches

> **Règle absolue :** chaque couche ne dépend que des couches créées avant elle. Jamais de dépendance circulaire.

```
1. src/styles/          → Charte graphique, index.css, config Tailwind
2. src/locales/         → Fichiers i18n (fr.json, structure pour en.json)
3. src/constants/       → Enums, routes, config statique
4. src/utils/           → Fonctions pures utilitaires
5. src/services/        → api.js centralisé + services métier (mock-ready)
6. src/hooks/           → Hooks custom utilisant TanStack Query + services
7. src/contexts/        → ThemeContext, AuthContext, AtelierContext
8. src/components/      → Composants réutilisables (UI kit interne)
9. src/layouts/         → Layouts (AppLayout, AuthLayout)
10. src/pages/          → Pages par feature
11. src/App.jsx         → Routes + providers
12. src/main.jsx        → Point d'entrée
```

---

## 4. Arborescence Complète

```
src/
├── App.jsx
├── main.jsx
├── index.css                          ← Charte graphique + variables CSS
│
├── constants/
│   ├── routes.js                      ← Toutes les routes en constantes
│   ├── enums.js                       ← Statuts, rôles, types (miroir backend)
│   └── config.js                      ← API_BASE_URL, durées, limites
│
├── locales/
│   ├── i18n.js                        ← Config i18next
│   ├── fr/
│   │   ├── common.json                ← Boutons, labels génériques
│   │   ├── auth.json                  ← Connexion, inscription, OTP
│   │   ├── clients.json
│   │   ├── commandes.json
│   │   ├── mesures.json
│   │   ├── vetements.json
│   │   ├── abonnement.json
│   │   ├── equipe.json
│   │   ├── points.json
│   │   ├── notifications.json
│   │   ├── parametres.json
│   │   └── errors.json
│   └── en/                            ← Vide Phase 1, structure identique prête
│       └── ...
│
├── utils/
│   ├── formatDate.js                  ← Formatage dates (date-fns + locale fr)
│   ├── formatCurrency.js              ← Formatage XOF
│   ├── cn.js                          ← Wrapper clsx + tailwind-merge
│   ├── storage.js                     ← Abstraction localStorage (token, préfs)
│   └── validators.js                  ← Schémas Zod réutilisables
│
├── services/
│   ├── api.js                         ← Instance Axios centralisée
│   ├── authService.js
│   ├── clientService.js
│   ├── commandeService.js
│   ├── mesureService.js
│   ├── vetementService.js
│   ├── abonnementService.js
│   ├── paiementService.js
│   ├── equipeService.js
│   ├── pointsService.js
│   ├── notificationService.js
│   ├── parametresService.js
│   ├── quotaService.js
│   ├── photoVipService.js
│   ├── ticketService.js
│   └── mockData/                      ← Données fictives réalistes par entité
│       ├── clients.mock.js
│       ├── commandes.mock.js
│       └── ...
│
├── hooks/
│   ├── useAuth.js
│   ├── useTheme.js
│   ├── useClients.js
│   ├── useCommandes.js
│   ├── useMesures.js
│   ├── useVetements.js
│   ├── useAbonnement.js
│   ├── usePaiement.js
│   ├── useEquipe.js
│   ├── usePoints.js
│   ├── useNotifications.js
│   ├── useParametres.js
│   ├── useQuota.js
│   └── useDashboard.js               ← Agrège les 3 indicateurs
│
├── contexts/
│   ├── AuthContext.jsx                ← User, token, rôle, atelier actif
│   ├── ThemeContext.jsx               ← Clair/sombre, persisté localStorage
│   └── AtelierContext.jsx             ← Multi-ateliers (préparé Phase 2)
│
├── components/
│   ├── ui/                            ← UI Kit interne
│   │   ├── Button.jsx
│   │   ├── Input.jsx
│   │   ├── Select.jsx
│   │   ├── Modal.jsx
│   │   ├── BottomSheet.jsx            ← Modal style app native (monte du bas)
│   │   ├── Card.jsx
│   │   ├── Badge.jsx
│   │   ├── Avatar.jsx                 ← Photo, avatar pré-installé, ou initiales
│   │   ├── Skeleton.jsx
│   │   ├── EmptyState.jsx
│   │   ├── SearchBar.jsx              ← Debounce 300ms
│   │   ├── FloatingActionButton.jsx   ← FAB style mobile
│   │   ├── SwipeableRow.jsx           ← Swipe pour archiver/supprimer
│   │   ├── TabBar.jsx                 ← Onglets (En cours / Livré)
│   │   ├── StatusBadge.jsx
│   │   ├── ProgressBar.jsx
│   │   └── Spinner.jsx
│   │
│   ├── layout/
│   │   ├── AppLayout.jsx              ← Sidebar desktop + bottom nav mobile + header
│   │   ├── AuthLayout.jsx             ← Centré, branding
│   │   ├── BottomNavigation.jsx       ← 4 onglets style app native
│   │   ├── Sidebar.jsx                ← Menu latéral desktop
│   │   ├── Header.jsx
│   │   ├── SyncIndicator.jsx          ← Pastille statut sync
│   │   └── ProtectedRoute.jsx
│   │
│   ├── clients/
│   │   ├── ClientCard.jsx
│   │   ├── ClientForm.jsx
│   │   ├── ClientAvatarPicker.jsx
│   │   └── ClientSearchResults.jsx
│   │
│   ├── commandes/
│   │   ├── CommandeCard.jsx
│   │   ├── CommandeForm.jsx
│   │   ├── CommandeStatusStepper.jsx
│   │   └── CommandePhotoTissu.jsx
│   │
│   ├── mesures/
│   │   ├── MesureForm.jsx             ← Dynamique selon le vêtement
│   │   ├── MesureCard.jsx
│   │   └── MesureExportButton.jsx     ← Export PDF (jsPDF local)
│   │
│   ├── vetements/
│   │   ├── VetementCard.jsx
│   │   ├── VetementForm.jsx
│   │   └── VetementLibellesEditor.jsx
│   │
│   ├── abonnement/
│   │   ├── PlanCard.jsx
│   │   ├── AbonnementStatus.jsx       ← Timer, jours restants
│   │   ├── CodeActivation.jsx         ← Canal manuel (code admin)
│   │   └── PaiementFlow.jsx           ← Canal auto (redirect checkout)
│   │
│   ├── equipe/
│   │   ├── MembreCard.jsx
│   │   ├── MembreForm.jsx
│   │   └── MembreRevokeModal.jsx
│   │
│   ├── points/
│   │   ├── PointsDashboard.jsx
│   │   ├── PointsHistorique.jsx
│   │   └── PointsConversionModal.jsx
│   │
│   ├── notifications/
│   │   ├── NotificationCard.jsx
│   │   └── NotificationBadge.jsx
│   │
│   ├── dashboard/
│   │   ├── DashboardCard.jsx
│   │   └── DashboardSummary.jsx       ← 3 indicateurs
│   │
│   └── onboarding/
│       ├── OnboardingStep.jsx
│       └── OnboardingCarousel.jsx     ← 3 étapes post-inscription
│
└── pages/
    ├── auth/
    │   ├── LoginPage.jsx              ← Connexion unifiée (gérant/assistant/membre)
    │   ├── RegisterPage.jsx
    │   ├── OtpVerificationPage.jsx
    │   ├── ForgotPasswordPage.jsx
    │   └── RecoverAccountPage.jsx
    │
    ├── dashboard/
    │   └── DashboardPage.jsx          ← 3 indicateurs + raccourcis
    │
    ├── clients/
    │   ├── ClientsListPage.jsx        ← Liste + recherche + FAB
    │   └── ClientDetailPage.jsx       ← Profil + mesures + commandes
    │
    ├── commandes/
    │   ├── CommandesListPage.jsx      ← Onglets En cours / Livré
    │   └── CommandeDetailPage.jsx
    │
    ├── mesures/
    │   └── MesuresPage.jsx
    │
    ├── vetements/
    │   └── VetementsListPage.jsx
    │
    ├── abonnement/
    │   └── AbonnementPage.jsx         ← Plans, statut, paiement, code
    │
    ├── points/
    │   └── PointsPage.jsx
    │
    ├── equipe/
    │   └── EquipePage.jsx
    │
    ├── notifications/
    │   └── NotificationsPage.jsx
    │
    ├── parametres/
    │   ├── ParametresPage.jsx         ← Hub paramètres
    │   ├── ProfilPage.jsx
    │   ├── CommunicationsPage.jsx     ← Config WhatsApp
    │   ├── ThemePage.jsx
    │   └── AproposPage.jsx            ← Version, CGU, confidentialité
    │
    ├── photos-vip/
    │   └── PhotosVipPage.jsx          ← Premium & Magnat
    │
    ├── historique/
    │   └── HistoriquePage.jsx
    │
    ├── faq/
    │   └── FaqPage.jsx
    │
    └── contact/
        └── ContactPage.jsx
```

---

## 5. Charte Graphique — `index.css`

> Toutes les couleurs, tailles, et espacements sont définis ici en variables CSS. Tailwind les consomme via `tailwind.config.js`. Aucun composant ne hardcode une couleur.

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
  /* ── Couleurs principales ── */
  --color-primary: #2563EB;
  --color-primary-light: #3B82F6;
  --color-primary-dark: #1D4ED8;
  --color-secondary: #F59E0B;        /* Or/Ambre — accent chaleureux */
  --color-secondary-light: #FBBF24;
  --color-secondary-dark: #D97706;

  /* ── Sémantiques ── */
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;
  --color-info: #3B82F6;

  /* ── Fond et texte — thème clair ── */
  --color-bg-primary: #FFFFFF;
  --color-bg-secondary: #F8FAFC;
  --color-bg-tertiary: #F1F5F9;
  --color-text-primary: #0F172A;
  --color-text-secondary: #475569;
  --color-text-tertiary: #94A3B8;
  --color-border: #E2E8F0;

  /* ── Typographie ── */
  --font-family: 'Inter', system-ui, -apple-system, sans-serif;

  /* ── Navigation mobile ── */
  --bottom-nav-height: 4rem;
  --safe-area-bottom: env(safe-area-inset-bottom, 0px);
}

/* ── Thème sombre ── */
[data-theme="dark"] {
  --color-bg-primary: #0F172A;
  --color-bg-secondary: #1E293B;
  --color-bg-tertiary: #334155;
  --color-text-primary: #F8FAFC;
  --color-text-secondary: #CBD5E1;
  --color-text-tertiary: #64748B;
  --color-border: #334155;
}

body {
  font-family: var(--font-family);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  -webkit-tap-highlight-color: transparent;
  -webkit-font-smoothing: antialiased;
  overscroll-behavior: none;
}

.pb-safe {
  padding-bottom: calc(var(--bottom-nav-height) + var(--safe-area-bottom));
}
```

### Tailwind Config — `tailwind.config.js`
```js
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        primary: { DEFAULT: 'var(--color-primary)', light: 'var(--color-primary-light)', dark: 'var(--color-primary-dark)' },
        secondary: { DEFAULT: 'var(--color-secondary)', light: 'var(--color-secondary-light)', dark: 'var(--color-secondary-dark)' },
        success: 'var(--color-success)',
        warning: 'var(--color-warning)',
        danger: 'var(--color-danger)',
        surface: { primary: 'var(--color-bg-primary)', secondary: 'var(--color-bg-secondary)', tertiary: 'var(--color-bg-tertiary)' },
        content: { primary: 'var(--color-text-primary)', secondary: 'var(--color-text-secondary)', tertiary: 'var(--color-text-tertiary)' },
        border: 'var(--color-border)',
      },
      fontFamily: { sans: ['var(--font-family)'] },
    },
  },
}
```

---

## 6. Internationalisation — i18n

### Config — `src/locales/i18n.js`
```js
import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import fr from './fr'

i18n.use(initReactI18next).init({
  resources: { fr: { translation: fr } },
  lng: 'fr',
  fallbackLng: 'fr',
  interpolation: { escapeValue: false },
})

export default i18n
```

> **Règle :** dans chaque composant, `const { t } = useTranslation()` puis `t('clients.titre')`. Jamais de texte français en dur.

---

## 7. Service API Centralisé — `api.js`

> Point d'entrée unique pour tous les appels HTTP. Les services métier importent `api` et ne font jamais d'appels Axios directs.

```js
// src/services/api.js
import axios from 'axios'
import { getToken, clearToken } from '@/utils/storage'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 15000,
  headers: { 'Content-Type': 'application/json', 'Accept-Language': 'fr' },
})

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      clearToken()
      window.location.href = '/login'
    }
    return Promise.reject(error.response?.data || error)
  }
)

export default api
```

### Pattern service métier (identique pour tous)
```js
// src/services/clientService.js
import api from './api'

export const clientService = {
  getAll:    (atelierId)       => api.get(`/ateliers/${atelierId}/clients`),
  getById:   (id)              => api.get(`/clients/${id}`),
  create:    (data)            => api.post(`/clients`, data),
  update:    (id, data)        => api.put(`/clients/${id}`, data),
  archive:   (id)              => api.patch(`/clients/${id}/archive`),
  delete:    (id)              => api.delete(`/clients/${id}`),
  search:    (atelierId, q)    => api.get(`/ateliers/${atelierId}/clients/search`, { params: { q } }),
}
```

---

## 8. Hooks — TanStack Query

> Chaque hook encapsule un service + TanStack Query. Les composants n'appellent jamais les services directement.

### Pattern hook (identique pour toutes les entités)
```js
// src/hooks/useClients.js
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { clientService } from '@/services/clientService'

export function useClients(atelierId) {
  return useQuery({
    queryKey: ['clients', atelierId],
    queryFn: () => clientService.getAll(atelierId),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateClient() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: clientService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['clients'] }),
  })
}

export function useSearchClients(atelierId, query) {
  return useQuery({
    queryKey: ['clients', 'search', atelierId, query],
    queryFn: () => clientService.search(atelierId, query),
    enabled: query.length >= 2,
  })
}
```

---

## 9. Contextes

| Contexte | Fournit | Persistance |
|---|---|---|
| **AuthContext** | `user`, `token`, `role` (proprietaire/assistant/membre), `atelierActifId`, `login()`, `logout()`, `can(action)` | Token dans localStorage |
| **ThemeContext** | `theme` (clair/sombre), `toggleTheme()` | localStorage + `data-theme` sur `<html>` |
| **AtelierContext** | `ateliers[]`, `atelierActif`, `switchAtelier()` | Phase 1 = un seul atelier, structure prête Phase 2 |

---

## 10. Navigation & Routing

### Bottom Navigation Mobile (4 onglets)
```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│  Accueil    │  Clients    │  Commandes  │    Menu     │
│  (Maison)   │  (Users)    │  (Scissors) │  (☰ Plus)  │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

- **Menu (☰ Plus)** ouvre un BottomSheet/Drawer avec : Vêtements, Mesures, Points, Abonnement, Équipe, Photos VIP, Notifications, Historique, Paramètres, FAQ, Contact
- **Desktop** → sidebar gauche permanente avec tous les menus
- Badge rouge sur Commandes si retard ou livraisons dans 48h

---

## 11. Design Mobile-First — Règles UX

| Règle | Détail |
|---|---|
| Touch targets | Minimum 44×44px |
| Bottom sheet > Modal | Sur mobile, modals depuis le bas (style natif) |
| FAB | Bouton flottant en bas à droite pour l'action principale |
| Pull-to-refresh | Geste natif pour rafraîchir les listes |
| Swipe actions | Swipe gauche sur carte = archiver/supprimer |
| Squelettes | Toujours un Skeleton pendant le chargement, jamais spinner plein écran |
| Transitions | Slide-in depuis la droite pour les pages détail |
| Safe areas | Padding bottom pour bottom nav + safe area iOS |
| Pas de hover-only | Aucune interaction ne dépend du hover |
| Scroll naturel | Scroll infini natif, pas de pagination cliquable |
| Taille police | 14px minimum corps de texte |

### Breakpoints
```
Mobile :   < 768px   → Bottom nav, full-width, bottom sheets
Tablette : 768-1024  → Sidebar rétractable, grille 2 colonnes
Desktop :  > 1024    → Sidebar fixe, grille 3 colonnes, modals centrées
```

---

## 12. Gestion des Rôles dans l'Interface

```
Gérant      → Tout visible. CRUD complet. Abonnement et Notifications visibles.
Assistant   → Création clients/commandes/mesures. Pas de modif/suppression. Pas d'Abonnement/Notifications.
Membre      → Lecture seule. Aucun FAB, aucun bouton d'action.
```

Le hook `useAuth()` expose `can(action)` pour conditionner l'affichage :
```js
const { can } = useAuth()
if (can('create_client')) // afficher le FAB
if (can('view_abonnement')) // afficher le menu
```

---

## 13. Stratégie de Mock

### `.env`
```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_USE_MOCKS=true
```

### Pattern dans chaque service
```js
const USE_MOCKS = import.meta.env.VITE_USE_MOCKS === 'true'

export const clientService = {
  getAll: (atelierId) =>
    USE_MOCKS
      ? Promise.resolve(mockClients.filter(c => c.atelier_id === atelierId))
      : api.get(`/ateliers/${atelierId}/clients`),
}
```

Les mocks reprennent exactement la structure des réponses API (miroir backend). Noms africains francophones, montants en XOF, dates réalistes, statuts variés.

---

## 14. Correspondance Pages ↔ Tables Backend

| Page | Hook(s) | Table(s) backend |
|---|---|---|
| DashboardPage | `useDashboard` | commandes, quotas_mensuels |
| ClientsListPage | `useClients`, `useSearchClients` | clients |
| ClientDetailPage | `useClient`, `useMesures`, `useCommandes` | clients, mesures, commandes |
| CommandesListPage | `useCommandes` | commandes |
| VetementsListPage | `useVetements` | vetements |
| AbonnementPage | `useAbonnement`, `usePaiement` | abonnements, paiements, niveaux_config, transactions_abonnement |
| PointsPage | `usePoints` | points_fidelite, points_historique |
| EquipePage | `useEquipe` | equipe_membres |
| NotificationsPage | `useNotifications` | notifications_systeme |
| ParametresPage | `useParametres` | parametres_atelier, communications_config |
| PhotosVipPage | `usePhotosVip` | photos_vip |

---

## 15. Liaison Backend — Le Jour J

> Quand le backend Laravel est prêt :

1. Mettre `VITE_USE_MOCKS=false` dans `.env`
2. Mettre `VITE_API_BASE_URL=https://api.couturepro.app`
3. Vérifier que les endpoints dans chaque service correspondent aux routes Laravel
4. Le token Sanctum est déjà géré par `api.js`
5. Tester le flux paiement webhook avec le provider réel

> **Aucun composant ni page ne devrait être modifié.** Seuls les fichiers `src/services/` sont impactés.

---

## 16. Fichiers à Créer — Résumé

```
CONFIG & STYLE :
  tailwind.config.js
  src/index.css
  .env

I18N :
  src/locales/i18n.js
  src/locales/fr/*.json                      ← 12 fichiers (common, auth, clients, commandes, mesures, vetements, abonnement, equipe, points, notifications, parametres, errors)

CONSTANTS & UTILS :
  src/constants/routes.js
  src/constants/enums.js
  src/constants/config.js
  src/utils/formatDate.js
  src/utils/formatCurrency.js
  src/utils/cn.js
  src/utils/storage.js
  src/utils/validators.js

SERVICES (15) :
  src/services/api.js                        ← Point d'entrée unique
  src/services/{auth,client,commande,mesure,vetement,abonnement,paiement,equipe,points,notification,parametres,quota,photoVip,ticket}Service.js
  src/services/mockData/*.mock.js

HOOKS (14) :
  src/hooks/use{Auth,Theme,Clients,Commandes,Mesures,Vetements,Abonnement,Paiement,Equipe,Points,Notifications,Parametres,Quota,Dashboard}.js

CONTEXTES (3) :
  src/contexts/{Auth,Theme,Atelier}Context.jsx

COMPOSANTS UI (17) :
  src/components/ui/{Button,Input,Select,Modal,BottomSheet,Card,Badge,Avatar,Skeleton,EmptyState,SearchBar,FloatingActionButton,SwipeableRow,TabBar,StatusBadge,ProgressBar,Spinner}.jsx

COMPOSANTS LAYOUT (7) :
  src/components/layout/{AppLayout,AuthLayout,BottomNavigation,Sidebar,Header,SyncIndicator,ProtectedRoute}.jsx

COMPOSANTS MÉTIER :
  src/components/{clients,commandes,mesures,vetements,abonnement,equipe,points,notifications,dashboard,onboarding}/*.jsx

PAGES (21) :
  src/pages/auth/{Login,Register,OtpVerification,ForgotPassword,RecoverAccount}Page.jsx
  src/pages/dashboard/DashboardPage.jsx
  src/pages/clients/{ClientsList,ClientDetail}Page.jsx
  src/pages/commandes/{CommandesList,CommandeDetail}Page.jsx
  src/pages/mesures/MesuresPage.jsx
  src/pages/vetements/VetementsListPage.jsx
  src/pages/abonnement/AbonnementPage.jsx
  src/pages/points/PointsPage.jsx
  src/pages/equipe/EquipePage.jsx
  src/pages/notifications/NotificationsPage.jsx
  src/pages/parametres/{Parametres,Profil,Communications,Theme,Apropos}Page.jsx
  src/pages/photos-vip/PhotosVipPage.jsx
  src/pages/historique/HistoriquePage.jsx
  src/pages/faq/FaqPage.jsx
  src/pages/contact/ContactPage.jsx

ENTRÉE :
  src/App.jsx
  src/main.jsx
```

---

## 17. Notes de Scalabilité

- **i18n** → ajouter anglais = dupliquer `fr/` en `en/` et traduire. Aucun composant touché
- **Thème** → une seule source de vérité (`data-theme`). Ajouter un thème = dupliquer les variables CSS
- **Mock → API** → un seul switch `VITE_USE_MOCKS`. Hooks et composants inchangés
- **Capacitor** → design mobile-first avec bottom nav, safe areas, touch targets déjà compatible. La branche Capacitor wrappe le build sans retouches
- **Nouveau module** → service + hook + composants + page + route. Même pattern pour toutes les features
- **Multi-ateliers Phase 2** → `AtelierContext` prêt, `atelierId` déjà passé aux hooks
