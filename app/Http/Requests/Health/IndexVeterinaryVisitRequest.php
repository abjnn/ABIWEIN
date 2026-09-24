<?php

namespace App\Http\Requests\Health;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/** Valide les filtres appliqués à la liste des visites sanitaires. */
class IndexVeterinaryVisitRequest extends FormRequest
{
    /** Les trois rôles actifs peuvent consulter le suivi sanitaire. */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active
            && ($user->hasRole(UserRole::Administrator)
                || $user->hasRole(UserRole::FarmManager)
                || $user->hasRole(UserRole::Veterinarian));
    }

    /** Chaque filtre reste facultatif et ne peut cibler qu’une donnée existante. */
    public function rules(): array
    {
        return [
            // Restreint les résultats à une bande précise.
            'flock_id' => ['nullable', 'integer', 'exists:flocks,id'],
            // Restreint les résultats à un vétérinaire précis.
            'veterinarian_id' => ['nullable', 'integer', 'exists:users,id'],
            // Cible les visites effectuées au jour exact choisi.
            'date' => ['nullable', 'date'],
        ];
    }

    /** Les messages français indiquent directement le filtre à corriger. */
    public function messages(): array
    {
        return [
            'flock_id.exists' => 'La bande sélectionnée n’existe plus.',
            'veterinarian_id.exists' => 'Le vétérinaire sélectionné n’existe plus.',
            'date.date' => 'La date de visite doit être valide.',
        ];
    }
}
