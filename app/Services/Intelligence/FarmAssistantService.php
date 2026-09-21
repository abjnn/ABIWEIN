<?php

namespace App\Services\Intelligence;

use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Interroge Gemini lorsqu'il est configuré et garde un repli local utilisable. */
final class FarmAssistantService
{
    public function __construct(private readonly FarmChatContextService $contextService) {}

    /** Indique à l'interface si une clé externe a été configurée. */
    public function providerIsConfigured(): bool
    {
        return trim((string) config('services.gemini.key')) !== '';
    }

    /**
     * Répond à une question avec des données agrégées et aucune action automatique.
     *
     * @param  iterable<AiMessage>  $history
     * @return array{content: string, provider: string}
     */
    public function answer(User $user, string $question, iterable $history): array
    {
        // Les salutations restent locales : elles sont plus naturelles et ne consomment aucun appel API.
        $socialAnswer = $this->socialAnswer($question);

        if ($socialAnswer !== null) {
            return ['content' => $socialAnswer, 'provider' => 'local'];
        }

        // Les indicateurs de la ferme ne sont chargés que lorsqu'une question métier est réellement posée.
        $snapshot = $this->contextService->snapshot();

        if ($this->providerIsConfigured()) {
            try {
                return [
                    'content' => $this->askGemini($question, $history, $snapshot),
                    'provider' => 'gemini',
                ];
            } catch (Throwable $exception) {
                // Le journal conserve la panne technique, jamais la clé ni le prompt.
                Log::warning('Le fournisseur IA ABIWEIN est indisponible.', [
                    'exception' => $exception::class,
                    'code' => $exception->getCode(),
                    'user_id' => $user->id,
                ]);
            }
        }

        return [
            'content' => $this->localAnswer($question, $snapshot),
            'provider' => 'local',
        ];
    }

    /** Envoie au fournisseur uniquement le contexte agrégé et les derniers échanges. */
    private function askGemini(string $question, iterable $history, array $snapshot): string
    {
        $model = trim((string) config('services.gemini.model', 'gemini-2.5-flash-lite'));
        $endpoint = rtrim((string) config('services.gemini.endpoint'), '/')
            .'/models/'.rawurlencode($model).':generateContent';

        // L'instruction interdit les décisions automatiques et diagnostics vétérinaires.
        $systemInstruction = implode("\n", [
            'Tu es l’assistant de pilotage de la ferme avicole ABIWEIN.',
            'Réponds en français avec des phrases courtes, précises et professionnelles.',
            'Si l’utilisateur salue, remercie ou discute normalement, réponds naturellement et chaleureusement.',
            'N’introduis les données de la ferme que lorsque la demande concerne explicitement l’exploitation.',
            'Base-toi uniquement sur les indicateurs agrégés fournis et signale toute donnée manquante.',
            'Ne révèle aucune donnée personnelle et n’invente jamais une vente, visite ou diagnostic.',
            'Tu proposes des contrôles, mais tu ne déclenches aucune action dans l’application.',
            'Pour la santé animale, rappelle qu’un vétérinaire doit confirmer toute décision clinique.',
            "Indicateurs actuels :\n".$this->contextService->asPromptContext($snapshot),
        ]);

        // Dix messages suffisent pour garder le fil sans envoyer tout l'historique.
        $contents = collect($history)
            ->take(-10)
            ->map(fn (AiMessage $message): array => [
                'role' => $message->role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message->content]],
            ])
            ->values()
            ->all();
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
            ->timeout(20)
            ->connectTimeout(5)
            ->post($endpoint, [
                'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                'contents' => $contents,
                'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 700],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Réponse IA non disponible.', $response->status());
        }

        $answer = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));

        if ($answer === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        return $answer;
    }

    /** Fournit une aide factuelle lorsque la clé gratuite n'est pas renseignée. */
    private function localAnswer(string $question, array $snapshot): string
    {
        $normalizedQuestion = mb_strtolower($question);

        if (str_contains($normalizedQuestion, 'stock') || str_contains($normalizedQuestion, 'aliment') || str_contains($normalizedQuestion, 'vitamine')) {
            return "ABIWEIN suit {$snapshot['intrants_actifs']} intrant(s) actif(s), dont {$snapshot['intrants_au_seuil']} au seuil d’alerte. Vérifiez le comptage physique et les besoins des bandes avant tout réapprovisionnement.";
        }

        if (str_contains($normalizedQuestion, 'mort') || str_contains($normalizedQuestion, 'sant') || str_contains($normalizedQuestion, 'vétér')) {
            return "Les relevés des 7 derniers jours totalisent {$snapshot['mortalite_7_jours']} mortalité(s). {$snapshot['controles_sanitaires_en_retard']} contrôle(s) sanitaire(s) sont en retard. Faites confirmer toute mesure clinique par le vétérinaire.";
        }

        if (str_contains($normalizedQuestion, 'vente') || str_contains($normalizedQuestion, 'créance') || str_contains($normalizedQuestion, 'finance')) {
            $turnover = number_format((float) $snapshot['chiffre_affaires_30_jours_fcfa'], 0, ',', ' ');
            $receivables = number_format((float) $snapshot['creances_fcfa'], 0, ',', ' ');

            return "Les 30 derniers jours comptent {$snapshot['ventes_30_jours']} vente(s), pour {$turnover} FCFA enregistrés. Les créances ouvertes atteignent {$receivables} FCFA. Contrôlez les pièces et paiements avant toute relance.";
        }

        if (str_contains($normalizedQuestion, 'rapport')) {
            return 'Ouvrez Rapports, choisissez le type et la période, puis générez le document. Le rapport enregistré pourra ensuite être consulté et téléchargé en PDF.';
        }

        return "La ferme compte actuellement {$snapshot['bandes_actives']} bande(s) active(s) et {$snapshot['effectif_actuel']} sujet(s). Posez une question sur la mortalité, les intrants, les visites sanitaires, les ventes, les créances ou les rapports pour obtenir une réponse ciblée.";
    }

    /** Répond aux échanges courants sans lancer prématurément une analyse de l'exploitation. */
    private function socialAnswer(string $question): ?string
    {
        // Cette normalisation accepte les accents et la ponctuation sans multiplier les variantes à maintenir.
        $message = Str::of($question)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        // Une question qui mentionne un sujet métier doit conserver le traitement analytique habituel.
        $businessTerms = ['ferme', 'bande', 'poulet', 'stock', 'aliment', 'vitamine', 'mort', 'sante', 'veter', 'vente', 'creance', 'finance', 'rapport'];

        if (Str::contains($message, $businessTerms)) {
            return null;
        }

        // Les formules de politesse les plus courantes reçoivent une réponse adaptée au lieu d'un bilan automatique.
        if (Str::startsWith($message, ['merci'])) {
            return 'Avec plaisir. Je reste disponible si vous souhaitez discuter ou poser une question sur ABIWEIN.';
        }

        if (Str::contains($message, ['au revoir', 'a bientot', 'bonne journee', 'bonne soiree'])) {
            return 'À bientôt. Je vous souhaite une excellente journée.';
        }

        if (Str::contains($message, ['comment vas tu', 'comment tu vas', 'comment allez vous', 'comment ca va', 'ca va', 'tu vas bien', 'vous allez bien'])) {
            return 'Je vais bien, merci. Et vous, comment allez-vous ?';
        }

        if (in_array($message, ['salut', 'bonjour', 'bonsoir', 'coucou', 'hello', 'bjr'], true)) {
            return 'Bonjour ! Comment allez-vous ? Je suis heureux de vous retrouver.';
        }

        return null;
    }
}
