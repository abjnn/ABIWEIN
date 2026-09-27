@extends('layouts.private')

@section('title', 'Vue d’ensemble')
@section('page_title', 'Vue d’ensemble')

@section('content')
    @php
        // Ces contrôles empêchent un appel à route() tant que le module Bandes n'est pas encore chargé.
        $hasFlocksIndexRoute = \Illuminate\Support\Facades\Route::has('flocks.index');
        $hasFlockShowRoute = \Illuminate\Support\Facades\Route::has('flocks.show');
        $dailyRecordRoute = \Illuminate\Support\Facades\Route::has('daily-records.create')
            ? 'daily-records.create'
            : (\Illuminate\Support\Facades\Route::has('flocks.daily-records.create') ? 'flocks.daily-records.create' : null);
    @endphp

    <div class="dashboard-page">
        {{-- EN-TÊTE : une seule ligne compacte remplace l'ancien grand bloc d'introduction. --}}
        <section class="dashboard-heading" aria-labelledby="dashboard-welcome">
            <div>
                <p class="private-kicker">Pilotage de l’exploitation</p>
                <h2 id="dashboard-welcome">Bonjour, {{ auth()->user()->name }}.</h2>
                <p>{{ auth()->user()->role->label() }} · {{ now()->translatedFormat('l d F Y') }}</p>
            </div>
            <div class="dashboard-heading__actions">
                @if ($hasFlocksIndexRoute)
                    <a class="btn btn-private-secondary" href="{{ route('flocks.index') }}">Consulter les bandes</a>
                @endif
                @if ($canViewFinancials)
                    <a class="btn btn-private-primary" href="{{ route('reports.index') }}">Voir les rapports</a>
                @endif
            </div>
        </section>

        {{-- INDICATEURS : quatre cartes courtes reprennent la hiérarchie de la maquette fournie. --}}
        <dl class="private-metrics-list" aria-label="Indicateurs principaux">
            <x-private.metric-row label="Bandes actives" :value="$activeFlocks->count()" detail="Cycles suivis actuellement" />
            <x-private.metric-row label="Effectif actuel" :value="number_format($totalBirds, 0, ',', ' ')" detail="Poulets présents dans les bandes" />
            @if ($canViewFinancials)
                <x-private.metric-row label="Stocks à contrôler" :value="$lowStockCount" detail="Intrants au niveau du seuil" :tone="$lowStockCount > 0 ? 'alert' : 'default'" />
                <x-private.metric-row label="Trésorerie du mois" :value="number_format($netCashThisMonth, 0, ',', ' ').' FCFA'" detail="Encaissements moins dépenses" :tone="$netCashThisMonth < 0 ? 'alert' : 'default'" />
            @else
                <x-private.metric-row label="Visites à venir" :value="$upcomingVisitsCount" detail="Contrôles sanitaires programmés" />
                <x-private.metric-row label="Alertes ouvertes" :value="$openAlertsCount" detail="Situations techniques à examiner" :tone="$openAlertsCount > 0 ? 'alert' : 'default'" />
            @endif
        </dl>

        {{-- VUE PRINCIPALE : le graphique occupe l'espace utile et la surveillance reste visible à droite. --}}
        <div class="dashboard-overview-grid">
            <section class="private-panel private-chart-panel" aria-labelledby="dashboard-chart-title">
                <div class="private-panel__header">
                    <div>
                        <p class="private-kicker">{{ $canViewFinancials ? 'Six derniers mois' : 'Sept derniers jours' }}</p>
                        <h2 id="dashboard-chart-title">{{ $canViewFinancials ? 'Activité financière' : 'Mortalité quotidienne' }}</h2>
                    </div>
                    <span>{{ $canViewFinancials ? number_format($cashThisMonth, 0, ',', ' ').' FCFA encaissés' : 'Toutes les bandes actives' }}</span>
                </div>
                <div class="private-chart private-chart--wide">
                    <canvas id="dashboardMainChart" aria-label="{{ $canViewFinancials ? 'Évolution mensuelle des ventes et dépenses' : 'Évolution de la mortalité quotidienne' }}"></canvas>
                </div>
                @if ($canViewFinancials)
                    <dl class="dashboard-chart-summary" aria-label="Résumé financier du mois">
                        <div><dt>Encaissements</dt><dd>{{ number_format($cashThisMonth, 0, ',', ' ') }} FCFA</dd></div>
                        <div><dt>Dépenses</dt><dd>{{ number_format($expensesThisMonth, 0, ',', ' ') }} FCFA</dd></div>
                        <div><dt>Créances</dt><dd>{{ number_format($receivables, 0, ',', ' ') }} FCFA</dd></div>
                    </dl>
                @endif
            </section>

            {{-- SURVEILLANCE : une liste simple évite les grandes cartes imbriquées. --}}
            <section class="private-panel dashboard-monitoring" aria-labelledby="alerts-title">
                <div class="private-panel__header">
                    <div><p class="private-kicker">Surveillance</p><h2 id="alerts-title">Alertes ouvertes</h2></div>
                    <span>{{ $openAlertsCount }}</span>
                </div>
                <div class="private-alert-list">
                    @forelse ($openAlerts as $alert)
                        <article>
                            <span class="private-alert-list__severity private-alert-list__severity--{{ $alert->severity }}">{{ $alert->severity === 'warning' ? 'Attention' : ($alert->severity === 'critical' ? 'Critique' : 'Information') }}</span>
                            <h3>{{ $alert->title }}</h3>
                            <p>{{ $alert->message }}</p>
                        </article>
                    @empty
                        <p class="private-empty">Aucune alerte ouverte.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- BANDES : aperçu compact des cycles actifs avec des liens seulement si les routes existent. --}}
        <section class="private-panel dashboard-table-panel" aria-labelledby="active-flocks-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Exploitation</p><h2 id="active-flocks-title">Bandes actives</h2></div>
                @if ($hasFlocksIndexRoute)
                    <a class="dashboard-panel-link" href="{{ route('flocks.index') }}">Afficher toutes les bandes</a>
                @else
                    <span>{{ $activeFlocks->count() }} au total</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table private-table align-middle">
                    <thead><tr><th>Référence</th><th>Bâtiment</th><th>Arrivée</th><th class="text-end">Effectif</th>@if (auth()->user()->isFarmManager() && $dailyRecordRoute)<th class="text-end">Action</th>@endif</tr></thead>
                    <tbody>
                        @forelse ($activeFlocks as $flock)
                            <tr>
                                <td>
                                    @if ($hasFlockShowRoute)
                                        <a class="dashboard-table-link" href="{{ route('flocks.show', $flock) }}">{{ $flock->code }}</a>
                                    @else
                                        <strong>{{ $flock->code }}</strong>
                                    @endif
                                    <small>{{ $flock->breed }}</small>
                                </td>
                                <td>{{ $flock->currentBuilding?->name ?? 'Non affecté' }}</td>
                                <td>{{ $flock->arrival_date->format('d/m/Y') }}</td>
                                <td class="text-end"><strong>{{ number_format($flock->current_quantity, 0, ',', ' ') }}</strong></td>
                                @if (auth()->user()->isFarmManager() && $dailyRecordRoute)
                                    <td class="text-end"><a class="dashboard-table-action" href="{{ route($dailyRecordRoute, ['flock' => $flock]) }}">Saisir le relevé</a></td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()->isFarmManager() && $dailyRecordRoute ? 5 : 4 }}" class="private-empty">Aucune bande active.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- RELEVÉS : les dernières mesures restent consultables dans un tableau dense et responsive. --}}
        <section class="private-panel dashboard-table-panel" aria-labelledby="records-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Suivi quotidien</p><h2 id="records-title">Derniers relevés</h2></div>
                <span>{{ $recentRecords->count() }} affichés</span>
            </div>
            <div class="table-responsive">
                <table class="table private-table align-middle">
                    <thead><tr><th>Date</th><th>Bande</th><th class="text-end">Mortalité</th><th class="text-end">Aliment</th><th class="text-end">Eau</th><th class="text-end">Poids moyen</th><th>État</th></tr></thead>
                    <tbody>
                        @forelse ($recentRecords as $record)
                            <tr>
                                <td>{{ $record->record_date->format('d/m/Y') }}</td>
                                <td>
                                    @if ($hasFlockShowRoute)
                                        <a class="dashboard-table-link" href="{{ route('flocks.show', $record->flock) }}">{{ $record->flock->code }}</a>
                                    @else
                                        <strong>{{ $record->flock->code }}</strong>
                                    @endif
                                </td>
                                <td class="text-end">{{ $record->mortality }}</td>
                                <td class="text-end">{{ number_format((float) $record->feed_kg, 2, ',', ' ') }} kg</td>
                                <td class="text-end">{{ number_format((float) $record->water_liters, 2, ',', ' ') }} L</td>
                                <td class="text-end">{{ number_format((float) $record->average_weight_kg, 3, ',', ' ') }} kg</td>
                                <td><span class="private-status private-status--{{ $record->health_status }}">{{ $record->health_status === 'good' ? 'Bon' : 'À surveiller' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="private-empty">Aucun relevé quotidien disponible.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- CONFIGURATION : seules les données autorisées pour le rôle connecté sont transmises à Chart.js. --}}
        <script type="application/json" data-chart-config data-target="dashboardMainChart">
            {!! Illuminate\Support\Js::encode($canViewFinancials ? [
                'type' => 'bar',
                'currency' => true,
                'data' => [
                    'labels' => $financialTrend['labels'],
                    'datasets' => [
                        ['label' => 'Ventes', 'data' => $financialTrend['sales'], 'backgroundColor' => '#234B35', 'borderRadius' => 4, 'maxBarThickness' => 34],
                        ['label' => 'Dépenses', 'data' => $financialTrend['expenses'], 'backgroundColor' => '#C99A2E', 'borderRadius' => 4, 'maxBarThickness' => 34],
                    ],
                ],
            ] : [
                'type' => 'bar',
                'currency' => false,
                'data' => [
                    'labels' => $technicalTrend['labels'],
                    'datasets' => [
                        ['label' => 'Mortalité', 'data' => $technicalTrend['mortality'], 'backgroundColor' => '#234B35', 'borderRadius' => 4, 'maxBarThickness' => 34],
                    ],
                ],
            ]) !!}
        </script>
    </div>
@endsection
