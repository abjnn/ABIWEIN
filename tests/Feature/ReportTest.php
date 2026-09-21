<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vérifie la synthèse des rapports et la confidentialité des montants. */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /** Le responsable voit les montants et indicateurs de la période demandée. */
    public function test_manager_can_view_consolidated_report(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $flock = Flock::factory()->create();

        // La vente à crédit fournit un chiffre d’affaires et une créance vérifiables.
        $sale = Sale::query()->create([
            'reference' => 'VTE-TEST-001',
            'sale_date' => today(),
            'sale_type' => 'credit',
            'payment_method' => 'cash',
            'total_amount' => 100000,
            'paid_amount' => 60000,
            'balance_due' => 40000,
            'status' => 'partial',
            'created_by' => $manager->id,
        ]);

        // Le paiement représente l’encaissement réel en espèces.
        Payment::query()->create([
            'sale_id' => $sale->id,
            'amount' => 60000,
            'paid_at' => now(),
            'payment_method' => 'cash',
            'reference' => 'REG-TEST-001',
            'recorded_by' => $manager->id,
        ]);

        // La dépense permet de vérifier le solde de trésorerie calculé.
        Expense::query()->create([
            'reference' => 'DEP-TEST-001',
            'category' => 'Alimentation',
            'description' => 'Dépense de contrôle',
            'amount' => 10000,
            'spent_on' => today(),
            'recorded_by' => $manager->id,
        ]);

        // Le relevé ajoute une valeur technique au même rapport.
        DailyRecord::query()->create([
            'flock_id' => $flock->id,
            'record_date' => today(),
            'mortality' => 2,
            'feed_kg' => 120,
            'water_liters' => 180,
            'average_weight_kg' => 1.8,
            'health_status' => 'good',
            'recorded_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('reports.index', ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]))
            ->assertOk()
            ->assertSeeText('100 000 FCFA')
            ->assertSeeText('60 000 FCFA')
            ->assertSeeText('50 000 FCFA')
            ->assertSeeText('VTE-TEST-001');
    }

    /** Le vétérinaire ne doit pas accéder aux rapports financiers. */
    public function test_veterinarian_cannot_view_financial_report(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();

        $this->actingAs($veterinarian)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    /** Une période inversée est refusée avant toute construction du rapport. */
    public function test_report_rejects_invalid_period(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('reports.index', ['date_from' => '2026-09-20', 'date_to' => '2026-09-10']))
            ->assertSessionHasErrors('date_to');
    }
}
