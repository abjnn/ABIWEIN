<?php

namespace App\Http\Requests\Flock;

use App\Enums\UserRole;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide la création d'une bande et son affectation initiale éventuelle. */
class StoreFlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /** Normalise la référence afin de conserver le même format dans tout le registre. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'breed' => trim((string) $this->input('breed')),
            'notes' => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', 'unique:flocks,code'],
            'breed' => ['required', 'string', 'max:100'],
            'arrival_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'initial_quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where(fn (Builder $query): Builder => $query->where('is_active', true))],
            'current_building_id' => [
                'nullable',
                'integer',
                Rule::exists('buildings', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('status', 'active')
                    ->whereIn('type', ['brooding', 'growing'])),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'La référence de la bande est obligatoire.',
            'code.regex' => 'La référence accepte uniquement les lettres, chiffres, points, tirets et tirets bas.',
            'code.unique' => 'Cette référence est déjà utilisée.',
            'breed.required' => 'La souche ou race est obligatoire.',
            'arrival_date.before_or_equal' => 'La date d’arrivée ne peut pas être future.',
            'expected_end_date.after_or_equal' => 'La fin prévisionnelle doit suivre la date d’arrivée.',
            'initial_quantity.min' => 'L’effectif initial doit être supérieur à zéro.',
            'supplier_id.exists' => 'Le fournisseur sélectionné est introuvable ou inactif.',
            'current_building_id.exists' => 'Le bâtiment sélectionné est indisponible pour l’élevage.',
        ];
    }
}
