<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JsonSerializable;
use Stringable;

/** Centralise la création des traces et le retrait systématique des secrets. */
class AuditLogService
{
    /**
     * Ces fragments couvrent les identifiants secrets usuels et leurs variantes.
     * Le filtrage s’applique à chaque niveau afin de protéger aussi les futurs champs JSON.
     */
    private const SENSITIVE_KEY_PATTERN = '/password|passwd|passphrase|token|secret|api[_-]?key|authorization|cookie/i';

    /** Les horodatages techniques ne sont pas utiles pour expliquer une modification. */
    private const IGNORED_KEYS = ['created_at', 'updated_at', 'deleted_at'];

    /** Enregistre une action uniquement lorsqu’elle contient des données métier utiles. */
    public function record(Model $model, string $event, array $oldValues, array $newValues): void
    {
        $safeOldValues = $this->sanitize($oldValues);
        $safeNewValues = $this->sanitize($newValues);

        if ($event === 'updated' && $safeOldValues === [] && $safeNewValues === []) {
            return;
        }

        $actor = Auth::user();
        $request = app()->bound('request') && app('request') instanceof Request
            ? app('request')
            : null;

        AuditLog::query()->create([
            'actor_id' => $this->actorId($actor, $model, $event),
            'actor_name' => $actor?->getAuthIdentifierName() === 'email'
                ? ($actor->name ?? $actor->getAuthIdentifier())
                : ($actor->name ?? null),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'auditable_label' => $this->targetLabel($model),
            'old_values' => $safeOldValues === [] ? null : $safeOldValues,
            'new_values' => $safeNewValues === [] ? null : $safeNewValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent()
                ? Str::limit($request->userAgent(), 1024, '')
                : null,
        ]);
    }

    /** Retire les secrets puis normalise les valeurs pour un stockage JSON prévisible. */
    private function sanitize(array $values): array
    {
        $safeValues = [];

        foreach ($values as $key => $value) {
            $key = (string) $key;

            if (in_array($key, self::IGNORED_KEYS, true) || preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                continue;
            }

            $safeValues[$key] = $this->normalizeValue($value);
        }

        return $safeValues;
    }

    /** Convertit récursivement les objets courants sans exposer leur représentation interne. */
    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitize($value);
        }

        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof JsonSerializable => $this->normalizeValue($value->jsonSerialize()),
            $value instanceof Stringable => (string) $value,
            is_scalar($value), $value === null => $value,
            default => get_debug_type($value),
        };
    }

    /** Évite une clé étrangère invalide si un utilisateur supprime son propre compte. */
    private function actorId(?Authenticatable $actor, Model $model, string $event): int|string|null
    {
        if ($actor === null) {
            return null;
        }

        if ($event === 'deleted' && $model instanceof User && (string) $model->getKey() === (string) $actor->getAuthIdentifier()) {
            return null;
        }

        return $actor->getAuthIdentifier();
    }

    /** Produit un repère lisible qui survit à la suppression de la cible. */
    private function targetLabel(Model $model): string
    {
        foreach (['name', 'reference', 'code', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return Str::limit((string) $value, 180, '');
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
