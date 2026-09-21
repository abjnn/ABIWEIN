<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Flock;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Vérifie le fonctionnement et les autorisations du module sanitaire. */
class VeterinaryVisitTest extends TestCase
{
    use RefreshDatabase;

    /** Charge les routes modulaires tant qu’elles ne sont pas encore incluses dans routes/web.php. */
    protected function setUp(): void
    {
        parent::setUp();

        // Ce garde évite tout doublon dès que le fichier principal chargera officiellement le module.
        if (! Route::has('health.visits.index')) {
            // Le groupe web restitue en test la session, les erreurs et la protection CSRF
            // que Laravel applique automatiquement lorsque web.php charge ce fichier.
            Route::middleware('web')->group(base_path('routes/health.php'));

            // Laravel a déjà construit ses index de routes pendant le démarrage du test.
            // Ces deux appels rendent immédiatement disponibles les noms ajoutés ci-dessus.
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    /** Un visiteur non connecté est redirigé vers la page de connexion. */
    public function test_guest_cannot_open_health_module(): void
    {
        $this->get(route('health.visits.index'))
            ->assertRedirect(route('login'));
    }

    /** Administrateur, responsable et vétérinaire peuvent tous lire l’historique. */
    public function test_all_business_roles_can_view_health_history(): void
    {
        $visit = VeterinaryVisit::factory()->create([
            'diagnosis' => 'Croissance régulière et comportement normal.',
        ]);

        // Cette boucle vérifie les trois rôles avec la même donnée sanitaire.
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('health.visits.index'))
                ->assertOk()
                ->assertSeeText($visit->flock->code)
                ->assertSeeText('Croissance régulière');
        }
    }

    /** Le responsable peut consulter une fiche mais ne peut pas créer de diagnostic. */
    public function test_farm_manager_has_read_only_access(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $visit = VeterinaryVisit::factory()->create();

        // La consultation détaillée doit rester disponible.
        $this->actingAs($manager)
            ->get(route('health.visits.show', $visit))
            ->assertOk()
            ->assertSeeText($visit->diagnosis);

        // Le formulaire de création doit être refusé au même responsable.
        $this->actingAs($manager)
            ->get(route('health.visits.create'))
            ->assertForbidden();

        // Une soumission directe doit être refusée même sans passer par l’interface.
        $this->actingAs($manager)
            ->post(route('health.visits.store'), $this->validPayload($visit->flock, $visit->veterinarian))
            ->assertForbidden();
    }

    /** Un vétérinaire crée une fiche obligatoirement signée avec son propre compte. */
    public function test_veterinarian_can_create_visit_only_as_self(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();
        $otherVeterinarian = User::factory()->veterinarian()->create();
        $flock = Flock::factory()->create();

        // Le formulaire doit être accessible au vétérinaire connecté.
        $this->actingAs($veterinarian)
            ->get(route('health.visits.create'))
            ->assertOk()
            ->assertSeeText('Enregistrer un diagnostic');

        // Un identifiant falsifié est envoyé volontairement pour tester la signature imposée.
        $response = $this->actingAs($veterinarian)
            ->post(route('health.visits.store'), $this->validPayload($flock, $otherVeterinarian));

        $visit = VeterinaryVisit::query()->sole();

        $response->assertRedirect(route('health.visits.show', $visit));
        $this->assertSame($veterinarian->id, $visit->veterinarian_id);
    }

    /** L’administrateur peut enregistrer une visite au nom d’un vétérinaire actif. */
    public function test_administrator_can_assign_active_veterinarian(): void
    {
        $administrator = User::factory()->administrator()->create();
        $veterinarian = User::factory()->veterinarian()->create();
        $flock = Flock::factory()->create();

        $response = $this->actingAs($administrator)
            ->post(route('health.visits.store'), $this->validPayload($flock, $veterinarian));

        $visit = VeterinaryVisit::query()->sole();

        $response->assertRedirect(route('health.visits.show', $visit));
        $this->assertDatabaseHas('veterinary_visits', [
            'flock_id' => $flock->id,
            'veterinarian_id' => $veterinarian->id,
            'diagnosis' => 'Aucun signe clinique préoccupant.',
        ]);
    }

    /** Une prochaine visite antérieure à l’examen est rejetée sans écriture en base. */
    public function test_next_visit_cannot_be_before_current_visit(): void
    {
        $veterinarian = User::factory()->veterinarian()->create();
        $flock = Flock::factory()->create();
        $payload = $this->validPayload($flock, $veterinarian);

        // Ces dates volontairement incohérentes doivent déclencher la règle métier.
        $payload['visit_date'] = '2026-09-20';
        $payload['next_visit_date'] = '2026-09-19';

        $this->actingAs($veterinarian)
            ->post(route('health.visits.store'), $payload)
            ->assertSessionHasErrors('next_visit_date');

        $this->assertDatabaseCount('veterinary_visits', 0);
    }

    /** Un compte non vétérinaire ne peut pas être choisi comme signataire. */
    public function test_administrator_cannot_assign_non_veterinarian(): void
    {
        $administrator = User::factory()->administrator()->create();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        $flock = Flock::factory()->create();

        $this->actingAs($administrator)
            ->post(route('health.visits.store'), $this->validPayload($flock, $manager))
            ->assertSessionHasErrors('veterinarian_id');

        $this->assertDatabaseCount('veterinary_visits', 0);
    }

    /** Construit les données valides communes aux scénarios de création. */
    private function validPayload(Flock $flock, User $veterinarian): array
    {
        return [
            // Cette relation indique la bande examinée.
            'flock_id' => $flock->id,
            // Cette relation indique le vétérinaire signataire proposé.
            'veterinarian_id' => $veterinarian->id,
            // Cette date correspond à une visite déjà effectuée.
            'visit_date' => today()->toDateString(),
            // Ce texte satisfait le diagnostic obligatoire.
            'diagnosis' => 'Aucun signe clinique préoccupant.',
            // Cette recommandation est facultative mais présente dans le scénario valide.
            'recommendations' => 'Poursuivre la surveillance quotidienne.',
            // Cette date programme un contrôle cohérent après la visite.
            'next_visit_date' => today()->addWeek()->toDateString(),
        ];
    }
}
