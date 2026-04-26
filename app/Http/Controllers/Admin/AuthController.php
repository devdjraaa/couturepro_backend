<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use LogsAdminAction;

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (! $admin->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        $admin->update(['derniere_connexion_at' => now()]);

        $this->audit($admin, 'admin.login', 'admin', $admin->id, [], $request->ip());

        $token = $admin->createToken('admin-token', ['*'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin->only(['id', 'nom', 'prenom', 'email', 'role', 'permissions']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $this->adminUser();
        $this->audit($admin, 'admin.logout', 'admin', $admin->id, [], $request->ip());

        $admin->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $this->adminUser();

        return response()->json($admin->only(['id', 'nom', 'prenom', 'email', 'role', 'permissions', 'derniere_connexion_at']));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'ancien'  => ['required', 'string'],
            'nouveau' => ['required', 'string', 'min:8'],
        ]);

        if (! Hash::check($data['ancien'], $admin->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $admin->update(['password' => Hash::make($data['nouveau'])]);

        $this->audit($admin, 'admin.change_password', 'admin', $admin->id, [], $request->ip());

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}
