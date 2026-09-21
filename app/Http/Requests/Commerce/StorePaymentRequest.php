<?php

namespace App\Http\Requests\Commerce;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide un encaissement complémentaire effectué exclusivement en espèces. */
class StorePaymentRequest extends FormRequest
{
    /** La route protégée détermine quels rôles peuvent encaisser. */
    public function authorize(): bool
    {
        return true;
    }

    /** Le mode de paiement n'est pas accepté en entrée : le contrôleur impose toujours "cash". */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'reference' => ['nullable', 'string', 'max:80', Rule::unique('payments', 'reference')],
        ];
    }

    /** Messages destinés au formulaire d'encaissement. */
    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant encaissé est obligatoire.',
            'amount.gt' => 'Le montant encaissé doit être supérieur à zéro.',
            'paid_at.required' => 'La date d’encaissement est obligatoire.',
            'paid_at.before_or_equal' => 'La date d’encaissement ne peut pas être dans le futur.',
            'reference.unique' => 'Cette référence de reçu est déjà utilisée.',
        ];
    }
}
