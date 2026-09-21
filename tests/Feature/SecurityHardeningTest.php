<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/** Vérifie les protections HTTP et la robustesse minimale des nouveaux comptes. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** Les réponses publiques doivent empêcher l’intégration et le chargement de scripts tiers. */
    public function test_public_pages_receive_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy');
    }

    /** Une page authentifiée ne doit pas rester dans le cache partagé du navigateur. */
    public function test_private_pages_are_not_cached(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    /** Un mot de passe trop simple reste refusé même si sa confirmation correspond. */
    public function test_weak_password_is_rejected_for_new_account(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Compte faible',
                'email' => 'faible@example.test',
                'role' => 'farm_manager',
                'password' => 'motdepassefaible',
                'password_confirmation' => 'motdepassefaible',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('password');
    }

    /** Les comptes connus de démonstration restent impossibles à créer en production. */
    public function test_demo_user_seeder_refuses_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(LogicException::class);
        (new DemoUserSeeder)->run();
    }

    /** Le tableau vétérinaire ne doit révéler ni titre ni quantité provenant des stocks. */
    public function test_veterinarian_dashboard_hides_stock_alerts(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();
        $product = Product::query()->create([
            'code' => 'SEC-STOCK',
            'name' => 'Produit confidentiel',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => 1,
            'alert_threshold' => 10,
            'is_active' => true,
        ]);

        Alert::query()->create([
            'type' => 'stock_threshold',
            'severity' => 'warning',
            'title' => 'Stock confidentiel',
            'message' => 'Quantité confidentielle',
            'source_type' => Product::class,
            'source_id' => $product->id,
            'status' => 'open',
        ]);
        Alert::query()->create([
            'type' => 'daily_monitoring',
            'severity' => 'info',
            'title' => 'Suivi sanitaire autorisé',
            'message' => 'Observation technique',
            'status' => 'open',
        ]);

        $this->actingAs($veterinarian)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Suivi sanitaire autorisé')
            ->assertDontSeeText('Stock confidentiel')
            ->assertDontSeeText('Quantité confidentielle');
    }

    /** Après cinq échecs, le sixième essai est bloqué par la limitation de débit. */
    public function test_login_is_rate_limited_after_five_failures(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => 'intrus@example.test',
                'password' => 'MotDePasseInvalide@2026',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'intrus@example.test',
            'password' => 'MotDePasseInvalide@2026',
        ])->assertTooManyRequests();
    }
}
