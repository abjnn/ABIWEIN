<?php

namespace App\Http\Requests\Flock;

use App\Enums\UserRole;
use App\Models\Flock;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide le transfert complet d'une bande vers un autre bâtiment. */
class TransferFlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notes' => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null]);
    }

    public function rules(): array
    {
        /** @var Flock $flock */
        $flock = $this->route('flock');

        return [
            'building_id' => [
                'required',
                'integer',
                Rule::notIn(array_filter([$flock->current_building_id])),
                Rule::exists('buildings', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('status', 'active')
                    ->whereIn('type', ['brooding', 'growing'])),
            ],
            'occurred_at' => ['required', 'date', 'after_or_equal:'.$flock->arrival_date->startOfDay()->toDateTimeString(), 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'building_id.not_in' => 'La bande occupe déjà ce bâtiment.',
            'building_id.exists' => 'Le bâtiment sélectionné est indisponible pour l’élevage.',
            'occurred_at.before_or_equal' => 'Le transfert ne peut pas être daté dans le futur.',
        ];
    }
}
