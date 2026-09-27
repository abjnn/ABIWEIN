<?php

namespace App\Http\Requests\Flock;

use App\Enums\FlockHealthStatus;
use App\Enums\UserRole;
use App\Models\Flock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Contrôle le résumé technique unique d'une bande pour une journée. */
class StoreDailyRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'observations' => filled($this->input('observations')) ? trim((string) $this->input('observations')) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Flock $flock */
        $flock = $this->route('flock');

        return [
            'record_date' => ['required', 'date', 'after_or_equal:'.$flock->arrival_date->toDateString(), 'before_or_equal:today'],
            'mortality' => ['required', 'integer', 'min:0', 'max:10000000'],
            'feed_kg' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'water_liters' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'average_weight_kg' => ['nullable', 'numeric', 'gt:0', 'max:99.999'],
            'health_status' => ['required', Rule::enum(FlockHealthStatus::class)],
            'observations' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'record_date.after_or_equal' => 'Le relevé ne peut pas précéder l’arrivée de la bande.',
            'record_date.before_or_equal' => 'Le relevé ne peut pas être daté dans le futur.',
            'mortality.min' => 'La mortalité ne peut pas être négative.',
            'average_weight_kg.gt' => 'Le poids moyen doit être supérieur à zéro.',
            'health_status.enum' => 'L’état sanitaire sélectionné n’est pas autorisé.',
        ];
    }
}
