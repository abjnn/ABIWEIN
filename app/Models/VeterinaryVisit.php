<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\VeterinaryVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Visite sanitaire réalisée par un vétérinaire autorisé. */
#[Fillable([
    'flock_id',
    'veterinarian_id',
    'visit_date',
    'diagnosis',
    'recommendations',
    'recommendation_status',
    'recommendation_applied_at',
    'recommendation_applied_by',
    'next_visit_date',
])]
class VeterinaryVisit extends Model
{
    /** @use HasFactory<VeterinaryVisitFactory> */
    use Auditable, HasFactory;

    /** Les deux dates sont converties pour les rappels et calendriers. */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'recommendation_applied_at' => 'datetime',
            'next_visit_date' => 'date',
        ];
    }

    /** Bande examinée pendant la visite. */
    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    /** Utilisateur vétérinaire ayant réalisé la visite. */
    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    /** Responsable ayant confirmé l'application de la recommandation. */
    public function recommendationAppliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommendation_applied_by');
    }
}
