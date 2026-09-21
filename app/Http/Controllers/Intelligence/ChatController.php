<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intelligence\StoreChatMessageRequest;
use App\Models\AiMessage;
use App\Services\Intelligence\FarmAssistantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Affiche et alimente la conversation d'aide au pilotage ABIWEIN. */
final class ChatController extends Controller
{
    /** Charge les quarante derniers messages du compte connecté seulement. */
    public function index(Request $request, FarmAssistantService $assistant): View
    {
        $messages = AiMessage::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(40)
            ->get()
            ->reverse()
            ->values();

        return view('intelligence.chat', [
            'messages' => $messages,
            'providerConfigured' => $assistant->providerIsConfigured(),
            'suggestions' => [
                'Quel est l’état général de la ferme aujourd’hui ?',
                'Quels intrants ont atteint leur seuil d’alerte ?',
                'Analyse la mortalité des sept derniers jours.',
                'Quel est le niveau actuel des créances ?',
                'Comment préparer un rapport journalier ?',
            ],
        ]);
    }

    /** Enregistre la question puis la réponse dans une transaction unique. */
    public function store(StoreChatMessageRequest $request, FarmAssistantService $assistant): RedirectResponse
    {
        $user = $request->user();
        $question = (string) $request->validated('message');

        // L'historique précédent est chargé avant l'ajout pour ne pas doubler la question.
        $history = AiMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();
        $answer = $assistant->answer($user, $question, $history);

        DB::transaction(function () use ($user, $question, $answer): void {
            AiMessage::query()->create([
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $question,
                'provider' => null,
            ]);
            AiMessage::query()->create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $answer['content'],
                'provider' => $answer['provider'],
            ]);
        });

        return to_route('intelligence.demo')->with('status', 'Réponse ajoutée à la conversation.');
    }

    /** Efface uniquement la conversation du compte qui en fait la demande. */
    public function destroy(Request $request): RedirectResponse
    {
        AiMessage::query()->where('user_id', $request->user()->id)->delete();

        return to_route('intelligence.demo')->with('status', 'La conversation a été effacée.');
    }
}
