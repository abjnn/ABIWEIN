<?php

namespace App\Http\Requests\Stock;

use App\Enums\ProductCategory;
use App\Enums\StockUnit;
use App\Enums\UserRole;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Contrôle la modification d’un produit sans permettre d’altérer son stock directement. */
class UpdateProductRequest extends FormRequest
{
    /** Seul le responsable d'exploitation peut modifier une référence. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::FarmManager) ?? false;
    }

    /** Uniformise la référence et le nom avant d’appliquer les règles. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    /** La référence peut rester celle du produit actuellement modifié. */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', Rule::unique('products', 'code')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'unit' => ['required', Rule::enum(StockUnit::class)],
            'alert_threshold' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** Les messages restent explicites pour un utilisateur non technique. */
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
            'alert_threshold.required' => 'Le seuil d’alerte est obligatoire.',
            'alert_threshold.min' => 'Le seuil d’alerte ne peut pas être négatif.',
            'expires_at.date' => 'La date d’expiration doit être valide.',
        ];
    }
}
