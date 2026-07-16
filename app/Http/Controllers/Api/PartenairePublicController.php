<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendCandidaturePartenaireEmails;
use App\Models\CandidaturePartenaire;
use App\Models\Partenaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// P204 : endpoints publics de la vitrine « Partenaires ».
class PartenairePublicController extends Controller
{
    /** GET /vitrine/partenaires — partenaires actifs groupés par catégorie (+ catégories du formulaire). */
    public function index(): JsonResponse
    {
        $partenaires = Partenaire::where('actif', true)
            ->orderBy('ordre')->orderBy('nom')
            ->get(['id', 'nom', 'categorie', 'logo_path', 'description', 'site_url', 'pays']);

        // Groupé par catégorie (structure évolutive : aucune catégorie n'est figée).
        $groupes = $partenaires->groupBy('categorie')
            ->map(fn ($items, $cat) => [
                'categorie'   => $cat,
                'partenaires' => $items->values(),
            ])
            ->values();

        // Catégories du menu déroulant : config ∪ catégories réellement utilisées.
        $categories = collect(config('partenaires.categories', []))
            ->merge($partenaires->pluck('categorie'))
            ->unique()->values();

        return response()->json([
            'groupes'     => $groupes,
            'categories'  => $categories,
        ]);
    }

    /** GET /vitrine/partenaires/cles — logos du bandeau d'accueil (partenaires « clés »). */
    public function cles(): JsonResponse
    {
        return response()->json(
            Partenaire::where('actif', true)->where('is_cle', true)
                ->orderBy('ordre')->orderBy('nom')
                ->get(['id', 'nom', 'logo_path', 'site_url'])
        );
    }

    /** POST /vitrine/partenaires/candidature — dépôt d'une candidature (modale). */
    public function candidater(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom_organisation'    => ['required', 'string', 'max:180'],
            'pays_region'         => ['nullable', 'string', 'max:120'],
            'categorie_souhaitee' => ['nullable', 'string', 'max:80'],
            'type_apport'         => ['nullable', 'string', 'max:2000'],
            'contact_nom'         => ['nullable', 'string', 'max:120'],
            'contact_email'       => ['required', 'email', 'max:180'],
            'contact_telephone'   => ['nullable', 'string', 'max:40'],
            'message'             => ['nullable', 'string', 'max:3000'],
        ]);

        $candidature = CandidaturePartenaire::create($data);

        // Confirmation candidat + alerte interne (JAMAIS de document contractuel ici).
        SendCandidaturePartenaireEmails::dispatch($candidature);

        return response()->json([
            'message' => 'Votre candidature a bien été reçue. Notre équipe reviendra vers vous.',
        ], 201);
    }
}
