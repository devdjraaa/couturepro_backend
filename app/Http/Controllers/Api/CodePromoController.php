<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\CodePromo;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// P153-158 : utilisation d'un code promo / ambassadeur côté utilisateur.
// Sécurité : rate-limit (route), message d'erreur générique (ne révèle pas si le code existe),
// unicité 1×/téléphone (contrainte DB + verrou), transaction.
class CodePromoController extends Controller
{
    use ResolvesAtelier;

    public function utiliser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        $user = $request->user();
        if (! $user instanceof Proprietaire) {
            return response()->json(['message' => 'Réservé au propriétaire du compte.'], 403);
        }

        $atelier   = $this->getAtelier($request);
        $code      = strtoupper(preg_replace('/\s+/', '', $data['code']));
        $telephone = Proprietaire::normalizePhone($user->telephone);

        // Message générique : ne révèle ni l'existence ni la raison exacte (anti-énumération).
        $refus = response()->json(['message' => 'Code invalide, expiré ou déjà utilisé.'], 422);

        $promo = CodePromo::where('code', $code)->first();
        if (! $promo || ! $promo->estValide()) {
            return $refus;
        }

        if ($promo->utilisations()->where('telephone', $telephone)->exists()) {
            return $refus;
        }

        $jours = (int) $promo->jours_bonus;

        try {
            DB::transaction(function () use ($promo, $user, $atelier, $telephone, $jours) {
                // Verrou : re-vérifie l'unicité et le quota sous transaction (anti-course).
                $dejaUtilise = $promo->utilisations()
                    ->where('telephone', $telephone)
                    ->lockForUpdate()
                    ->exists();
                if ($dejaUtilise) {
                    throw new \RuntimeException('deja_utilise');
                }
                if ($promo->max_utilisations !== null
                    && $promo->utilisations()->lockForUpdate()->count() >= $promo->max_utilisations) {
                    throw new \RuntimeException('quota');
                }

                $promo->utilisations()->create([
                    'proprietaire_id' => $user->id,
                    'telephone'       => $telephone,
                    'atelier_id'      => $atelier->id,
                ]);

                // P155 : les jours s'AJOUTENT au temps restant (pas « à partir d'aujourd'hui »).
                $abonnement = Abonnement::where('atelier_id', $atelier->id)
                    ->latest('timestamp_debut')
                    ->lockForUpdate()
                    ->first();

                if ($abonnement
                    && in_array($abonnement->statut, ['actif', 'essai'], true)
                    && $abonnement->timestamp_expiration
                    && $abonnement->timestamp_expiration->isFuture()) {
                    $abonnement->update([
                        'jours_restants'       => $abonnement->jours_restants + $jours,
                        'timestamp_expiration' => $abonnement->timestamp_expiration->copy()->addDays($jours),
                    ]);
                } elseif ($abonnement) {
                    // Abonnement expiré : le bonus repart d'aujourd'hui (il n'y avait plus de restant).
                    $abonnement->update([
                        'statut'               => $abonnement->statut === 'actif' ? 'actif' : 'essai',
                        'jours_restants'       => $jours,
                        'timestamp_expiration' => now()->addDays($jours),
                    ]);
                    $atelier->update(['statut' => $abonnement->statut]);
                } else {
                    throw new \RuntimeException('sans_abonnement');
                }
            });
        } catch (\RuntimeException) {
            return $refus;
        } catch (\Illuminate\Database\QueryException) {
            // Contrainte unique (course) → même refus générique.
            return $refus;
        }

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Code {$promo->code} appliqué !",
            'contenu'    => "+{$jours} jours ajoutés à votre temps restant.",
            'type'       => 'abonnement_active',
        ]);

        return response()->json([
            'message'      => "+{$jours} jours ajoutés à votre temps restant.",
            'jours_bonus'  => $jours,
        ]);
    }
}
