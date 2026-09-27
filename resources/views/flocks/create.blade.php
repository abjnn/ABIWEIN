@extends('layouts.private')

@section('title', 'Créer une bande')
@section('page_title', 'Nouvelle bande')

@section('content')
    <section class="private-intro"><div><p class="private-kicker">Exploitation avicole</p><h2>Ouvrir un nouveau cycle</h2><p>L’effectif initial et l’affectation seront inscrits dans l’historique technique.</p></div></section>
    <section class="private-panel">
        <form method="POST" action="{{ route('flocks.store') }}">
            @csrf
            @include('flocks._form')
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-private-primary" type="submit">Enregistrer la bande</button>
                <a class="btn btn-outline-secondary" href="{{ route('flocks.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
