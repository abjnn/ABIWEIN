<?php

namespace App\Services\Flock;

use App\Enums\FlockActivityType;
use App\Enums\FlockMovementType;
use App\Enums\FlockStatus;
use App\Models\Building;
use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\FlockActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Centralise les règles qui modifient les effectifs ou l'occupation des bâtiments. */
class FlockOperationService
{
    /** Crée la bande, son arrivée et sa première affectation dans une seule transaction. */
    public function create(array $data, User $actor): Flock
    {
        return DB::transaction(function () use ($data, $actor): Flock {
            $quantity = (int) $data['initial_quantity'];
            $building = filled($data['current_building_id'] ?? null)
                ? Building::query()->lockForUpdate()->findOrFail($data['current_building_id'])
                : null;

            if ($building !== null) {
                $this->ensureCapacity($building, $quantity, 'current_building_id');
            }

            $flock = Flock::query()->create(array_merge($data, [
                'current_quantity' => $quantity,
                'status' => FlockStatus::Active->value,
                'closed_at' => null,
            ]));
            $arrival = CarbonImmutable::parse($flock->arrival_date)->startOfDay();

            $flock->movements()->create([
                'type' => FlockMovementType::Arrival->value,
                'from_building_id' => null,
                'to_building_id' => $building?->id,
                'quantity' => $quantity,
                'occurred_at' => $arrival,
                'reason' => 'Arrivée initiale de la bande',
                'recorded_by' => $actor->id,
            ]);

            if ($building !== null) {
                $flock->buildingAssignments()->create([
                    'building_id' => $building->id,
                    'started_at' => $arrival,
                    'ended_at' => null,
                    'quantity' => $quantity,
                    'notes' => 'Affectation initiale',
                ]);
            }

            return $flock->fresh(['supplier', 'currentBuilding']);
        });
    }

