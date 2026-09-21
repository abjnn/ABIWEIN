<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Vente au comptant ou à crédit, toujours réglée en espèces. */
#[Fillable(['reference', 'client_id', 'sale_date', 'sale_type', 'payment_method', 'total_amount', 'paid_amount', 'balance_due', 'status', 'created_by'])]
class Sale extends Model
{
    /** Les montants restent décimaux et la date devient un objet Carbon. */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    /** Client lié à la vente. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Versements successifs en espèces. */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
