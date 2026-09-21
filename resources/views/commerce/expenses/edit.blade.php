@extends('layouts.private')

@section('title', 'Modifier une dépense')
@section('page_title', 'Modifier une dépense')

@section('content')
    {{-- EN-TÊTE : affiche la référence immuable de l'opération corrigée. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="edit-expense-title"><div><p class="private-kicker">Gestion financière</p><h2 id="edit-expense-title">{{ $expense->reference }}</h2><p>La référence et l'auteur initial restent conservés après la correction.</p></div><a class="btn btn-private-secondary" href="{{ route('expenses.index') }}">Retour aux dépenses</a></section>

    @include('commerce._navigation')

    <section class="private-form-card" aria-label="Formulaire de correction d'une dépense">
        @if ($errors->any())<div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>@endif
        {{-- PUT indique qu'une dépense existante est mise à jour sans créer un doublon. --}}
        <form method="POST" action="{{ route('expenses.update', $expense) }}">@csrf @method('PUT') @include('commerce.expenses._form', ['submitLabel' => 'Enregistrer les modifications'])</form>
    </section>
@endsection
