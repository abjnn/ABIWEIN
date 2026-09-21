@extends('layouts.private')

@section('title', 'Ventes')
@section('page_title', 'Ventes')

@section('content')
    {{-- EN-TÊTE : ouvre le formulaire d'une vente au comptant ou à crédit. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="sales-title">
        <div><p class="private-kicker">Gestion commerciale</p><h2 id="sales-title">Journal des ventes</h2><p>Consultez chaque vente, son mode, les espèces encaissées et son solde restant.</p></div>
        @if (auth()->user()->isFarmManager())
            <a class="btn btn-private-primary" href="{{ route('sales.create') }}">Nouvelle vente</a>
        @endif
    </section>

    @include('commerce._navigation')

    {{-- FILTRES : les trois champs peuvent être combinés et sont conservés dans la pagination. --}}
    <section class="private-form-card mb-4" aria-label="Filtres du journal des ventes">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('sales.index') }}">
            <div class="col-lg-5"><label class="form-label" for="sale-search">Référence ou client</label><input class="form-control" id="sale-search" name="recherche" type="search" value="{{ $search }}" maxlength="150"></div>
            <div class="col-sm-4 col-lg-2"><label class="form-label" for="sale-type-filter">Type</label><select class="form-select" id="sale-type-filter" name="type"><option value="">Tous</option><option value="cash" @selected($type === 'cash')>Comptant</option><option value="credit" @selected($type === 'credit')>Crédit</option></select></div>
            <div class="col-sm-4 col-lg-2"><label class="form-label" for="sale-status-filter">Statut</label><select class="form-select" id="sale-status-filter" name="statut"><option value="">Tous</option><option value="paid" @selected($status === 'paid')>Payée</option><option value="partial" @selected($status === 'partial')>Partielle</option><option value="unpaid" @selected($status === 'unpaid')>Impayée</option></select></div>
            <div class="col-sm-4 col-lg-3 d-flex gap-2"><button class="btn btn-private-primary" type="submit">Filtrer</button><a class="btn btn-private-secondary" href="{{ route('sales.index') }}">Effacer</a></div>
        </form>
    </section>

    {{-- TABLEAU : chaque référence ouvre la fiche complète et l'historique des paiements. --}}
    <section class="private-panel" aria-labelledby="sales-list-title">
        <div class="private-panel__header"><div><p class="private-kicker">Registre</p><h2 id="sales-list-title">Ventes enregistrées</h2></div><span>{{ $sales->total() }} vente(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Référence</th><th>Client</th><th>Type</th><th>Total</th><th>Encaissé</th><th>Solde</th><th>État</th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td><a href="{{ route('sales.show', $sale) }}"><strong>{{ $sale->reference }}</strong></a><small>{{ $sale->sale_date->format('d/m/Y') }}</small></td>
                            <td>{{ $sale->client?->name ?? 'Client comptoir' }}</td>
                            <td>{{ $sale->sale_type === 'cash' ? 'Comptant' : 'Crédit' }}</td>
                            <td>{{ number_format((float) $sale->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format((float) $sale->paid_amount, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA</td>
                            <td><span class="private-status private-status--{{ $sale->status === 'paid' ? 'good' : 'watch' }}">{{ ['paid' => 'Payée', 'partial' => 'Partielle', 'unpaid' => 'Impayée'][$sale->status] }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="7">Aucune vente ne correspond à ces filtres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())<div class="private-pagination">{{ $sales->links() }}</div>@endif
    </section>
@endsection
