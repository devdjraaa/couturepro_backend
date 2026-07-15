<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodePromo;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// P153/P158 : panneau admin des codes promo / ambassadeurs.
class CodePromoController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $codes = CodePromo::withCount('utilisations')
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->search, fn ($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($codes);
    }

    public function show(CodePromo $codePromo): JsonResponse
    {
        $codePromo->loadCount('utilisations');
        $codePromo->load(['utilisations' => fn ($q) => $q->latest()->limit(100)->with('proprietaire:id,nom,prenom,telephone')]);

        return response()->json($codePromo);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9\-]+$/i', Rule::unique('codes_promo', 'code')],
            'type'             => ['required', Rule::in(['evenement', 'ambassadeur'])],
            'jours_bonus'      => ['required', 'integer', 'min:1', 'max:365'],
            'expire_at'        => ['nullable', 'date', 'after:now'],
            'max_utilisations' => ['nullable', 'integer', 'min:1'],
            'note'             => ['nullable', 'string', 'max:255'],
        ]);

        $admin = $this->adminUser();

        $code = CodePromo::create([
            ...$data,
            'code'       => strtoupper($data['code']),
            'is_actif'   => true,
            'created_by' => $admin?->id,
        ]);

        $this->audit($admin, 'code_promo.creer', 'code_promo', $code->id, [
            'code' => $code->code, 'jours' => $code->jours_bonus,
        ], $request->ip());

        return response()->json($code, 201);
    }

    // Active / désactive un code (un code désactivé est immédiatement « mort »).
    public function toggle(Request $request, CodePromo $codePromo): JsonResponse
    {
        $codePromo->update(['is_actif' => ! $codePromo->is_actif]);

        $this->audit($this->adminUser(), 'code_promo.toggle', 'code_promo', $codePromo->id, [
            'is_actif' => $codePromo->is_actif,
        ], $request->ip());

        return response()->json($codePromo);
    }
}
