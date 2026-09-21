<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

/** Crée trois comptes locaux représentatifs des rôles ABIWEIN. */
class DemoUserSeeder extends Seeder
{
    /** Insère ou actualise les comptes sans créer de doublon lors d’une relance. */
    public function run(): void
    {
        // Cette garde reste ici afin qu’un appel direct au seeder soit également sûr.
        if (app()->environment('production')) {
            throw new LogicException('Les comptes de démonstration sont interdits en production.');
        }

        // Ce mot de passe est réservé à la démonstration locale et doit être changé en production.
        $configuredPassword = trim((string) env('ABIWEIN_DEMO_PASSWORD', ''));
        $demoPassword = Hash::make($configuredPassword !== '' ? $configuredPassword : 'Abiwein@Demo2026');

        // Le tableau centralise les identités pour modifier facilement les données de démonstration.
        $users = [
            ['name' => 'Administrateur ABIWEIN', 'email' => 'admin@abiwein.local', 'role' => UserRole::Administrator],
            ['name' => 'Responsable d’exploitation', 'email' => 'responsable@abiwein.local', 'role' => UserRole::FarmManager],
            ['name' => 'Vétérinaire ABIWEIN', 'email' => 'veterinaire@abiwein.local', 'role' => UserRole::Veterinarian],
        ];

        foreach ($users as $userData) {
            // updateOrCreate rend le seeder répétable sans violer l’unicité de l’e-mail.
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $demoPassword,
                    'role' => $userData['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
