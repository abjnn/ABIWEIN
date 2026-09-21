@extends('layouts.private')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')

@section('content')
    {{-- EN-TÊTE : explique la portée administrative et ouvre le formulaire de création. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="users-title">
        <div>
            <p class="private-kicker">Administration</p>
            <h2 id="users-title">Comptes et autorisations</h2>
            <p>Créez les comptes internes et contrôlez leur accès à ABIWEIN.</p>
        </div>
        <a class="btn btn-private-primary" href="{{ route('admin.users.create') }}">Créer un compte</a>
    </section>

    {{-- TABLEAU : aucune donnée sensible comme le mot de passe n’est affichée. --}}
    <section class="private-panel" aria-labelledby="users-table-title">
        <div class="private-panel__header">
            <div><p class="private-kicker">Accès</p><h2 id="users-table-title">Utilisateurs enregistrés</h2></div>
            <span>{{ $users->total() }} compte(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table private-table align-middle">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Dernière connexion</th><th>État</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td>
                            <td>{{ $user->role->label() }}</td>
                            <td>{{ $user->last_login_at?->format('d/m/Y à H:i') ?? 'Jamais' }}</td>
                            <td><span class="private-status private-status--{{ $user->is_active ? 'good' : 'blocked' }}">{{ $user->is_active ? 'Actif' : 'Bloqué' }}</span></td>
                            <td class="text-end">
                                {{-- Cette action utilise PATCH car elle modifie uniquement l’état du compte. --}}
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-private-secondary" type="submit" @disabled(auth()->user()->is($user))>{{ $user->is_active ? 'Bloquer' : 'Activer' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Les liens Bootstrap apparaissent lorsque le nombre de comptes dépasse 15. --}}
        @if ($users->hasPages())
            <div class="private-pagination">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
