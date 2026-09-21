<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * SÉCURITÉ DES DONNÉES DE DÉMONSTRATION
         * Cette condition empêche la création des comptes fictifs en production.
         * Pour autoriser un jeu de démonstration dans un autre environnement,
         * modifiez uniquement la liste passée à environment().
         */
        if ($this->command?->getLaravel()->environment('production')) {
            $this->command?->warn('Les données de démonstration ABIWEIN ne sont jamais créées en production.');

            return;
        }

        // Les comptes doivent exister avant les données qui référencent leurs identifiants.
        $this->call(DemoUserSeeder::class);

        // Le scénario de ferme est ensuite créé de manière répétable et transactionnelle.
        $this->call(DemoFarmSeeder::class);
    }
}
