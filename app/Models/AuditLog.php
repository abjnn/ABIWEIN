<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Trace en lecture seule d’une action réalisée sur une donnée sensible. */
#[Fillable([
    'actor_id',
    'actor_name',
    'event',
    'auditable_type',
    'auditable_id',
    'auditable_label',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** Les événements possibles restent volontairement limités aux mutations. */
    public const EVENTS = ['created', 'updated', 'deleted'];

    /** Convertit les instantanés JSON en tableaux prêts à afficher. */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Compte à l’origine de l’action, s’il existe encore. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Enregistrement concerné, lorsqu’il n’a pas été supprimé depuis. */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Fournit un libellé français stable pour l’interface d’administration. */
    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            default => $this->event,
        };
    }
}
