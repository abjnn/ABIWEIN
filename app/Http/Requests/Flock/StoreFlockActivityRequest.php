<?php

namespace App\Http\Requests\Flock;

use App\Enums\FlockActivityType;
use App\Enums\UserRole;
use App\Models\Flock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide une action horodatée du journal quotidien. */
class StoreFlockActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['description' => trim((string) $this->input('description'))]);
    }

    public function rules(): array
    {
        /** @var Flock $flock */
        $flock = $this->route('flock');
        $measuredTypes = implode(',', [
            FlockActivityType::Feeding->value,
            FlockActivityType::Watering->value,
            FlockActivityType::Weighing->value,
            FlockActivityType::Mortality->value,
        ]);

        return [
            'occurred_at' => ['required', 'date', 'after_or_equal:'.$flock->arrival_date->startOfDay()->toDateTimeString(), 'before_or_equal:now'],
            'type' => ['required', Rule::enum(FlockActivityType::class)],
            'description' => ['required', 'string', 'min:3', 'max:2000'],
            'quantity' => ['nullable', 'required_if:type,'.$measuredTypes, 'numeric', 'gt:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_at.after_or_equal' => 'L’action ne peut pas précéder l’arrivée de la bande.',
            'occurred_at.before_or_equal' => 'L’action ne peut pas être datée dans le futur.',
            'quantity.required_if' => 'Une quantité est obligatoire pour ce type d’action.',
            'quantity.gt' => 'La quantité doit être supérieure à zéro.',
        ];
    }
}
