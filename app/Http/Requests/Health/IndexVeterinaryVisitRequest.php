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
            // Recherche dans le code de bande, le diagnostic et les recommandations.
            'search' => ['nullable', 'string', 'max:120'],
            // Restreint les résultats à une bande précise.
            'flock_id' => ['nullable', 'integer', 'exists:flocks,id'],
            // Restreint les résultats à un vétérinaire précis.
            'veterinarian_id' => ['nullable', 'integer', 'exists:users,id'],
            // Définit le premier jour de la période recherchée.
            'date_from' => ['nullable', 'date'],
            // Empêche la fin de période de précéder son début.
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /** Les messages français indiquent directement le filtre à corriger. */
    public function messages(): array
    {
        return [
            'search.max' => 'La recherche ne peut pas dépasser 120 caractères.',
            'flock_id.exists' => 'La bande sélectionnée n’existe plus.',
            'veterinarian_id.exists' => 'Le vétérinaire sélectionné n’existe plus.',
            'date_from.date' => 'La date de début doit être valide.',
            'date_to.date' => 'La date de fin doit être valide.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
