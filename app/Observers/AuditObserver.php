<?php

namespace App\Observers;

use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

/** Traduit les événements Eloquent en entrées compactes du journal. */
class AuditObserver
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    /** Conserve l’état sûr obtenu après la création. */
    public function created(Model $model): void
    {
        $this->auditLogService->record($model, 'created', [], $model->getAttributes());
    }

    /** Ne conserve que les champs réellement modifiés et leur ancienne valeur. */
    public function updated(Model $model): void
    {
        $newValues = $model->getChanges();
        $oldValues = array_intersect_key($model->getRawOriginal(), $newValues);

        $this->auditLogService->record($model, 'updated', $oldValues, $newValues);
    }

    /** Conserve le dernier état sûr avant que la cible ne devienne introuvable. */
    public function deleted(Model $model): void
    {
        $this->auditLogService->record($model, 'deleted', $model->getAttributes(), []);
    }
}
