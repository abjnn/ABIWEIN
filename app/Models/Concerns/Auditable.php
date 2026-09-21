<?php

namespace App\Models\Concerns;

use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Model;

/** Branche automatiquement le journal d’audit sur un modèle sensible. */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        // Les callbacks évitent de redémarrer le modèle pendant son propre cycle de boot.
        static::created(fn (Model $model) => app(AuditObserver::class)->created($model));
        static::updated(fn (Model $model) => app(AuditObserver::class)->updated($model));
        static::deleted(fn (Model $model) => app(AuditObserver::class)->deleted($model));
    }
}
