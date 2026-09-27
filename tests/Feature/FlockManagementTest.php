<?php

namespace Tests\Feature;

use App\Enums\FlockActivityType;
use App\Enums\FlockMovementType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie les droits et les règles transactionnelles du module Bandes. */
class FlockManagementTest extends TestCase
{
    use RefreshDatabase;

    /** Charge localement le fichier tant que son inclusion dans web.php reste gérée séparément. */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('flocks.index')) {
            Route::middleware('web')->group(base_path('routes/flocks.php'));
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    /** Le registre est consultable par les rôles privés, mais seul le responsable écrit. */
    public function test_role_access_is_manager_write_and_other_business_roles_read_only(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $administrator = User::factory()->administrator()->create();
        $veterinarian = User::factory()->veterinarian()->create();

        $this->get(route('flocks.index'))->assertRedirect(route('login'));
        $this->actingAs($manager)->get(route('flocks.index'))->assertOk();
        $this->actingAs($administrator)->get(route('flocks.index'))->assertOk();
        $flock = $this->flock(50);
        $this->actingAs($veterinarian)->get(route('flocks.index'))->assertOk();
        $this->actingAs($veterinarian)->get(route('flocks.show', $flock))->assertOk()->assertDontSeeText('Ajouter une action');
        $this->actingAs($veterinarian)->post(route('flocks.activities.store', $flock), [])->assertForbidden();
        $this->actingAs($administrator)->post(route('flocks.store'), [])->assertForbidden();
    }

    /** La création produit la bande, l'arrivée et l'affectation initiale atomiques. */
    public function test_manager_can_create_a_flock_with_initial_movement_and_assignment(): void
    {
        $manager = User::factory()->create();
        $building = $this->building('BAT-A', 500);

        $response = $this->actingAs($manager)->post(route('flocks.store'), [
            'code' => 'bnd-test-001',
            'breed' => 'Cobb 500',
            'arrival_date' => today()->subDay()->toDateString(),
            'expected_end_date' => today()->addDays(40)->toDateString(),
            'initial_quantity' => 300,
            'supplier_id' => null,
            'current_building_id' => $building->id,
            'notes' => 'Cycle de vérification.',
        ]);

        $flock = Flock::query()->where('code', 'BND-TEST-001')->firstOrFail();
        $response->assertRedirect(route('flocks.show', $flock));
        $this->assertSame(300, $flock->current_quantity);
        $this->assertDatabaseHas('flock_movements', [
            'flock_id' => $flock->id,
            'type' => FlockMovementType::Arrival->value,
            'quantity' => 300,
            'recorded_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('flock_building_assignments', [
            'flock_id' => $flock->id,
            'building_id' => $building->id,
            'quantity' => 300,
            'ended_at' => null,
        ]);

        $this->actingAs($manager)->put(route('flocks.update', $flock), [
            'code' => $flock->code,
            'breed' => $flock->breed,
            'arrival_date' => today()->subDays(2)->toDateString(),
            'expected_end_date' => $flock->expected_end_date?->toDateString(),
            'supplier_id' => null,
            'notes' => 'Tentative de déplacement de la chronologie.',
        ])->assertSessionHasErrors('arrival_date');
        $this->assertSame(today()->subDay()->toDateString(), $flock->fresh()->arrival_date->toDateString());
    }

    /** Les actions mesurables alimentent le résumé sans double saisie. */
    public function test_timed_activities_update_daily_totals_and_current_quantity(): void
    {
        $manager = User::factory()->create();
        $flock = $this->flock(100);

        $this->actingAs($manager)->post(route('flocks.activities.store', $flock), [
            'occurred_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'type' => FlockActivityType::Feeding->value,
            'description' => 'Distribution du repas du matin.',
            'quantity' => 12.5,
        ])->assertRedirect(route('flocks.show', $flock));

        $this->actingAs($manager)->post(route('flocks.activities.store', $flock), [
            'occurred_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'type' => FlockActivityType::Mortality->value,
            'description' => 'Trois sujets retrouvés morts pendant le contrôle.',
            'quantity' => 3,
        ])->assertRedirect(route('flocks.show', $flock));

        $record = DailyRecord::query()->where('flock_id', $flock->id)->firstOrFail();
        $this->assertSame('12.50', $record->feed_kg);
        $this->assertSame(3, $record->mortality);
        $this->assertSame(97, $flock->fresh()->current_quantity);
        $this->assertDatabaseCount('flock_activities', 2);
        $this->assertDatabaseHas('flock_movements', ['flock_id' => $flock->id, 'type' => FlockMovementType::Mortality->value, 'quantity' => 3]);
    }

    /** Une correction applique uniquement l'écart avec l'ancienne mortalité. */
    public function test_daily_record_correction_does_not_decrement_mortality_twice(): void
    {
        $manager = User::factory()->create();
        $flock = $this->flock(100);
        $data = $this->dailyRecordData(5);

        $this->actingAs($manager)->post(route('flocks.daily-records.store', $flock), $data)
            ->assertRedirect(route('flocks.show', $flock));
        $this->assertSame(95, $flock->fresh()->current_quantity);

        $record = DailyRecord::query()->where('flock_id', $flock->id)->firstOrFail();
        $this->actingAs($manager)->put(route('flocks.daily-records.update', [$flock, $record]), array_merge($data, ['mortality' => 2]))
            ->assertRedirect(route('flocks.show', $flock));

        $this->assertSame(98, $flock->fresh()->current_quantity);
        $this->assertSame(2, $record->fresh()->mortality);
        $this->assertDatabaseHas('flock_movements', [
            'flock_id' => $flock->id,
            'type' => FlockMovementType::MortalityCorrection->value,
            'quantity' => 3,
        ]);
    }

    /** Une mortalité supérieure à l'effectif est rejetée sans écriture partielle. */
    public function test_mortality_cannot_exceed_available_birds(): void
    {
        $manager = User::factory()->create();
        $flock = $this->flock(5);

        $this->actingAs($manager)
            ->from(route('flocks.daily-records.create', $flock))
            ->post(route('flocks.daily-records.store', $flock), $this->dailyRecordData(6))
            ->assertSessionHasErrors('mortality');

        $this->assertSame(5, $flock->fresh()->current_quantity);
        $this->assertDatabaseCount('daily_records', 0);
    }

    /** Le transfert vérifie la capacité et conserve les deux périodes d'occupation. */
    public function test_manager_can_transfer_a_flock_to_a_building_with_enough_capacity(): void
    {
        $manager = User::factory()->create();
        $from = $this->building('BAT-DEP', 200);
        $to = $this->building('BAT-ARR', 300);
        $flock = $this->flock(120, $from);
        $flock->buildingAssignments()->create([
            'building_id' => $from->id,
            'started_at' => now()->subDays(5),
            'quantity' => 120,
        ]);

        $this->actingAs($manager)->post(route('flocks.transfer', $flock), [
            'building_id' => $to->id,
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'notes' => 'Passage au bâtiment de croissance.',
        ])->assertRedirect(route('flocks.show', $flock));

        $this->assertSame($to->id, $flock->fresh()->current_building_id);
        $this->assertDatabaseHas('flock_building_assignments', ['flock_id' => $flock->id, 'building_id' => $to->id, 'ended_at' => null]);
        $this->assertNotNull($flock->buildingAssignments()->where('building_id', $from->id)->value('ended_at'));

        $this->actingAs($manager)->post(route('flocks.transfer', $flock), [
            'building_id' => $from->id,
            'occurred_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'notes' => 'Transfert antidaté qui doit être refusé.',
        ])->assertSessionHasErrors('occurred_at');
        $this->assertSame($to->id, $flock->fresh()->current_building_id);
    }

    /** Une bande clôturée reste consultable mais refuse de nouvelles activités. */
    public function test_closed_flock_rejects_new_daily_activity(): void
    {
        $manager = User::factory()->create();
        $administrator = User::factory()->administrator()->create();
        $flock = $this->flock(80);

        $this->actingAs($manager)->patch(route('flocks.close', $flock))->assertRedirect(route('flocks.show', $flock));
        $this->assertSame('closed', $flock->fresh()->status);
        $this->actingAs($administrator)->get(route('flocks.show', $flock))->assertOk()->assertDontSeeText('Ajouter une action');
        $this->actingAs($manager)->get(route('flocks.edit', $flock))->assertStatus(422);

        $this->actingAs($manager)->put(route('flocks.update', $flock), [
            'code' => 'BND-CLOTUREE',
            'breed' => $flock->breed,
            'arrival_date' => $flock->arrival_date->toDateString(),
            'expected_end_date' => $flock->expected_end_date?->toDateString(),
            'supplier_id' => null,
            'notes' => 'Cette modification doit être refusée.',
        ])->assertSessionHasErrors('flock');
        $this->assertNotSame('BND-CLOTUREE', $flock->fresh()->code);

        $this->actingAs($manager)->post(route('flocks.activities.store', $flock), [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'type' => FlockActivityType::Inspection->value,
            'description' => 'Action qui doit être refusée.',
        ])->assertSessionHasErrors('flock');
    }

    /** Construit un bâtiment d'élevage actif et prévisible. */
    private function building(string $code, int $capacity): Building
    {
        return Building::query()->create([
            'code' => $code,
            'name' => 'Bâtiment '.$code,
            'type' => 'growing',
            'capacity' => $capacity,
            'status' => 'active',
            'notes' => null,
        ]);
    }

    /** Crée une bande active directement pour isoler la règle testée. */
    private function flock(int $quantity, ?Building $building = null): Flock
    {
        return Flock::factory()->create([
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'arrival_date' => today()->subDays(10),
            'expected_end_date' => today()->addDays(30),
            'current_building_id' => $building?->id,
            'status' => 'active',
        ]);
    }

    /** Fournit un relevé complet afin que chaque test se concentre sur sa règle métier. */
    private function dailyRecordData(int $mortality): array
    {
        return [
            'record_date' => today()->toDateString(),
            'mortality' => $mortality,
            'feed_kg' => 25,
            'water_liters' => 40,
            'average_weight_kg' => 1.25,
            'health_status' => 'good',
            'observations' => 'Comportement général normal.',
        ];
    }
}
