<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Charge financière engagée par l’exploitation. */
#[Fillable(['reference', 'category', 'description', 'amount', 'spent_on', 'recorded_by'])]
class Expense extends Model
{
    /** Préserve la précision monétaire et convertit la date. */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'spent_on' => 'date'];
    }

    /** Utilisateur ayant saisi la sortie de caisse. */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