    /** Met à jour uniquement l'identité de la bande, jamais son effectif ni son bâtiment. */
    public function update(Flock $flock, array $data): Flock
    {
        return DB::transaction(function () use ($flock, $data): Flock {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);
            $this->ensureActive($lockedFlock);

            // La date d'arrivée fonde toute la chronologie et devient donc immuable après création.
            if (CarbonImmutable::parse($data['arrival_date'])->toDateString() !== $lockedFlock->arrival_date->toDateString()) {
                throw ValidationException::withMessages([
                    'arrival_date' => 'La date d’arrivée ne peut plus être modifiée après la création de la bande.',
                ]);
            }

            $earliestRecordDate = $lockedFlock->dailyRecords()->min('record_date');

            if ($earliestRecordDate !== null && $data['arrival_date'] > $earliestRecordDate) {
                throw ValidationException::withMessages([
                    'arrival_date' => 'La date d’arrivée ne peut pas dépasser un relevé quotidien déjà enregistré.',
                ]);
            }

            $lockedFlock->update(Arr::only($data, [
                'code', 'breed', 'arrival_date', 'expected_end_date', 'supplier_id', 'notes',
            ]));

            return $lockedFlock->fresh(['supplier', 'currentBuilding']);
        });
    }

    /** Enregistre ou corrige le résumé d'une journée en ajustant une seule fois l'effectif. */
    public function saveDailyRecord(Flock $flock, array $data, User $actor, ?DailyRecord $dailyRecord = null): DailyRecord
    {
        return DB::transaction(function () use ($flock, $data, $actor, $dailyRecord): DailyRecord {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);
            $this->ensureActive($lockedFlock);

            if ($dailyRecord !== null && $dailyRecord->flock_id !== $lockedFlock->id) {
                abort(404);
            }

            $record = $dailyRecord !== null
                ? DailyRecord::query()->lockForUpdate()->findOrFail($dailyRecord->id)
                : DailyRecord::query()
                    ->where('flock_id', $lockedFlock->id)
                    ->whereDate('record_date', $data['record_date'])
                    ->lockForUpdate()
                    ->first();

            if ($record !== null && $record->record_date->toDateString() !== $data['record_date']) {
                throw ValidationException::withMessages([
                    'record_date' => 'La date d’un relevé existant ne peut pas être déplacée.',
                ]);
            }

            $oldMortality = (int) ($record?->mortality ?? 0);
            $newMortality = (int) $data['mortality'];
            $mortalityDelta = $newMortality - $oldMortality;
            $newCurrentQuantity = $lockedFlock->current_quantity - $mortalityDelta;

            if ($newCurrentQuantity < 0) {
                throw ValidationException::withMessages([
                    'mortality' => "La mortalité dépasse l’effectif actuel de {$lockedFlock->current_quantity} sujet(s).",
                ]);
            }

            if ($newCurrentQuantity > $lockedFlock->initial_quantity) {
                throw ValidationException::withMessages([
                    'mortality' => 'Cette correction dépasserait l’effectif initial de la bande.',
                ]);
            }

            $attributes = array_merge($data, ['recorded_by' => $actor->id]);
            if ($record === null) {
                $record = $lockedFlock->dailyRecords()->create($attributes);
            } else {
                $record->update($attributes);
            }

            if ($mortalityDelta !== 0) {
                $lockedFlock->update(['current_quantity' => $newCurrentQuantity]);
                $lockedFlock->movements()->create([
                    'type' => $mortalityDelta > 0
                        ? FlockMovementType::Mortality->value
                        : FlockMovementType::MortalityCorrection->value,
                    'from_building_id' => $lockedFlock->current_building_id,
                    'to_building_id' => null,
                    'quantity' => abs($mortalityDelta),
                    'occurred_at' => CarbonImmutable::parse($data['record_date'])->endOfDay(),
                    'reason' => $mortalityDelta > 0
                        ? 'Mortalité du relevé quotidien'
                        : 'Correction de la mortalité du relevé quotidien',
                    'recorded_by' => $actor->id,
                ]);
            }

            return $record->fresh(['flock', 'recorder']);
        });
    }

    /** Ajoute une action à la chronologie et synchronise les totaux mesurables du jour. */
    public function addActivity(Flock $flock, array $data, User $actor): FlockActivity
    {
        return DB::transaction(function () use ($flock, $data, $actor): FlockActivity {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);
            $this->ensureActive($lockedFlock);

            $occurredAt = CarbonImmutable::parse($data['occurred_at']);
            $type = FlockActivityType::from($data['type']);
            $quantity = $type->requiresQuantity() ? (float) $data['quantity'] : null;

            if ($type === FlockActivityType::Mortality && $quantity !== floor($quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'La mortalité doit être exprimée en nombre entier de sujets.',
                ]);
            }

            $record = DailyRecord::query()
                ->where('flock_id', $lockedFlock->id)
                ->whereDate('record_date', $occurredAt->toDateString())
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $record = $lockedFlock->dailyRecords()->create([
                    'record_date' => $occurredAt->toDateString(),
                    'mortality' => 0,
                    'feed_kg' => 0,
                    'water_liters' => 0,
                    'average_weight_kg' => null,
                    'health_status' => 'good',
                    'observations' => null,
                    'recorded_by' => $actor->id,
                ]);
            }

            $this->applyActivityMeasure($lockedFlock, $record, $type, $quantity, $occurredAt, $actor);

            return FlockActivity::query()->create([
                'flock_id' => $lockedFlock->id,
                'daily_record_id' => $record->id,
                'occurred_at' => $occurredAt,
                'type' => $type->value,
                'description' => $data['description'],
                'quantity' => $quantity === null ? null : number_format($quantity, 2, '.', ''),
                'unit' => $type->unit(),
                'recorded_by' => $actor->id,
            ]);
        });
    }

    /** Transfère toute la bande et ferme proprement son ancienne affectation. */
    public function transfer(Flock $flock, array $data, User $actor): Flock
    {
        return DB::transaction(function () use ($flock, $data, $actor): Flock {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);
            $this->ensureActive($lockedFlock);
            $building = Building::query()->lockForUpdate()->findOrFail($data['building_id']);
            $this->ensureCapacity($building, $lockedFlock->current_quantity, 'building_id', $lockedFlock);
            $occurredAt = CarbonImmutable::parse($data['occurred_at']);
            $oldBuildingId = $lockedFlock->current_building_id;

            // Verrouille la période courante et le dernier mouvement pour préserver l'ordre chronologique.
            $currentAssignment = $lockedFlock->buildingAssignments()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->latest('started_at')
                ->first();
            $lastMovement = $lockedFlock->movements()
                ->lockForUpdate()
                ->latest('occurred_at')
                ->first();

            if (($currentAssignment !== null && $occurredAt->lt($currentAssignment->started_at))
                || ($lastMovement !== null && $occurredAt->lt($lastMovement->occurred_at))) {
                throw ValidationException::withMessages([
                    'occurred_at' => 'Le transfert ne peut pas précéder l’affectation ou le dernier mouvement enregistré.',
                ]);
            }

            $currentAssignment?->update(['ended_at' => $occurredAt]);

            $lockedFlock->buildingAssignments()->create([
                'building_id' => $building->id,
                'started_at' => $occurredAt,
                'ended_at' => null,
                'quantity' => $lockedFlock->current_quantity,
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedFlock->movements()->create([
                'type' => FlockMovementType::Transfer->value,
                'from_building_id' => $oldBuildingId,
                'to_building_id' => $building->id,
                'quantity' => $lockedFlock->current_quantity,
                'occurred_at' => $occurredAt,
                'reason' => $data['notes'] ?: 'Transfert complet de la bande',
                'recorded_by' => $actor->id,
            ]);

            $lockedFlock->update(['current_building_id' => $building->id]);

            return $lockedFlock->fresh('currentBuilding');
        });
    }

    /** Clôture le cycle sans supprimer les données techniques déjà acquises. */
    public function close(Flock $flock, User $actor): Flock
    {
        return DB::transaction(function () use ($flock, $actor): Flock {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);
            $this->ensureActive($lockedFlock);

            $lockedFlock->buildingAssignments()->whereNull('ended_at')->update(['ended_at' => now()]);
            $lockedFlock->movements()->create([
                'type' => FlockMovementType::Closure->value,
                'from_building_id' => $lockedFlock->current_building_id,
                'to_building_id' => null,
                'quantity' => $lockedFlock->current_quantity,
                'occurred_at' => now(),
                'reason' => 'Clôture du cycle d’élevage',
                'recorded_by' => $actor->id,
            ]);
            $lockedFlock->update([
                'status' => FlockStatus::Closed->value,
                'closed_at' => now(),
                'current_building_id' => null,
            ]);

            return $lockedFlock->fresh();
        });
    }

    /** Archive uniquement une bande déjà clôturée. */
    public function archive(Flock $flock): Flock
    {
        return DB::transaction(function () use ($flock): Flock {
            $lockedFlock = Flock::query()->lockForUpdate()->findOrFail($flock->id);

            if ($lockedFlock->status !== FlockStatus::Closed->value) {
                throw ValidationException::withMessages([
                    'status' => 'Une bande doit être clôturée avant son archivage.',
                ]);
            }

            $lockedFlock->update(['status' => FlockStatus::Archived->value]);

            return $lockedFlock->fresh();
        });
    }

    /** Répercute seulement les mesures qui possèdent un total quotidien officiel. */
    private function applyActivityMeasure(
        Flock $flock,
        DailyRecord $record,
        FlockActivityType $type,
        ?float $quantity,
        CarbonImmutable $occurredAt,
        User $actor,
    ): void {
        if ($quantity === null) {
            return;
        }

        if ($type === FlockActivityType::Feeding) {
            $record->update(['feed_kg' => (float) $record->feed_kg + $quantity]);
        } elseif ($type === FlockActivityType::Watering) {
            $record->update(['water_liters' => (float) $record->water_liters + $quantity]);
        } elseif ($type === FlockActivityType::Weighing) {
            $record->update(['average_weight_kg' => $quantity]);
        } elseif ($type === FlockActivityType::Mortality) {
            $mortality = (int) $quantity;
            if ($mortality > $flock->current_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "La mortalité dépasse l’effectif actuel de {$flock->current_quantity} sujet(s).",
                ]);
            }

            $record->update(['mortality' => $record->mortality + $mortality]);
            $flock->update(['current_quantity' => $flock->current_quantity - $mortality]);
            $flock->movements()->create([
                'type' => FlockMovementType::Mortality->value,
                'from_building_id' => $flock->current_building_id,
                'to_building_id' => null,
                'quantity' => $mortality,
                'occurred_at' => $occurredAt,
                'reason' => 'Mortalité consignée dans le journal quotidien',
                'recorded_by' => $actor->id,
            ]);
        }
    }

    /** Refuse toute opération quotidienne après la fin du cycle. */
    private function ensureActive(Flock $flock): void
    {
        if ($flock->status !== FlockStatus::Active->value) {
            throw ValidationException::withMessages([
                'flock' => 'Cette opération exige une bande active.',
            ]);
        }
    }

    /** Contrôle la capacité globale du bâtiment avant une affectation. */
    private function ensureCapacity(Building $building, int $incomingQuantity, string $field, ?Flock $excludedFlock = null): void
    {
        $occupiedQuery = Flock::query()
            ->where('status', FlockStatus::Active->value)
            ->where('current_building_id', $building->id);

        if ($excludedFlock !== null) {
            $occupiedQuery->whereKeyNot($excludedFlock->id);
        }

        $occupied = (int) $occupiedQuery->sum('current_quantity');
        if ($occupied + $incomingQuantity > $building->capacity) {
            $available = max(0, $building->capacity - $occupied);
            throw ValidationException::withMessages([
                $field => "La capacité disponible de ce bâtiment est de {$available} sujet(s).",
            ]);
        }
    }
}
