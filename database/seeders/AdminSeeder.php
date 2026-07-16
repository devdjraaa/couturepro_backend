<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin principal — CHANGER le mot de passe en production
        Admin::updateOrCreate(
            ['email' => 'admin.gextimo@novafriq.africa'],
            [
                'nom'         => 'Super',
                'prenom'      => 'Admin',
                'email'       => 'admin.gextimo@novafriq.africa',
                'password'    => Hash::make('Gextimo@2026!'), // ← CHANGER EN PRODUCTION
                'role'        => 'super_admin',
                'permissions' => null, // null = accès total
                'is_active'   => true,
            ]
        );

        $this->command->info('✅ AdminSeeder : super_admin créé (email: admin.gextimo@novafriq.africa)');
        $this->command->warn('⚠️  Pensez à changer le mot de passe en production !');
    }
}
