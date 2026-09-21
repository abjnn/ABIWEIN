@extends('layouts.private')

@section('title', 'Journal d’audit')
@section('page_title', 'Journal d’audit')

@section('content')
    {{-- EN-TÊTE : rappelle que le journal retrace les mutations des données sensibles. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="audit-title">
        <div>
            <p class="private-kicker">Administration</p>
            <h2 id="audit-title">Traçabilité des opérations</h2>
            <p>Consultez l’acteur, la cible et les changements sûrs associés aux créations, modifications et suppressions.</p>
        </div>
    </section>

    {{-- FILTRES : les listes fermées empêchent de transmettre un nom de modèle arbitraire. --}}
    <section class="private-form-card mb-3" aria-labelledby="audit-filters-title">
        <div class="mb-3">
            <p class="private-kicker">Recherche</p>
            <h2 class="h5 mb-0" id="audit-filters-title">Filtrer le journal</h2>
        </div>

        <form method="GET" action="{{ url()->current() }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="event">Action</label>
                    <select class="form-select @error('event') is-invalid @enderror" id="event" name="event">
                        <option value="">Toutes les actions</option>
                        <option value="created" @selected(($filters['event'] ?? '') === 'created')>Création</option>
                        <option value="updated" @selected(($filters['event'] ?? '') === 'updated')>Modification</option>
                        <option value="deleted" @selected(($filters['event'] ?? '') === 'deleted')>Suppression</option>
                    </select>
                    @error('event')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="type">Cible</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                        <option value="">Toutes les cibles</option>
                        @foreach ($auditableTypes as $key => $type)
                            <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="actor_id">Acteur</label>
                    <select class="form-select @error('actor_id') is-invalid @enderror" id="actor_id" name="actor_id">
                        <option value="">Tous les acteurs</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                    @error('actor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="date_from">Du</label>
                    <input class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
                    @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="date_to">Au</label>
                    <input class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
                    @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-private-primary" type="submit">Appliquer</button>
                    <a class="btn btn-private-secondary" href="{{ url()->current() }}">Réinitialiser</a>
                </div>
            </div>
        </form>
    </section>

    {{-- TABLEAU : les détails restent repliés pour garder une lecture compacte du journal. --}}
    <section class="private-panel" aria-labelledby="audit-table-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Historique</p><h2 id="audit-table-title">Opérations enregistrées</h2></div>
            <span>{{ $logs->total() }} résultat(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead>
                    <tr><th>Date</th><th>Action</th><th>Cible</th><th>Acteur</th><th>Changements</th><th>Contexte</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $changedKeys = collect(array_keys($log->old_values ?? []))
                                ->merge(array_keys($log->new_values ?? []))
                                ->unique();
                            $eventTone = match ($log->event) {
                                'created' => 'private-status--good',
                                'deleted' => 'private-status--blocked',
                                default => '',
                            };
                            $displayValue = static function (mixed $value): string {
                                if ($value === null) {
                                    return 'Non renseigné';
                                }

                                if (is_bool($value)) {
                                    return $value ? 'Oui' : 'Non';
                                }

                                return is_array($value)
                                    ? (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Valeur structurée')
                                    : (string) $value;
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $log->created_at->format('d/m/Y') }}</strong><small>{{ $log->created_at->format('H:i:s') }}</small></td>
                            <td><span class="private-status {{ $eventTone }}">{{ $log->eventLabel() }}</span></td>
                            <td>
                                <strong>{{ $log->auditable_label ?? 'Cible sans libellé' }}</strong>
                                <small>{{ $typeLabels[$log->auditable_type] ?? class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</small>
                            </td>
                            <td><strong>{{ $log->actor?->name ?? $log->actor_name ?? 'Système' }}</strong><small>{{ $log->actor_id ? 'Compte #'.$log->actor_id : 'Sans compte associé' }}</small></td>
                            <td>
                                @if ($changedKeys->isEmpty())
                                    <span>Aucune valeur métier</span>
                                @else
                                    <details>
                                        <summary>{{ $changedKeys->count() }} champ(s)</summary>
                                        @foreach ($changedKeys as $key)
                                            <strong>{{ str_replace('_', ' ', ucfirst($key)) }}</strong>
                                            @if (array_key_exists($key, $log->old_values ?? []))
                                                <small>Avant : {{ $displayValue($log->old_values[$key]) }}</small>
                                            @endif
                                            @if (array_key_exists($key, $log->new_values ?? []))
                                                <small>Après : {{ $displayValue($log->new_values[$key]) }}</small>
                                            @endif
                                        @endforeach
                                    </details>
                                @endif
                            </td>
                            <td><strong>{{ $log->ip_address ?? 'Adresse inconnue' }}</strong><small title="{{ $log->user_agent }}">{{ \Illuminate\Support\Str::limit($log->user_agent ?? 'Client inconnu', 55) }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucune opération ne correspond aux critères sélectionnés.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="private-pagination">{{ $logs->links() }}</div>
        @endif
    </section>
@endsection
