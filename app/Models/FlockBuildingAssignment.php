<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Période pendant laquelle une bande occupe un bâtiment donné. */
#[Fillable(['flock_id', 'building_id', 'started_at', 'ended_at', 'quantity', 'notes'])]
class FlockBuildingAssignment extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
