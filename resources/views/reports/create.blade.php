@extends('layouts.private')

@section('title', 'Générer un rapport')
@section('page_title', 'Générer un rapport')

@section('content')
    {{-- Cette page est réservée au responsable ; l'administrateur reste lecteur. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="report-create-title">
        <div>
            <p class="private-kicker">Nouveau document</p>
            <h2 id="report-create-title">Rapport administratif</h2>
            <p>Les informations seront figées dans l’archive dès la génération.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('reports.index') }}">Retour aux rapports</a>
    </section>

    {{-- Le formulaire sépare la date officielle de la période couverte par les opérations. --}}
    <section class="private-panel" aria-labelledby="report-form-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Paramètres</p><h2 id="report-form-title">Informations du document</h2></div>
        </div>

        <form method="POST" action="{{ route('reports.store') }}" class="private-form">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="report-type">Type de rapport</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="report-type" name="type" required>
                        <option value="">Choisir un type</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="report-date">Date officielle</label>
                    <input class="form-control @error('report_date') is-invalid @enderror" id="report-date" name="report_date" type="date" value="{{ old('report_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('report_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="report-title">Objet du rapport <span class="text-muted">(facultatif)</span></label>
                    <input class="form-control @error('title') is-invalid @enderror" id="report-title" name="title" type="text" maxlength="180" value="{{ old('title') }}" placeholder="Le type choisi sera utilisé si ce champ reste vide">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="period-start">Début de la période</label>
                    <input class="form-control @error('period_start') is-invalid @enderror" id="period-start" name="period_start" type="date" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="period-end">Fin de la période</label>
                    <input class="form-control @error('period_end') is-invalid @enderror" id="period-end" name="period_end" type="date" value="{{ old('period_end', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <p class="form-text mt-3">Pour un rapport journalier, les dates de début et de fin doivent être identiques.</p>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-private-secondary" href="{{ route('reports.index') }}">Annuler</a>
                <button class="btn btn-private-primary" type="submit">Générer et archiver</button>
            </div>
        </form>
    </section>
@endsection
