<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Building> */
class BuildingFactory extends Factory
{
    /** Définit un bâtiment fictif cohérent pour les tests et démonstrations. */
    public function definition(): array
    {
        return [
            // Le code est court, unique et facile à repérer dans les tableaux.
            'code' => 'BAT-'.fake()->unique()->numberBetween(100, 999),
            // Le nom explicite la fonction sans dépendre du code technique.
            'name' => 'Bâtiment '.fake()->unique()->numberBetween(1, 99),
            // Les types correspondent aux zones prévues dans le cadrage.
            'type' => fake()->randomElement(['brooding', 'growing', 'cold_storage', 'stock']),
            // La capacité reste réaliste pour un bâtiment d’élevage de démonstration.
            'capacity' => fake()->numberBetween(500, 3000),
            // Un bâtiment de démonstration est actif par défaut.
            'status' => 'active',
            // Les notes facultatives restent vides tant qu’aucune précision n’est nécessaire.
            'notes' => null,
        ];
    }
}
