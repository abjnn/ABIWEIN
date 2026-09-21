<?php

namespace App\Http\Requests\Commerce;

use App\Enums\SaleItemType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Valide une vente, ses lignes et son premier versement éventuel. */
class StoreSaleRequest extends FormRequest
{
    /** Les autorisations sont centralisées dans les middlewares des routes privées. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /**
     * Les lignes décrivent les poulets vendus sans référencer le stock d'intrants.
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'nullable',
                'required_if:sale_type,credit',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'sale_type' => ['required', Rule::in(['cash', 'credit'])],
            'initial_payment' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.item_type' => [
                'required',
                'distinct',
                Rule::enum(SaleItemType::class),
            ],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'items.*.unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    /** Ajoute la règle financière qui empêche un premier versement supérieur au total. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            // Aucun calcul n'est tenté tant que les lignes de base sont invalides.
            if ($validator->errors()->hasAny(['items', 'items.*.quantity', 'items.*.unit_price'])) {
                return;
            }

            // Le total est recalculé côté serveur ; le total affiché par le navigateur n'est jamais fiable seul.
            $total = collect($this->input('items', []))->sum(
                fn (array $item): float => round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2)
            );

            // La limite correspond à la colonne DECIMAL(12,2) utilisée par la base.
            if ($total > 9999999999.99) {
                $validator->errors()->add('items', 'Le montant total dépasse la limite autorisée.');
            }

            // Cette erreur cible le champ visible du premier versement.
            if ((float) $this->input('initial_payment', 0) > $total) {
                $validator->errors()->add('initial_payment', 'Le premier versement ne peut pas dépasser le montant total de la vente.');
            }
        }];
    }

    /** Messages français affichés sous les champs concernés. */
    public function messages(): array
    {
        return [
            'client_id.required_if' => 'Un client est obligatoire pour suivre une vente à crédit.',
            'client_id.exists' => 'Le client sélectionné est introuvable ou inactif.',
            'sale_date.required' => 'La date de vente est obligatoire.',
            'sale_date.before_or_equal' => 'La date de vente ne peut pas être dans le futur.',
            'sale_type.required' => 'Veuillez choisir comptant ou crédit.',
            'sale_type.in' => 'Le type de vente est invalide.',
            'items.required' => 'Ajoutez au moins une ligne de vente.',
            'items.min' => 'Ajoutez au moins une ligne de vente.',
            'items.*.item_type.required' => 'Chaque ligne doit préciser le type de poulet vendu.',
            'items.*.item_type.distinct' => 'Regroupez un même type de poulet sur une seule ligne.',
            'items.*.item_type.enum' => 'Le type de poulet sélectionné n’est pas autorisé.',
            'items.*.quantity.gt' => 'La quantité doit être supérieure à zéro.',
            'items.*.unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
        ];
    }
}
