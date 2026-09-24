<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Enums\StockMovementType;
use App\Enums\StockUnit;
use App\Enums\UserRole;
use App\Http\Controllers\Stock\ProductController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Models\Alert;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DemoFarmSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie les règles métier et les écrans du module de gestion des stocks. */
class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Enregistre localement les routes du module tant que leur intégration dans web.php
     * reste volontairement confiée au fichier de routes principal.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('stocks.index')) {
            Route::middleware(['web', 'auth', 'active', 'role:administrator,farm_manager'])
                ->prefix('stocks')
                ->name('stocks.')
                ->group(function (): void {
                    Route::get('/', [ProductController::class, 'index'])->name('index');
                    Route::get('/creer', [ProductController::class, 'create'])->name('create');
                    Route::post('/', [ProductController::class, 'store'])->name('store');
                    Route::get('/mouvements', [StockMovementController::class, 'index'])->name('movements.index');
                    Route::get('/mouvements/creer', [StockMovementController::class, 'create'])->name('movements.create');
                    Route::post('/mouvements', [StockMovementController::class, 'store'])->name('movements.store');
                    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
                    Route::get('/{product}/modifier', [ProductController::class, 'edit'])->name('edit');
                    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                    Route::patch('/{product}/statut', [ProductController::class, 'toggleStatus'])->name('toggle-status');
                });

            // Les routes ajoutées après le démarrage de l’application de test actualisent ici leur index de noms.
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    /** Un visiteur est redirigé vers la connexion avant d’accéder aux stocks. */
    public function test_guest_cannot_view_stocks(): void
    {
        $this->get(route('stocks.index'))->assertRedirect(route('login'));
    }

    /** Le vétérinaire ne reçoit pas l’accès opérationnel au module Stocks. */
    public function test_veterinarian_cannot_view_stocks(): void
    {
        $veterinarian = User::factory()->create(['role' => UserRole::Veterinarian]);

        $this->actingAs($veterinarian)
            ->get(route('stocks.index'))
            ->assertForbidden();
    }

    /** La création conserve le stock initial dans le produit et dans le journal. */
    public function test_manager_can_create_product_with_initial_stock_and_alert(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $response = $this->actingAs($manager)->post(route('stocks.store'), [
            'code' => 'alim-demarrage',
            'name' => 'Aliment démarrage',
            'category' => ProductCategory::Feed->value,
            'unit' => StockUnit::Kilogram->value,
            'initial_stock' => '5',
            'alert_threshold' => '10',
            'expires_at' => now()->addMonths(2)->toDateString(),
            'is_active' => '1',
        ]);

        $product = Product::query()->where('code', 'ALIM-DEMARRAGE')->firstOrFail();

        $response->assertRedirect(route('stocks.show', $product));
        $this->assertSame('5.00', $product->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Entry->value,
            'quantity' => 5,
            'recorded_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('alerts', [
            'type' => 'stock_threshold',
            'source_type' => Product::class,
            'source_id' => $product->id,
            'status' => 'open',
        ]);
    }

    /** Une entrée puis une sortie produisent le stock attendu et deux traces distinctes. */
    public function test_entries_and_exits_update_stock_atomically(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(['current_stock' => 10]);

        $this->actingAs($manager)->post(route('stocks.movements.store'), $this->movementData($product, 'entry', 5, 'Réception fournisseur'))
            ->assertRedirect(route('stocks.show', $product));

        $this->actingAs($manager)->post(route('stocks.movements.store'), $this->movementData($product, 'exit', 4, 'Consommation bâtiment A'))
            ->assertRedirect(route('stocks.show', $product));

        $this->assertSame('11.00', $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    /** Une sortie supérieure au disponible est rejetée sans écriture partielle. */
    public function test_exit_cannot_exceed_available_stock(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(['current_stock' => 3]);

        $response = $this->actingAs($manager)
            ->from(route('stocks.movements.create'))
            ->post(route('stocks.movements.store'), $this->movementData($product, 'exit', 4, 'Sortie impossible'));

        $response->assertRedirect(route('stocks.movements.create'))->assertSessionHasErrors('quantity');
        $this->assertSame('3.00', $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** L’ajustement remplace le stock théorique par le résultat du comptage physique. */
    public function test_inventory_adjustment_replaces_current_stock(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(['current_stock' => 12]);

        $this->actingAs($manager)->post(
            route('stocks.movements.store'),
            $this->movementData($product, StockMovementType::Adjustment->value, 7.5, 'Inventaire mensuel')
        )->assertRedirect(route('stocks.show', $product));

        $this->assertSame('7.50', $product->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Adjustment->value,
            'quantity' => 7.5,
        ]);
    }

    /** Modifier la fiche ne contourne jamais l’historique pour changer la quantité. */
    public function test_product_update_keeps_current_stock_unchanged(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(['current_stock' => 18]);

        $this->actingAs($manager)->put(route('stocks.update', $product), [
            'code' => $product->code,
            'name' => 'Aliment croissance supérieur',
            'category' => ProductCategory::Feed->value,
            'unit' => StockUnit::Kilogram->value,
            'alert_threshold' => 6,
            'expires_at' => null,
            'is_active' => '1',
            'current_stock' => 999,
        ])->assertRedirect(route('stocks.show', $product));

        $product->refresh();
        $this->assertSame('18.00', $product->current_stock);
        $this->assertSame('Aliment croissance supérieur', $product->name);
    }

    /** L'administrateur consulte les stocks mais ne peut jamais les modifier. */
    public function test_administrator_has_read_only_stock_access(): void
    {
        $administrator = User::factory()->administrator()->create();
        $product = $this->createProduct();

        $this->actingAs($administrator)->get(route('stocks.index'))->assertOk();
        $this->actingAs($administrator)->get(route('stocks.show', $product))->assertOk();
        $this->actingAs($administrator)->post(route('stocks.store'), [])->assertForbidden();
        $this->actingAs($administrator)->post(route('stocks.movements.store'), [])->assertForbidden();
    }

    /** Un réapprovisionnement suffisant clôt automatiquement l’alerte de seuil. */
    public function test_restock_resolves_low_stock_alert(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct(['current_stock' => 2, 'alert_threshold' => 5]);
        Alert::query()->create([
            'type' => 'stock_threshold',
            'severity' => 'warning',
            'title' => 'Stock faible',
            'message' => 'Réapprovisionnement nécessaire.',
            'source_type' => Product::class,
            'source_id' => $product->id,
            'status' => 'open',
        ]);

        $this->actingAs($manager)->post(
            route('stocks.movements.store'),
            $this->movementData($product, 'entry', 10, 'Réapprovisionnement complet')
        )->assertRedirect(route('stocks.show', $product));

        $this->assertDatabaseHas('alerts', [
            'type' => 'stock_threshold',
            'source_id' => $product->id,
            'status' => 'resolved',
        ]);
    }

    /** La liste donne le délai exact sans proposer d'anciens filtres d'expiration. */
    public function test_stock_overview_displays_expiration_delays_without_expiration_filters(): void
    {
        $this->travelTo('2026-09-24 12:00:00');
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $this->createProduct([
            'name' => 'Vaccin expiré',
            'code' => 'SANT-EXP',
            'category' => ProductCategory::Health->value,
            'unit' => StockUnit::Bottle->value,
            'current_stock' => 1,
            'alert_threshold' => 3,
            'expires_at' => today()->subDays(3)->toDateString(),
        ]);
        $this->createProduct([
            'name' => 'Vitamines à contrôler',
            'code' => 'SANT-VIT-FUTUR',
            'category' => ProductCategory::Vitamin->value,
            'unit' => StockUnit::Bottle->value,
            'current_stock' => 8,
            'alert_threshold' => 2,
            'expires_at' => today()->addDays(45)->toDateString(),
        ]);

        $this->actingAs($manager)
            ->get(route('stocks.index'))
            ->assertOk()
            ->assertSeeText('Vaccin expiré')
            ->assertSeeText('Expiré depuis 3 jours')
            ->assertSeeText('Expire dans 45 jours')
            ->assertSeeText('Seuil atteint')
            ->assertDontSeeText('Expiration sous 30 jours')
            ->assertDontSee('value="expired"', false)
            ->assertDontSee('value="expiring"', false)
            ->assertDontSeeText('21/09/2026')
            ->assertDontSeeText('08/11/2026');
    }

    /** Les anciennes valeurs d'URL ne contournent pas la liste des états autorisés. */
    public function test_expiration_status_filters_are_rejected(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $this->actingAs($manager)
            ->get(route('stocks.index', ['status' => 'expired']))
            ->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    /** Le scénario de démonstration contient les grandes familles d'intrants avicoles. */
    public function test_demo_stock_contains_feed_phases_and_safe_health_product_categories(): void
    {
        $this->seed([DemoUserSeeder::class, DemoFarmSeeder::class]);

        foreach (['ALIM-PREDEM', 'ALIM-DEM', 'ALIM-CROISS', 'ALIM-FIN'] as $code) {
            $this->assertDatabaseHas('products', [
                'code' => $code,
                'category' => ProductCategory::Feed->value,
                'is_active' => true,
            ]);
        }

        $this->assertDatabaseHas('products', ['code' => 'SANT-VACC-ND', 'category' => ProductCategory::Vaccine->value]);
        $this->assertDatabaseHas('products', ['code' => 'SANT-VACC-GUM', 'category' => ProductCategory::Vaccine->value]);
        $this->assertDatabaseHas('products', ['code' => 'SANT-ANTIBIO', 'category' => ProductCategory::Antibiotic->value]);
        $this->assertDatabaseHas('products', ['code' => 'SANT-VIT', 'category' => ProductCategory::Vitamin->value]);
        $this->assertDatabaseHas('products', ['code' => 'SANT-ANTICOCC', 'category' => ProductCategory::Health->value]);
        $this->assertDatabaseHas('products', ['code' => 'SANT-ANTIPARA', 'category' => ProductCategory::Health->value]);

        foreach (['EQUIP-AMPOULE', 'EQUIP-ABREUV', 'EQUIP-PLATEAU', 'LITI-COPEAUX', 'EQUIP-BAC-DES'] as $code) {
            $this->assertDatabaseHas('products', [
                'code' => $code,
                'category' => ProductCategory::Equipment->value,
                'is_active' => true,
            ]);
        }
    }

    /** Toutes les vues séparées du module peuvent être rendues sans erreur. */
    public function test_manager_can_render_each_stock_page(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $product = $this->createProduct();
        StockMovement::query()->create([
            'product_id' => $product->id,
            'type' => StockMovementType::Entry->value,
            'quantity' => 10,
            'occurred_at' => now()->subHour(),
            'reason' => 'Stock de vérification',
            'recorded_by' => $manager->id,
        ]);

        $this->actingAs($manager)->get(route('stocks.create'))->assertOk()->assertSeeText('Créer une référence');
        $this->actingAs($manager)->get(route('stocks.show', $product))->assertOk()->assertSeeText('Historique des mouvements');
        $this->actingAs($manager)->get(route('stocks.edit', $product))->assertOk()->assertSeeText('Modifiez les informations descriptives');
        $this->actingAs($manager)->get(route('stocks.movements.index'))->assertOk()->assertSeeText('Opérations enregistrées');
        $this->actingAs($manager)->get(route('stocks.movements.create'))->assertOk()->assertSeeText('Enregistrer une opération');
    }

    /** Crée rapidement un produit valide pour isoler la règle testée. */
    private function createProduct(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'code' => 'ALIM-TEST',
            'name' => 'Aliment test',
            'category' => ProductCategory::Feed->value,
            'unit' => StockUnit::Kilogram->value,
            'current_stock' => 10,
            'alert_threshold' => 2,
            'expires_at' => null,
            'is_active' => true,
        ], $overrides));
    }

    /** Construit les données communes à une opération de stock valide. */
    private function movementData(Product $product, string $type, float|int $quantity, string $reason): array
    {
        return [
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'reason' => $reason,
        ];
    }
}
