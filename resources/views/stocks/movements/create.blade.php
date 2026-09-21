@extends('layouts.private')

@section('title', 'Nouveau mouvement')
@section('page_title', 'Nouveau mouvement')

@section('content')
    {{-- EN-TÊTE : explique la portée comptable de l’opération avant sa saisie. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-movement-title">
        <div>
            <p class="private-kicker">Gestion des stocks</p>
            <h2 id="create-movement-title">Enregistrer une opération</h2>
            <p>Chaque mouvement met immédiatement le stock à jour et reste visible dans l’historique.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('stocks.movements.index') }}">Retour à l’historique</a>
    </section>

    <section class="private-form-card" aria-label="Enregistrement d’un mouvement de stock">
        {{-- Ce message complète les erreurs détaillées sous chaque champ. --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Le mouvement n’a pas été enregistré. Vérifiez les informations indiquées.</div>
        @endif

        {{-- FORMULAIRE MOUVEMENT : toutes les modifications de quantité passent par ce point contrôlé. --}}
        <form method="POST" action="{{ route('stocks.movements.store') }}">
            @csrf
            <div class="row g-4">
                {{-- Seuls les produits actifs sont proposés afin d’éviter une opération accidentelle. --}}
                <div class="col-12">
                    <label class="form-label" for="product_id">Produit concerné</label>
                    <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                        <option value="">Sélectionner un produit</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id', $selectedProductId) === (string) $product->id)>
                                {{ $product->code }} — {{ $product->name }} ({{ number_format((float) $product->current_stock, 2, ',', ' ') }} {{ $product->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Le type détermine si la quantité est ajoutée, retirée ou remplacée. --}}
                <div class="col-md-6">
                    <label class="form-label" for="type">Type d’opération</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">Sélectionner une opération</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">L’ajustement remplace le stock par la quantité réellement comptée.</div>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- La précision de deux décimales correspond au format conservé en base. --}}
                <div class="col-md-6">
                    <label class="form-label" for="quantity">Quantité</label>
                    <input class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('quantity') }}" required>
                    <div class="form-text">Pour une sortie, la quantité ne peut pas dépasser le disponible.</div>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Cette date permet de saisir une opération passée mais interdit une date future. --}}
                <div class="col-md-6">
                    <label class="form-label" for="occurred_at">Date et heure de l’opération</label>
                    <input class="form-control @error('occurred_at') is-invalid @enderror" id="occurred_at" name="occurred_at" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('occurred_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- La justification rend le journal compréhensible lors d’un contrôle ultérieur. --}}
                <div class="col-12">
                    <label class="form-label" for="reason">Justification</label>
                    <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3" maxlength="255" placeholder="Exemple : réception du fournisseur ou consommation du bâtiment A" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- L’enregistrement est la seule action qui modifie réellement le niveau de stock. --}}
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-private-primary" type="submit">Enregistrer le mouvement</button>
                    <a class="btn btn-private-secondary" href="{{ route('stocks.index') }}">Annuler</a>
                </div>
            </div>
        </form>
    </section>
@endsection
