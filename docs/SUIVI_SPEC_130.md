# Suivi — Spécification Gextimo (130 points)

> Statut audité de chaque point de `Gextimo_Specification_COMPLETE_1_130.md`.
> **Légende** : ✅ Fait · 🟡 Partiel · ⬜ À faire · 🔵 Gros chantier (test device / design) · ⚖️ Décision direction.
> Dernière mise à jour : **18 juillet 2026**. Beaucoup de points recoupent le travail déjà livré (P202 espace client, Brevo, Makila AI, cookies…).

## Vue d'ensemble
- **✅ Faits : ~70 points** (dont ~20 livrés/vérifiés le 17-18/07)
- **🟡 Partiels / à re-tester sur device : ~30**
- **⬜ À faire : ~20**
- **🔵 Gros chantiers : 4** (57 événements, 68-69 flux mesures, 101 Mes Réalisations, 124-125 SEO SSR)
- **⚖️ Décisions direction : 3** (128, 129, 130)

---

## Studio / Outils créatifs (1-7)
| Pt | Sujet | Statut |
|---|---|---|
| 1-3 | Libellé vidéo, import direct, réorg interface vidéo | 🟡 à re-tester |
| 4-7 | Boutons/onglets Outils créatifs | 🟡 à re-tester |

## Espace client — auth & inscription (8-22)
| Pt | Sujet | Statut |
|---|---|---|
| 8-9 | Plusieurs méthodes de connexion/inscription | 🟡 Google + OTP e-mail en place ; autres méthodes = pt 130 (décision) |
| 10 | Acceptation obligatoire des conditions | ✅ |
| 11 | Newsletter facultative | ⬜ |
| 12 | Message de confiance | 🟡 |
| 13 | Création auto du compte client | ✅ (OTP crée le compte) |
| 14 | Infos facultatives du profil | ✅ (ville, date naissance…) |
| 15 | Server Error (connexion client) | 🟡 à confirmer (≠ pt 77 déjà réglé) |
| 16-22 | Onboarding + récupération de compte client | 🟡 espace sans mot de passe (OTP) → récupération inhérente ; onboarding client ⬜ |

## Modules espace client (23-31)
| Pt | Sujet | Statut |
|---|---|---|
| 23 | Inscription / activation | ✅ |
| 24 | Notifications client | ⬜ |
| 25 | Messagerie client ↔ créateur | ⬜ (nouveau module) |
| 26 | Avis | ✅ |
| 27 | Plaintes / réclamations | ✅ |
| 28 | Profil / Paramètres | ✅ |
| 29 | Recherche | 🟡 |
| 30 | Code promo client | ⬜ |
| 31 | Commandes (suivi) | ✅ |

## Architecture v3 + brief (32-45) — **tous ✅ (livrés le 16-17/07)**
Conflits de noms (32), 6 tables (33), 5 phases espace client (34-38), mémoire chatbot (39), analytics consenti (40), profil (41), recommandation (42), veille (43), identité visuelle (44), charte d'accès (45).

## Corrections diverses lot 2 (46-56)
| Pt | Sujet | Statut |
|---|---|---|
| 46 | Consentement structuré à l'inscription | 🟡 |
| 47 | Nom de famille en majuscules | 🟡 à confirmer |
| 48 | Case CGU à l'inscription | ✅ |
| 49 | Mot anglais résiduel | 🟡 à localiser |
| 50-52 | Filigrane + pied de page PDF mesures, branding WhatsApp | 🟡 |
| 53 | Bug commande groupée | ✅ (18/07) |
| 54 | « Article » → « Type de vêtement » | ✅ (18/07) |
| 55 | Réseaux sociaux profil Designer | 🟡 (champs socials en base) |
| 56 | Type de document (vérification Designer) | 🟡 |

## Événements dynamiques (57-59)
| Pt | Sujet | Statut |
|---|---|---|
| 57 | Système d'événements dynamiques complet | 🔵 splash saisonnier config-driven ✅ ; 5 familles + JSON serveur + priorités + overlay perso = à construire |
| 58 | Champ date de naissance | ✅ espace client + PROS (jour/mois) — 18/07 |
| 59 | Test 3-4 jours | ⏳ process |

## Corrections app (60-79) — **majorité ✅ le 17-18/07**
| Pt | Sujet | Statut |
|---|---|---|
| 60 | E-mails : expéditeur officiel + branding Gextimo | ✅ (Brevo, zéro CouturePro) |
| 61 | État vide Clients incitatif | ✅ (18/07) |
| 62 | Import contacts : safe-area + toast | ✅ (18/07) |
| 63 | Client visible pendant la commande | ✅ (18/07) |
| 64 | Catalogue vide : action créer | ✅ (18/07) |
| 65 | Icônes par type de vêtement | ✅ (18/07) |
| 66 | Catalogue alphabétique | ✅ (18/07) |
| 67 | Onboarding 3 étapes | ⬜ |
| 68 | Refonte flux création commande | 🟡 renommage + client visible + **menu déroulant ✅** (18/07) ; « Éditer les mesures » inline = à itérer sur device |
| 69 | Mesures définies par type de vêtement | 🟡 **décidé : mesures PAR CLIENT** (pas de refonte modèle) ; `libelles_mesures` par vêtement existe ; chargement auto à la commande = à itérer device |
| 70 | WhatsApp : libellés de mesures | ✅ (déjà inclus) |
| 71 | Prénom dans Réglages > Profil | ✅ (18/07) |
| 72 | Fidélité : 1 client = 1 point | 🟡 |
| 73 | « Se souvenir de moi » | ✅ (18/07) |
| 74 | Préférences appliquées immédiatement | ✅ (18/07) |
| 75 | Historique des mises à jour | ⬜ |
| 76 | Bouton Conditions d'utilisation | ✅ (ouvre la page externe) |
| 77 | Server Error inscription Designer | ✅ (réglé le 15/07 — normalisation téléphone) |
| 78 | Texte standard case CGU + Politique | 🟡 |
| 79 | Documents légaux dans les pages | ✅ (éditeur back-office livré) |

