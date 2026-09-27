@extends('layouts.private')

@section('title', 'Nouveau relevé')
@section('page_title', 'Relevé quotidien')

@section('content')
    <section class="private-intro"><div><p class="private-kicker">{{ $flock->code }}</p><h2>Résumer une journée</h2><p>Un seul relevé est conservé par date et par bande.</p></div></section>
    <section class="private-panel">
        <form method="POST" action="{{ route('flocks.daily-records.store', $flock) }}">
            @csrf
            @include('flocks.daily-records._form')
            <div class="d-flex gap-2 mt-4"><button class="btn btn-private-primary" type="submit">Enregistrer le relevé</button><a class="btn btn-outline-secondary" href="{{ route('flocks.show', $flock) }}">Annuler</a></div>
        </form>
    </section>
@endsection
