<?php

namespace App\Http\Requests\Stock;

use App\Enums\ProductCategory;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide les recherches et filtres appliqués à la liste des produits. */
class ProductIndexRequest extends FormRequest
{
    /** Seuls l’administrateur et le responsable d’exploitation gèrent les stocks. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Administrator)
            || $this->user()?->hasRole(UserRole::FarmManager);
    }

    /** Ces règles empêchent qu’un filtre inconnu modifie la requête de liste. */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::enum(ProductCategory::class)],
            'status' => ['nullable', Rule::in(['all', 'active', 'low', 'inactive'])],
        ];
    }
}
