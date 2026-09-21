<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Représente une ligne figée d'une vente.
 * Le libellé et le prix sont conservés ici, même si le produit change ensuite.
 */
#[Fillable(['sale_id', 'product_id', 'description', 'quantity', 'unit_price', 'line_total'])]
class SaleItem extends Model
{
    /** Convertit les valeurs chiffrées sans perdre la précision prévue par la base. */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /** Retourne la vente parente de cette ligne. */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** Retourne le produit d'origine lorsqu'il existe encore dans le catalogue. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
