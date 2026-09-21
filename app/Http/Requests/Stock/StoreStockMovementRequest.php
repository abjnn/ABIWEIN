<?php

namespace App\Http\Requests\Stock;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide une entrée, une sortie ou un ajustement avant le calcul transactionnel. */
class StoreStockMovementRequest extends FormRequest
{
    /** Seuls les rôles opérationnels autorisés peuvent modifier une quantité. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /** Nettoie la justification afin d’éviter les espaces inutiles dans l’historique. */
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    /**
     * Une entrée ou sortie exige une quantité strictement positive.
     * Un ajustement accepte zéro car un comptage physique peut constater un stock épuisé.
     */
    public function rules(): array
    {
        $quantityMinimum = $this->input('type') === StockMovementType::Adjustment->value ? 'min:0' : 'gt:0';

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn (Builder $query): Builder => $query->where('is_active', true)),
            ],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', $quantityMinimum, 'max:9999999999.99'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    /** Ces textes distinguent clairement les erreurs de saisie et de règle métier. */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Veuillez sélectionner un produit.',
            'product_id.exists' => 'Le produit sélectionné est introuvable ou inactif.',
            'type.required' => 'Veuillez sélectionner le type de mouvement.',
            'type.enum' => 'Le type de mouvement sélectionné n’est pas autorisé.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.gt' => 'La quantité doit être supérieure à zéro.',
            'quantity.min' => 'La quantité comptée ne peut pas être négative.',
            'occurred_at.required' => 'La date et l’heure du mouvement sont obligatoires.',
            'occurred_at.before_or_equal' => 'Un mouvement ne peut pas être daté dans le futur.',
            'reason.required' => 'La justification du mouvement est obligatoire.',
            'reason.min' => 'La justification doit contenir au moins trois caractères.',
        ];
    }
}
