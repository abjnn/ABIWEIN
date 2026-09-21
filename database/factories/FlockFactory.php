<?php

namespace Database\Factories;

use App\Models\Flock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Flock> */
class FlockFactory extends Factory
{
    /** Définit une bande active avec des quantités techniquement cohérentes. */
    public function definition(): array
    {
        $initialQuantity = fake()->numberBetween(800, 2000);

        return [
            // La date dans le code évite les références difficiles à interpréter.
            'code' => 'BND-'.fake()->unique()->date('Ymd').'-'.fake()->unique()->numberBetween(10, 99),
            // Cobb 500 est utilisé uniquement comme valeur de démonstration modifiable.
            'breed' => 'Cobb 500',
            // La bande a commencé dans les six dernières semaines.
            'arrival_date' => fake()->dateTimeBetween('-6 weeks', '-1 week'),
            // La fin prévisionnelle est calculée environ huit semaines après le démarrage.
            'expected_end_date' => fake()->dateTimeBetween('+1 week', '+5 weeks'),
            // L’effectif initial sert de référence aux indicateurs de mortalité.
            'initial_quantity' => $initialQuantity,
            // L’effectif courant reste inférieur ou égal à l’effectif initial.
            'current_quantity' => $initialQuantity - fake()->numberBetween(0, 35),
            // Les relations réelles sont précisées par le seeder ou le test appelant.
            'supplier_id' => null,
            'current_building_id' => null,
            // La factory représente une bande en cours par défaut.
            'status' => 'active',
            // Une bande active n’est pas encore clôturée.
            'closed_at' => null,
            // Les observations générales restent facultatives.
            'notes' => null,
        ];
    }
}
