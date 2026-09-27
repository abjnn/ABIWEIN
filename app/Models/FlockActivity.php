<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Action horodatée composant le déroulement réel d'une journée d'élevage. */
#[Fillable(['flock_id', 'daily_record_id', 'occurred_at', 'type', 'description', 'quantity', 'unit', 'recorded_by'])]
class FlockActivity extends Model
{
    use Auditable;

    /** Préserve l'heure et la précision des mesures lors de l'affichage. */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity' => 'decimal:2',
        ];
    }

    /** Bande concernée par l'action. */
    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    /** Résumé journalier auquel l'action est rattachée. */
    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    /** Responsable ayant consigné l'action. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
