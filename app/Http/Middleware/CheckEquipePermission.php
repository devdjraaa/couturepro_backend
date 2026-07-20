<?php

namespace App\Http\Middleware;

use App\Models\EquipeMembre;
use App\Models\PermissionEquipe;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique le référentiel de permissions d'équipe côté SERVEUR.
 *
 * ⚠️ Constat du 20/07 : les 19 permissions du référentiel étaient configurées par
 * le propriétaire, renvoyées au front — qui masquait les boutons correspondants —
 * et **vérifiées nulle part**. Un membre d'équipe dont le rôle excluait
 * `clients.delete` ne voyait pas le bouton, mais un appel direct à
 * `DELETE /api/clients/{id}` passait sans obstacle. Les permissions étaient donc
 * un confort d'affichage, pas une protection.
 *
 * Le PROPRIÉTAIRE n'est pas un membre d'équipe : il n'est jamais concerné par ce
 * contrôle. Seules les requêtes authentifiées en tant qu'`EquipeMembre` le sont.
 */
class CheckEquipePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user instanceof EquipeMembre) {
            return $next($request);
        }

        $permissions = PermissionEquipe::getForAtelier($user->atelier_id, $user->role);

        if (! in_array($permission, $permissions, true)) {
            return response()->json([
                'message'    => 'Votre rôle ne vous permet pas cette action. Demandez au responsable de l\'atelier de vous y autoriser.',
                'code'       => 'permission_refusee',
                'permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
