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
        if (app()->environment('production') && ! config('abiwein.deployment_demo_enabled')) {
            throw new LogicException('Les comptes de démonstration sont interdits en production.');
        }

        // Chaque identité possède son adresse et son secret configurables, distincts des deux autres.
        $users = [
            [
                'name' => 'Nadège Nkomo',
                'email' => config('abiwein.demo_users.administrator.email'),
                'password' => config('abiwein.demo_users.administrator.password'),
                'legacy_email' => 'admin@abiwein.local',
                'role' => UserRole::Administrator,
            ],
            [
                'name' => 'Alain Tchoumi',
                'email' => config('abiwein.demo_users.farm_manager.email'),
                'password' => config('abiwein.demo_users.farm_manager.password'),
                'legacy_email' => 'responsable@abiwein.local',
                'role' => UserRole::FarmManager,
            ],
            [
                'name' => 'Dre Mireille Ngono',
                'email' => config('abiwein.demo_users.veterinarian.email'),
                'password' => config('abiwein.demo_users.veterinarian.password'),
                'legacy_email' => 'veterinaire@abiwein.local',
                'role' => UserRole::Veterinarian,
            ],
        ];

        $emails = array_column($users, 'email');
        $passwords = array_column($users, 'password');

        // En production, l'absence d'un secret bloque volontairement l'installation du compte concerné.
        if (in_array(null, $emails, true) || in_array(null, $passwords, true)) {
            throw new LogicException('Les six secrets ABIWEIN_DEMO_* doivent être configurés sur l’hébergeur.');
        }

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
