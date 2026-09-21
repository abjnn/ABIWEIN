@extends('layouts.private')

@section('title', 'Clients')
@section('page_title', 'Clients')

@section('content')
    {{-- EN-TÊTE : regroupe le titre du répertoire et le bouton de création. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="clients-title">
        <div><p class="private-kicker">Gestion commerciale</p><h2 id="clients-title">Répertoire des clients</h2><p>Conservez les coordonnées des acheteurs et retrouvez leurs ventes et créances.</p></div>
        @if (auth()->user()->isFarmManager())
            <a class="btn btn-private-primary" href="{{ route('clients.create') }}">Ajouter un client</a>
        @endif
    </section>

    @include('commerce._navigation')

    {{-- RECHERCHE : le paramètre "recherche" filtre le nom, le téléphone et l'e-mail. --}}
    <section class="private-form-card mb-4" aria-label="Recherche de clients">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('clients.index') }}">
            <div class="col-md-9"><label class="form-label" for="client-search">Nom, téléphone ou e-mail</label><input class="form-control" id="client-search" name="recherche" type="search" value="{{ $search }}" maxlength="150"></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-private-primary" type="submit">Rechercher</button><a class="btn btn-private-secondary" href="{{ route('clients.index') }}">Effacer</a></div>
        </form>
    </section>

    {{-- TABLEAU : affiche uniquement des informations utiles au suivi commercial. --}}
    <section class="private-panel" aria-labelledby="client-list-title">
        <div class="private-panel__header"><div><p class="private-kicker">Acheteurs</p><h2 id="client-list-title">Clients enregistrés</h2></div><span>{{ $clients->total() }} client(s)</span></div>
        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Client</th><th>Coordonnées</th><th>Ventes</th><th>Créance</th><th>État</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td><strong>{{ $client->name }}</strong><small>{{ $client->type === 'business' ? 'Entreprise' : 'Particulier' }}</small></td>
                            <td>{{ $client->phone ?: 'Non renseigné' }}<small>{{ $client->email ?: 'Aucun e-mail' }}</small></td>
                            <td>{{ $client->sales_count }}</td>
                            <td>{{ number_format((float) ($client->sales_sum_balance_due ?? 0), 0, ',', ' ') }} FCFA</td>
                            <td><span class="private-status private-status--{{ $client->is_active ? 'good' : 'blocked' }}">{{ $client->is_active ? 'Actif' : 'Inactif' }}</span></td>
                            <td class="text-end">
                                @if (auth()->user()->isFarmManager())
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-private-secondary" href="{{ route('clients.edit', $client) }}">Modifier</a>
                                    {{-- Cette action conserve le client et tout son historique ; seul son usage futur change. --}}
                                    <form method="POST" action="{{ route('clients.toggle-status', $client) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-private-secondary" type="submit">{{ $client->is_active ? 'Désactiver' : 'Activer' }}</button></form>
                                </div>
                                @else
                                    <span class="text-muted small">Consultation</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="private-empty" colspan="6">Aucun client ne correspond à cette recherche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clients->hasPages())<div class="private-pagination">{{ $clients->links() }}</div>@endif
    </section>
@endsection
