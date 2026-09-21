@extends('layouts.private')

@section('title', $product->name)
@section('page_title', 'Fiche produit')

@section('content')
    @php
        // Ces états pilotent uniquement les messages de vigilance présentés sur la fiche.
        $isLow = (float) $product->current_stock <= (float) $product->alert_threshold;
        $isExpired = $product->expires_at?->isBefore(today()) ?? false;
        $categoryLabel = \App\Enums\ProductCategory::tryFrom($product->category)?->label() ?? $product->category;
    @endphp

    {{-- EN-TÊTE : regroupe les actions disponibles pour la référence consultée. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="product-title">
        <div>
            <p class="private-kicker">{{ $product->code }}</p>
            <h2 id="product-title">{{ $product->name }}</h2>
            <p>{{ $categoryLabel }} · suivi en {{ $product->unit }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-private-secondary" href="{{ route('stocks.index') }}">Retour aux stocks</a>
            @if (auth()->user()->isFarmManager())
                <a class="btn btn-private-secondary" href="{{ route('stocks.edit', $product) }}">Modifier</a>
                @if ($product->is_active)
                    <a class="btn btn-private-primary" href="{{ route('stocks.movements.create', ['product' => $product->id]) }}">Nouveau mouvement</a>
                @endif
            @endif
        </div>
    </section>

    {{-- ALERTES LOCALES : rendent les anomalies visibles sans remplacer le registre d’alertes global. --}}
    @if ($isExpired)
        <div class="alert alert-danger" role="alert"><strong>Produit expiré.</strong> Isolez cette référence du stock utilisable.</div>
    @elseif ($isLow)
        <div class="alert alert-warning" role="alert"><strong>Seuil atteint.</strong> Un réapprovisionnement doit être planifié.</div>
    @endif

    {{-- INDICATEURS : résument le niveau disponible et les limites définies. --}}
    <dl class="private-summary-strip" aria-label="Indicateurs de l'intrant">
        <div><dt>Disponible</dt><dd>{{ number_format((float) $product->current_stock, 2, ',', ' ') }} {{ $product->unit }}</dd></div>
        <div><dt>Seuil</dt><dd>{{ number_format((float) $product->alert_threshold, 2, ',', ' ') }} {{ $product->unit }}</dd></div>
        <div><dt>Expiration</dt><dd>{{ $product->expires_at?->format('d/m/Y') ?? 'Non applicable' }}</dd></div>
        <div><dt>État</dt><dd>{{ $product->is_active ? 'Actif' : 'Inactif' }}</dd></div>
    </dl>

    {{-- INFORMATIONS : l’activation est réversible et n’efface jamais les mouvements existants. --}}
    <section class="private-panel mb-3" aria-labelledby="product-information-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Paramètres</p><h2 id="product-information-title">Informations du produit</h2></div>
            @if (auth()->user()->isFarmManager())
                <form method="POST" action="{{ route('stocks.toggle-status', $product) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-sm btn-private-secondary" type="submit">{{ $product->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                </form>
            @endif
        </div>
        <div class="p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4 col-lg-3">Référence</dt><dd class="col-sm-8 col-lg-9">{{ $product->code }}</dd>
                <dt class="col-sm-4 col-lg-3">Catégorie</dt><dd class="col-sm-8 col-lg-9">{{ $categoryLabel }}</dd>
                <dt class="col-sm-4 col-lg-3">Unité</dt><dd class="col-sm-8 col-lg-9">{{ $product->unit }}</dd>
                <dt class="col-sm-4 col-lg-3">Création</dt><dd class="col-sm-8 col-lg-9 mb-0">{{ $product->created_at->format('d/m/Y à H:i') }}</dd>
            </dl>
        </div>
    </section>

    {{-- HISTORIQUE : les opérations sont immuables et classées de la plus récente à la plus ancienne. --}}
    <section class="private-panel" aria-labelledby="product-movements-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Traçabilité</p><h2 id="product-movements-title">Historique des mouvements</h2></div>
            <a class="btn btn-sm btn-private-secondary" href="{{ route('stocks.movements.index', ['product_id' => $product->id]) }}">Historique complet</a>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th>Opération</th><th>Quantité</th><th>Justification</th><th>Enregistré par</th></tr></thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php($type = \App\Enums\StockMovementType::tryFrom($movement->type))
                        <tr>
                            <td>{{ $movement->occurred_at->format('d/m/Y à H:i') }}</td>
                            <td>{{ $type?->label() ?? $movement->type }}</td>
                            <td>
                                @if ($movement->type === 'entry')+@elseif ($movement->type === 'exit')−@endif{{ number_format((float) $movement->quantity, 2, ',', ' ') }} {{ $product->unit }}
                                @if ($movement->type === 'adjustment')<small>Stock compté</small>@endif
                            </td>
                            <td>{{ $movement->reason }}</td>
                            <td>{{ $movement->recordedBy?->name ?? 'Compte supprimé' }}</td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="5">Aucun mouvement n’a encore été enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="private-pagination">{{ $movements->links() }}</div>
        @endif
    </section>
@endsection
