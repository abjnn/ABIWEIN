@extends('layouts.private')

@section('title', 'Bandes')
@section('page_title', 'Bandes et suivi')

@section('content')
    {{-- EN-TÊTE : le bouton d'écriture n'est jamais rendu pour l'administrateur. --}}
    <section class="private-intro" aria-labelledby="flocks-title">
        <div>
            <p class="private-kicker">Exploitation avicole</p>
            <h2 id="flocks-title">Registre des bandes</h2>
            <p>Suivez les effectifs, bâtiments et journées de chaque cycle d’élevage.</p>
        </div>
        @if ($canWrite)
            <div class="private-intro__aside">
                <a class="btn btn-private-primary" href="{{ route('flocks.create') }}">Créer une bande</a>
            </div>
        @endif
    </section>

    {{-- FILTRES : ils restent limités aux critères utiles à la recherche d'une bande. --}}
    <section class="private-panel mb-4" aria-labelledby="flock-filters-title">
        <div class="private-panel__header"><h2 id="flock-filters-title">Filtrer le registre</h2></div>
        <form class="row g-3" method="GET" action="{{ route('flocks.index') }}">
            <div class="col-lg-4">
                <label class="form-label" for="q">Référence ou souche</label>
                <input class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label" for="status">État</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tous les états</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label" for="building_id">Bâtiment actuel</label>
                <select class="form-select" id="building_id" name="building_id">
                    <option value="">Tous les bâtiments</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected((string) ($filters['building_id'] ?? '') === (string) $building->id)>{{ $building->code }} — {{ $building->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                <button class="btn btn-private-primary" type="submit">Appliquer</button>
                <a class="btn btn-outline-secondary" href="{{ route('flocks.index') }}">Effacer</a>
            </div>
        </form>
    </section>

    {{-- LISTE : une ligne compacte remplace les grandes cartes répétitives. --}}
    <section class="private-panel" aria-labelledby="flock-list-title">
        <div class="private-panel__header">
            <h2 id="flock-list-title">Bandes enregistrées</h2>
            <span>{{ $flocks->total() }} résultat(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Référence</th><th>Cycle</th><th>Bâtiment</th><th class="text-end">Effectif</th><th>État</th><th></th></tr></thead>
                <tbody>
                    @forelse ($flocks as $flock)
                        @php($status = \App\Enums\FlockStatus::tryFrom($flock->status))
                        <tr>
                            <td><strong>{{ $flock->code }}</strong><small>{{ $flock->breed }}</small></td>
                            <td>{{ $flock->arrival_date->format('d/m/Y') }}<small>Fin prévue : {{ $flock->expected_end_date?->format('d/m/Y') ?? 'non définie' }}</small></td>
                            <td>{{ $flock->currentBuilding?->name ?? 'Non affectée' }}</td>
                            <td class="text-end"><strong>{{ number_format($flock->current_quantity, 0, ',', ' ') }}</strong><small>sur {{ number_format($flock->initial_quantity, 0, ',', ' ') }}</small></td>
                            <td><span class="private-status">{{ $status?->label() ?? $flock->status }}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('flocks.show', $flock) }}">Ouvrir</a></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucune bande ne correspond aux critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $flocks->links() }}
    </section>
@endsection
