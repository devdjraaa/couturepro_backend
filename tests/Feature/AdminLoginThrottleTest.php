<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Le login de l'administration doit être limité en débit.
 *
 * Sans throttle, le panneau d'administration — l'accès le plus sensible — était
 * brute-forçable : un attaquant pouvait tester des milliers de mots de passe
 * sans être ralenti. Le login de l'app, lui, était déjà limité (throttle:10,1).
 */
class AdminLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Les autres tests peuvent avoir consommé le compteur : on repart propre.
        RateLimiter::clear('');
    }

    public function test_le_login_admin_bloque_apres_cinq_tentatives(): void
    {
        Admin::create([
            'nom' => 'Admin', 'prenom' => 'Test', 'email' => 'brute@test.local',
            'password' => bcrypt('leBonMotDePasse'), 'role' => 'super_admin', 'is_active' => true,
        ]);

        $mauvais = ['email' => 'brute@test.local', 'password' => 'faux'];

        // 5 tentatives autorisées (elles échouent en 401, mais passent le throttle).
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/auth/login', $mauvais)->assertStatus(401);
        }

        // La 6e est bloquée par la limite de débit.
        $this->postJson('/api/admin/auth/login', $mauvais)->assertStatus(429);
    }
}
