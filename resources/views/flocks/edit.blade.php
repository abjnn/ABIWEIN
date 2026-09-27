@extends('layouts.private')

@section('title', 'Modifier une bande')
@section('page_title', 'Modifier la bande')

@section('content')
    <section class="private-intro"><div><p class="private-kicker">{{ $flock->code }}</p><h2>Informations descriptives</h2><p>L’effectif et le bâtiment se modifient uniquement au moyen des opérations dédiées.</p></div></section>
    <section class="private-panel">
        <form method="POST" action="{{ route('flocks.update', $flock) }}">
            @csrf
            @method('PUT')
            @include('flocks._form')
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-private-primary" type="submit">Enregistrer les modifications</button>
                <a class="btn btn-outline-secondary" href="{{ route('flocks.show', $flock) }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
