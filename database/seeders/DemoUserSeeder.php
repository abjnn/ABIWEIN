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

        // Chaque identité possède son adresse et son secret configurables, distincts des deux autres.
        $users = [
            [
                'name' => 'Nadège Nkomo',
                'email' => env('ABIWEIN_DEMO_ADMIN_EMAIL', 'nadege.nkomo@abiwein.local'),
                'password' => env('ABIWEIN_DEMO_ADMIN_PASSWORD', 'Nadege#Abiwein26!'),
                'legacy_email' => 'admin@abiwein.local',
                'role' => UserRole::Administrator,
            ],
            [
                'name' => 'Alain Tchoumi',
                'email' => env('ABIWEIN_DEMO_MANAGER_EMAIL', 'alain.tchoumi@abiwein.local'),
                'password' => env('ABIWEIN_DEMO_MANAGER_PASSWORD', 'Ferme#Tchoumi26!'),
                'legacy_email' => 'responsable@abiwein.local',
                'role' => UserRole::FarmManager,
            ],
            [
                'name' => 'Dre Mireille Ngono',
                'email' => env('ABIWEIN_DEMO_VET_EMAIL', 'mireille.ngono@abiwein.local'),
                'password' => env('ABIWEIN_DEMO_VET_PASSWORD', 'Sante#Ngono2026!'),
                'legacy_email' => 'veterinaire@abiwein.local',
                'role' => UserRole::Veterinarian,
            ],
        ];

        $emails = array_column($users, 'email');
        $passwords = array_column($users, 'password');

        // Une erreur de configuration est signalée avant toute écriture partielle en base.
        if (count(array_unique($emails)) !== count($emails) || count(array_unique($passwords)) !== count($passwords)) {
            throw new LogicException('Chaque compte de démonstration doit avoir un e-mail et un mot de passe distincts.');
        }

        foreach ($users as $userData) {
            // La recherche de l’ancienne adresse migre les trois comptes existants sans doublon.
            $user = User::query()->where('email', $userData['email'])->first()
                ?? User::query()->where('email', $userData['legacy_email'])->first()
                ?? User::query()->where('role', $userData['role'])->oldest('id')->first()
                ?? new User;

            $user->forceFill([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
