<?php

namespace App\Http\Requests\Stock;

use App\Enums\ProductCategory;
use App\Enums\StockUnit;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Contrôle les informations utilisées pour créer un produit stocké. */
class StoreProductRequest extends FormRequest
{
    /** Seul le responsable d'exploitation tient le registre des intrants. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /** Normalise le code avant validation pour conserver des références homogènes. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    /** Chaque règle correspond à une contrainte de la table products. */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', 'unique:products,code'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'unit' => ['required', Rule::enum(StockUnit::class)],
            'initial_stock' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'alert_threshold' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** Les messages français indiquent directement la correction attendue. */
    public function messages(): array
    {
        return [
            'code.required' => 'La référence du produit est obligatoire.',
            'code.regex' => 'La référence accepte uniquement les lettres, chiffres, points, tirets et tirets bas.',
            'code.unique' => 'Cette référence est déjà utilisée.',
            'name.required' => 'Le nom du produit est obligatoire.',
            'category.required' => 'Veuillez sélectionner une catégorie.',
            'category.enum' => 'La catégorie sélectionnée n’est pas autorisée.',
            'unit.required' => 'Veuillez sélectionner une unité.',
            'unit.enum' => 'L’unité sélectionnée n’est pas autorisée.',
            'initial_stock.required' => 'Indiquez le stock initial, même s’il est égal à zéro.',
            'initial_stock.min' => 'Le stock initial ne peut pas être négatif.',
            'alert_threshold.required' => 'Le seuil d’alerte est obligatoire.',
            'alert_threshold.min' => 'Le seuil d’alerte ne peut pas être négatif.',
            'expires_at.date' => 'La date d’expiration doit être valide.',
        ];
    }
}
