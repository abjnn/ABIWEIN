<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    /** Les trois profils reçoivent la même liste verticale compacte, sans anciennes cartes statistiques. */
    public function test_each_role_dashboard_uses_compact_metric_rows(): void
    {
        // Ces trois comptes couvrent l'administrateur, le responsable et le vétérinaire.
        $users = [
            User::factory()->administrator()->create(),
            User::factory()->create(),
            User::factory()->veterinarian()->create(),
        ];

        foreach ($users as $user) {
            // Chaque réponse doit contenir la liste et au moins une ligne, jamais l'ancienne grille de cartes.
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('class="private-metrics-list"', false)
                ->assertSee('class="private-metric"', false)
                ->assertDontSee('private-stats', false)
                ->assertDontSee('<article class="private-stat', false);
        }
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

    /** Deux personnes ne peuvent pas recevoir le même mot de passe, même si son empreinte est salée. */
    public function test_existing_user_password_cannot_be_reused_for_new_account(): void
    {
        $administrator = User::factory()->administrator()->create();
        User::factory()->create(['password' => Hash::make('Secret#Partage2026!')]);

        $this->actingAs($administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Nouveau responsable',
                'email' => 'nouveau.responsable.abiwein@gmail.com',
                'role' => 'farm_manager',
                'password' => 'Secret#Partage2026!',
                'password_confirmation' => 'Secret#Partage2026!',
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

    /** Le jeu de démonstration utilise trois identités et trois secrets réellement distincts. */
    public function test_demo_user_seeder_creates_distinct_local_credentials(): void
    {
        (new DemoUserSeeder)->run();

        $accounts = User::query()->orderBy('email')->get();

        $this->assertCount(3, $accounts);
        $this->assertSame(3, $accounts->pluck('email')->unique()->count());
        $this->assertTrue(Hash::check('Nadege#Abiwein26!', $accounts->firstWhere('email', 'nadege.nkomo@abiwein.local')->password));
        $this->assertTrue(Hash::check('Ferme#Tchoumi26!', $accounts->firstWhere('email', 'alain.tchoumi@abiwein.local')->password));
        $this->assertTrue(Hash::check('Sante#Ngono2026!', $accounts->firstWhere('email', 'mireille.ngono@abiwein.local')->password));
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
