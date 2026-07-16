<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use Illuminate\Http\Response;

// P199 : sitemap dynamique des profils créateurs publics (indexation SEO).
class SitemapController extends Controller
{
    private const BASE = 'https://gextimo.novafriq.africa';

    public function createurs(): Response
    {
        // Profils designers actifs — accessibles publiquement à /createurs/{id}.
        $ateliers = Atelier::where('type', 'designer')
            ->whereIn('statut', ['actif', 'essai'])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'updated_at']);

        $urls = $ateliers->map(function ($a) {
            $loc  = self::BASE . '/createurs/' . $a->id;
            $last = optional($a->updated_at)->toDateString();
            return "  <url><loc>{$loc}</loc>"
                . ($last ? "<lastmod>{$last}</lastmod>" : '')
                . "<changefreq>weekly</changefreq><priority>0.7</priority></url>";
        })->implode("\n");

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $urls . "\n"
            . "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
