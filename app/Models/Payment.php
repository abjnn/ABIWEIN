<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versement en espèces effectué sur une vente. */
#[Fillable(['sale_id', 'amount', 'paid_at', 'payment_method', 'reference', 'recorded_by'])]
class Payment extends Model
{
    /** Le montant et la date utilisent des types adaptés aux calculs. */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    /** Vente réglée par ce versement. */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
