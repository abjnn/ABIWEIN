<?php

namespace App\Http\Controllers\Flock;

use App\Enums\FlockHealthStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flock\StoreDailyRecordRequest;
use App\Models\DailyRecord;
use App\Models\Flock;
use App\Services\Flock\FlockOperationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Gère le résumé technique unique de chaque journée d'élevage. */
class DailyRecordController extends Controller
{
    public function __construct(private readonly FlockOperationService $operations) {}

    /** Ouvre un relevé neuf prépositionné sur la date courante. */
    public function create(Request $request, Flock $flock): View
    {
        $this->ensureWritable($request, $flock);

        return view('flocks.daily-records.create', [
            'flock' => $flock,
            'healthStatuses' => FlockHealthStatus::cases(),
        ]);
    }

    /** Enregistre ou complète le relevé de la date sélectionnée. */
    public function store(StoreDailyRecordRequest $request, Flock $flock): RedirectResponse
    {
        $this->operations->saveDailyRecord($flock, $request->validated(), $request->user());

        return to_route('flocks.show', $flock)->with('status', 'Le relevé quotidien a été enregistré.');
    }

    /** Ouvre la correction contrôlée d'un relevé existant. */
    public function edit(Request $request, Flock $flock, DailyRecord $dailyRecord): View
    {
        $this->ensureWritable($request, $flock);
        $this->ensureBelongsToFlock($flock, $dailyRecord);

        return view('flocks.daily-records.edit', [
            'flock' => $flock,
            'dailyRecord' => $dailyRecord,
            'healthStatuses' => FlockHealthStatus::cases(),
        ]);
    }

    /** Corrige le relevé et applique seulement l'écart de mortalité à l'effectif. */
    public function update(StoreDailyRecordRequest $request, Flock $flock, DailyRecord $dailyRecord): RedirectResponse
    {
        $this->ensureBelongsToFlock($flock, $dailyRecord);
        $this->operations->saveDailyRecord($flock, $request->validated(), $request->user(), $dailyRecord);

        return to_route('flocks.show', $flock)->with('status', 'Le relevé quotidien a été corrigé.');
    }

    private function ensureWritable(Request $request, Flock $flock): void
    {
        abort_unless($request->user()?->isFarmManager(), 403);
        abort_unless($flock->status === 'active', 422, 'Cette bande n’est plus active.');
    }

    private function ensureBelongsToFlock(Flock $flock, DailyRecord $dailyRecord): void
    {
        abort_unless($dailyRecord->flock_id === $flock->id, 404);
    }
}
