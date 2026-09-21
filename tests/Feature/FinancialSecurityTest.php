<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vérifie les limites qui protègent les écritures financières. */
class FinancialSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Un encaissement ne peut pas être daté avant la vente correspondante. */
    public function test_payment_cannot_predate_sale(): void
    {
        $manager = User::factory()->create(['role' => 'farm_manager']);
        $sale = Sale::query()->create([
            'reference' => 'VTE-SEC-001',
            'sale_date' => '2026-09-20',
            'sale_type' => 'credit',
            'payment_method' => 'cash',
            'total_amount' => 50000,
            'paid_amount' => 0,
            'balance_due' => 50000,
            'status' => 'unpaid',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('sales.payments.store', $sale), [
                'amount' => 10000,
                'paid_at' => '2026-09-19',
                'reference' => 'REG-SEC-001',
            ])
            ->assertSessionHasErrors('paid_at');

        $this->assertDatabaseCount('payments', 0);
    }

    /** Un montant qui dépasse la précision SQL est refusé avant l’écriture. */
    public function test_expense_over_database_limit_is_rejected(): void
    {
        $manager = User::factory()->create(['role' => 'farm_manager']);

        $this->actingAs($manager)
            ->post(route('expenses.store'), [
                'category' => 'Test de sécurité',
                'description' => 'Montant volontairement hors limite',
                'amount' => '10000000000.00',
                'spent_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('expenses', 0);
    }
}
