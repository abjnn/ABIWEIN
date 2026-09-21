@extends('layouts.private')

@section('title', 'Nouvelle vente')
@section('page_title', 'Nouvelle vente')

@section('content')
    {{-- EN-TÊTE : rappelle que tous les encaissements ABIWEIN sont effectués en espèces. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-sale-title"><div><p class="private-kicker">Gestion commerciale</p><h2 id="create-sale-title">Enregistrer une vente</h2><p>Choisissez comptant ou crédit. Le mode de paiement reste exclusivement en espèces.</p></div><a class="btn btn-private-secondary" href="{{ route('sales.index') }}">Retour aux ventes</a></section>

    @include('commerce._navigation')

    <section class="private-form-card mw-100" aria-label="Formulaire d'enregistrement d'une vente">
        {{-- Ce résumé invite à consulter les erreurs placées sous les champs. --}}
        @if ($errors->any())<div class="alert alert-danger" role="alert">La vente n'a pas été enregistrée. Corrigez les informations signalées.</div>@endif

        {{-- Tout le formulaire de vente commence ici ; le jeton CSRF protège l'écriture. --}}
        <form method="POST" action="{{ route('sales.store') }}" id="sale-form">
            @csrf

            <div class="row g-4 mb-4">
                {{-- La date autorise une saisie antérieure mais jamais une date future. --}}
                <div class="col-md-4"><label class="form-label" for="sale_date">Date de vente</label><input class="form-control @error('sale_date') is-invalid @enderror" id="sale_date" name="sale_date" type="date" value="{{ old('sale_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>@error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                {{-- Comptant solde automatiquement la vente ; crédit autorise des paiements partiels. --}}
                <div class="col-md-4"><label class="form-label" for="sale_type">Type de vente</label><select class="form-select @error('sale_type') is-invalid @enderror" id="sale_type" name="sale_type" required><option value="cash" @selected(old('sale_type', 'cash') === 'cash')>Au comptant</option><option value="credit" @selected(old('sale_type') === 'credit')>À crédit</option></select>@error('sale_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                {{-- Le client est facultatif au comptant et obligatoire pour une créance. --}}
                <div class="col-md-4"><label class="form-label" for="client_id">Client</label><select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id"><option value="">Client comptoir</option>@foreach ($clients as $client)<option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select>@error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>

            {{-- LIGNES : chaque bloc décrit un produit, sa quantité et le prix réellement appliqué. --}}
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3"><div><p class="private-kicker mb-1">Détail</p><h3 class="h5 mb-0">Produits vendus</h3></div><button class="btn btn-private-secondary" id="add-sale-line" type="button">Ajouter une ligne</button></div>

            {{-- Les anciennes valeurs sont réaffichées lorsque la validation refuse le formulaire. --}}
            @php($oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]))
            <div class="vstack gap-3" id="sale-lines" data-next-index="{{ count($oldItems) }}">
                @foreach ($oldItems as $index => $oldItem)
                    <div class="border rounded-3 p-3" data-sale-line>
                        <div class="row g-3 align-items-end">
                            {{-- Le produit choisi fournit le stock et l'unité affichés sous le sélecteur. --}}
                            <div class="col-lg-5"><label class="form-label" for="items_{{ $index }}_product">Produit</label><select class="form-select @error("items.$index.product_id") is-invalid @enderror" id="items_{{ $index }}_product" name="items[{{ $index }}][product_id]" data-product required><option value="">Sélectionner</option>@foreach ($products as $product)<option value="{{ $product->id }}" data-stock="{{ $product->current_stock }}" data-unit="{{ $product->unit }}" @selected((string) ($oldItem['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>@endforeach</select><small class="text-muted" data-stock-label>Choisissez un produit pour voir le stock.</small>@error("items.$index.product_id")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                            {{-- La quantité vendue est contrôlée une seconde fois sous verrou en base. --}}
                            <div class="col-sm-4 col-lg-2"><label class="form-label" for="items_{{ $index }}_quantity">Quantité</label><input class="form-control @error("items.$index.quantity") is-invalid @enderror" id="items_{{ $index }}_quantity" name="items[{{ $index }}][quantity]" type="number" value="{{ $oldItem['quantity'] ?? 1 }}" min="0.01" step="0.01" data-quantity required>@error("items.$index.quantity")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                            {{-- Le prix unitaire est saisi en FCFA et reste figé dans la ligne de vente. --}}
                            <div class="col-sm-4 col-lg-2"><label class="form-label" for="items_{{ $index }}_price">Prix unitaire</label><input class="form-control @error("items.$index.unit_price") is-invalid @enderror" id="items_{{ $index }}_price" name="items[{{ $index }}][unit_price]" type="number" value="{{ $oldItem['unit_price'] ?? '' }}" min="0" step="1" data-price required>@error("items.$index.unit_price")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                            {{-- Ce total est une aide visuelle ; le serveur effectue toujours son propre calcul. --}}
                            <div class="col-sm-3 col-lg-2"><span class="form-label d-block">Total ligne</span><strong data-line-total>0 FCFA</strong></div>
                            <div class="col-sm-1 col-lg-1 text-end"><button class="btn btn-sm btn-private-secondary" type="button" data-remove-line aria-label="Retirer cette ligne">Retirer</button></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- TOTAL ET ENCAISSEMENT : cette zone synthétise la somme des lignes avant validation. --}}
            <div class="row g-4 mt-2 align-items-end">
                <div class="col-md-5" id="initial-payment-group"><label class="form-label" for="initial_payment">Premier versement en espèces</label><input class="form-control @error('initial_payment') is-invalid @enderror" id="initial_payment" name="initial_payment" type="number" value="{{ old('initial_payment', 0) }}" min="0" step="1"><div class="form-text">Utilisé uniquement pour une vente à crédit.</div>@error('initial_payment')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><p class="mb-1 text-muted">Montant total estimé</p><strong class="fs-3" id="sale-grand-total">0 FCFA</strong></div>
                <div class="col-md-3 text-md-end"><button class="btn btn-private-primary" type="submit">Enregistrer la vente</button></div>
            </div>
        </form>
    </section>

    {{-- MODÈLE : JavaScript copie ce bloc lors d'un clic sur "Ajouter une ligne". --}}
    <template id="sale-line-template">
        <div class="border rounded-3 p-3" data-sale-line>
            <div class="row g-3 align-items-end">
                <div class="col-lg-5"><label class="form-label">Produit</label><select class="form-select" name="items[__INDEX__][product_id]" data-product required><option value="">Sélectionner</option>@foreach ($products as $product)<option value="{{ $product->id }}" data-stock="{{ $product->current_stock }}" data-unit="{{ $product->unit }}">{{ $product->name }}</option>@endforeach</select><small class="text-muted" data-stock-label>Choisissez un produit pour voir le stock.</small></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label">Quantité</label><input class="form-control" name="items[__INDEX__][quantity]" type="number" value="1" min="0.01" step="0.01" data-quantity required></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label">Prix unitaire</label><input class="form-control" name="items[__INDEX__][unit_price]" type="number" min="0" step="1" data-price required></div>
                <div class="col-sm-3 col-lg-2"><span class="form-label d-block">Total ligne</span><strong data-line-total>0 FCFA</strong></div>
                <div class="col-sm-1 col-lg-1 text-end"><button class="btn btn-sm btn-private-secondary" type="button" data-remove-line aria-label="Retirer cette ligne">Retirer</button></div>
            </div>
        </div>
    </template>

@endsection
