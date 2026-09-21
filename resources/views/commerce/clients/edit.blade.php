@extends('layouts.private')

@section('title', 'Modifier un client')
@section('page_title', 'Modifier un client')

@section('content')
    {{-- EN-TÊTE : rappelle la fiche en cours de modification. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="edit-client-title"><div><p class="private-kicker">Gestion commerciale</p><h2 id="edit-client-title">{{ $client->name }}</h2><p>Les ventes déjà enregistrées seront conservées sans modification.</p></div><a class="btn btn-private-secondary" href="{{ route('clients.index') }}">Retour aux clients</a></section>

    @include('commerce._navigation')

    <section class="private-form-card" aria-label="Formulaire de modification du client">
        @if ($errors->any())<div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>@endif
        {{-- PUT indique qu'une ressource existante est mise à jour. --}}
        <form method="POST" action="{{ route('clients.update', $client) }}">@csrf @method('PUT') @include('commerce.clients._form', ['submitLabel' => 'Enregistrer les modifications'])</form>
    </section>
@endsection
