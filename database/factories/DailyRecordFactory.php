<?php

namespace Database\Factories;

use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DailyRecord> */
class DailyRecordFactory extends Factory
{
    /** Produit un relevé quotidien complet pour les scénarios automatisés. */
    public function definition(): array
    {
        return [
            // Une bande fictive est créée si le test n’en fournit pas.
            'flock_id' => Flock::factory(),
            // La date de relevé se situe dans la semaine courante.
            'record_date' => fake()->dateTimeBetween('-6 days', 'now'),
            // Les mesures ci-dessous restent positives et plausibles pour la démonstration.
            'mortality' => fake()->numberBetween(0, 5),
            'feed_kg' => fake()->randomFloat(2, 50, 180),
            'water_liters' => fake()->randomFloat(2, 80, 300),
            'average_weight_kg' => fake()->randomFloat(3, 0.4, 2.5),
            // La majorité des journées sont normales, avec quelques états à surveiller.
            'health_status' => fake()->randomElement(['good', 'good', 'good', 'watch']),
            // Une phrase courte facilite l’aperçu dans le tableau de bord.
            'observations' => fake()->sentence(),
            // Le relevé conserve toujours l’identité de son auteur.
            'recorded_by' => User::factory(),
        ];
    }
}
