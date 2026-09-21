<?php

namespace Database\Factories;

use App\Models\Flock;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VeterinaryVisit> */
class VeterinaryVisitFactory extends Factory
{
    /**
     * Produit une visite sanitaire cohérente pour les tests et les démonstrations.
     * MODIFICATION : adaptez les textes ci-dessous pour changer les exemples générés.
     */
    public function definition(): array
    {
        return [
            // Ce champ rattache automatiquement la visite à une bande de démonstration.
            'flock_id' => Flock::factory(),
            // Ce champ crée un compte vétérinaire autorisé à signer le diagnostic.
            'veterinarian_id' => User::factory()->veterinarian(),
            // Cette date place la visite dans les quinze derniers jours.
            'visit_date' => fake()->dateTimeBetween('-15 days', 'now'),
            // Ce texte représente le constat clinique principal.
            'diagnosis' => 'État général satisfaisant, sans signe clinique préoccupant.',
            // Ce texte explique les mesures préventives conseillées à l’exploitation.
            'recommendations' => 'Maintenir la ventilation et surveiller quotidiennement la consommation d’eau.',
            // Cette date programme un contrôle dans les deux prochaines semaines.
            'next_visit_date' => fake()->dateTimeBetween('+7 days', '+14 days'),
        ];
    }
}
