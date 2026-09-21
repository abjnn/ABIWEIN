@extends('layouts.private')

@section('title', 'Dépenses')
@section('page_title', 'Dépenses')

@section('content')
    {{-- EN-TÊTE : met en avant le total du registre et le bouton de saisie. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="expenses-title">
        <div><p class="private-kicker">Gestion financière</p><h2 id="expenses-title">Registre des dépenses</h2><p>{{ number_format((float) $totalExpenses, 0, ',', ' ') }} FCFA de sorties de caisse enregistrées depuis l'ouverture du registre.</p></div>
        <a class="btn btn-private-primary" href="{{ route('expenses.create') }}">Ajouter une dépense</a>
    </section>

    @include('commerce._navigation')

    {{-- RECHERCHE : retrouve une référence, une catégorie ou un mot du motif. --}}
    <section class="private-form-card mb-4" aria-label="Recherche dans les dépenses">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('expenses.index') }}">
            <div class="col-md-9"><label class="form-label" for="expense-search">Référence, catégorie ou motif</label><input class="form-control" id="expense-search" name="recherche" type="search" value="{{ $search }}" maxlength="150"></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-private-primary" type="submit">Rechercher</button><a class="btn btn-private-secondary" href="{{ route('expenses.index') }}">Effacer</a></div>
        </form>
    </section>

    {{-- TABLEAU : chaque sortie de caisse conserve sa date, son auteur et sa référence. --}}
    <section class="private-panel" aria-labelledby="expenses-list-title">
        <div class="private-panel__header"><div><p class="private-kicker">Sorties de caisse</p><h2 id="expenses-list-title">Dépenses enregistrées</h2></div><span>{{ $expenses->total() }} opération(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Référence</th><th>Catégorie</th><th>Motif</th><th>Date</th><th>Auteur</th><th>Montant</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td><strong>{{ $expense->reference }}</strong></td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->spent_on->format('d/m/Y') }}</td>
                            <td>{{ $expense->recordedBy?->name ?? 'Utilisateur supprimé' }}</td>
                            <td><strong>{{ number_format((float) $expense->amount, 0, ',', ' ') }} FCFA</strong></td>
                            <td class="text-end"><a class="btn btn-sm btn-private-secondary" href="{{ route('expenses.edit', $expense) }}">Modifier</a></td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="7">Aucune dépense ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())<div class="private-pagination">{{ $expenses->links() }}</div>@endif
    </section>
@endsection
