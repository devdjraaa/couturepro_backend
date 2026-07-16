<?php

namespace App\Http\Middleware;

use App\Models\GxtClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// P202 / Espace Client v3 — isole les jetons "client vitrine" des jetons "espace pro"
// (Proprietaire / EquipeMembre). Le même magasin de jetons Sanctum est partagé ; ce
// garde-fou empêche un jeton client d'atteindre les routes pro et inversement.
//   account:client → réservé aux GxtClient
//   account:app    → réservé aux comptes pro (tout SAUF GxtClient)
class EnsureAccountType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $isClient = $request->user() instanceof GxtClient;

        if ($type === 'client' && ! $isClient) {
            abort(403, 'Réservé à l’espace client.');
        }
        if ($type === 'app' && $isClient) {
            abort(403, 'Réservé à l’espace professionnel.');
        }

        return $next($request);
    }
}
