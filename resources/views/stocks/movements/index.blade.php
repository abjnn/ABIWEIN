@extends('layouts.private')

@section('title', 'Historique des stocks')
@section('page_title', 'Mouvements de stock')

@section('content')
    {{-- EN-TÊTE : distingue le journal immuable de la liste courante des produits. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="movements-title">
        <div>
            <p class="private-kicker">Traçabilité</p>
            <h2 id="movements-title">Historique des mouvements</h2>
            <p>Retrouvez l’auteur, la date et la justification de chaque entrée, sortie ou ajustement d’inventaire.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-private-secondary" href="{{ route('stocks.index') }}">Retour aux stocks</a>
            @if (auth()->user()->isFarmManager())
                <a class="btn btn-private-primary" href="{{ route('stocks.movements.create') }}">Nouveau mouvement</a>
            @endif
        </div>
    </section>

    {{-- FILTRES : ils réduisent l’historique affiché sans modifier aucun mouvement. --}}
    <section class="private-form-card mb-3" aria-labelledby="movement-filters-title">
        <div class="mb-3">
            <p class="private-kicker">Recherche</p>
            <h2 class="h5 mb-0" id="movement-filters-title">Filtrer le journal</h2>
        </div>
        <form method="GET" action="{{ route('stocks.movements.index') }}">
            <div class="row g-3 align-items-end">
                {{-- Le produit limite le journal à une seule référence. --}}
                <div class="col-lg-4">
                    <label class="form-label" for="product_id">Produit</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">Tous les produits</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>{{ $product->code }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Le type distingue les entrées, sorties et inventaires. --}}
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="type">Opération</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Toutes</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Les bornes sont inclusives et peuvent être utilisées séparément. --}}
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="date_from">Du</label>
                    <input class="form-control" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="date_to">Au</label>
                    <input class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
                    @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Les actions appliquent ou effacent la recherche en cours. --}}
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-private-primary flex-grow-1" type="submit">Filtrer</button>
                    <a class="btn btn-private-secondary" href="{{ route('stocks.movements.index') }}">Effacer</a>
                </div>
            </div>
        </form>
    </section>

    {{-- JOURNAL : aucune action de suppression n’est proposée afin de préserver la traçabilité. --}}
    <section class="private-panel" aria-labelledby="movement-table-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Registre</p><h2 id="movement-table-title">Opérations enregistrées</h2></div>
            <span>{{ $movements->total() }} mouvement(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Date</th><th>Produit</th><th>Opération</th><th>Quantité</th><th>Justification</th><th>Enregistré par</th></tr></thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php($movementType = \App\Enums\StockMovementType::tryFrom($movement->type))
                        <tr>
                            <td>{{ $movement->occurred_at->format('d/m/Y à H:i') }}</td>
                            <td><strong>{{ $movement->product->name }}</strong><small>{{ $movement->product->code }}</small></td>
                            <td><span class="private-status private-status--{{ $movement->type === 'entry' ? 'good' : 'watch' }}">{{ $movementType?->label() ?? $movement->type }}</span></td>
                            <td>
                                @if ($movement->type === 'entry')+@elseif ($movement->type === 'exit')−@endif{{ number_format((float) $movement->quantity, 2, ',', ' ') }} {{ $movement->product->unit }}
                                @if ($movement->type === 'adjustment')<small>Stock compté après inventaire</small>@endif
                            </td>
                            <td>{{ $movement->reason }}</td>
                            <td>{{ $movement->recordedBy?->name ?? 'Compte supprimé' }}</td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucun mouvement ne correspond aux critères sélectionnés.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="private-pagination">{{ $movements->links() }}</div>
        @endif
    </section>
@endsection
