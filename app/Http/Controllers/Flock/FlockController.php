<?php

namespace App\Http\Controllers\Flock;

use App\Enums\FlockActivityType;
use App\Enums\FlockHealthStatus;
use App\Enums\FlockStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flock\IndexFlockRequest;
use App\Http\Requests\Flock\StoreFlockRequest;
use App\Http\Requests\Flock\TransferFlockRequest;
use App\Http\Requests\Flock\UpdateFlockRequest;
use App\Models\Building;
use App\Models\Flock;
use App\Models\Supplier;
use App\Services\Flock\FlockOperationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pilote le cycle de vie des bandes sans exposer les écritures à l'administrateur. */
class FlockController extends Controller
{
    public function __construct(private readonly FlockOperationService $operations) {}

    /** Affiche le registre compact des bandes et ses filtres principaux. */
    public function index(IndexFlockRequest $request): View
    {
        $filters = $request->validated();

        $flocks = Flock::query()
            ->with(['supplier:id,name', 'currentBuilding:id,code,name'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $nested): Builder => $nested
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['building_id'] ?? null, fn (Builder $query, int|string $buildingId): Builder => $query->where('current_building_id', $buildingId))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'closed' THEN 1 ELSE 2 END")
            ->orderByDesc('arrival_date')
            ->paginate(15)
            ->withQueryString();

        return view('flocks.index', [
            'flocks' => $flocks,
            'filters' => $filters,
            'statuses' => FlockStatus::cases(),
            'buildings' => Building::query()->orderBy('name')->get(['id', 'code', 'name']),
            'canWrite' => $request->user()->isFarmManager(),
        ]);
    }

    /** Ouvre le formulaire autonome de création. */
    public function create(Request $request): View
    {
        $this->ensureManager($request);

        return view('flocks.create', $this->formOptions());
    }

    /** Crée la bande avec son mouvement d'arrivée initial. */
    public function store(StoreFlockRequest $request): RedirectResponse
    {
        $flock = $this->operations->create($request->validated(), $request->user());

        return to_route('flocks.show', $flock)
            ->with('status', 'La bande et son effectif initial ont été enregistrés.');
    }

    /** Présente l'identité, les relevés, la chronologie et les mouvements de la bande. */
    public function show(Request $request, Flock $flock): View
    {
        // Charge les relations d'identité et calcule la mortalité sans récupérer deux fois tous les relevés.
        $flock->load(['supplier', 'currentBuilding'])->loadSum('dailyRecords', 'mortality');

        return view('flocks.show', [
            'flock' => $flock,
            'dailyRecords' => $flock->dailyRecords()
                ->with('recorder:id,name')
                ->orderByDesc('record_date')
                ->paginate(10, ['*'], 'records_page'),
            'activities' => $flock->activities()
                ->with('recorder:id,name')
                ->orderByDesc('occurred_at')
                ->paginate(15, ['*'], 'activities_page'),
            'movements' => $flock->movements()
                ->with(['fromBuilding:id,code,name', 'toBuilding:id,code,name', 'recorder:id,name'])
                ->latest('occurred_at')
                ->limit(12)
                ->get(),
            'visits' => $flock->veterinaryVisits()->with('veterinarian:id,name')->latest('visit_date')->limit(6)->get(),
            'availableBuildings' => Building::query()
                ->where('status', 'active')
                ->whereIn('type', ['brooding', 'growing'])
                ->whereKeyNot($flock->current_building_id)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'capacity']),
            'activityTypes' => FlockActivityType::cases(),
            'healthStatuses' => FlockHealthStatus::cases(),
            'canWrite' => $request->user()->isFarmManager(),
        ]);
    }

    /** Ouvre l'édition des informations descriptives uniquement. */
    public function edit(Request $request, Flock $flock): View
    {
        $this->ensureManager($request);
        abort_unless($flock->status === FlockStatus::Active->value, 422, 'Cette bande n’est plus active.');

        return view('flocks.edit', array_merge($this->formOptions(), ['flock' => $flock]));
    }

    /** Modifie l'identité sans contourner les journaux d'effectif. */
    public function update(UpdateFlockRequest $request, Flock $flock): RedirectResponse
    {
        $this->operations->update($flock, $request->validated());

        return to_route('flocks.show', $flock)->with('status', 'Les informations de la bande ont été mises à jour.');
    }

    /** Transfère l'ensemble de l'effectif vers un bâtiment disponible. */
    public function transfer(TransferFlockRequest $request, Flock $flock): RedirectResponse
    {
        $this->operations->transfer($flock, $request->validated(), $request->user());

        return to_route('flocks.show', $flock)->with('status', 'Le transfert de la bande a été enregistré.');
    }

    /** Clôture le cycle tout en conservant ses données consultables. */
    public function close(Request $request, Flock $flock): RedirectResponse
    {
        $this->ensureManager($request);
        $this->operations->close($flock, $request->user());

        return to_route('flocks.show', $flock)->with('status', 'La bande a été clôturée.');
    }

    /** Archive une bande clôturée sans suppression destructive. */
    public function archive(Request $request, Flock $flock): RedirectResponse
    {
        $this->ensureManager($request);
        $this->operations->archive($flock);

        return to_route('flocks.show', $flock)->with('status', 'La bande a été archivée.');
    }

    /** Fournit les listes communes aux formulaires de création et d'édition. */
    private function formOptions(): array
    {
        return [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'buildings' => Building::query()
                ->where('status', 'active')
                ->whereIn('type', ['brooding', 'growing'])
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'capacity']),
        ];
    }

    /** Double protection utile si une future route omet accidentellement son middleware. */
    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->isFarmManager(), 403);
    }
}
