<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermission;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminsController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = Admin::select('id', 'nom', 'prenom', 'email', 'role', 'permissions', 'is_active', 'derniere_connexion_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'admins'      => $admins,
            'permissions' => AdminPermission::PERMISSIONS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100',
            'prenom'      => 'required|string|max:100',
            'email'       => 'required|email|unique:admins,email',
            'password'    => ['required', Password::min(8)],
            'role'        => 'required|in:admin,support',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', AdminPermission::all()),
        ]);

        $admin = Admin::create([
            'nom'         => $data['nom'],
            'prenom'      => $data['prenom'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => $data['role'],
            'permissions' => $data['permissions'] ?? [],
            'is_active'   => true,
        ]);

        return response()->json(
            $admin->only('id', 'nom', 'prenom', 'email', 'role', 'permissions', 'is_active', 'created_at'),
            201
        );
    }

    public function update(Request $request, Admin $admin): JsonResponse
    {
        // Interdit de modifier un super_admin via cette route
        if ($admin->isSuperAdmin()) {
            return response()->json(['message' => 'Impossible de modifier un super_admin.'], 403);
        }

        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', AdminPermission::all()),
            'is_active'   => 'sometimes|boolean',
            'role'        => 'sometimes|in:admin,support',
        ]);

        $admin->update($data);

        return response()->json($admin->only('id', 'nom', 'prenom', 'email', 'role', 'permissions', 'is_active'));
    }

    public function destroy(Admin $admin): JsonResponse
    {
        // Interdit de supprimer un super_admin ou de se supprimer soi-même
        if ($admin->isSuperAdmin()) {
            return response()->json(['message' => 'Impossible de supprimer un super_admin.'], 403);
        }

        if ($admin->id === request()->user('admin')->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 403);
        }

        $admin->delete();

        return response()->json(['message' => 'Compte admin révoqué.']);
    }
}
