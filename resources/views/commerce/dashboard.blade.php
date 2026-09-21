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
        {{-- Seul le responsable voit l'action d'écriture ; l'administrateur reste en supervision. --}}
        @if (auth()->user()->isFarmManager())
            <a class="btn btn-private-primary" href="{{ route('sales.create') }}">Enregistrer une vente</a>
        @endif
    </section>

    @include('commerce._navigation')

    {{-- INDICATEURS : la ligne compacte évite quatre grandes cartes sans perdre d'information. --}}
    <dl class="private-summary-strip" aria-label="Indicateurs commerciaux">
        <div><dt>Chiffre d'affaires</dt><dd>{{ number_format($salesTotal, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Espèces encaissées</dt><dd>{{ number_format($cashCollected, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Créances</dt><dd>{{ number_format($receivables, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Solde de trésorerie</dt><dd>{{ number_format($netCash, 0, ',', ' ') }} FCFA</dd></div>
    </dl>

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
