@extends('layouts.private')

@section('title', 'Gestion commerciale')
@section('page_title', 'Gestion commerciale et financière')

@section('content')
    {{-- EN-TÊTE : présente le rôle du module et son action principale. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="commerce-title">
        <div>
            <p class="private-kicker">Pilotage financier</p>
            <h2 id="commerce-title">Ventes et trésorerie</h2>
            <p>Suivez les ventes, les encaissements en espèces, les créances clients et les dépenses de la ferme.</p>
        </div>
        {{-- MODIFICATION : ce bouton conduit au formulaire qui crée une nouvelle vente. --}}
        <a class="btn btn-private-primary" href="{{ route('sales.create') }}">Enregistrer une vente</a>
    </section>

    @include('commerce._navigation')

    {{-- INDICATEURS : chaque montant provient directement des enregistrements financiers. --}}
    <section class="private-stats" aria-label="Indicateurs commerciaux">
        <article class="private-stat"><p>Chiffre d'affaires</p><strong>{{ number_format($salesTotal, 0, ',', ' ') }} FCFA</strong><small>Total des ventes enregistrées</small></article>
        <article class="private-stat"><p>Espèces encaissées</p><strong>{{ number_format($cashCollected, 0, ',', ' ') }} FCFA</strong><small>Paiements reçus</small></article>
        <article class="private-stat private-stat--alert"><p>Créances clients</p><strong>{{ number_format($receivables, 0, ',', ' ') }} FCFA</strong><small>Montant encore attendu</small></article>
        <article class="private-stat"><p>Solde de trésorerie</p><strong>{{ number_format($netCash, 0, ',', ' ') }} FCFA</strong><small>Encaissements moins {{ number_format($expensesTotal, 0, ',', ' ') }} FCFA de dépenses</small></article>
    </section>

    {{-- TABLEAU : donne un accès direct aux dernières opérations commerciales. --}}
    <section class="private-panel" aria-labelledby="recent-sales-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Activité récente</p><h2 id="recent-sales-title">Dernières ventes</h2></div>
            <a class="btn btn-sm btn-private-secondary" href="{{ route('sales.index') }}">Voir le journal</a>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Référence</th><th>Client</th><th>Type</th><th>Total</th><th>Solde</th><th>État</th></tr></thead>
                <tbody>
                    @forelse ($recentSales as $sale)
                        <tr>
                            <td><a href="{{ route('sales.show', $sale) }}"><strong>{{ $sale->reference }}</strong></a><small>{{ $sale->sale_date->format('d/m/Y') }}</small></td>
                            <td>{{ $sale->client?->name ?? 'Client comptoir' }}</td>
                            <td>{{ $sale->sale_type === 'cash' ? 'Comptant' : 'Crédit' }}</td>
                            <td>{{ number_format((float) $sale->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA</td>
                            <td><span class="private-status private-status--{{ $sale->status === 'paid' ? 'good' : 'watch' }}">{{ ['paid' => 'Payée', 'partial' => 'Partielle', 'unpaid' => 'Impayée'][$sale->status] }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucune vente n'a encore été enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
