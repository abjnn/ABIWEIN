@extends('layouts.private')

{{-- Ce titre apparaît dans l’onglet du navigateur. --}}
@section('title', 'Suivi sanitaire')
{{-- Ce titre apparaît dans l’en-tête fixe de l’espace privé. --}}
@section('page_title', 'Suivi sanitaire')

@section('content')
    {{-- EN-TÊTE : présente le module et affiche l’action uniquement aux profils habilités. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="health-visits-title">
        <div>
            <p class="private-kicker">Santé et prévention</p>
            <h2 id="health-visits-title">Visites vétérinaires</h2>
            <p>Consultez les diagnostics, les recommandations et les contrôles programmés pour chaque bande.</p>
        </div>

        {{-- Ce bouton disparaît pour le responsable, qui possède un accès en lecture seule. --}}
        @if ($canCreate)
            <a class="btn btn-private-primary" href="{{ route('health.visits.create') }}">Enregistrer une visite</a>
        @endif
    </section>

    {{-- INDICATEURS : résument l’historique filtré et les contrôles encore à venir. --}}
    <section class="private-stats" aria-label="Indicateurs sanitaires">
        <x-private.stat-card label="Visites retrouvées" :value="number_format($visits->total(), 0, ',', ' ')" detail="Selon les filtres actuels" />
        <x-private.stat-card label="Contrôles à venir" :value="number_format($upcomingCount, 0, ',', ' ')" detail="Prochaines visites programmées" :tone="$upcomingCount > 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="Bandes disponibles" :value="number_format($flocks->count(), 0, ',', ' ')" detail="Cycles présents dans le registre" />
        <x-private.stat-card label="Vétérinaires actifs" :value="number_format($veterinarians->count(), 0, ',', ' ')" detail="Professionnels pouvant signer" />
    </section>

    {{-- FILTRES : tous les champs utilisent la méthode GET afin de produire une URL partageable. --}}
    <section class="private-form-card mb-3" aria-labelledby="health-filters-title">
        <div class="mb-3">
            <p class="private-kicker">Recherche</p>
            <h2 class="h5 mb-0" id="health-filters-title">Filtrer l’historique</h2>
        </div>

        {{-- Ce formulaire ne modifie aucune donnée ; il limite seulement les résultats affichés. --}}
        <form method="GET" action="{{ route('health.visits.index') }}">
            <div class="row g-3 align-items-end">
                {{-- Ce champ recherche dans le code de bande et les textes cliniques. --}}
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="search">Recherche</label>
                    <input class="form-control @error('search') is-invalid @enderror" id="search" name="search" type="search" value="{{ request('search') }}" maxlength="120" placeholder="Bande, diagnostic, recommandation">
                    @error('search')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Ce sélecteur limite l’historique à une seule bande. --}}
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="flock_id">Bande</label>
                    <select class="form-select @error('flock_id') is-invalid @enderror" id="flock_id" name="flock_id">
                        <option value="">Toutes les bandes</option>
                        @foreach ($flocks as $flock)
                            <option value="{{ $flock->id }}" @selected((string) request('flock_id') === (string) $flock->id)>{{ $flock->code }}</option>
                        @endforeach
                    </select>
                    @error('flock_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Ce sélecteur limite l’historique au professionnel choisi. --}}
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="veterinarian_id">Vétérinaire</label>
                    <select class="form-select @error('veterinarian_id') is-invalid @enderror" id="veterinarian_id" name="veterinarian_id">
                        <option value="">Tous les vétérinaires</option>
                        @foreach ($veterinarians as $veterinarian)
                            <option value="{{ $veterinarian->id }}" @selected((string) request('veterinarian_id') === (string) $veterinarian->id)>{{ $veterinarian->name }}</option>
                        @endforeach
                    </select>
                    @error('veterinarian_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Ces deux dates définissent la période de visites à consulter. --}}
                <div class="col-md-4">
                    <label class="form-label" for="date_from">Du</label>
                    <input class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
                    @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="date_to">Au</label>
                    <input class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
                    @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Le premier bouton applique les filtres ; le second restaure la liste complète. --}}
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-private-primary" type="submit">Appliquer</button>
                    <a class="btn btn-private-secondary" href="{{ route('health.visits.index') }}">Réinitialiser</a>
                </div>
            </div>
        </form>
    </section>

    {{-- TABLEAU : chaque ligne représente une visite et ouvre sa fiche détaillée. --}}
    <section class="private-panel" aria-labelledby="health-history-title">
        <div class="private-panel__header">
            <div>
                <p class="private-kicker">Historique clinique</p>
                <h2 id="health-history-title">Diagnostics enregistrés</h2>
            </div>
            <span>{{ $visits->total() }} résultat{{ $visits->total() > 1 ? 's' : '' }}</span>
        </div>

        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Bande</th>
                        <th scope="col">Vétérinaire</th>
                        <th scope="col">Diagnostic</th>
                        <th scope="col">Prochain contrôle</th>
                        <th scope="col"><span class="visually-hidden">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        {{-- Cette ligne affiche les informations essentielles sans tronquer les données de la fiche. --}}
                        <tr>
                            <td>{{ $visit->visit_date->format('d/m/Y') }}</td>
                            <td>
                                <strong>{{ $visit->flock->code }}</strong>
                                <small>{{ $visit->flock->currentBuilding?->name ?? 'Bâtiment non indiqué' }}</small>
                            </td>
                            <td>{{ $visit->veterinarian->name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($visit->diagnosis, 90) }}</td>
                            <td>
                                @if ($visit->next_visit_date)
                                    {{-- Une échéance du jour reste normale ; seules les dates antérieures sont signalées. --}}
                                    <span class="private-status {{ $visit->next_visit_date->lt(today()) ? 'private-status--watch' : 'private-status--good' }}">
                                        {{ $visit->next_visit_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span>Non programmée</span>
                                @endif
                            </td>
                            <td><a class="btn btn-sm btn-private-secondary" href="{{ route('health.visits.show', $visit) }}">Consulter</a></td>
                        </tr>
                    @empty
                        {{-- Cet état informe clairement lorsque les filtres ne renvoient aucune visite. --}}
                        <tr><td colspan="6" class="private-empty">Aucune visite vétérinaire ne correspond aux critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- La pagination conserve automatiquement les filtres grâce à withQueryString(). --}}
        @if ($visits->hasPages())
            <div class="private-pagination">{{ $visits->links() }}</div>
        @endif
    </section>
@endsection
