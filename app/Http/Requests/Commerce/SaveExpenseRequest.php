<?php

namespace App\Http\Requests\Commerce;

use Illuminate\Foundation\Http\FormRequest;

/** Valide une entrée du registre des dépenses. */
class SaveExpenseRequest extends FormRequest
{
    /** Les rôles sont filtrés par la route du module financier. */
    public function authorize(): bool
    {
        return true;
    }

    /** Chaque règle protège une information utilisée dans les bilans. */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /** Messages français associés aux champs de la dépense. */
    public function messages(): array
    {
        return [
            'category.required' => 'La catégorie est obligatoire.',
            'description.required' => 'Le motif de la dépense est obligatoire.',
            'amount.required' => 'Le montant de la dépense est obligatoire.',
            'amount.gt' => 'Le montant doit être supérieur à zéro.',
            'spent_on.required' => 'La date de la dépense est obligatoire.',
            'spent_on.before_or_equal' => 'La date de la dépense ne peut pas être dans le futur.',
        ];
    }
}
