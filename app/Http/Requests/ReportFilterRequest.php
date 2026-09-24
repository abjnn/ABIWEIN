<?php

namespace App\Http\Requests;

use App\Models\FarmReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide les filtres appliqués à l'archive des rapports administratifs. */
class ReportFilterRequest extends FormRequest
{
    /** Les rapports sont déjà protégés par les middlewares de l’espace privé. */
    public function authorize(): bool
    {
        return true;
    }

    /** La date cible exactement la date officielle du rapport, et non ses données internes. */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'type' => ['nullable', 'string', Rule::in(array_keys(FarmReport::TYPES))],
        ];
    }

    /** Les messages français expliquent précisément comment corriger le filtre. */
    public function messages(): array
    {
        return [
            'date.date' => 'La date du rapport doit être valide.',
            'type.in' => 'Le type de rapport choisi n’est pas reconnu.',
        ];
    }
}
