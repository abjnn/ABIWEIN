@extends('layouts.private')

@section('title', 'Ajouter un client')
@section('page_title', 'Ajouter un client')

@section('content')
    {{-- EN-TÊTE : permet de revenir au répertoire sans enregistrer. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-client-title"><div><p class="private-kicker">Gestion commerciale</p><h2 id="create-client-title">Nouvelle fiche client</h2><p>Renseignez les coordonnées utiles aux ventes et au suivi des créances.</p></div><a class="btn btn-private-secondary" href="{{ route('clients.index') }}">Retour aux clients</a></section>

    @include('commerce._navigation')

    <section class="private-form-card" aria-label="Formulaire de création d'un client">
        {{-- Cette alerte indique que des champs doivent être corrigés avant l'enregistrement. --}}
        @if ($errors->any())<div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>@endif
        <form method="POST" action="{{ route('clients.store') }}">@csrf @include('commerce.clients._form', ['client' => null, 'submitLabel' => 'Créer le client'])</form>
    </section>
@endsection
