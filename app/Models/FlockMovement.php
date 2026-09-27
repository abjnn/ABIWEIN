<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Trace immuable d'une arrivée, mortalité, correction, clôture ou transfert. */
#[Fillable(['flock_id', 'type', 'from_building_id', 'to_building_id', 'quantity', 'occurred_at', 'reason', 'recorded_by'])]
class FlockMovement extends Model
{
    use Auditable;

    /** L'heure du mouvement est manipulée comme une date Laravel. */
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    public function fromBuilding(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'from_building_id');
    }

    public function toBuilding(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'to_building_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
