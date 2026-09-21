@extends('layouts.private')

@section('title', 'Créances clients')
@section('page_title', 'Créances clients')

@section('content')
    {{-- EN-TÊTE : affiche le total encore attendu et explique le contenu de la page. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="receivables-title">
        <div><p class="private-kicker">Suivi des crédits</p><h2 id="receivables-title">{{ number_format((float) $totalDue, 0, ',', ' ') }} FCFA à recouvrer</h2><p>Les ventes sont classées des plus anciennes aux plus récentes pour faciliter les relances.</p></div>
        <a class="btn btn-private-primary" href="{{ route('sales.index', ['statut' => 'partial']) }}">Voir les ventes</a>
    </section>

    @include('commerce._navigation')

    {{-- RECHERCHE : ce champ filtre une référence de vente ou le nom d'un client débiteur. --}}
    <section class="private-form-card mb-4" aria-label="Recherche dans les créances">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('receivables.index') }}">
            <div class="col-md-9"><label class="form-label" for="receivable-search">Référence ou client</label><input class="form-control" id="receivable-search" name="recherche" type="search" value="{{ $search }}" maxlength="150"></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-private-primary" type="submit">Rechercher</button><a class="btn btn-private-secondary" href="{{ route('receivables.index') }}">Effacer</a></div>
        </form>
    </section>

    {{-- TABLEAU : le bouton Encaisser ouvre la vente et son formulaire de versement en espèces. --}}
    <section class="private-panel" aria-labelledby="receivables-list-title">
        <div class="private-panel__header"><div><p class="private-kicker">Soldes ouverts</p><h2 id="receivables-list-title">Ventes non soldées</h2></div><span>{{ $receivables->total() }} dossier(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Vente</th><th>Client</th><th>Total</th><th>Déjà versé</th><th>Reste dû</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse ($receivables as $sale)
                        <tr>
                            <td><strong>{{ $sale->reference }}</strong><small>{{ $sale->sale_date->format('d/m/Y') }}</small></td>
                            <td>{{ $sale->client?->name ?? 'Client non renseigné' }}</td>
                            <td>{{ number_format((float) $sale->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format((float) $sale->paid_amount, 0, ',', ' ') }} FCFA</td>
                            <td><strong>{{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA</strong></td>
                            <td class="text-end"><a class="btn btn-sm btn-private-primary" href="{{ route('sales.show', $sale) }}">Encaisser</a></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucune créance ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($receivables->hasPages())<div class="private-pagination">{{ $receivables->links() }}</div>@endif
    </section>
@endsection
