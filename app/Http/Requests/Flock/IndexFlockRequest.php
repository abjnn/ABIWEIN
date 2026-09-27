<?php

namespace App\Http\Requests\Flock;

use App\Enums\FlockStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide les quelques filtres utiles de la liste des bandes. */
class IndexFlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canViewFlocks() ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(FlockStatus::class)],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
        ];
    }
}
