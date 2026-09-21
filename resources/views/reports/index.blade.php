@extends('layouts.private')

@section('title', 'Rapports')
@section('page_title', 'Tableaux de bord et rapports')

@section('content')
    {{-- EN-TÊTE : présente la période et offre une impression propre du rapport courant. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="reports-title">
        <div>
            <p class="private-kicker">Analyse consolidée</p>
            <h2 id="reports-title">Rapport de l’exploitation</h2>
            <p>Lecture technique, commerciale et financière construite à partir des opérations enregistrées.</p>
        </div>
        {{-- Ce bouton utilise la fonction d’impression du navigateur, sans transmettre de donnée. --}}
        <button class="btn btn-private-secondary no-print" type="button" data-print-page>Imprimer le rapport</button>
    </section>

    {{-- FILTRE : modifier les deux champs change uniquement la période analysée. --}}
    <form class="private-filter no-print" method="GET" action="{{ route('reports.index') }}">
        <div>
            <label class="form-label" for="date-from">Du</label>
            <input class="form-control" id="date-from" name="date_from" type="date" value="{{ old('date_from', $dateFrom->toDateString()) }}">
        </div>
        <div>
            <label class="form-label" for="date-to">Au</label>
            <input class="form-control" id="date-to" name="date_to" type="date" value="{{ old('date_to', $dateTo->toDateString()) }}">
        </div>
        <button class="btn btn-private-primary" type="submit">Actualiser</button>
    </form>

    {{-- INDICATEURS : chaque montant provient du ReportController et non d’un texte statique. --}}
    <section class="private-stats private-stats--six" aria-label="Synthèse financière">
        <x-private.stat-card label="Chiffre d’affaires" :value="number_format($totalSales, 0, ',', ' ').' FCFA'" detail="Ventes non annulées" />
        <x-private.stat-card label="Espèces encaissées" :value="number_format($cashCollected, 0, ',', ' ').' FCFA'" detail="Paiements de la période" />
        <x-private.stat-card label="Dépenses" :value="number_format($totalExpenses, 0, ',', ' ').' FCFA'" detail="Sorties enregistrées" />
        <x-private.stat-card label="Solde de trésorerie" :value="number_format($netCash, 0, ',', ' ').' FCFA'" detail="Encaissements moins dépenses" :tone="$netCash < 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="Créances" :value="number_format($receivables, 0, ',', ' ').' FCFA'" detail="Reste à recouvrer" :tone="$receivables > 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="Mortalité relevée" :value="number_format($totalMortality, 0, ',', ' ')" :detail="number_format($totalFeed, 0, ',', ' ').' kg d’aliment suivis'" />
    </section>

    {{-- GRAPHIQUES : les canevas restent accompagnés de titres et tableaux accessibles. --}}
    <div class="private-grid private-grid--charts">
        <section class="private-panel private-chart-panel" aria-labelledby="financial-chart-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Évolution</p><h2 id="financial-chart-title">Ventes et dépenses</h2></div>
            </div>
            <div class="private-chart"><canvas id="reportFinancialChart" aria-label="Graphique des ventes et dépenses"></canvas></div>
        </section>

        <section class="private-panel private-chart-panel" aria-labelledby="sales-type-title">
            <div class="private-panel__header">
                <div><p class="private-kicker">Répartition</p><h2 id="sales-type-title">Comptant et crédit</h2></div>
            </div>
            <div class="private-chart"><canvas id="reportSalesTypeChart" aria-label="Répartition des ventes au comptant et à crédit"></canvas></div>
        </section>
    </div>

    {{-- TABLEAU : cette partie constitue le détail imprimable des dernières ventes de la période. --}}
    <section class="private-panel" aria-labelledby="report-sales-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Détail</p><h2 id="report-sales-title">Ventes de la période</h2></div>
            <span>{{ $sales->count() }} opération(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th>Référence</th><th>Client</th><th>Type</th><th class="text-end">Total</th><th class="text-end">Reste</th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td><strong>{{ $sale->reference }}</strong></td>
                            <td>{{ $sale->client?->name ?? 'Client de passage' }}</td>
                            <td>{{ $sale->sale_type === 'credit' ? 'Crédit' : 'Comptant' }}</td>
                            <td class="text-end">{{ number_format((float) $sale->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end">{{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucune vente sur cette période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- CONFIGURATION : ce JSON relie les montants du contrôleur au premier graphique. --}}
    <script type="application/json" data-chart-config data-target="reportFinancialChart">
        {!! Illuminate\Support\Js::encode([
            'type' => 'bar',
            'currency' => true,
            'data' => [
                'labels' => $financialChart['labels'],
                'datasets' => [
                    ['label' => 'Ventes', 'data' => $financialChart['sales'], 'backgroundColor' => '#31380F', 'borderRadius' => 5],
                    ['label' => 'Dépenses', 'data' => $financialChart['expenses'], 'backgroundColor' => '#C7AF97', 'borderRadius' => 5],
                ],
            ],
        ]) !!}
    </script>

    {{-- CONFIGURATION : ce second JSON crée le graphique circulaire comptant/crédit. --}}
    <script type="application/json" data-chart-config data-target="reportSalesTypeChart">
        {!! Illuminate\Support\Js::encode([
            'type' => 'doughnut',
            'currency' => true,
            'data' => [
                'labels' => $salesTypeChart['labels'],
                'datasets' => [['label' => 'Montant', 'data' => $salesTypeChart['values'], 'backgroundColor' => ['#31380F', '#798057'], 'borderWidth' => 0]],
            ],
        ]) !!}
    </script>
@endsection
