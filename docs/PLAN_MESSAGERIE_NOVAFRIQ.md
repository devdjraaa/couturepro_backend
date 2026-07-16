# Plan de messagerie — NOVAFRIQ GROUPE

**Objet** : organisation des adresses e-mail professionnelles, confidentialité et routage.
**Domaines** : `novafriq.africa` (corporate) · `gextimo.novafriq.africa` (produit).
**Version** : 1.2 — Document interne *(corrigé le 16/07/2026)*.

> **✅ Décisions actées** — (1) la **zone DNS est chez Cloudflare** (migration faite le 16/07,
> `novafriq.africa` désormais **actif/protégé** sur Cloudflare), donc SPF/DKIM/DMARC se collent **dans
> Cloudflare** ; (2) doublon `direction@` supprimé (§5) ; (3) contrainte **« 1 seule vraie boîte »**
> intégrée (§6-7) ; (4) **Option B retenue** : toutes les adresses sur `@novafriq.africa` avec préfixe
> `.gextimo` (§4) ; (5) DMARC : `p=none` d'abord, puis `p=quarantine` (§8).

---

## 1. Objectif
Une messagerie pro qui garantit :
- **Confidentialité** : chacun ne voit que le courrier qui le concerne (fin de la boîte partagée).
- **Stockage suffisant** : le courrier est stocké sur les **Gmail personnels** (15 Go chacun), pas sur
  la boîte pro (limitée à 10 Go).
- **Simplicité** : chacun travaille depuis son Gmail, mais **envoie et reçoit** avec son adresse pro.

## 2. Principe : la boîte pro sert de « tuyau »
- **Un alias n'est pas une boîte.** Plusieurs alias qui tombent dans une même boîte = tout le monde
  voit tout. La bonne solution = la **redirection (forwarder)** : l'adresse pro fait suivre vers le
  Gmail de la personne, **sans rien stocker** côté serveur.

| Élément | Rôle | Stockage |
|---|---|---|
| Boîte pro (Hostinger) | Relais (tuyau) | ~0 (ne conserve pas de copie) |
| Gmail de chaque personne | Réception + interface de travail | 15 Go / personne |

- **Réception** : `adresse@domaine` → redirigée → Gmail de la personne.
- **Envoi** : depuis Gmail, en « Envoyer en tant que » l'adresse pro (via SMTP Hostinger).
- **Confidentialité** : chacun ne reçoit que ses propres adresses.

## 3. Limites du plan Hostinger (à respecter)

| Paramètre | Limite |
|---|---|
| Stockage par boîte | 10 Go |
| Envois/jour (par boîte) | 1 000 / 24 h |
| Alias par boîte | 50 |
| **Redirections (forwarders) par boîte** | **10** |
| Pièce jointe max | 25 Mo |

**Conséquences** :
- Rester **sous 10 redirections par boîte**.
- L'**envoi automatique de l'app** (OTP, factures) **ne passe PAS** par cette boîte (la limite de
  1 000/jour serait vite atteinte) → service transactionnel dédié (§7).
- ⚠️ **Les réponses humaines « en tant que »** passent, elles, par le SMTP Hostinger et **comptent**
  dans les 1 000/jour. C'est sans risque au volume humain, mais à garder en tête.

## 4. ✅ DÉCISION ACTÉE : Option B — tout sur `@novafriq.africa`

Vu la contrainte **« 1 seule vraie boîte + 10 forwarders + 50 alias »**, on retient l'**Option B** :
toutes les adresses (corporate ET Gextimo) sont sur le **domaine parent `@novafriq.africa`**, les
adresses Gextimo prenant un préfixe `.gextimo` (ex. `support.gextimo@novafriq.africa`).

- **Une seule boîte, un seul domaine e-mail** → les 9 redirections tiennent dans la limite de 10.
- **Simple, gratuit, immédiat** (pas de MX de sous-domaine à créer, pas de 2ᵉ boîte à payer).
- Compromis accepté : branding `support.gextimo@novafriq.africa` (au lieu de
  `support@gextimo.novafriq.africa`). On pourra basculer en sous-domaine plus tard si le volume/le
  budget le justifient.

## 5. Liste d'adresses retenue
> ✅ **CONFIRMÉE PAR LA DIRECTION le 16/07/2026** — les 9 alias officiels sont exactement :
> `contact@` · `direction@` · `finance@` · `dpo@` · `partenariats@` (novafriq) +
> `contact.gextimo@` · `support.gextimo@` · `communaute.gextimo@` · `privacy.gextimo@`
> (tous `@novafriq.africa`). Le code a été aligné dessus (partenaires→`partenariats@`,
> alerte inscription→`direction@`).

