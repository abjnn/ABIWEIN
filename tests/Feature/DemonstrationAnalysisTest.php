<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AiMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Vérifie la conversation IA, son repli local et ses autorisations. */
class DemonstrationAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /** Un visiteur doit d'abord ouvrir une session valide. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('intelligence.demo'))->assertRedirect(route('login'));
    }

    /** L'administrateur et le responsable voient le chat, jamais le vétérinaire. */
    public function test_only_administrator_and_manager_can_use_the_assistant(): void
    {
        foreach ([UserRole::Administrator, UserRole::FarmManager] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($user)
                ->get(route('intelligence.demo'))
                ->assertOk()
                ->assertSeeText('Discutez avec les données de l’exploitation')
                ->assertSeeText('Exemples de demandes');
        }

        $veterinarian = User::factory()->veterinarian()->create();

        $this->actingAs($veterinarian)
            ->get(route('intelligence.demo'))
            ->assertForbidden();
        $this->actingAs($veterinarian)
            ->post(route('intelligence.messages.store'), ['message' => 'Analyse la ferme'])
            ->assertForbidden();
    }

    /** Sans clé, une réponse locale factuelle est enregistrée sans aucun réseau. */
    public function test_local_fallback_answers_and_persists_a_private_conversation(): void
    {
        config()->set('services.gemini.key', null);
        Http::fake();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        Product::query()->create([
            'code' => 'ALIM-TEST',
            'name' => 'Aliment démarrage',
            'category' => 'feed',
            'unit' => 'kg',
            'current_stock' => 5,
            'alert_threshold' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->post(route('intelligence.messages.store'), ['message' => 'Quel est le stock d’aliment ?'])
            ->assertRedirect(route('intelligence.demo'));

        $this->assertDatabaseHas('ai_messages', [
            'user_id' => $manager->id,
            'role' => 'assistant',
            'provider' => 'local',
        ]);
        $this->actingAs($manager)
            ->get(route('intelligence.demo'))
            ->assertOk()
            ->assertSeeText('Quel est le stock d’aliment ?')
            ->assertSeeText('1 intrant(s) actif(s)')
            ->assertSeeText('Réponse locale');
        Http::assertNothingSent();
    }

    /** Une salutation déclenche une conversation naturelle sans bilan métier ni appel externe. */
    public function test_greeting_receives_a_social_answer_before_any_farm_analysis(): void
    {
        config()->set('services.gemini.key', null);
        Http::fake();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);

        $this->actingAs($manager)
            ->post(route('intelligence.messages.store'), ['message' => 'Salut'])
            ->assertRedirect(route('intelligence.demo'));

        $this->assertDatabaseHas('ai_messages', [
            'user_id' => $manager->id,
            'role' => 'assistant',
            'content' => 'Bonjour ! Comment allez-vous ? Je suis heureux de vous retrouver.',
            'provider' => 'local',
        ]);
        $this->assertDatabaseMissing('ai_messages', [
            'user_id' => $manager->id,
            'role' => 'assistant',
            'content' => 'La ferme compte actuellement',
        ]);
        Http::assertNothingSent();
    }

    /** Une question de courtoisie reçoit une réponse de courtoisie, comme dans un véritable chat. */
    public function test_wellbeing_question_receives_a_natural_answer(): void
    {
        config()->set('services.gemini.key', null);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('intelligence.messages.store'), ['message' => 'Bonjour, comment tu vas ?'])
            ->assertRedirect(route('intelligence.demo'));

        $this->assertDatabaseHas('ai_messages', [
            'user_id' => $administrator->id,
            'role' => 'assistant',
            'content' => 'Je vais bien, merci. Et vous, comment allez-vous ?',
        ]);
    }

    /** Avec une clé, la requête Gemini n'emporte que des agrégats contrôlés. */
    public function test_configured_gemini_provider_is_called_without_personal_data(): void
    {
        config()->set('services.gemini.key', 'test-secret-key');
        config()->set('services.gemini.model', 'gemini-test');
        $administrator = User::factory()->administrator()->create([
            'name' => 'Nom Confidentiel',
            'email' => 'secret@example.com',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Voici la synthèse demandée.']]],
                ]],
            ]),
        ]);

        $this->actingAs($administrator)
            ->post(route('intelligence.messages.store'), ['message' => 'Donne la synthèse générale.'])
            ->assertRedirect(route('intelligence.demo'));

        $this->assertDatabaseHas('ai_messages', [
            'user_id' => $administrator->id,
            'role' => 'assistant',
            'provider' => 'gemini',
            'content' => 'Voici la synthèse demandée.',
        ]);
        Http::assertSent(function ($request): bool {
            $body = $request->body();

            return str_contains($request->url(), '/models/gemini-test:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-secret-key')
                && str_contains($body, 'bandes_actives')
                && ! str_contains($body, 'Nom Confidentiel')
                && ! str_contains($body, 'secret@example.com');
        });
    }

    /** Chaque compte voit et efface uniquement son propre historique. */
    public function test_conversations_are_isolated_and_clear_is_scoped_to_current_user(): void
    {
        $administrator = User::factory()->administrator()->create();
        $manager = User::factory()->create(['role' => UserRole::FarmManager]);
        AiMessage::query()->create([
            'user_id' => $administrator->id,
            'role' => 'user',
            'content' => 'Question privée administrateur',
        ]);
        AiMessage::query()->create([
            'user_id' => $manager->id,
            'role' => 'user',
            'content' => 'Question privée responsable',
        ]);

        $this->actingAs($administrator)
            ->get(route('intelligence.demo'))
            ->assertSeeText('Question privée administrateur')
            ->assertDontSeeText('Question privée responsable');

        $this->actingAs($administrator)
            ->delete(route('intelligence.conversation.destroy'))
            ->assertRedirect(route('intelligence.demo'));

        $this->assertDatabaseMissing('ai_messages', ['user_id' => $administrator->id]);
        $this->assertDatabaseHas('ai_messages', ['user_id' => $manager->id]);
    }
}
