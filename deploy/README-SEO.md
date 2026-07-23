# Routage des robots vers le pré-rendu (P1)

## Ce que c'était

Le `SeoRenderController` fonctionnait depuis le 20/07, mais **aucune règle nginx ne
lui envoyait de robot**. Googlebot recevait la coquille de l'application : un titre
générique, aucune description propre à la page, aucun contenu. Google indexait une
page vide depuis le lancement du site.

Deux fichiers de suivi se contredisaient sur ce point — l'un le déclarait livré,
l'autre manquant. C'est un `curl -A Googlebot` qui a tranché, le 23/07.

## Les deux fichiers

| Fichier | Destination sur le serveur |
|---|---|
| `gextimo-seo-bots.conf` | `/etc/nginx/conf.d/gextimo-seo-bots.conf` |
| `gextimo_frontend.nginx.conf` | `/etc/nginx/sites-available/gextimo_frontend.conf` |

⚠️ **La copie du serveur fait autorité.** Ce dépôt en est un miroir, tenu à jour à la
main : le workflow de déploiement n'a jamais pu écrire ce vhost (`600 root:root`, hors
du `sudo` autorisé). Vérifier la dérive de temps en temps.

## Trois pièges rencontrés, à ne pas réapprendre

1. **`proxy_pass` ne peut pas porter de chemin dans un `if`.** nginx refuse la
   configuration. D'où le détour par `return 418` et une location nommée : c'est la
   seule forme qui passe, pas une coquetterie.
2. **Le pré-rendu lit le chemin dans l'en-tête `X-Original-Path`**, pas dans l'URL ni
   un paramètre. Sans lui, *toutes* les pages reçoivent le rendu de l'accueil — et le
   défaut est invisible tant qu'on ne teste que la page d'accueil.
3. **Seules les pages vont au pré-rendu.** Sans le second critère (`$gx_page`), un
   robot demandant `robots.txt`, le sitemap ou une image recevrait du HTML à la
   place du fichier : on casserait le référencement là où l'on croit le réparer.

## Vérifier

```bash
# Un robot doit recevoir le titre de la PAGE
curl -s -A "Googlebot" https://gextimo.novafriq.africa/createurs | grep -o "<title>[^<]*"

# Un navigateur doit recevoir l'application
curl -s -A "Mozilla/5.0 Chrome/120" https://gextimo.novafriq.africa/createurs | grep -o "<title>[^<]*"

# Ces trois-là ne doivent JAMAIS être pré-rendus
curl -s -A "Googlebot" https://gextimo.novafriq.africa/robots.txt | head -1
curl -s -o /dev/null -w "%{content_type}\n" -A "Googlebot" https://gextimo.novafriq.africa/sitemap.xml
```

Les deux titres doivent **différer**. S'ils sont identiques, le routage est de nouveau
inerte.
