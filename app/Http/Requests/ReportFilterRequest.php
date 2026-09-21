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

    /** Les dates ciblent la date officielle du rapport, et non ses données internes. */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', 'string', Rule::in(array_keys(FarmReport::TYPES))],
        ];
    }

    /** Les messages français expliquent précisément comment corriger le filtre. */
    public function messages(): array
    {
        return [
            'date_from.date' => 'La date de début doit être valide.',
            'date_to.date' => 'La date de fin doit être valide.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'type.in' => 'Le type de rapport choisi n’est pas reconnu.',
        ];
    }
}
