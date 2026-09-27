<?php

namespace App\Http\Requests\Flock;

use App\Enums\UserRole;
use App\Models\Flock;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Limite la modification aux informations descriptives de la bande. */
class UpdateFlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

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
        /** @var Flock|null $flock */
        $flock = $this->route('flock');

        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', Rule::unique('flocks', 'code')->ignore($flock)],
            'breed' => ['required', 'string', 'max:100'],
            'arrival_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where(fn (Builder $query): Builder => $query->where('is_active', true))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
