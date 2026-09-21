<?php

namespace Tests\Feature;

use App\Enums\SaleItemType;
use App\Enums\UserRole;
use App\Http\Controllers\Commerce\ClientController;
use App\Http\Controllers\Commerce\CommerceDashboardController;
use App\Http\Controllers\Commerce\ExpenseController;
use App\Http\Controllers\Commerce\PaymentController;
use App\Http\Controllers\Commerce\ReceivableController;
use App\Http\Controllers\Commerce\SaleController;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie les règles critiques du module commercial et financier ABIWEIN. */
class CommerceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Déclare les routes du module uniquement tant que routes/web.php n'a pas
     * encore reçu l'intégration finale effectuée par le responsable du projet.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Route::has('sales.store')) {
            return;
        }

        Route::middleware(['web', 'auth', 'active', 'role:administrator,farm_manager'])
            ->prefix('_test-commerce')
            ->group(function (): void {
                Route::get('/', CommerceDashboardController::class)->name('commerce.dashboard');

                Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
                Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
                Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
                Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
                Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
                Route::patch('/clients/{client}/status', [ClientController::class, 'toggleStatus'])->name('clients.toggle-status');

                Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
                Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
                Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
                Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
                Route::post('/sales/{sale}/payments', [PaymentController::class, 'store'])->name('sales.payments.store');

                Route::get('/receivables', ReceivableController::class)->name('receivables.index');

                Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
                Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
                Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
                Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
                Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            });

        // Les routes ajoutées après le démarrage du framework exigent ce rafraîchissement dans l'environnement de test uniquement.
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /** Un responsable d'exploitation peut créer un client actif. */
    public function test_farm_manager_can_create_a_client(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $response = $this->actingAs($manager)->post(route('clients.store'), [
            'name' => 'Hôtel du Littoral',
            'type' => 'business',
            'phone' => '+237 600 100 200',
            'email' => 'achats@hotel-littoral.test',
            'address' => 'Douala',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Hôtel du Littoral',
            'type' => 'business',
            'is_active' => true,
        ]);
    }

    /** Une vente au comptant est soldée sans toucher au stock d'intrants. */
    public function test_cash_sale_keeps_input_stock_unchanged_and_creates_cash_receipt(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(stock: 10);

        $response = $this->actingAs($manager)->post(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'sale_type' => 'cash',
            'items' => [[
                'item_type' => SaleItemType::PreparedChicken->value,
                'quantity' => 3,
                'unit_price' => 2500,
            ]],
        ]);

        $sale = Sale::query()->firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertSame('paid', $sale->status);
        $this->assertSame('7500.00', $sale->paid_amount);
        $this->assertSame('0.00', $sale->balance_due);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => null, 'description' => 'Poulet de chair préparé', 'line_total' => 7500]);
        $this->assertDatabaseHas('payments', ['sale_id' => $sale->id, 'amount' => 7500, 'payment_method' => 'cash']);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('10.00', $product->fresh()->current_stock);
    }

    /** Une vente à crédit conserve son client, son premier versement et son solde. */
    public function test_credit_sale_tracks_partial_cash_payment_and_receivable(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $client = Client::query()->create(['name' => 'Restaurant Central', 'type' => 'business', 'is_active' => true]);
        $product = $this->createProduct(stock: 20);

        $response = $this->actingAs($manager)->post(route('sales.store'), [
            'client_id' => $client->id,
            'sale_date' => now()->toDateString(),
            'sale_type' => 'credit',
            'initial_payment' => 3000,
            'items' => [[
                'item_type' => SaleItemType::LiveChicken->value,
                'quantity' => 4,
                'unit_price' => 2000,
            ]],
        ]);

        $sale = Sale::query()->firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertSame('partial', $sale->status);
        $this->assertSame('3000.00', $sale->paid_amount);
        $this->assertSame('5000.00', $sale->balance_due);
        $this->assertSame($client->id, $sale->client_id);
    }

    /** Le dernier versement en espèces solde exactement une vente à crédit. */
    public function test_cash_payment_can_close_a_credit_sale(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $sale = $this->createCreditSale($manager, balance: 5000);

        $response = $this->actingAs($manager)->post(route('sales.payments.store', $sale), [
            'amount' => 5000,
            'paid_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'reference' => 'RECU-TEST-001',
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $sale->refresh();
        $this->assertSame('paid', $sale->status);
        $this->assertSame('0.00', $sale->balance_due);
        $this->assertDatabaseHas('payments', ['reference' => 'RECU-TEST-001', 'payment_method' => 'cash']);
    }

    /** Un versement supérieur à la créance est refusé sans modifier les montants. */
    public function test_payment_cannot_exceed_remaining_balance(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $sale = $this->createCreditSale($manager, balance: 5000);

        $response = $this->actingAs($manager)->from(route('sales.show', $sale))->post(route('sales.payments.store', $sale), [
            'amount' => 5001,
            'paid_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('sales.show', $sale))->assertSessionHasErrors('amount');
        $this->assertSame('5000.00', $sale->fresh()->balance_due);
        $this->assertDatabaseCount('payments', 0);
    }

    /** Une vente de poulets reste indépendante de la quantité d'aliments en magasin. */
    public function test_sale_is_independent_from_farm_input_stock(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(stock: 0);

        $response = $this->actingAs($manager)->post(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'sale_type' => 'cash',
            'items' => [[
                'item_type' => SaleItemType::LiveChicken->value,
                'quantity' => 3,
                'unit_price' => 2500,
            ]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('0.00', $product->fresh()->current_stock);
    }

    /** Une dépense valide reçoit une référence et conserve l'identité de son auteur. */
    public function test_farm_manager_can_record_an_expense(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $response = $this->actingAs($manager)->post(route('expenses.store'), [
            'category' => 'Alimentation',
            'description' => 'Achat de sacs d’aliment croissance',
            'amount' => 125000,
            'spent_on' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'category' => 'Alimentation',
            'amount' => 125000,
            'recorded_by' => $manager->id,
        ]);
        $this->assertStringStartsWith('DEP-', (string) Expense::query()->value('reference'));
    }

    /** Un vétérinaire ne peut pas ouvrir les écrans financiers. */
    public function test_veterinarian_cannot_access_commercial_module(): void
    {
        $veterinarian = User::factory()->create(['role' => UserRole::Veterinarian]);

        $this->actingAs($veterinarian)->get(route('sales.index'))->assertForbidden();
    }

    /** L'administrateur consulte les ventes mais toute écriture lui est refusée. */
    public function test_administrator_has_read_only_commercial_access(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->get(route('sales.index'))->assertOk();
        $this->actingAs($administrator)->get(route('clients.index'))->assertOk();
        $this->actingAs($administrator)->get(route('expenses.index'))->assertOk();

        $this->actingAs($administrator)->post(route('sales.store'), [])->assertForbidden();
        $this->actingAs($administrator)->post(route('clients.store'), [])->assertForbidden();
        $this->actingAs($administrator)->post(route('expenses.store'), [])->assertForbidden();
    }

    /** Crée un produit vendable minimal utilisé par plusieurs scénarios. */
    private function createProduct(float $stock): Product
    {
        return Product::query()->create([
            'code' => 'VENT-'.str_pad((string) Product::query()->count() + 1, 3, '0', STR_PAD_LEFT),
            'name' => 'Aliment finition',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => $stock,
            'alert_threshold' => 1,
            'is_active' => true,
        ]);
    }

    /** Crée directement une créance pour isoler les tests du formulaire d'encaissement. */
    private function createCreditSale(User $manager, float $balance): Sale
    {
        $client = Client::query()->create(['name' => 'Client Test', 'type' => 'business', 'is_active' => true]);

        return Sale::query()->create([
            'reference' => 'VTE-TEST-'.str_pad((string) Sale::query()->count() + 1, 3, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'sale_date' => now()->toDateString(),
            'sale_type' => 'credit',
            'payment_method' => 'cash',
            'total_amount' => $balance,
            'paid_amount' => 0,
            'balance_due' => $balance,
            'status' => 'unpaid',
            'created_by' => $manager->id,
        ]);
    }
}
