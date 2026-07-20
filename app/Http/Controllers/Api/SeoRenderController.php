<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\VitrineSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * REL-2 / Pt 125 — Rendu HTML pour les ROBOTS (pré-rendu côté serveur).
 *
 * La vitrine est une application monopage : un robot sans JavaScript recevait
 * une coquille vide avec le même titre partout (audit du 18/07). Nginx route
 * désormais les User-Agents de robots vers cette page, qui rend un HTML
 * sémantique complet : titre PAR PAGE, description, canonique, Open Graph et le
 * contenu réel quand le serveur le possède (créateurs, profils).
 *
 * Les humains ne passent JAMAIS ici : leur expérience est inchangée.
 * Titres et descriptions : `VitrineSetting::seoPages()`, éditables en admin.
 */
class SeoRenderController extends Controller
{
    private const ORIGINE = 'https://gextimo.novafriq.africa';

    public function render(Request $request): Response
    {
        // Le chemin d'origine arrive via nginx (en-tête), jamais par la query :
        // la page rendue doit canoniser l'URL réellement demandée.
        $path = parse_url($request->header('X-Original-Path', '/'), PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $pages = VitrineSetting::seoPages();

        // Profil créateur : contenu dynamique complet.
        if (preg_match('#^/createurs/([\w-]+)$#', $path, $m)) {
            return $this->pageCreateur($m[1], $path, $pages);
        }

        $meta = $pages[$path] ?? null;
        $corps = '';

        if ($path === '/' || $path === '/createurs') {
            // Les pages qui comptent pour le référencement : le contenu réel.
            $createurs = Atelier::where('is_demo', false)
                ->where('type', 'designer')
                ->orderBy('nom')
                ->get(['id', 'nom', 'ville', 'specialite'])
                ->filter(fn ($a) => ($a->abonnement?->getConfigEffective()['visible_galerie'] ?? true) !== false);

            $items = $createurs->map(fn ($a) => sprintf(
                '<li><a href="%s/createurs/%s">%s</a>%s%s</li>',
                self::ORIGINE,
                e($a->id),
                e($a->nom),
                $a->specialite ? ' — ' . e($a->specialite) : '',
                $a->ville ? ' (' . e($a->ville) . ')' : '',
            ))->implode("\n");

            $corps = "<h2>Créateurs</h2>\n<ul>\n{$items}\n</ul>";
        }

        // Page inconnue du plan SEO : titre générique + lien vers l'accueil,
        // en 200 (c'est l'application qui tranchera le 404 pour les humains).
        $meta ??= $pages['/'];

        return $this->html($meta['titre'], $meta['description'], $path, $corps);
    }

    private function pageCreateur(string $id, string $path, array $pages): Response
    {
        $atelier = Atelier::where('id', $id)
            ->where('is_demo', false)
            ->where('type', 'designer')
            ->first();

        if (! $atelier) {
            $meta = $pages['/createurs'] ?? $pages['/'];

            return $this->html($meta['titre'], $meta['description'], '/createurs', '', 404);
        }

        $creations = $atelier->vetements()
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->pluck('nom');

        $note = $atelier->avis()->where('statut', 'valide')->avg('note');
        $nb   = $atelier->avis()->where('statut', 'valide')->count();

        $corps = '<p>' . e((string) $atelier->bio) . '</p>';
        if ($atelier->ville) {
            $corps .= '<p>Atelier basé à ' . e($atelier->ville) . '.</p>';
        }
        if ($note) {
            $corps .= '<p>Note moyenne : ' . round($note, 1) . '/5 (' . $nb . ' avis).</p>';
        }
        if ($creations->isNotEmpty()) {
            $corps .= "<h2>Créations</h2>\n<ul>\n"
                . $creations->map(fn ($n) => '<li>' . e($n) . '</li>')->implode("\n")
                . "\n</ul>";
        }

        $titre = $atelier->nom . ($atelier->specialite ? ' — ' . $atelier->specialite : '') . ' | Gextimo';
        $desc  = mb_substr(trim(($atelier->bio ?: $atelier->nom . ', créateur de mode africaine sur Gextimo.')), 0, 160);

        return $this->html($titre, $desc, $path, $corps);
    }

    private function html(string $titre, string $desc, string $path, string $corps, int $statut = 200): Response
    {
        $url = self::ORIGINE . ($path === '/' ? '' : $path);
        $t   = e($titre);
        $d   = e($desc);

        $html = <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{$t}</title>
<meta name="description" content="{$d}">
<link rel="canonical" href="{$url}">
<meta property="og:title" content="{$t}">
<meta property="og:description" content="{$d}">
<meta property="og:url" content="{$url}">
<meta property="og:type" content="website">
<meta property="og:image" content="https://gextimo.novafriq.africa/og-cover.png">
</head>
<body>
<h1>{$t}</h1>
{$corps}
<p><a href="https://gextimo.novafriq.africa/">Gextimo — la marketplace des créateurs de mode africains</a></p>
</body>
</html>
HTML;

        return response($html, $statut)
            ->header('Content-Type', 'text/html; charset=utf-8')
            // Les robots peuvent recevoir une version vieille de 10 minutes :
            // inutile de recalculer la liste des créateurs à chaque passage.
            ->header('Cache-Control', 'public, max-age=600');
    }
}
