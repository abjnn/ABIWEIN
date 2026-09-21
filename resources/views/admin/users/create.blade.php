@extends('layouts.private')

@section('title', 'Créer un utilisateur')
@section('page_title', 'Créer un utilisateur')

@section('content')
    {{-- EN-TÊTE : le retour ramène à la liste sans enregistrer le formulaire. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-user-title">
        <div>
            <p class="private-kicker">Administration</p>
            <h2 id="create-user-title">Nouveau compte interne</h2>
            <p>Attribuez un rôle adapté aux responsabilités réelles de la personne.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('admin.users.index') }}">Retour à la liste</a>
    </section>

    <section class="private-form-card" aria-label="Création d’un compte utilisateur">
        {{-- Ce résumé complète les messages placés directement sous les champs. --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>
        @endif

        {{-- Tout le code qui génère le formulaire de création commence ici. --}}
        <form method="POST" action="{{ route('admin.users.store') }}">
            {{-- Le jeton CSRF protège la création du compte contre une requête externe. --}}
            @csrf

            <div class="row g-4">
                {{-- Ce bloc crée le champ du nom complet ; conservez name="name". --}}
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom complet</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" maxlength="120" autocomplete="name" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Ce bloc crée l’identifiant de connexion unique. --}}
                <div class="col-md-6">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="off" aria-describedby="account-email-help" required>
                    <div class="form-text" id="account-email-help">Utilisez l’adresse personnelle de l’utilisateur ; son domaine doit exister.</div>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Ce sélecteur utilise directement les rôles centralisés dans UserRole. --}}
                <div class="col-12">
                    <label class="form-label" for="role">Rôle</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="">Sélectionner un rôle</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Les deux champs suivants doivent correspondre avant tout enregistrement. --}}
                <div class="col-md-6">
                    <label class="form-label" for="password">Mot de passe temporaire</label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="12" autocomplete="new-password" aria-describedby="password-help" required>
                    <div class="form-text" id="password-help">12 caractères minimum avec majuscule, minuscule, chiffre et symbole. Le secret doit être différent de ceux des autres comptes.</div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                </div>

                {{-- Une case cochée permet au nouveau compte de se connecter immédiatement. --}}
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">Compte actif dès sa création</label>
                    </div>
                </div>

                {{-- Ce bouton est l’unique action qui crée définitivement le compte. --}}
                <div class="col-12"><button class="btn btn-private-primary" type="submit">Créer le compte</button></div>
            </div>
        </form>
    </section>
@endsection