*Principe : une adresse = quelqu'un qui la relève réellement.*

### NOVAFRIQ — `@novafriq.africa`
| Adresse | Rôle | Décision |
|---|---|---|
| `contact@` | Entrée générale (presse, institutionnels) | Conservée |
| `direction@` | Président : décisions, banques, signatures | Conservée |
| `finance@` | Comptabilité, DGI/OHADA, paie, fournisseurs (absorbe admin + facturation) | Conservée |
| `dpo@` | Protection des données (APDP) | Conservée (redirection) |
| `partenariats@` | Partenariats du groupe (commun à tout NOVAFRIQ) | Conservée |
| ~~`admin@`~~ | Administratif/juridique | Fusionnée → `finance@` |
| ~~`talents@`~~ | Recrutement | Reportée (via `contact@`) |
| ~~`noreply@`~~ | Envois automatiques corporate | Reportée |

### GEXTIMO — `@novafriq.africa` (Option B, préfixe `.gextimo`)
| Adresse | Rôle | Décision |
|---|---|---|
| `contact.gextimo@` | Entrée produit, prospects | Conservée |
| `support.gextimo@` | Assistance, bugs (absorbe réclamations + ventes) | Conservée |
| `communaute.gextimo@` | Community manager : réseaux, newsletter | Conservée |
| `privacy.gextimo@` | Droits des utilisateurs → redirige vers `dpo@` | Conservée (redirection) |
| `noreply.gextimo@` | Notifications app (OTP, factures, reçus) | **Service dédié (§7)** |
| ~~`reclamations@`~~ | Réclamations | Fusionnée → `support.gextimo@` |
| ~~`ventes@`~~ | Abonnements, devis | Fusionnée → `contact.gextimo@` / `support.gextimo@` |
| ~~`facturation@`~~ | Paiements, reçus | Fusionnée → `finance@` |

**Bilan : ~9 adresses réellement utiles aujourd'hui.**

## 6. Routage de réception (qui reçoit quoi) — 9 redirections sur **1 boîte**
Chaque adresse = une **redirection** vers le Gmail de la personne responsable. Comme tout est sur
`@novafriq.africa` (Option B), les **9 redirections tiennent dans la limite de 10 d'une seule boîte.**

| Adresse | Redirigée vers (Gmail de…) |
|---|---|
| `contact@novafriq.africa` | *(à compléter)* |
| `direction@novafriq.africa` | *(Président)* |
| `finance@novafriq.africa` | *(Responsable finance)* |
| `dpo@novafriq.africa` | *(DPO)* |
| `partenariats@novafriq.africa` | *(à compléter)* |
| `contact.gextimo@novafriq.africa` | *(à compléter)* |
| `support.gextimo@novafriq.africa` | supportgextimo@gmail.com *(déjà en place ✅)* |
| `communaute.gextimo@novafriq.africa` | *(Community manager)* |
| `privacy.gextimo@novafriq.africa` → `dpo@novafriq.africa` | *(puis Gmail du DPO)* |

**Total : 9 redirections — sous la limite de 10.** *(doublon `direction@` de la v1.0 supprimé.)*
**Réglage sur chaque redirection : « ne pas conserver de copie »** → la boîte reste un simple tuyau,
le stockage se fait sur Gmail.

> Note technique : la **redirection casse parfois le SPF** de l'expéditeur d'origine → un peu de
> courrier redirigé peut atterrir en spam chez certains. Gmail est généralement tolérant. Si un envoi
> légitime tombe en spam, le marquer « non spam » une fois suffit en général.

## 7. Envoi « en tant que » l'adresse pro (répondre depuis Gmail)
Dans chaque Gmail concerné : **Paramètres → Comptes et importation → « Envoyer en tant que » → Ajouter**.
- **Adresse** : l'adresse pro (ex. `support.gextimo@novafriq.africa`).
- **Serveur SMTP** : `smtp.hostinger.com` · **port 465 (SSL)** *(ou 587 TLS)*.
- **⚠️ Identifiants** : ceux de la **vraie boîte** (la seule qui a un mot de passe — ex.
  `direction@novafriq.africa`), **PAS** ceux de l'alias (un alias n'a pas de mot de passe).
  - **Cas où Hostinger REFUSE** d'envoyer « en tant qu'alias » (erreur d'authentification/de « from ») :
    alors soit l'adresse doit devenir une **vraie boîte** (si l'offre le permet), soit on route ces
    envois par le **service transactionnel** (§7bis). À **tester sur 1 adresse** avant de généraliser.
