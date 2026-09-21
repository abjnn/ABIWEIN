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
        <p class="private-intro__date">{{ now()->translatedFormat('l d F Y') }}</p>
    </section>

    {{-- INDICATEURS : chaque valeur est calculée par DashboardController. --}}
    <section class="private-stats" aria-label="Indicateurs principaux">
        <x-private.stat-card label="Bandes actives" :value="$activeFlocks->count()" detail="Cycles actuellement suivis" />
        <x-private.stat-card label="Effectif actuel" :value="number_format($totalBirds, 0, ',', ' ')" detail="Sujets dans les bandes actives" />
        <x-private.stat-card label="Stocks sous le seuil" :value="$lowStockCount" detail="Produits à contrôler" :tone="$lowStockCount > 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="Créances" :value="number_format($receivables, 0, ',', ' ').' FCFA'" detail="Soldes des ventes non réglées" />
    </section>

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
@endsection
