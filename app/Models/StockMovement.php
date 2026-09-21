<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mouvement d’entrée, de sortie ou de correction d’un produit. */
#[Fillable(['product_id', 'type', 'quantity', 'occurred_at', 'reason', 'recorded_by'])]
class StockMovement extends Model
{
    /** Types nécessaires aux calculs et à l’affichage. */
    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    /** Produit concerné par le mouvement. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
