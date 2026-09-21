<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Valide la période utilisée par les rapports techniques et financiers. */
class ReportFilterRequest extends FormRequest
{
    /** Les rapports sont déjà protégés par les middlewares de l’espace privé. */
    public function authorize(): bool
    {
        return true;
    }

    /** Les deux dates sont facultatives, mais la fin ne peut pas précéder le début. */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /** Les messages français expliquent précisément comment corriger le filtre. */
    public function messages(): array
    {
        return [
            'date_from.date' => 'La date de début doit être valide.',
            'date_to.date' => 'La date de fin doit être valide.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
