<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DailyRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Relevé technique unique pour une bande et une date données. */
#[Fillable(['flock_id', 'record_date', 'mortality', 'feed_kg', 'water_liters', 'average_weight_kg', 'health_status', 'observations', 'recorded_by'])]
class DailyRecord extends Model
{
    /** @use HasFactory<DailyRecordFactory> */
    use Auditable, HasFactory;

    /** Préserve les unités et la précision des mesures affichées. */
    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'feed_kg' => 'decimal:2',
            'water_liters' => 'decimal:2',
            'average_weight_kg' => 'decimal:3',
        ];
    }

    /** Bande mesurée. */
    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    /** Utilisateur ayant saisi le relevé. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Actions horodatées appartenant à la même journée. */
    public function activities(): HasMany
    {
        return $this->hasMany(FlockActivity::class);
    }
}
