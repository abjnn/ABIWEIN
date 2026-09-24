<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Intrant conservé dans le magasin de la ferme ; les ventes sont gérées séparément. */
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

    /**
     * Exprime l'échéance en temps restant ou écoulé, sans répéter la date brute.
     * La date du jour est traitée séparément afin d'éviter un trompeur « dans 0 jour ».
     */
    public function expirationTimeLabel(): string
    {
        if (! $this->expires_at) {
            return 'Non applicable';
        }

        $today = today();
        $expirationDate = $this->expires_at->copy()->startOfDay();

        if ($expirationDate->isSameDay($today)) {
            return 'Expire aujourd’hui';
        }

        $days = (int) $today->diffInDays($expirationDate, true);
        $unit = $days === 1 ? 'jour' : 'jours';

        return $expirationDate->isBefore($today)
            ? "Expiré depuis {$days} {$unit}"
            : "Expire dans {$days} {$unit}";
    }

    /** Historique des entrées, sorties et ajustements. */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
