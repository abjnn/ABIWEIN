@extends('layouts.private')

@section('title', 'Nouvelle vente')
@section('page_title', 'Nouvelle vente')

@section('content')
    {{-- EN-TÊTE : rappelle que la vente concerne les poulets et non les intrants du magasin. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-sale-title">
        <div>
            <p class="private-kicker">Gestion commerciale</p>
            <h2 id="create-sale-title">Enregistrer une vente</h2>
            <p>Enregistrez les poulets vendus au comptant ou à crédit. Le règlement se fait uniquement en espèces.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('sales.index') }}">Retour aux ventes</a>
    </section>

    @include('commerce._navigation')

    {{-- FORMULAIRE : le panneau unique évite d'empiler des cartes et garde la saisie compacte. --}}
    <section class="private-form-card mw-100" aria-label="Formulaire d'enregistrement d'une vente">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">La vente n'a pas été enregistrée. Corrigez les informations signalées.</div>
        @endif

        <form method="POST" action="{{ route('sales.store') }}" id="sale-form">
            @csrf

            {{-- IDENTIFICATION : date, type et client sont regroupés sur une ligne. --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="sale_date">Date de vente</label>
                    <input class="form-control @error('sale_date') is-invalid @enderror" id="sale_date" name="sale_date" type="date" value="{{ old('sale_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sale_type">Type de vente</label>
                    <select class="form-select @error('sale_type') is-invalid @enderror" id="sale_type" name="sale_type" required>
                        <option value="cash" @selected(old('sale_type', 'cash') === 'cash')>Au comptant</option>
                        <option value="credit" @selected(old('sale_type') === 'credit')>À crédit</option>
                    </select>
                    @error('sale_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="client_id">Client</label>
                    <select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id">
                        <option value="">Client comptoir</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- LIGNES : les poulets vendus sont distincts des aliments, vitamines et autres intrants. --}}
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <div><p class="private-kicker mb-1">Détail</p><h3 class="h5 mb-0">Poulets vendus</h3></div>
                <button class="btn btn-private-secondary" id="add-sale-line" type="button">Ajouter une ligne</button>
            </div>

            @php($oldItems = old('items', [['item_type' => '', 'quantity' => 1, 'unit_price' => '']]))
            <div class="sale-lines" id="sale-lines" data-next-index="{{ count($oldItems) }}">
                @foreach ($oldItems as $index => $oldItem)
                    <div class="sale-line" data-sale-line>
                        <div>
                            <label class="form-label" for="items_{{ $index }}_type">Type de poulet</label>
                            <select class="form-select @error("items.$index.item_type") is-invalid @enderror" id="items_{{ $index }}_type" name="items[{{ $index }}][item_type]" required>
                                <option value="">Sélectionner</option>
                                @foreach ($itemTypes as $itemType)
                                    <option value="{{ $itemType->value }}" @selected(($oldItem['item_type'] ?? '') === $itemType->value)>{{ $itemType->label() }}</option>
                                @endforeach
                            </select>
                            @error("items.$index.item_type")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="items_{{ $index }}_quantity">Quantité</label>
                            <input class="form-control @error("items.$index.quantity") is-invalid @enderror" id="items_{{ $index }}_quantity" name="items[{{ $index }}][quantity]" type="number" value="{{ $oldItem['quantity'] ?? 1 }}" min="0.01" step="0.01" data-quantity required>
                            @error("items.$index.quantity")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="items_{{ $index }}_price">Prix unitaire</label>
                            <input class="form-control @error("items.$index.unit_price") is-invalid @enderror" id="items_{{ $index }}_price" name="items[{{ $index }}][unit_price]" type="number" value="{{ $oldItem['unit_price'] ?? '' }}" min="0" step="1" data-price required>
                            @error("items.$index.unit_price")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div><span class="form-label d-block">Total</span><strong data-line-total>0 FCFA</strong></div>
                        <button class="btn btn-sm btn-private-secondary" type="button" data-remove-line>Retirer</button>
                    </div>
                @endforeach
            </div>

            {{-- TOTAL : le serveur refait tous les calculs avant d'enregistrer. --}}
            <div class="row g-3 mt-3 align-items-end">
                <div class="col-md-5" id="initial-payment-group">
                    <label class="form-label" for="initial_payment">Premier versement en espèces</label>
                    <input class="form-control @error('initial_payment') is-invalid @enderror" id="initial_payment" name="initial_payment" type="number" value="{{ old('initial_payment', 0) }}" min="0" step="1">
                    <div class="form-text">Utilisé uniquement pour une vente à crédit.</div>
                    @error('initial_payment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4"><p class="mb-1 text-muted">Montant total estimé</p><strong class="fs-3" id="sale-grand-total">0 FCFA</strong></div>
                <div class="col-md-3 text-md-end"><button class="btn btn-private-primary" type="submit">Enregistrer la vente</button></div>
            </div>
        </form>
    </section>

    {{-- MODÈLE : JavaScript remplace uniquement l'index avant d'ajouter une ligne. --}}
    <template id="sale-line-template">
        <div class="sale-line" data-sale-line>
            <div><label class="form-label">Type de poulet</label><select class="form-select" name="items[__INDEX__][item_type]" required><option value="">Sélectionner</option>@foreach ($itemTypes as $itemType)<option value="{{ $itemType->value }}">{{ $itemType->label() }}</option>@endforeach</select></div>
            <div><label class="form-label">Quantité</label><input class="form-control" name="items[__INDEX__][quantity]" type="number" value="1" min="0.01" step="0.01" data-quantity required></div>
            <div><label class="form-label">Prix unitaire</label><input class="form-control" name="items[__INDEX__][unit_price]" type="number" min="0" step="1" data-price required></div>
            <div><span class="form-label d-block">Total</span><strong data-line-total>0 FCFA</strong></div>
            <button class="btn btn-sm btn-private-secondary" type="button" data-remove-line>Retirer</button>
        </div>
    </template>
@endsection
