@extends('layouts.private')

@section('title', $flock->code)
@section('page_title', 'Fiche de bande')

@section('content')
    @php
        $status = \App\Enums\FlockStatus::tryFrom($flock->status);
        $statusTone = $flock->status === 'active' ? 'good' : ($flock->status === 'closed' ? 'watch' : 'default');
    @endphp

    {{-- IDENTITÉ : les opérations modifiantes sont absentes de la vue administrateur. --}}
    <section class="private-intro" aria-labelledby="flock-title">
        <div>
            <p class="private-kicker">{{ $status?->label() ?? $flock->status }}</p>
            <h2 id="flock-title">{{ $flock->code }} — {{ $flock->breed }}</h2>
            <p>{{ number_format($flock->current_quantity, 0, ',', ' ') }} sujet(s) actuellement déclarés sur {{ number_format($flock->initial_quantity, 0, ',', ' ') }} à l’arrivée.</p>
        </div>
        <div class="private-intro__aside d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('flocks.index') }}">Retour au registre</a>
            @if ($canWrite && $flock->status === 'active')
                <a class="btn btn-private-primary" href="{{ route('flocks.daily-records.create', $flock) }}">Saisir le relevé du jour</a>
                <a class="btn btn-outline-secondary" href="{{ route('flocks.edit', $flock) }}">Modifier la fiche</a>
            @endif
        </div>
    </section>

    {{-- SYNTHÈSE : les valeurs essentielles tiennent dans une liste lisible. --}}
    <dl class="private-metrics-list mb-4" aria-label="Résumé de la bande">
        <x-private.metric-row label="Effectif actuel" :value="number_format($flock->current_quantity, 0, ',', ' ')" detail="Sujets déclarés disponibles" />
        {{-- Le total est calculé directement par la base dans le contrôleur pour éviter un chargement inutile. --}}
        <x-private.metric-row label="Mortalité cumulée" :value="number_format($flock->daily_records_sum_mortality ?? 0, 0, ',', ' ')" detail="Total des relevés enregistrés" :tone="($flock->daily_records_sum_mortality ?? 0) > 0 ? 'alert' : 'default'" />
        <x-private.metric-row label="Bâtiment" :value="$flock->currentBuilding?->code ?? 'Non affectée'" :detail="$flock->currentBuilding?->name ?? 'Aucun bâtiment actuel'" />
        <x-private.metric-row label="Arrivée" :value="$flock->arrival_date->format('d/m/Y')" :detail="$flock->expected_end_date ? 'Fin prévue : '.$flock->expected_end_date->format('d/m/Y') : 'Fin non planifiée'" />
    </dl>

    <div class="private-grid private-grid--main mb-4">
        {{-- INFORMATIONS GÉNÉRALES : aucune donnée calculée n'est modifiable ici. --}}
        <section class="private-panel" aria-labelledby="flock-info-title">
            <div class="private-panel__header"><div><p class="private-kicker">Cycle</p><h2 id="flock-info-title">Informations générales</h2></div><span class="private-status private-status--{{ $statusTone }}">{{ $status?->label() ?? $flock->status }}</span></div>
            <dl class="row mb-0">
                <dt class="col-sm-5">Fournisseur</dt><dd class="col-sm-7">{{ $flock->supplier?->name ?? 'Non renseigné' }}</dd>
                <dt class="col-sm-5">Bâtiment actuel</dt><dd class="col-sm-7">{{ $flock->currentBuilding?->name ?? 'Non affectée' }}</dd>
                <dt class="col-sm-5">Date d’arrivée</dt><dd class="col-sm-7">{{ $flock->arrival_date->format('d/m/Y') }}</dd>
                <dt class="col-sm-5">Clôture</dt><dd class="col-sm-7">{{ $flock->closed_at?->format('d/m/Y H:i') ?? 'Cycle en cours' }}</dd>
                <dt class="col-sm-5">Notes</dt><dd class="col-sm-7">{{ $flock->notes ?: 'Aucune observation générale.' }}</dd>
            </dl>
        </section>

        {{-- ACTION HORODATÉE : le formulaire alimente à la fois la chronologie et les totaux mesurables. --}}
        @if ($canWrite && $flock->status === 'active')
            <section class="private-panel" aria-labelledby="activity-form-title">
                <div class="private-panel__header"><div><p class="private-kicker">Journal du jour</p><h2 id="activity-form-title">Ajouter une action</h2></div></div>
                <form method="POST" action="{{ route('flocks.activities.store', $flock) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="occurred_at">Date et heure</label>
                            <input class="form-control @error('occurred_at') is-invalid @enderror" type="datetime-local" id="occurred_at" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                            @error('occurred_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="type">Type d’action</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                @foreach ($activityTypes as $type)
                                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="quantity">Quantité mesurée</label>
                            <input class="form-control @error('quantity') is-invalid @enderror" type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="0.01" step="0.01" aria-describedby="quantity-help">
                            <small id="quantity-help" class="form-text">Obligatoire pour aliment, eau, pesée et mortalité. L’unité est automatique.</small>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="description">Compte rendu</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="2000" required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button class="btn btn-private-primary mt-3" type="submit">Ajouter au journal</button>
                </form>
            </section>
        @endif
    </div>

    {{-- CHRONOLOGIE : chaque opération apparaît à son heure réelle, du plus récent au plus ancien. --}}
    <section class="private-panel mb-4" aria-labelledby="timeline-title">
        <div class="private-panel__header"><div><p class="private-kicker">Du matin au soir</p><h2 id="timeline-title">Chronologie des actions</h2></div><span>{{ $activities->total() }} action(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date et heure</th><th>Action</th><th>Compte rendu</th><th class="text-end">Mesure</th><th>Auteur</th></tr></thead>
                <tbody>
                    @forelse ($activities as $activity)
                        @php($activityType = \App\Enums\FlockActivityType::tryFrom($activity->type))
                        <tr>
                            <td>{{ $activity->occurred_at->format('d/m/Y H:i') }}</td>
                            <td><strong>{{ $activityType?->label() ?? $activity->type }}</strong></td>
                            <td>{{ $activity->description }}</td>
                            <td class="text-end">{{ $activity->quantity === null ? '—' : number_format((float) $activity->quantity, 2, ',', ' ').' '.$activity->unit }}</td>
                            <td>{{ $activity->recorder?->name ?? 'Compte supprimé' }}</td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="5">Aucune action horodatée n’a encore été consignée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $activities->links() }}
    </section>

    {{-- RELEVÉS : le tableau présente les résumés utilisés par le tableau de bord et les rapports. --}}
    <section class="private-panel mb-4" aria-labelledby="records-title">
        <div class="private-panel__header"><div><p class="private-kicker">Mesures agrégées</p><h2 id="records-title">Relevés quotidiens</h2></div><span>{{ $dailyRecords->total() }} journée(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th class="text-end">Mortalité</th><th class="text-end">Aliment</th><th class="text-end">Eau</th><th class="text-end">Poids</th><th>État</th><th></th></tr></thead>
                <tbody>
                    @forelse ($dailyRecords as $record)
                        @php($health = \App\Enums\FlockHealthStatus::tryFrom($record->health_status))
                        <tr>
                            <td><strong>{{ $record->record_date->format('d/m/Y') }}</strong><small>{{ $record->observations ?: 'Aucune observation' }}</small></td>
                            <td class="text-end">{{ $record->mortality }}</td>
                            <td class="text-end">{{ number_format((float) $record->feed_kg, 2, ',', ' ') }} kg</td>
                            <td class="text-end">{{ number_format((float) $record->water_liters, 2, ',', ' ') }} L</td>
                            <td class="text-end">{{ $record->average_weight_kg === null ? '—' : number_format((float) $record->average_weight_kg, 3, ',', ' ').' kg' }}</td>
                            <td><span class="private-status private-status--{{ $record->health_status }}">{{ $health?->label() ?? $record->health_status }}</span></td>
                            <td class="text-end">@if($canWrite && $flock->status === 'active')<a class="btn btn-sm btn-outline-secondary" href="{{ route('flocks.daily-records.edit', [$flock, $record]) }}">Corriger</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="7">Aucun relevé quotidien n’est disponible.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $dailyRecords->links() }}
    </section>

    <div class="private-grid private-grid--main mb-4">
        {{-- SANITAIRE : cette section reste en lecture et renvoie vers le module vétérinaire existant. --}}
        <section class="private-panel" aria-labelledby="flock-visits-title">
            <div class="private-panel__header"><h2 id="flock-visits-title">Visites vétérinaires</h2><span>{{ $visits->count() }} récente(s)</span></div>
            <div class="list-group list-group-flush">
                @forelse ($visits as $visit)
                    <a class="list-group-item list-group-item-action px-0" href="{{ route('health.visits.show', $visit) }}"><strong>{{ $visit->visit_date->format('d/m/Y') }}</strong> — {{ $visit->veterinarian?->name ?? 'Vétérinaire' }}<br><small>{{ \Illuminate\Support\Str::limit($visit->diagnosis, 100) }}</small></a>
                @empty
                    <p class="private-empty">Aucune visite liée à cette bande.</p>
                @endforelse
            </div>
        </section>

        {{-- TRANSFERT : toute la bande change de bâtiment en une opération transactionnelle. --}}
        @if ($canWrite && $flock->status === 'active')
            <section class="private-panel" aria-labelledby="transfer-title">
                <div class="private-panel__header"><h2 id="transfer-title">Transférer la bande</h2></div>
                @if ($availableBuildings->isEmpty())
                    <p class="private-empty">Aucun autre bâtiment d’élevage actif n’est disponible.</p>
                @else
                    <form method="POST" action="{{ route('flocks.transfer', $flock) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="building_id">Nouveau bâtiment</label>
                            <select class="form-select @error('building_id') is-invalid @enderror" id="building_id" name="building_id" required>
                                <option value="">Sélectionner</option>
                                @foreach ($availableBuildings as $building)
                                    <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>{{ $building->code }} — {{ $building->name }} ({{ number_format($building->capacity, 0, ',', ' ') }} places)</option>
                                @endforeach
                            </select>
                            @error('building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="transfer_occurred_at">Date et heure</label>
                            <input class="form-control @error('occurred_at') is-invalid @enderror" type="datetime-local" id="transfer_occurred_at" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                            @error('occurred_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3"><label class="form-label" for="transfer_notes">Motif ou précision</label><textarea class="form-control" id="transfer_notes" name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div>
                        <button class="btn btn-private-primary" type="submit">Enregistrer le transfert</button>
                    </form>
                @endif
            </section>
        @endif
    </div>

    {{-- HISTORIQUE TECHNIQUE : ces traces expliquent chaque changement important de l'effectif. --}}
    <section class="private-panel mb-4" aria-labelledby="movements-title">
        <div class="private-panel__header"><h2 id="movements-title">Historique technique</h2><span>12 dernières opérations</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th>Type</th><th>Départ</th><th>Destination</th><th class="text-end">Quantité</th><th>Motif</th></tr></thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr><td>{{ $movement->occurred_at->format('d/m/Y H:i') }}</td><td>{{ str($movement->type)->replace('_', ' ')->title() }}</td><td>{{ $movement->fromBuilding?->code ?? '—' }}</td><td>{{ $movement->toBuilding?->code ?? '—' }}</td><td class="text-end">{{ number_format($movement->quantity, 0, ',', ' ') }}</td><td>{{ $movement->reason ?: '—' }}</td></tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucun mouvement technique disponible.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- FIN DE CYCLE : la clôture et l'archivage sont explicites et non destructifs. --}}
    @if ($canWrite)
        <section class="private-panel" aria-labelledby="lifecycle-title">
            <div class="private-panel__header"><div><p class="private-kicker">Cycle de vie</p><h2 id="lifecycle-title">Clôture et archivage</h2></div></div>
            @if ($flock->status === 'active')
                <p>La clôture bloque les nouveaux relevés et libère le bâtiment actuel sans supprimer l’historique.</p>
                {{-- La clôture est un envoi explicite protégé par CSRF, sans script intégré à la vue. --}}
                <form method="POST" action="{{ route('flocks.close', $flock) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger" type="submit">Clôturer la bande</button></form>
            @elseif ($flock->status === 'closed')
                <p>L’archivage retire la bande des cycles courants tout en la gardant consultable.</p>
                <form method="POST" action="{{ route('flocks.archive', $flock) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary" type="submit">Archiver la bande</button></form>
            @else
                <p class="private-empty">Cette bande est archivée et reste disponible en lecture seule.</p>
            @endif
        </section>
    @endif
@endsection
