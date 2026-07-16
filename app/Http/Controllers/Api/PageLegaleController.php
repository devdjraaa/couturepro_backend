<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Pages légales éditables (footer vitrine) : lecture publique + édition back-office
// (éditeur riche). Le HTML est assaini côté serveur avant stockage.
class PageLegaleController extends Controller
{
    /** Les 11 pages du footer — liste fermée (aucune création libre). */
    public const CLES = [
        'confidentialite', 'mentions', 'cookies', 'protection_donnees', 'cgu',
        'droits_createurs', 'conditions_vente', 'produits_interdits',
        'livraison_retours', 'regles_communaute', 'contact_reclamations',
    ];

    /** GET /vitrine/pages/{cle} — public. {personnalise:false} = la vitrine garde son texte i18n. */
    public function show(string $cle): JsonResponse
    {
        abort_unless(in_array($cle, self::CLES, true), 404);

        $page = DB::table('pages_legales')->where('cle', $cle)->first();
        if (! $page || (empty($page->contenu_fr) && empty($page->contenu_en))) {
            return response()->json(['cle' => $cle, 'personnalise' => false]);
        }

        return response()->json([
            'cle'          => $cle,
            'personnalise' => true,
            'titre_fr'     => $page->titre_fr,
            'titre_en'     => $page->titre_en,
            'contenu_fr'   => $page->contenu_fr,
            'contenu_en'   => $page->contenu_en,
            'updated_at'   => $page->updated_at,
        ]);
    }

    /** GET /admin/pages — état des 11 pages (personnalisée ou texte par défaut). */
    public function index(): JsonResponse
    {
        $enBase = DB::table('pages_legales')->get()->keyBy('cle');

        return response()->json(collect(self::CLES)->map(fn ($cle) => [
            'cle'          => $cle,
            'personnalise' => isset($enBase[$cle]) && (! empty($enBase[$cle]->contenu_fr) || ! empty($enBase[$cle]->contenu_en)),
            'titre_fr'     => $enBase[$cle]->titre_fr ?? null,
            'updated_at'   => $enBase[$cle]->updated_at ?? null,
        ])->values());
    }

    /** GET /admin/pages/{cle} — contenu complet pour l'éditeur. */
    public function showAdmin(string $cle): JsonResponse
    {
        abort_unless(in_array($cle, self::CLES, true), 404);
        $page = DB::table('pages_legales')->where('cle', $cle)->first();

        return response()->json([
            'cle'        => $cle,
            'titre_fr'   => $page->titre_fr ?? null,
            'titre_en'   => $page->titre_en ?? null,
            'contenu_fr' => $page->contenu_fr ?? null,
            'contenu_en' => $page->contenu_en ?? null,
        ]);
    }

    /** PUT /admin/pages/{cle} — enregistre le contenu de l'éditeur (HTML assaini). */
    public function update(Request $request, string $cle): JsonResponse
    {
        abort_unless(in_array($cle, self::CLES, true), 404);

        $data = $request->validate([
            'titre_fr'   => ['nullable', 'string', 'max:200'],
            'titre_en'   => ['nullable', 'string', 'max:200'],
            'contenu_fr' => ['nullable', 'string', 'max:200000'],
            'contenu_en' => ['nullable', 'string', 'max:200000'],
        ]);

        DB::table('pages_legales')->updateOrInsert(
            ['cle' => $cle],
            [
                'id'         => DB::table('pages_legales')->where('cle', $cle)->value('id') ?? (string) Str::uuid(),
                'titre_fr'   => $data['titre_fr'] ?? null,
                'titre_en'   => $data['titre_en'] ?? null,
                'contenu_fr' => $this->assainir($data['contenu_fr'] ?? null),
                'contenu_en' => $this->assainir($data['contenu_en'] ?? null),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $this->showAdmin($cle);
    }

    /** Assainissement serveur : pas de scripts/iframes ni de handlers inline dans le HTML stocké. */
    private function assainir(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }
        $html = preg_replace('#<\s*(script|iframe|object|embed|form)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('#<\s*(script|iframe|object|embed|form)[^>]*/?\s*>#i', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2#i', '$1=$2#$2', $html);

        return trim($html) === '' ? null : $html;
    }
}
