@extends('layouts.private')

@section('title', 'Gestion des stocks')
@section('page_title', 'Stocks')

@section('content')
    {{-- EN-TÊTE : présente le module et ses trois actions principales. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="stocks-title">
        <div>
            <p class="private-kicker">Gestion des stocks</p>
            <h2 id="stocks-title">Intrants et disponibilités</h2>
            <p>Aliments, vitamines, produits sanitaires, emballages et matériel utilisés par la ferme. Aucun article de ce registre n'est vendu.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-private-secondary" href="{{ route('stocks.movements.index') }}">Consulter l’historique</a>
            @if (auth()->user()->isFarmManager())
                <a class="btn btn-private-secondary" href="{{ route('stocks.movements.create') }}">Enregistrer un mouvement</a>
                <a class="btn btn-private-primary" href="{{ route('stocks.create') }}">Ajouter un intrant</a>
            @endif
        </div>
    </section>

    {{-- INDICATEURS : les valeurs globales restent visibles même lorsqu’un filtre est actif. --}}
    <dl class="private-summary-strip" aria-label="Indicateurs des stocks">
        <div><dt>Intrants actifs</dt><dd>{{ $statistics['active'] }}</dd></div>
        <div><dt>Seuil atteint</dt><dd>{{ $statistics['low'] }}</dd></div>
    </dl>

    {{-- FILTRES : cette requête GET peut être partagée ou actualisée sans modifier les données. --}}
    <section class="private-form-card mb-3" aria-labelledby="stock-filters-title">
        <div class="mb-3">
            <p class="private-kicker">Recherche</p>
            <h2 class="h5 mb-0" id="stock-filters-title">Filtrer les produits</h2>
        </div>
        <form method="GET" action="{{ route('stocks.index') }}">
            <div class="row g-3 align-items-end">
                {{-- Ce champ recherche simultanément la référence et le nom du produit. --}}
                <div class="col-lg-5">
                    <label class="form-label" for="q">Référence ou nom</label>
                    <input class="form-control" id="q" name="q" type="search" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="Exemple : ALIM-CROISS">
                </div>

                {{-- Ce sélecteur est alimenté par l’énumération ProductCategory. --}}
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="category">Catégorie</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Toutes les catégories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Le filtre d’état permet de cibler immédiatement les anomalies. --}}
                <div class="col-md-6 col-lg-2">
                    <label class="form-label" for="status">État</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" @selected(($filters['status'] ?? 'active') === 'all')>Tous</option>
                        <option value="active" @selected(($filters['status'] ?? 'active') === 'active')>Actifs</option>
                        <option value="low" @selected(($filters['status'] ?? '') === 'low')>Seuil atteint</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactifs</option>
                    </select>
                </div>

                {{-- Les deux boutons appliquent ou annulent les critères sans effet sur la base. --}}
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-private-primary flex-grow-1" type="submit">Filtrer</button>
                    <a class="btn btn-private-secondary" href="{{ route('stocks.index') }}" aria-label="Effacer les filtres">Effacer</a>
                </div>
            </div>
        </form>
    </section>

    {{-- TABLEAU : affiche les quantités et signale visuellement les seuils et expirations. --}}
    <section class="private-panel" aria-labelledby="products-table-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Catalogue</p><h2 id="products-table-title">Produits suivis</h2></div>
            <span>{{ $products->total() }} résultat(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead>
                    <tr><th>Produit</th><th>Catégorie</th><th>Disponible</th><th>Seuil</th><th>Expiration</th><th>État</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            // Ces valeurs servent uniquement à choisir le libellé d’état de la ligne.
                            $isExpired = $product->expires_at?->isBefore(today()) ?? false;
                            $isLow = (float) $product->current_stock <= (float) $product->alert_threshold;
                            $categoryLabel = \App\Enums\ProductCategory::tryFrom($product->category)?->label() ?? $product->category;
                        @endphp
                        <tr>
                            <td><strong>{{ $product->name }}</strong><small>{{ $product->code }}</small></td>
                            <td>{{ $categoryLabel }}</td>
                            <td><strong>{{ number_format((float) $product->current_stock, 2, ',', ' ') }}</strong><small>{{ $product->unit }}</small></td>
                            <td>{{ number_format((float) $product->alert_threshold, 2, ',', ' ') }} {{ $product->unit }}</td>
                            <td>{{ $product->expirationTimeLabel() }}</td>
                            <td>
                                @if (! $product->is_active)
                                    <span class="private-status private-status--blocked">Inactif</span>
                                @elseif ($isExpired)
                                    <span class="private-status private-status--watch">Expiré</span>
                                @elseif ($isLow)
                                    <span class="private-status private-status--watch">Seuil atteint</span>
                                @else
                                    <span class="private-status private-status--good">Disponible</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                    <a class="btn btn-sm btn-private-secondary" href="{{ route('stocks.show', $product) }}">Consulter</a>
                                    @if (auth()->user()->isFarmManager())
                                        <a class="btn btn-sm btn-private-secondary" href="{{ route('stocks.edit', $product) }}">Modifier</a>
                                        @if ($product->is_active)
                                            <a class="btn btn-sm btn-private-primary" href="{{ route('stocks.movements.create', ['product' => $product->id]) }}">Mouvement</a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="7">Aucun produit ne correspond aux critères sélectionnés.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- La pagination conserve automatiquement les filtres de la recherche en cours. --}}
        @if ($products->hasPages())
            <div class="private-pagination">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
