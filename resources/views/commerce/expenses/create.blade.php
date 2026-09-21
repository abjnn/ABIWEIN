@extends('layouts.private')

@section('title', 'Ajouter une dépense')
@section('page_title', 'Ajouter une dépense')

@section('content')
    {{-- EN-TÊTE : replace l'utilisateur dans le registre financier. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-expense-title"><div><p class="private-kicker">Gestion financière</p><h2 id="create-expense-title">Nouvelle sortie de caisse</h2><p>Enregistrez le montant, la date et le motif réel de la charge.</p></div><a class="btn btn-private-secondary" href="{{ route('expenses.index') }}">Retour aux dépenses</a></section>

    @include('commerce._navigation')

    <section class="private-form-card" aria-label="Formulaire d'une nouvelle dépense">
        @if ($errors->any())<div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>@endif
        {{-- Le formulaire utilise POST pour créer une nouvelle opération dans le registre. --}}
        <form method="POST" action="{{ route('expenses.store') }}">@csrf @include('commerce.expenses._form', ['expense' => null, 'submitLabel' => 'Enregistrer la dépense'])</form>
    </section>
@endsection
