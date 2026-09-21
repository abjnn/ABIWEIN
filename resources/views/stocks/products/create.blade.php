@extends('layouts.private')

@section('title', 'Ajouter un produit')
@section('page_title', 'Nouveau produit')

@section('content')
    {{-- EN-TÊTE : rappelle que le stock initial sera automatiquement journalisé. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-product-title">
        <div>
            <p class="private-kicker">Gestion des stocks</p>
            <h2 id="create-product-title">Créer une référence</h2>
            <p>Définissez le produit, son unité et le niveau à partir duquel ABIWEIN doit signaler un réapprovisionnement.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('stocks.index') }}">Retour aux stocks</a>
    </section>

    <section class="private-form-card" aria-label="Création d’un produit">
        {{-- Le résumé annonce les erreurs ; les détails restent placés sous les champs concernés. --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>
        @endif

        @include('stocks.products._form', [
            'product' => null,
            'action' => route('stocks.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer le produit',
            'cancelUrl' => route('stocks.index'),
        ])
    </section>
@endsection
