<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vérifie la connexion, la déconnexion et le blocage des comptes ABIWEIN. */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** Un visiteur doit être renvoyé vers la connexion avant d’ouvrir l’espace privé. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** Un compte actif peut ouvrir une session avec son adresse et son mot de passe. */
    public function test_active_user_can_login(): void
    {
        // Le mot de passe en clair n’existe que dans ce test éphémère.
        $user = User::factory()->create(['password' => 'MotDePasse@2026']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'MotDePasse@2026',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    /** Un compte bloqué reçoit le même message neutre qu’un identifiant incorrect. */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'MotDePasse@2026']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'MotDePasse@2026',
        ]);

        $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** La déconnexion invalide l’accès privé et ramène vers l’accueil public. */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
