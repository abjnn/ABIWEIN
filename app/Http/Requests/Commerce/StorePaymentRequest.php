<?php

namespace App\Http\Requests\Commerce;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide un encaissement complémentaire effectué exclusivement en espèces. */
class StorePaymentRequest extends FormRequest
{
    /** Seul le responsable d'exploitation enregistre un encaissement. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /** Le mode de paiement n'est pas accepté en entrée : le contrôleur impose toujours "cash". */
    public function rules(): array
    {
        // La date de la vente devient la première date possible pour son encaissement.
        $saleDate = $this->route('sale')?->sale_date?->toDateString();

        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'paid_at' => array_values(array_filter(['required', 'date', 'before_or_equal:now', $saleDate ? 'after_or_equal:'.$saleDate : null])),
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
            'paid_at.after_or_equal' => 'La date d’encaissement ne peut pas précéder la vente.',
            'reference.unique' => 'Cette référence de reçu est déjà utilisée.',
        ];
    }
}
