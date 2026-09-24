@extends('layouts.public')

@section('title', 'Espace privé — ABIWEIN')
@section('meta_description', 'Accès réservé aux utilisateurs autorisés de la plateforme ABIWEIN.')

@section('content')
    {{--
        PAGE DE CONNEXION
        Le formulaire transmet uniquement email, password et remember à LoginRequest.
        La création de comptes reste réservée à l’administrateur dans l’espace privé.
    --}}
    <section class="login-section">
        <div class="container">
            <div class="login-shell">
                {{-- Cette photo de poulets de chair reste indépendante des autres visuels du site. --}}
                <div class="login-visual" style="--login-image: url('{{ asset('images/public/login-broiler-farm.jpg') }}')">
                    <div class="login-visual__content">
                        <p class="hero-eyebrow">Espace professionnel</p>
                        <h1>Le pilotage de la ferme commence ici.</h1>
                        <p>Un accès réservé aux membres autorisés de l’équipe ABIWEIN.</p>
                    </div>
                </div>

                <div class="login-form-panel">
                    {{-- Le logo renvoie vers l’accueil public sans envoyer le formulaire. --}}
                    <a class="login-logo" href="{{ route('home') }}" aria-label="ABIWEIN — retour à l’accueil"><img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="136" height="136"></a>
                    <h2>Connexion</h2>
                    <p class="login-intro">Utilisez le compte qui vous a été attribué par l’administrateur.</p>

                    {{-- Le message de session confirme notamment une action précédente. --}}
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    {{-- Ce résumé apparaît si un champ est invalide ou si la connexion échoue. --}}
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">La connexion n’a pas pu être effectuée. Vérifiez les informations indiquées.</div>
                    @endif

                    {{-- Tout le code responsable du formulaire de connexion commence ici. --}}
                    <form method="POST" action="{{ route('login.store') }}" aria-label="Formulaire de connexion">
                        {{-- Cette directive ajoute le jeton CSRF qui protège l’envoi. --}}
                        @csrf

                        {{-- Cette partie génère le champ e-mail ; conservez name="email". --}}
                        <div class="mb-4">
                            <label class="form-label" for="login-email">Adresse e-mail</label>
                            <input class="form-control @error('email') is-invalid @enderror" id="login-email" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="username" placeholder="nom@exemple.com" autofocus required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cette partie génère le mot de passe ; sa valeur n’est jamais renvoyée. --}}
                        <div class="mb-4">
                            <label class="form-label" for="login-password">Mot de passe</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="login-password" name="password" type="password" maxlength="255" autocomplete="current-password" placeholder="Votre mot de passe" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cette case contrôle la conservation prolongée de la session. --}}
                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">Rester connecté sur cet appareil</label>
                        </div>

                        {{-- Ce bouton envoie les identifiants au contrôleur de session. --}}
                        <button class="btn btn-brand btn-lg w-100" type="submit">Se connecter</button>
                    </form>

                    {{-- Aucun lien d’inscription ou de création de compte public n’est proposé. --}}
                    <a class="back-link" href="{{ route('home') }}"><span aria-hidden="true">←</span> Retour au site public</a>
                </div>
            </div>
        </div>
    </section>
@endsection
