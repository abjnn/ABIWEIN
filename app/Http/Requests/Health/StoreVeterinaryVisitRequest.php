<?php

namespace App\Http\Requests\Health;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide et sécurise l’enregistrement d’une visite vétérinaire. */
class StoreVeterinaryVisitRequest extends FormRequest
{
    /** Seuls un vétérinaire actif ou un administrateur actif peut créer une visite. */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active
            && ($user->hasRole(UserRole::Administrator) || $user->hasRole(UserRole::Veterinarian));
    }

    /**
     * Un vétérinaire signe toujours sa propre visite.
     * Cette préparation empêche de falsifier son identifiant depuis le navigateur.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user()?->hasRole(UserRole::Veterinarian)) {
            $this->merge(['veterinarian_id' => $this->user()->getKey()]);
        }
    }

    /** Chaque règle protège la cohérence clinique et relationnelle de la visite. */
    public function rules(): array
    {
        return [
            // La visite doit obligatoirement concerner une bande existante.
            'flock_id' => ['required', 'integer', 'exists:flocks,id'],
            // La signature doit appartenir à un compte vétérinaire actif.
            'veterinarian_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('role', UserRole::Veterinarian->value)
                        ->where('is_active', true)
                ),
            ],
            // Une visite réalisée ne peut pas être datée dans le futur.
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            // Le diagnostic constitue le contenu clinique obligatoire de la fiche.
            'diagnosis' => ['required', 'string', 'min:3', 'max:5000'],
            // Les recommandations peuvent rester vides si aucune mesure n’est nécessaire.
            'recommendations' => ['nullable', 'string', 'max:5000'],
            // Le contrôle suivant, s’il existe, ne peut pas précéder la visite actuelle.
            'next_visit_date' => ['nullable', 'date', 'after_or_equal:visit_date'],
        ];
    }

    /** Les messages sont formulés pour les utilisateurs de l’exploitation. */
    public function messages(): array
    {
        return [
            'flock_id.required' => 'Veuillez sélectionner la bande examinée.',
            'flock_id.exists' => 'La bande sélectionnée n’existe plus.',
            'veterinarian_id.required' => 'Veuillez sélectionner le vétérinaire responsable.',
            'veterinarian_id.exists' => 'Le compte sélectionné doit être un vétérinaire actif.',
            'visit_date.required' => 'La date de visite est obligatoire.',
            'visit_date.date' => 'La date de visite doit être valide.',
            'visit_date.before_or_equal' => 'Une visite réalisée ne peut pas être datée dans le futur.',
            'diagnosis.required' => 'Le diagnostic ou constat clinique est obligatoire.',
            'diagnosis.min' => 'Le diagnostic doit contenir au moins 3 caractères.',
            'diagnosis.max' => 'Le diagnostic ne peut pas dépasser 5 000 caractères.',
            'recommendations.max' => 'Les recommandations ne peuvent pas dépasser 5 000 caractères.',
            'next_visit_date.date' => 'La date de la prochaine visite doit être valide.',
            'next_visit_date.after_or_equal' => 'La prochaine visite ne peut pas précéder la visite actuelle.',
        ];
    }
}
