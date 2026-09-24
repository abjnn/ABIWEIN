@extends('layouts.private')

@section('title', 'Rapports')
@section('page_title', 'Rapports administratifs')

@section('content')
    {{-- L'en-tête explique l'archive ; seul le responsable voit l'action de génération. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="reports-title">
        <div>
            <p class="private-kicker">Registre officiel</p>
            <h2 id="reports-title">Rapports de l’exploitation</h2>
            <p>Chaque document conserve les données telles qu’elles existaient au moment de sa génération.</p>
        </div>
        @if ($canGenerate)
            <a class="btn btn-private-primary" href="{{ route('reports.create') }}">Générer un rapport</a>
        @endif
    </section>

    {{-- Les filtres portent sur la date officielle et le type du document archivé. --}}
    <form class="private-filter" method="GET" action="{{ route('reports.index') }}">
        <div>
            <label class="form-label" for="report-date">Date</label>
            <input class="form-control @error('date') is-invalid @enderror" id="report-date" name="date" type="date" value="{{ $filters['date'] ?? '' }}">
            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="form-label" for="report-type">Type</label>
            <select class="form-select" id="report-type" name="type">
                <option value="">Tous les rapports</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-private-primary" type="submit">Filtrer</button>
        <a class="btn btn-private-secondary" href="{{ route('reports.index') }}">Réinitialiser</a>
    </form>

    {{-- Une seule table compacte remplace les cartes et graphiques qui gaspillaient l'espace. --}}
    <section class="private-panel" aria-labelledby="reports-list-title">
        <div class="private-panel__header">
            <div>
                <p class="private-kicker">Classement chronologique</p>
                <h2 id="reports-list-title">Documents disponibles</h2>
            </div>
            <span>{{ $reports->total() }} rapport(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Type et objet</th>
                        <th>Période couverte</th>
                        <th>Établi par</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->report_date->format('d/m/Y') }}</td>
                            <td><strong>{{ $report->reference }}</strong></td>
                            <td>
                                <span class="d-block">{{ $report->typeLabel() }}</span>
                                <small>{{ $report->title }}</small>
                            </td>
                            <td>{{ $report->period_start->format('d/m/Y') }} au {{ $report->period_end->format('d/m/Y') }}</td>
                            <td>{{ $report->generator?->name ?? 'Compte supprimé' }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-private-secondary" href="{{ route('reports.show', $report) }}">Consulter</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="private-empty" colspan="6">Aucun rapport ne correspond aux critères choisis.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="mt-3">{{ $reports->links() }}</div>
        @endif
    </section>
@endsection
