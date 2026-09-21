@extends('layouts.private')

{{-- Ce titre identifie précisément la fiche dans l’onglet du navigateur. --}}
@section('title', 'Visite du '.$visit->visit_date->format('d/m/Y'))
{{-- Ce titre apparaît dans l’en-tête fixe de l’espace privé. --}}
@section('page_title', 'Fiche sanitaire')

@section('content')
    {{-- EN-TÊTE : rappelle la bande, la date et propose un retour vers l’historique. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="visit-detail-title">
        <div>
            <p class="private-kicker">Diagnostic vétérinaire</p>
            <h2 id="visit-detail-title">Bande {{ $visit->flock->code }}</h2>
            <p>Examen réalisé le {{ $visit->visit_date->translatedFormat('d F Y') }} par {{ $visit->veterinarian->name }}.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('health.visits.index') }}">Retour à l’historique</a>
    </section>

    {{-- RÉSUMÉ : une seule bande horizontale utilise l'espace sans multiplier les rectangles. --}}
    <dl class="private-summary-strip" aria-label="Résumé de la visite">
        <div><dt>Date</dt><dd>{{ $visit->visit_date->format('d/m/Y') }}</dd></div>
        <div><dt>Bande</dt><dd>{{ $visit->flock->code }}</dd></div>
        <div><dt>Bâtiment</dt><dd>{{ $visit->flock->currentBuilding?->code ?? 'Non indiqué' }}</dd></div>
        <div><dt>Prochain contrôle</dt><dd>{{ $visit->next_visit_date?->format('d/m/Y') ?? 'Non programmé' }}</dd></div>
    </dl>

    {{-- CONTENU CLINIQUE : deux panneaux distinguent le constat des actions à mener. --}}
    <div class="private-grid private-grid--main">
        <section class="private-panel" aria-labelledby="diagnosis-title">
            <div class="private-panel__header">
                <div>
                    <p class="private-kicker">Constat clinique</p>
                    <h2 id="diagnosis-title">Diagnostic</h2>
                </div>
            </div>
            {{-- nl2br conserve les retours à la ligne tout en échappant le contenu saisi. --}}
            <div class="p-4 lh-lg">{!! nl2br(e($visit->diagnosis)) !!}</div>
        </section>

        <section class="private-panel" aria-labelledby="recommendations-title">
            <div class="private-panel__header">
                <div>
                    <p class="private-kicker">Plan d’action</p>
                    <h2 id="recommendations-title">Recommandations</h2>
                </div>
            </div>
            {{-- L’état vide évite d’afficher une zone silencieuse lorsqu’aucune consigne n’a été donnée. --}}
            <div class="p-4 lh-lg">
                @if ($visit->recommendations)
                    {!! nl2br(e($visit->recommendations)) !!}

                    {{-- Le responsable confirme l'exécution sans pouvoir modifier la prescription. --}}
                    <div class="private-recommendation-state mt-4">
                        @if ($visit->recommendation_status === 'applied')
                            <span class="private-status private-status--good">Appliquée</span>
                            <small>Validée {{ $visit->recommendation_applied_at?->format('d/m/Y à H:i') }} par {{ $visit->recommendationAppliedBy?->name ?? 'un responsable' }}.</small>
                        @elseif (auth()->user()->isFarmManager())
                            <form method="POST" action="{{ route('health.visits.mark-applied', $visit) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-private-primary" type="submit">Valider comme appliquée</button>
                            </form>
                        @else
                            <span class="private-status private-status--watch">En attente d'application</span>
                        @endif
                    </div>
                @else
                    <p class="mb-0 text-secondary">Aucune recommandation particulière n’a été enregistrée.</p>
                @endif
            </div>
        </section>
    </div>

    {{-- TRAÇABILITÉ : ces données identifient le signataire et le moment d’enregistrement. --}}
    <section class="private-panel" aria-labelledby="traceability-title">
        <div class="private-panel__header">
            <div>
                <p class="private-kicker">Traçabilité</p>
                <h2 id="traceability-title">Informations d’enregistrement</h2>
            </div>
        </div>
        <div class="p-4 row g-4">
            {{-- Cette colonne affiche le professionnel responsable du diagnostic. --}}
            <div class="col-md-6">
                <span class="d-block small text-secondary mb-1">Vétérinaire responsable</span>
                <strong>{{ $visit->veterinarian->name }}</strong>
            </div>
            {{-- Cette colonne affiche l’horodatage technique de création de la fiche. --}}
            <div class="col-md-6">
                <span class="d-block small text-secondary mb-1">Fiche enregistrée le</span>
                <strong>{{ $visit->created_at->format('d/m/Y à H:i') }}</strong>
            </div>
        </div>
    </section>
@endsection
