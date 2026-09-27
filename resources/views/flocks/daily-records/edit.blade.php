@extends('layouts.private')

@section('title', 'Corriger un relevé')
@section('page_title', 'Correction du relevé')

@section('content')
    <section class="private-intro"><div><p class="private-kicker">{{ $flock->code }}</p><h2>{{ $dailyRecord->record_date->format('d/m/Y') }}</h2><p>Seule la différence de mortalité modifiera l’effectif actuel.</p></div></section>
    <section class="private-panel">
        <form method="POST" action="{{ route('flocks.daily-records.update', [$flock, $dailyRecord]) }}">
            @csrf
            @method('PUT')
            @include('flocks.daily-records._form')
            <div class="d-flex gap-2 mt-4"><button class="btn btn-private-primary" type="submit">Enregistrer la correction</button><a class="btn btn-outline-secondary" href="{{ route('flocks.show', $flock) }}">Annuler</a></div>
        </form>
    </section>
@endsection