- Gmail envoie un **code** à l'adresse (qui arrive via la redirection) → le coller.

**Résultat** : la personne écrit depuis son Gmail, le destinataire voit l'adresse pro, une copie reste
dans Gmail.

## 7bis. Courrier automatique de l'app (`noreply.gextimo@`)
> ✅ **RÉSOLU le 16/07 — Brevo en production.** L'app envoie désormais ses OTP/notifs via **Brevo**
> (`smtp-relay.brevo.com`, expéditeur `noreply.gextimo@novafriq.africa`), domaine **authentifié** (DKIM
> Brevo dans Cloudflare). **Testé → arrive en boîte de réception** (plus de spam). Fini l'ancien
> `noreply@gextimo.com` via le Gmail perso `kounoumarcus@gmail.com`. Sauvegarde `.env` conservée sur le
> VPS (`.env.bak-brevo-*`) pour rollback. Plan Free = **300 e-mails/jour** (upgrade/SES si dépassé).

Les e-mails **automatiques** (OTP, factures, reçus, notifications) **ne passent pas** par Hostinger
(limite 1 000/jour). → **Service transactionnel dédié.**

| Service | Gratuit | Pour |
|---|---|---|
| **Brevo** | 300 e-mails/jour | Démarrage simple *(recommandé)* |
| Resend | 3 000 e-mails/mois | Orienté développeur |
| Amazon SES | ~0,10 $/1000 | Montée en charge (quasi illimité) |

**Mise en place** : configurer l'app pour envoyer via ce service, expéditeur
`noreply.gextimo@novafriq.africa` (ou `no-reply@novafriq.africa`) + ajouter ses enregistrements DNS
(SPF/DKIM) **dans Cloudflare**.

## 8. DNS — anti-spam (indispensable) — **désormais dans Cloudflare**
> ✅ **VÉRIFIÉ le 16/07 dans Cloudflare** — les 3 enregistrements sont **déjà en place** :
> - SPF : `novafriq.africa` TXT `v=spf1 include:_spf.mail.hostinger.com ~all` ✅
> - DKIM : signature active (confirmée par `signed-by: novafriq.africa` sur un envoi réel) ✅
> - DMARC : `_dmarc.novafriq.africa` TXT `v=DMARC1; p=none` ✅
>
> ⚠️ **La zone DNS est chez Cloudflare** depuis le 16/07. Tout enregistrement e-mail à venir (ex. Brevo)
> se colle **dans le dashboard Cloudflare** (DNS → Records), **en DNS-only (nuage gris)**.

- **SPF** : autoriser Hostinger **et** le service transactionnel dans le **même** enregistrement TXT.
  Ex. : `v=spf1 include:_spf.mail.hostinger.com include:spf.brevo.com ~all` *(un seul SPF par domaine !)*.
- **DKIM** : garder les CNAME DKIM Hostinger **+ ajouter** ceux du service transactionnel.
- **DMARC** : **commencer par `p=none`** (surveillance, aucun blocage) pendant 1-2 semaines pour
  vérifier que SPF/DKIM passent, **puis** passer à `p=quarantine`. *(La v1.0 sautait directement à
  `quarantine`, ce qui risque de mettre du courrier légitime en spam avant validation.)*

## 9. Récapitulatif de mise en place
1. ✅ **Trancher §4** : Option B (parent) ou A (sous-domaine).
2. Créer/confirmer la boîte pro `@novafriq.africa` (déjà là).
3. Créer les **9 redirections** (§6), option **« ne pas conserver de copie »**.
4. Sur chaque Gmail : configurer **« Envoyer en tant que »** (§7) — **tester d'abord 1 adresse**.
5. Choisir/configurer le **service transactionnel** pour `noreply.gextimo@` (§7bis).
6. Ajouter **SPF / DKIM / DMARC (p=none)** **dans Cloudflare** (§8) pour les deux domaines.
7. **Tester** : envoyer/recevoir sur 2-3 adresses, vérifier que rien ne tombe en spam. Puis passer
   DMARC en `p=quarantine`.

## 10. Règles de bonne gestion
- **Une adresse = une personne qui la relève.** Si personne ne la lit, elle ne doit pas exister.
- **Fusionner** tant que le volume est faible, **séparer** quand il grandit.
- La **boîte pro reste un relais** : ne jamais y accumuler de courrier (stockage sur Gmail).
- Le **transactionnel reste séparé** du courrier humain (services et limites différents).
