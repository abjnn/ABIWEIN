@extends('layouts.private')

@section('title', 'Modifier un produit')
@section('page_title', 'Modifier le produit')

@section('content')
    {{-- EN-TÊTE : identifie précisément la référence en cours de modification. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="edit-product-title">
        <div>
            <p class="private-kicker">Gestion des stocks</p>
            <h2 id="edit-product-title">{{ $product->name }}</h2>
            <p>Modifiez les informations descriptives. La quantité se corrige exclusivement par un mouvement de stock.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('stocks.show', $product) }}">Retour à la fiche</a>
    </section>

    <section class="private-form-card" aria-label="Modification d’un produit">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>
        @endif

        @include('stocks.products._form', [
            'action' => route('stocks.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
            'cancelUrl' => route('stocks.show', $product),
        ])
    </section>
@endsection
