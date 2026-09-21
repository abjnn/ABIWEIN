<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\FarmReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Valide la création d'un rapport par le responsable d'exploitation. */
class StoreFarmReportRequest extends FormRequest
{
    /** L'administrateur consulte les rapports, mais ne les produit pas. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) === true;
    }

    /** Encadre la nature, l'objet et la période du document administratif. */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(FarmReport::TYPES))],
            'title' => ['nullable', 'string', 'max:180'],
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'period_start' => ['required', 'date', 'before_or_equal:today'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
        ];
    }

    /** Un rapport journalier doit obligatoirement porter sur une seule journée. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->string('type')->toString() === 'daily'
                    && (string) $this->input('period_start') !== (string) $this->input('period_end')) {
                    $validator->errors()->add('period_end', 'Un rapport journalier doit commencer et finir le même jour.');
                }
            },
        ];
    }

    /** Les messages indiquent clairement la correction attendue dans le formulaire. */
    public function messages(): array
    {
        return [
            'type.required' => 'Choisissez un type de rapport.',
            'type.in' => 'Le type de rapport choisi n’est pas reconnu.',
            'report_date.required' => 'Indiquez la date officielle du rapport.',
            'report_date.before_or_equal' => 'La date du rapport ne peut pas être future.',
            'period_start.required' => 'Indiquez le début de la période.',
            'period_end.required' => 'Indiquez la fin de la période.',
            'period_end.after_or_equal' => 'La fin doit être postérieure ou égale au début.',
            'period_end.before_or_equal' => 'La période ne peut pas se terminer dans le futur.',
        ];
    }
}
