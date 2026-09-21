<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Building;
use App\Models\Client;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Remplit la base avec un scénario cohérent destiné aux écrans de démonstration. */
class DemoFarmSeeder extends Seeder
{
    /** Toutes les créations sont transactionnelles pour éviter une démonstration partielle. */
    public function run(): void
    {
        DB::transaction(function (): void {
            // Les comptes existent grâce à DemoUserSeeder exécuté juste avant.
            $manager = User::query()->where('email', 'responsable@abiwein.local')->firstOrFail();
            $veterinarian = User::query()->where('email', 'veterinaire@abiwein.local')->firstOrFail();

            // Fournisseur d’origine des bandes de démonstration.
            $supplier = Supplier::query()->updateOrCreate(
                ['name' => 'Couvoir partenaire de démonstration'],
                ['contact_person' => 'Service commercial', 'phone' => '+237 600 000 001', 'email' => 'couvoir@example.test', 'address' => 'Douala, Cameroun', 'is_active' => true]
            );

            // Bâtiments représentatifs des zones décrites dans le cadrage.
            $brooding = Building::query()->updateOrCreate(['code' => 'BAT-CHAUF'], ['name' => 'Poussinière et chauffage', 'type' => 'brooding', 'capacity' => 1500, 'status' => 'active']);
            $growing = Building::query()->updateOrCreate(['code' => 'BAT-ELEV-01'], ['name' => 'Bâtiment d’élevage principal', 'type' => 'growing', 'capacity' => 2500, 'status' => 'active']);
            Building::query()->updateOrCreate(['code' => 'BAT-STOCK'], ['name' => 'Magasin des intrants', 'type' => 'stock', 'capacity' => 800, 'status' => 'active']);
            Building::query()->updateOrCreate(['code' => 'BAT-FROID'], ['name' => 'Chambre froide', 'type' => 'cold_storage', 'capacity' => 1200, 'status' => 'active']);

            // Deux bandes actives alimentent les indicateurs du tableau de bord.
            $flockA = Flock::query()->updateOrCreate(
                ['code' => 'BND-2026-001'],
                ['breed' => 'Cobb 500', 'arrival_date' => now()->subDays(28)->toDateString(), 'expected_end_date' => now()->addDays(18)->toDateString(), 'initial_quantity' => 1200, 'current_quantity' => 1172, 'supplier_id' => $supplier->id, 'current_building_id' => $growing->id, 'status' => 'active', 'notes' => 'Bande principale de démonstration.']
            );
            $flockB = Flock::query()->updateOrCreate(
                ['code' => 'BND-2026-002'],
                ['breed' => 'Ross 308', 'arrival_date' => now()->subDays(12)->toDateString(), 'expected_end_date' => now()->addDays(32)->toDateString(), 'initial_quantity' => 900, 'current_quantity' => 892, 'supplier_id' => $supplier->id, 'current_building_id' => $brooding->id, 'status' => 'active', 'notes' => 'Jeune bande de démonstration.']
            );

            // Sept jours de relevés rendent immédiatement les tendances visibles.
            foreach (range(6, 0) as $daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();
                DailyRecord::query()->updateOrCreate(
                    ['flock_id' => $flockA->id, 'record_date' => $date],
                    ['mortality' => $daysAgo === 2 ? 3 : 1, 'feed_kg' => 145 + (6 - $daysAgo) * 4, 'water_liters' => 238 + (6 - $daysAgo) * 6, 'average_weight_kg' => 1.25 + (6 - $daysAgo) * 0.09, 'health_status' => $daysAgo === 2 ? 'watch' : 'good', 'observations' => $daysAgo === 2 ? 'Consommation à surveiller sur une zone du bâtiment.' : 'Comportement général normal.', 'recorded_by' => $manager->id]
                );
            }

            // Produits suivis avec un cas volontairement proche du seuil d’alerte.
            $feed = Product::query()->updateOrCreate(['code' => 'ALIM-CROISS'], ['name' => 'Aliment croissance', 'category' => 'feed', 'unit' => 'kg', 'current_stock' => 620, 'alert_threshold' => 500, 'is_active' => true]);
            $vitamins = Product::query()->updateOrCreate(['code' => 'SANT-VIT'], ['name' => 'Complexe vitaminé', 'category' => 'health', 'unit' => 'flacon', 'current_stock' => 8, 'alert_threshold' => 10, 'expires_at' => now()->addMonths(5)->toDateString(), 'is_active' => true]);
            $preparedChicken = Product::query()->updateOrCreate(['code' => 'VENT-POUL'], ['name' => 'Poulet entier préparé', 'category' => 'processed_chicken', 'unit' => 'pièce', 'current_stock' => 84, 'alert_threshold' => 20, 'is_active' => true]);

            // Les mouvements expliquent l’origine des quantités présentes en stock.
            StockMovement::query()->firstOrCreate(['product_id' => $feed->id, 'reason' => 'Réception initiale de démonstration'], ['type' => 'entry', 'quantity' => 1000, 'occurred_at' => now()->subDays(8), 'recorded_by' => $manager->id]);
            StockMovement::query()->firstOrCreate(['product_id' => $vitamins->id, 'reason' => 'Utilisation sanitaire de démonstration'], ['type' => 'exit', 'quantity' => 4, 'occurred_at' => now()->subDays(2), 'recorded_by' => $manager->id]);

            // Client et vente à crédit illustrant les règlements partiels en espèces.
            $client = Client::query()->updateOrCreate(['name' => 'Restaurant partenaire'], ['type' => 'business', 'phone' => '+237 600 000 002', 'email' => 'client@example.test', 'address' => 'Douala, Cameroun', 'is_active' => true]);
            $sale = Sale::query()->updateOrCreate(['reference' => 'VTE-DEMO-001'], ['client_id' => $client->id, 'sale_date' => now()->subDay()->toDateString(), 'sale_type' => 'credit', 'payment_method' => 'cash', 'total_amount' => 180000, 'paid_amount' => 100000, 'balance_due' => 80000, 'status' => 'partial', 'created_by' => $manager->id]);
            // Cette ligne rend le détail de la vente de démonstration immédiatement consultable.
            SaleItem::query()->updateOrCreate(
                ['sale_id' => $sale->id, 'product_id' => $preparedChicken->id],
                ['description' => $preparedChicken->name, 'quantity' => 60, 'unit_price' => 3000, 'line_total' => 180000]
            );
            Payment::query()->firstOrCreate(['sale_id' => $sale->id, 'reference' => 'REG-DEMO-001'], ['amount' => 100000, 'paid_at' => now()->subDay(), 'payment_method' => 'cash', 'recorded_by' => $manager->id]);
            Expense::query()->updateOrCreate(['reference' => 'DEP-DEMO-001'], ['category' => 'Alimentation', 'description' => 'Achat complémentaire d’aliment', 'amount' => 95000, 'spent_on' => now()->subDays(3)->toDateString(), 'recorded_by' => $manager->id]);

            // Visite et alertes internes complètent le scénario sanitaire et opérationnel.
            VeterinaryVisit::query()->updateOrCreate(['flock_id' => $flockA->id, 'visit_date' => now()->subDays(4)->toDateString()], ['veterinarian_id' => $veterinarian->id, 'diagnosis' => 'État général satisfaisant.', 'recommendations' => 'Maintenir la ventilation et surveiller la consommation.', 'next_visit_date' => now()->addDays(10)->toDateString()]);
            Alert::query()->updateOrCreate(['title' => 'Stock vitaminé sous le seuil'], ['type' => 'stock_threshold', 'severity' => 'warning', 'message' => 'Le complexe vitaminé doit être réapprovisionné.', 'source_type' => Product::class, 'source_id' => $vitamins->id, 'status' => 'open']);
            Alert::query()->updateOrCreate(['title' => 'Suivi renforcé de la bande BND-2026-001'], ['type' => 'daily_monitoring', 'severity' => 'info', 'message' => 'Contrôler la consommation lors du prochain relevé.', 'source_type' => Flock::class, 'source_id' => $flockA->id, 'status' => 'open']);
        });
    }
}
