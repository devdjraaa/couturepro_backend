<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoVip;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalerieController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    // GET /galerie
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'photos_vip')) {
            return $gate;
        }

        $photos = PhotoVip::where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->get(['id', 'nom', 'file_url', 'taille_octets', 'created_at']);

        return response()->json($photos);
    }

    // POST /galerie
    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'photos_vip')) {
            return $gate;
        }

        // Vérifier quota mensuel photos VIP
        $config   = $atelier->abonnement?->getConfigEffective() ?? [];
        $maxPhotos = isset($config['max_photos_vip_par_mois']) ? (int) $config['max_photos_vip_par_mois'] : null;

        if ($maxPhotos !== null && $maxPhotos !== -1) {
            $photosCoMois = PhotoVip::where('atelier_id', $atelier->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            if ($photosCoMois >= $maxPhotos) {
                return response()->json([
                    'message'    => "Quota mensuel de photos atteint ({$maxPhotos} photos/mois).",
                    'plan_requis'=> 'premium_annuel',
                    'action'     => 'upgrade',
                ], 403);
            }
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'nom'   => ['nullable', 'string', 'max:150'],
        ]);

        $file     = $request->file('photo');
        $path     = $file->store('galerie/' . $atelier->id, 'public');
        $url      = url(Storage::url($path));

        $photo = PhotoVip::create([
            'atelier_id'   => $atelier->id,
            'uploaded_by'  => $request->user()->id,
            'file_path'    => $path,
            'file_url'     => $url,
            'nom'          => $request->input('nom', $file->getClientOriginalName()),
            'taille_octets'=> $file->getSize(),
        ]);

        return response()->json([
            'id'           => $photo->id,
            'nom'          => $photo->nom,
            'file_url'     => $photo->file_url,
            'taille_octets'=> $photo->taille_octets,
            'created_at'   => $photo->created_at,
        ], 201);
    }

    // DELETE /galerie/{photo}
    public function destroy(Request $request, PhotoVip $photo): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($photo->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        Storage::disk('public')->delete($photo->file_path);
        $photo->delete();

        return response()->json(['message' => 'Photo supprimée.']);
    }

    // GET /galerie/quota — quota mensuel restant
    public function quota(Request $request): JsonResponse
    {
        $atelier  = $this->getAtelier($request);
        $config   = $atelier->abonnement?->getConfigEffective() ?? [];
        $max      = isset($config['max_photos_vip_par_mois']) ? (int) $config['max_photos_vip_par_mois'] : null;
        $utilise  = PhotoVip::where('atelier_id', $atelier->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'utilise'   => $utilise,
            'max'       => $max,
            'restant'   => $max !== null ? max(0, $max - $utilise) : null,
            'illimite'  => $max === null || $max === -1,
        ]);
    }
}
