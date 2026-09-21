<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\VeterinaryVisit;
use App\Services\Intelligence\FarmDemonstrationAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie le mode d'analyse local, ses règles et sa confidentialité. */
class DemonstrationAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /** Charge la route isolée tant que son intégration à web.php reste volontairement manuelle. */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('intelligence.demo')) {
            Route::middleware('web')->group(base_path('routes/intelligence.php'));
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    /** Un visiteur doit d'abord ouvrir une session valide. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('intelligence.demo'))->assertRedirect(route('login'));
    }

    /** Les trois rôles actifs disposent de la page d'aide à la décision. */
    public function test_every_active_role_can_view_the_local_analysis(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);

            $response = $this->actingAs($user)
                ->get(route('intelligence.demo'))
                ->assertOk()
                ->assertSeeText('Démonstration locale')
                ->assertSeeText('Validation humaine obligatoire');

            // Les deux rôles de pilotage voient le domaine financier, jamais le vétérinaire.
            if ($role === UserRole::Veterinarian) {
                $response->assertDontSeeText('Créances clients');
            } else {
                $response->assertSeeText('Créances clients');
            }
        }
    }

    /** Le middleware commun conserve le refus des comptes désactivés. */
    public function test_inactive_account_cannot_view_the_analysis(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $this->actingAs($inactiveUser)
            ->get(route('intelligence.demo'))
            ->assertRedirect(route('login'));
    }

    /** Les règles produisent deux fois le même résultat à partir des mêmes données. */
    public function test_service_uses_real_farm_data_with_deterministic_rules(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-21 10:00:00'));

        $administrator = User::factory()->administrator()->create();
        $veterinarian = User::factory()->veterinarian()->create();

        Product::query()->create([
            'code' => 'TEST-EXPIRE',
            'name' => 'Produit expiré',
            'category' => 'health',
            'unit' => 'unit',
            'current_stock' => 0,
            'alert_threshold' => 5,
            'expires_at' => today()->subDay(),
            'is_active' => true,
        ]);
        Product::query()->create([
            'code' => 'TEST-STABLE',
            'name' => 'Aliment disponible',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => 50,
            'alert_threshold' => 10,
            'expires_at' => today()->addMonths(3),
            'is_active' => true,
        ]);

        $flock = Flock::factory()->create([
            'code' => 'BND-TEST-IA',
            'initial_quantity' => 100,
            'current_quantity' => 96,
            'status' => 'active',
        ]);
        DailyRecord::factory()->create([
            'flock_id' => $flock->id,
            'record_date' => today(),
            'mortality' => 4,
            'health_status' => 'watch',
            'recorded_by' => $administrator->id,
        ]);
        VeterinaryVisit::factory()->create([
            'flock_id' => $flock->id,
            'veterinarian_id' => $veterinarian->id,
            'visit_date' => today()->subDays(5),
            'next_visit_date' => today()->subDay(),
        ]);
        Sale::query()->create([
            'reference' => 'VTE-IA-001',
            'client_id' => null,
            'sale_date' => today()->subDays(40),
            'sale_type' => 'credit',
            'payment_method' => 'cash',
            'total_amount' => 1200000,
            'paid_amount' => 200000,
            'balance_due' => 1000000,
            'status' => 'partial',
            'created_by' => $administrator->id,
        ]);

        $service = app(FarmDemonstrationAnalysisService::class);
        $firstResult = $service->analyzeFor($administrator);
        $secondResult = $service->analyzeFor($administrator);

        $this->assertEquals($firstResult, $secondResult);
        $analyses = collect($firstResult['analyses'])->keyBy('domain');
        $this->assertSame('critical', $analyses['stocks']['level']);
        $this->assertSame('1', $analyses['stocks']['evidence']['Produits expirés']);
        $this->assertSame('critical', $analyses['mortality_monitoring']['level']);
        $this->assertSame('4,00 %', $analyses['mortality_monitoring']['evidence']['Taux sur effectif initial']);
        $this->assertSame('critical', $analyses['sanitary']['level']);
        $this->assertSame('1 000 000 FCFA', $analyses['receivables']['evidence']['Solde à recouvrer']);

        // Le faux client HTTP rend tout appel réseau visible et prouve que la page reste locale.
        Http::fake();
        $this->actingAs($administrator)
            ->get(route('intelligence.demo'))
            ->assertOk()
            ->assertSeeText('produit(s) expiré(s)')
            ->assertSeeText('Créances clients');
        Http::assertNothingSent();
    }

    /**
     * Le vétérinaire ne reçoit aucune créance et le service ne consulte même
     * pas la table des ventes pour construire sa réponse.
     */
    public function test_veterinarian_never_receives_or_queries_financial_data(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        Sale::query()->create([
            'reference' => 'VTE-PRIVEE-001',
            'client_id' => null,
            'sale_date' => today()->subDays(45),
            'sale_type' => 'credit',
            'payment_method' => 'cash',
            'total_amount' => 987654,
            'paid_amount' => 0,
            'balance_due' => 987654,
            'status' => 'unpaid',
            'created_by' => $manager->id,
        ]);

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries): void {
            $executedQueries[] = strtolower($query->sql);
        });

        $response = $this->actingAs($veterinarian)->get(route('intelligence.demo'));
        $serviceResult = app(FarmDemonstrationAnalysisService::class)->analyzeFor($veterinarian);

        $response->assertOk()
            ->assertDontSeeText('Créances clients')
            ->assertDontSee('987 654')
            ->assertDontSee('FCFA')
            ->assertSeeText('Domaines analysés')
            ->assertSeeText('2');
        $this->assertFalse($serviceResult['includes_financial_data']);
        $this->assertNotContains('receivables', collect($serviceResult['analyses'])->pluck('domain')->all());
        $this->assertFalse(
            collect($executedQueries)->contains(fn (string $sql): bool => str_contains($sql, 'sales')),
            'La table sales ne doit pas être interrogée pour un vétérinaire.'
        );
        $this->assertFalse(
            collect($executedQueries)->contains(fn (string $sql): bool => str_contains($sql, 'products')),
            'La table products ne doit pas être interrogée pour un vétérinaire.'
        );
    }
}
