# SEO — Rendu Open Graph pour les robots sociaux (SSR ciblé)

## Problème
La vitrine `gextimo.novafriq.africa` est une SPA servie en statique par nginx. Les robots de
**WhatsApp / Facebook / X / LinkedIn** n'exécutent pas le JavaScript : au partage d'un lien
`/createurs/{id}`, ils ne voient que les balises OG **génériques** d'`index.html` (page d'accueil),
pas le nom / la photo / la description du designer. Résultat : previews de partage génériques.

## Solution (déjà codée côté backend)
Route Laravel qui renvoie un **HTML minimal** avec les vraies balises OG + JSON-LD du créateur :

```
GET https://gextimoapi.novafriq.africa/api/vitrine/og/createurs/{id}
```

- Renvoie `og:title` = nom du designer, `og:description` = bio, `og:image` = logo (ou l'og-cover
  par défaut), `og:url` = page canonique, `og:type=profile` + JSON-LD `LocalBusiness`.
- Pour un humain qui atterrirait dessus : redirection (`meta refresh` + JS) vers la vraie page SPA.
- Un compte non-designer / démo renvoie les balises génériques.

## Étape restante : config nginx (côté vitrine)
Détecter l'User-Agent des robots sur `/createurs/{id}` et proxifier vers la route ci-dessus ;
les visiteurs normaux continuent de recevoir la SPA.

Dans le `http { … }` (hors server) :

```nginx
map $http_user_agent $gx_social_bot {
    default 0;
    ~*(facebookexternalhit|Facebot|Twitterbot|WhatsApp|LinkedInBot|Slackbot|TelegramBot|Discordbot|Pinterest|redditbot|Applebot|Embedly|vkShare|W3C_Validator) 1;
}
```

Dans le `server { … }` de `gextimo.novafriq.africa`, AVANT le `location /` habituel :

```nginx
location ~ ^/createurs/(?<gx_crea>[^/]+)/?$ {
    if ($gx_social_bot) {
        proxy_pass https://gextimoapi.novafriq.africa/api/vitrine/og/createurs/$gx_crea;
        proxy_ssl_server_name on;
        proxy_set_header Host gextimoapi.novafriq.africa;
    }
    try_files $uri /index.html;   # visiteurs normaux → SPA
}
```

> Note : `proxy_pass` dans un `if` est un cas documenté qui fonctionne pour ce besoin.
> Ne PAS ajouter `Googlebot`/`bingbot` (ils exécutent le JS et voient déjà les bonnes balises).

Puis `nginx -t && systemctl reload nginx`.

## Déploiement backend
- `git pull` (route + `config/vitrine.php` + `VitrineController::ogCreateur`).
- Si le domaine vitrine diffère du défaut : définir `VITRINE_URL=https://…` dans le `.env` du backend.
- `php artisan config:cache && php artisan route:cache`.

## Test après déploiement
```bash
# doit renvoyer le nom du designer dans <title> / og:title (pas la home générique)
curl -s -A "facebookexternalhit/1.1" https://gextimo.novafriq.africa/createurs/<ID> | grep -i 'og:title'
# et un visiteur normal doit toujours recevoir la SPA :
curl -s https://gextimo.novafriq.africa/createurs/<ID> | grep -c 'id="root"'
```
Validateurs officiels : Facebook Sharing Debugger, X Card Validator, LinkedIn Post Inspector.
