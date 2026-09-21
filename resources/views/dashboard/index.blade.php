@extends('layouts.private')

@section('title', 'Vue d’ensemble')
@section('page_title', 'Vue d’ensemble')

@section('content')
    {{-- INTRODUCTION : le nom provient du compte connecté. --}}
    <section class="private-intro" aria-labelledby="dashboard-welcome">
        <div>
            <p class="private-kicker">Pilotage de l’exploitation</p>
            <h2 id="dashboard-welcome">Bonjour, {{ auth()->user()->name }}.</h2>
            <p>Voici l’état synthétique des données actuellement disponibles dans ABIWEIN.</p>
        </div>
        {{-- La date suit la locale française configurée dans Laravel. --}}
        <div class="private-intro__aside">
            <p class="private-intro__date">{{ now()->translatedFormat('l d F Y') }}</p>
            {{-- Le rapport détaillé reste accessible sans encombrer le premier écran. --}}
            @if ($canViewFinancials)
                <a class="btn btn-private-primary" href="{{ route('reports.index') }}">Voir les rapports</a>
            @endif
        </div>
    </section>

    {{-- INDICATEURS : chaque valeur est calculée par DashboardController. --}}
    <section class="private-stats private-stats--six" aria-label="Indicateurs principaux">
        <x-private.stat-card label="Bandes actives" :value="$activeFlocks->count()" detail="Cycles actuellement suivis" />
        <x-private.stat-card label="Effectif actuel" :value="number_format($totalBirds, 0, ',', ' ')" detail="Sujets dans les bandes actives" />
        @if ($canViewFinancials)
            <x-private.stat-card label="Stocks sous le seuil" :value="$lowStockCount" detail="Produits à contrôler" :tone="$lowStockCount > 0 ? 'alert' : 'default'" />
            <x-private.stat-card label="Créances" :value="number_format($receivables, 0, ',', ' ').' FCFA'" detail="Soldes des ventes non réglées" />
            <x-private.stat-card label="Trésorerie du mois" :value="number_format($netCashThisMonth, 0, ',', ' ').' FCFA'" detail="Encaissements moins dépenses" :tone="$netCashThisMonth < 0 ? 'alert' : 'default'" />
        @else
            <x-private.stat-card label="Alertes ouvertes" :value="$openAlertsCount" detail="Situations à examiner" :tone="$openAlertsCount > 0 ? 'alert' : 'default'" />
        @endif
        <x-private.stat-card label="Visites à venir" :value="$upcomingVisitsCount" detail="Suivis sanitaires programmés" />
    </section>

    {{-- ACCÈS RAPIDES : chaque carte mène à une fonction réellement disponible. --}}
    <nav class="private-quick-actions" aria-label="Actions rapides">
        <a href="{{ route('health.visits.index') }}"><span>Sanitaire</span><strong>Suivre les visites vétérinaires</strong></a>
        @if ($canViewFinancials)
            <a href="{{ route('stocks.index') }}"><span>Stocks</span><strong>Consulter les produits et mouvements</strong></a>
            <a href="{{ auth()->user()->isFarmManager() ? route('sales.create') : route('sales.index') }}"><span>Ventes</span><strong>{{ auth()->user()->isFarmManager() ? 'Enregistrer une nouvelle opération' : 'Consulter les opérations enregistrées' }}</strong></a>
            <a href="{{ route('reports.index') }}"><span>Rapports</span><strong>Analyser la période et imprimer</strong></a>
        @endif
    </nav>

    {{-- GRAPHIQUE FINANCIER : ventes et dépenses proviennent des six derniers mois. --}}
    @if ($canViewFinancials)
        <section class="private-panel private-chart-panel" aria-labelledby="dashboard-chart-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Tendance sur six mois</p><h2 id="dashboard-chart-title">Activité financière</h2></div>
                <span>{{ number_format($cashThisMonth, 0, ',', ' ') }} FCFA encaissés ce mois</span>
            </div>
            <div class="private-chart private-chart--wide"><canvas id="dashboardFinancialChart" aria-label="Évolution mensuelle des ventes et dépenses"></canvas></div>
        </section>
    @endif

    <div class="private-grid private-grid--main">
        {{-- BANDES : aperçu des cycles actifs et de leur bâtiment actuel. --}}
        <section class="private-panel" aria-labelledby="active-flocks-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Exploitation</p><h2 id="active-flocks-title">Bandes actives</h2></div>
                <span>{{ $activeFlocks->count() }} au total</span>
            </div>
            <div class="table-responsive">
                <table class="table private-table align-middle">
                    <thead><tr><th>Référence</th><th>Bâtiment</th><th>Arrivée</th><th class="text-end">Effectif</th></tr></thead>
                    <tbody>
                        @forelse ($activeFlocks as $flock)
                            <tr>
                                <td><strong>{{ $flock->code }}</strong><small>{{ $flock->breed }}</small></td>
                                <td>{{ $flock->currentBuilding?->name ?? 'Non affecté' }}</td>
                                <td>{{ $flock->arrival_date->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($flock->current_quantity, 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="private-empty">Aucune bande active.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ALERTES : aucune action n’est simulée avant le développement du module dédié. --}}
        <section class="private-panel" aria-labelledby="alerts-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Surveillance</p><h2 id="alerts-title">Alertes ouvertes</h2></div>
                <span>{{ $openAlertsCount }}</span>
            </div>
            <div class="private-alert-list">
                @forelse ($openAlerts as $alert)
                    <article>
                        {{-- Le texte complète la couleur pour que la gravité reste accessible. --}}
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

    {{-- RELEVÉS : tableau des dernières mesures quotidiennes enregistrées. --}}
    <section class="private-panel" aria-labelledby="records-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Suivi quotidien</p><h2 id="records-title">Derniers relevés</h2></div>
            <span>Données de démonstration</span>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th>Bande</th><th class="text-end">Mortalité</th><th class="text-end">Aliment</th><th class="text-end">Eau</th><th class="text-end">Poids moyen</th><th>État</th></tr></thead>
                <tbody>
                    @forelse ($recentRecords as $record)
                        <tr>
                            <td>{{ $record->record_date->format('d/m/Y') }}</td>
                            <td><strong>{{ $record->flock->code }}</strong></td>
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

    {{-- CONFIGURATION : ce bloc transmet uniquement les séries autorisées au module Chart.js. --}}
    @if ($canViewFinancials)
        <script type="application/json" data-chart-config data-target="dashboardFinancialChart">
            {!! Illuminate\Support\Js::encode([
                'type' => 'bar',
                'currency' => true,
                'data' => [
                    'labels' => $financialTrend['labels'],
                    'datasets' => [
                        ['label' => 'Ventes', 'data' => $financialTrend['sales'], 'backgroundColor' => '#31380F', 'borderRadius' => 5],
                        ['label' => 'Dépenses', 'data' => $financialTrend['expenses'], 'backgroundColor' => '#C7AF97', 'borderRadius' => 5],
                    ],
                ],
            ]) !!}
        </script>
    @endif
@endsection
