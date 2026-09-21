<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vérifie les autorisations liées aux trois rôles de l’espace privé. */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** L’administrateur peut consulter la gestion des comptes. */
    public function test_administrator_can_view_users(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeText('Utilisateurs');
    }

    /** Le responsable d’exploitation ne peut pas ouvrir une page d’administration. */
    public function test_farm_manager_cannot_view_users(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $this->actingAs($manager)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    /** L’administrateur peut créer un compte actif avec un rôle valide. */
    public function test_administrator_can_create_user(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->post(route('admin.users.store'), [
            'name' => 'Responsable Test',
            'email' => 'responsable.test@abiwein.local',
            'role' => UserRole::FarmManager->value,
            'password' => 'MotDePasse@2026',
            'password_confirmation' => 'MotDePasse@2026',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'responsable.test@abiwein.local',
            'role' => UserRole::FarmManager->value,
            'is_active' => true,
        ]);
    }

    /** Un administrateur connecté ne peut pas bloquer son propre compte. */
    public function test_administrator_cannot_block_own_account(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)
            ->patch(route('admin.users.toggle-status', $administrator));

        $response->assertSessionHasErrors('account');
        $this->assertTrue($administrator->fresh()->is_active);
    }
}
