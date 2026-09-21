<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Alerte interne créée par un seuil, une échéance ou une anomalie. */
#[Fillable(['type', 'severity', 'title', 'message', 'source_type', 'source_id', 'status', 'acknowledged_by', 'acknowledged_at', 'resolved_at'])]
class Alert extends Model
{
    /** Convertit les dates d’acquittement et de résolution. */
    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    /** Élément métier à l’origine de l’alerte, par exemple un produit ou une bande. */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
