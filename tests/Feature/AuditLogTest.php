<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AuditLogController;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Concerns\Auditable;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie la traçabilité, la confidentialité et l’accès au journal. */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** La route locale au test reflète les lignes d’intégration à ajouter dans web.php. */
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active', 'role:administrator'])
            ->get('/_tests/journal-audit', [AuditLogController::class, 'index'])
            ->name('admin.audit-logs.index');
    }

    /** Création, modification et suppression conservent acteur, contexte et différences. */
    public function test_it_records_the_full_product_lifecycle(): void
    {
        $administrator = User::factory()->administrator()->create(['name' => 'Administratrice ABIWEIN']);
        $this->actingAs($administrator);
        app('request')->server->set('REMOTE_ADDR', '203.0.113.20');
        app('request')->headers->set('User-Agent', 'ABIWEIN-Audit-Test/1.0');

        $product = Product::query()->create([
            'code' => 'AUDIT-001',
            'name' => 'Aliment démarrage',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => 25,
            'alert_threshold' => 5,
            'is_active' => true,
        ]);

        $created = $this->productLogs($product)->where('event', 'created')->firstOrFail();
        $this->assertSame($administrator->id, $created->actor_id);
        $this->assertSame('Administratrice ABIWEIN', $created->actor_name);
        $this->assertSame('203.0.113.20', $created->ip_address);
        $this->assertSame('ABIWEIN-Audit-Test/1.0', $created->user_agent);
        $this->assertSame('Aliment démarrage', $created->new_values['name']);
        $this->assertArrayNotHasKey('created_at', $created->new_values);

        $product->update(['name' => 'Aliment croissance']);

        $updated = $this->productLogs($product)->where('event', 'updated')->firstOrFail();
        $this->assertSame(['name' => 'Aliment démarrage'], $updated->old_values);
        $this->assertSame(['name' => 'Aliment croissance'], $updated->new_values);

        $product->delete();

        $deleted = $this->productLogs($product)->where('event', 'deleted')->firstOrFail();
        $this->assertSame('Aliment croissance', $deleted->auditable_label);
        $this->assertSame('Aliment croissance', $deleted->old_values['name']);
        $this->assertNull($deleted->new_values);
    }

    /** Aucun mot de passe, jeton ou secret ne doit entrer dans les instantanés JSON. */
    public function test_it_never_records_passwords_or_tokens(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator);

        $user = User::factory()->create([
            'name' => 'Compte audité',
            'email' => 'audit.user@abiwein.local',
            'password' => Hash::make('Secret@2026'),
            'remember_token' => 'jeton-a-ne-jamais-conserver',
        ]);

        $created = AuditLog::query()
            ->where('auditable_type', $user->getMorphClass())
            ->where('auditable_id', $user->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $created->new_values);
        $this->assertArrayNotHasKey('remember_token', $created->new_values);
        $this->assertStringNotContainsString('Secret@2026', json_encode($created->new_values));
        $this->assertStringNotContainsString('jeton-a-ne-jamais-conserver', json_encode($created->new_values));

        $user->update([
            'name' => 'Compte audité modifié',
            'password' => Hash::make('AutreSecret@2026'),
            'remember_token' => 'nouveau-jeton-interdit',
        ]);

        $updated = AuditLog::query()
            ->where('auditable_type', $user->getMorphClass())
            ->where('auditable_id', $user->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame(['name' => 'Compte audité'], $updated->old_values);
        $this->assertSame(['name' => 'Compte audité modifié'], $updated->new_values);
    }

    /** Les huit modèles annoncés utilisent tous le même observer générique. */
    public function test_all_sensitive_models_are_auditable(): void
    {
        $models = [
            User::class,
            Product::class,
            StockMovement::class,
            VeterinaryVisit::class,
            Client::class,
            Sale::class,
            Payment::class,
            Expense::class,
        ];

        foreach ($models as $model) {
            $this->assertContains(Auditable::class, class_uses_recursive($model));
        }
    }

    /** L’administrateur peut filtrer la vue, contrairement aux autres rôles. */
    public function test_only_administrator_can_view_and_filter_audit_logs(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator);

        $product = Product::query()->create([
            'code' => 'FILTER-001',
            'name' => 'Produit initial',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => 10,
            'alert_threshold' => 2,
            'is_active' => true,
        ]);
        $product->update(['name' => 'Produit modifié']);
        Client::query()->create(['name' => 'Client à exclure', 'type' => 'individual', 'is_active' => true]);

        $this->get('/_tests/journal-audit?event=updated&type=product')
            ->assertOk()
            ->assertSeeText('Journal d’audit')
            ->assertSeeText('Produit modifié')
            ->assertSeeText('Modification')
            ->assertDontSeeText('Client à exclure');

        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $this->actingAs($manager)
            ->get('/_tests/journal-audit')
            ->assertForbidden();
    }

    /** Limite les recherches de test au produit courant. */
    private function productLogs(Product $product): Builder
    {
        return AuditLog::query()
            ->where('auditable_type', $product->getMorphClass())
            ->where('auditable_id', $product->id);
    }
}
