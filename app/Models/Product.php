<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Produit stocké ou vendu par l’exploitation. */
#[Fillable(['code', 'name', 'category', 'unit', 'current_stock', 'alert_threshold', 'expires_at', 'is_active'])]
class Product extends Model
{
    use Auditable, HasFactory;

    /** Convertit les quantités, dates et états dans les bons types. */
    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'alert_threshold' => 'decimal:2',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Historique des entrées, sorties et ajustements. */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
