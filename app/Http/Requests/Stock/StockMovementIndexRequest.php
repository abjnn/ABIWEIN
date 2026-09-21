<?php

namespace App\Http\Requests\Stock;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide les critères appliqués à l’historique des mouvements. */
class StockMovementIndexRequest extends FormRequest
{
    /** Les utilisateurs autorisés à gérer les stocks peuvent consulter l’historique. */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Administrator)
            || $this->user()?->hasRole(UserRole::FarmManager);
    }

    /** Les dates et identifiants sont contrôlés avant leur utilisation en requête. */
    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