## Correctifs vitrine + web (80-100)
| Pt | Sujet | Statut |
|---|---|---|
| 80 | Logo page de connexion | 🟡 |
| 81 | Casse copyright | ✅ (résolu) |
| 82 | Texte « espace sur l'app mobile » | 🟡 |
| 83 | Refonte plans d'abonnement | ✅ |
| 84 | Ordre rubriques Paramètres | 🟡 |
| 85 | Bug « Ajouter une ville » | 🟡 |
| 86-89 | Sidebar : scroll, état actif, logo, hamburger | 🟡 à re-tester |
| 90 | Profil : format Nom/Prénom + champs | 🟡 |
| 91 | Avatar 404 | 🟡 |
| 92 | Tickets : images + horodatage | 🟡 |
| 93 | Revue des liens internes | 🟡 |
| 94-95 | 54 pays africains + affichage | 🟡 |
| 96 | Nom de marque | ✅ (résolu) |
| 97 | Bug déconnexion | 🟡 |
| 98 | Bulle flottante retour vitrine | ⬜ |
| 99-100 | Sync Web/Mobile : caisse + catalogues | ⚠️ à reproduire sur device |

## Modules & divers (101-114)
| Pt | Sujet | Statut |
|---|---|---|
| 101 | Module « Mes Réalisations » | 🔵 spec (nouveau module) |
| 102 | Distinction Notifications / Gextimo Infos | ✅ |
| 103 | Compte à rebours de lancement | 🟡 |
| 104 | Messages de récupération | 🟡 |
| 105 | Taux de commission affiché (15 %) | ✅ |
| 106 | Import contacts anti-doublon | 🟡 |
| 107 | Méthodes d'inscription complémentaires | 🟡 (cf. pt 130) |
| 108 | Architecture assistant conversationnel | ✅ (Makila AI livré) |
| 109 | Ancrage/scroll boutons header | 🟡 |
| 110 | Message d'erreur support (ton moins IA) | ✅ (18/07, sans tiret) |
| 111 | Structure officielle e-mail support | ✅ (support.gextimo@novafriq.africa) |
| 113 | Audit/standardisation adresses e-mail | ✅ |
| 114 | Upgrades d'abonnement (prorata) | ✅ |

## Cookies + Makila (115-127) — **tous ✅ (17-18/07)**
Bandeau/panneau conforme (115), rendu visuel charte (116), contenu des catégories (117), identité Makila (118), menu chatbot (119), liens légaux chatbot (120), cookies décochés par défaut (126), persistance du consentement (127).
| Pt | Sujet | Statut |
|---|---|---|
| 121 | Pages légales en sidebar (module unique) | ⬜ (refonte à faire) |
| 122 | Compléments légaux surlignés en vert | ⬜ |
| 123 | Conformité (code du numérique Bénin + RGPD) | ⬜ |

## SEO indexabilité (124-125)
| Pt | Sujet | Statut |
|---|---|---|
| 124 | Audit lisibilité par les robots | ✅ **FAIT (18/07)** — voir finding ci-dessous |
| 125 | Rendu serveur / pré-rendu des pages clés | ⬜ reco prête (à valider avant prod) |

**Finding audit 124 (curl User-Agent Googlebot, sans exécution JS) :**
- `/`, `/createurs`, `/confidentialite` renvoient **toutes le même `<title>` générique** (« Gextimo — La marketplace… ») — aucun titre par page.
- Le HTML brut ne contient **quasiment aucun texte réel** : tout le contenu (créateurs, texte légal, produits) est injecté par JavaScript après coup. Un robot qui n'exécute pas le JS voit une coquille vide.
- Confirmation exacte du risque du pt 124 : Google finit par exécuter le JS (donc pas critique), mais c'est plus lent, non garanti, et les robots simples / aperçus sociaux ne voient rien.

**Reco pt 125 (à valider avant de toucher la prod) :** la vitrine est une **SPA Vite/React**. Le plus léger et sûr, sans réécrire en SSR :
1. **Pré-rendu au build** des pages statiques (accueil + 11 pages légales) → HTML complet dans le bundle (plugin de prerender Vite ou script Puppeteer).
2. **Métadonnées par page** (title/description) injectées dans ce HTML pré-rendu.
3. Pour les pages dynamiques (créateurs/produits) : **rendu bot via nginx** selon le User-Agent (le mécanisme `og/createurs` existe déjà pour les robots sociaux → à étendre).
⚠️ Touche la façon dont le site est servi à Google → à faire avec validation + test, pas à l'aveugle.

## ✅ Décisions tranchées par la direction (128-130) — le 18/07
| Pt | Décision | Statut |
|---|---|---|
| 128 | Copyright = **« Novafriq »** | ✅ appliqué (textes visibles) |
| 129 | **« Gextimo »** partout | ✅ confirmé (aucune coquille « Gestimo » dans le code) |
| 130 | Espace client : **on garde e-mail OTP + Google** (pas d'ajout) | ✅ rien à changer |
