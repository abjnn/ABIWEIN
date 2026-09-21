@extends('layouts.public')

@section('title', 'Espace privé — ABIWEIN')
@section('meta_description', 'Accès réservé aux utilisateurs autorisés de la plateforme ABIWEIN.')

@section('content')
    {{--
        PAGE DE CONNEXION
        Les champs seront activés à l'étape 8 avec les rôles et protections Laravel.
        Cette page ne simule donc aucun accès utilisateur.
    --}}
    <section class="login-section">
        <div class="container">
            <div class="login-shell">
                <div class="login-visual" style="--login-image: url('{{ asset('images/public/hero-poultry.jpg') }}')">
                    <div class="login-visual__content">
                        <p class="hero-eyebrow">Espace professionnel</p>
                        <h1>Le pilotage de la ferme commence ici.</h1>
                        <p>Un accès réservé aux membres autorisés de l’équipe ABIWEIN.</p>
                    </div>
                </div>

                <div class="login-form-panel">
                    <a class="login-logo" href="{{ route('home') }}" aria-label="ABIWEIN — retour à l’accueil"><img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="136" height="136"></a>
                    <h2>Connexion</h2>
                    <p class="login-intro">Saisissez vos identifiants lorsque l’accès sécurisé sera activé.</p>

                    {{-- Ce message explique honnêtement l'état actuel du projet. --}}
                    <div class="login-notice" role="note">L’authentification et les rôles seront activés à l’étape 8. Aucun identifiant n’est encore accepté.</div>

                    {{-- Les champs sont désactivés pour ne jamais envoyer un mot de passe vers une fausse route. --}}
                    <form aria-label="Aperçu du formulaire de connexion">
                        <div class="mb-4"><label class="form-label" for="login-email">Adresse e-mail</label><input class="form-control" id="login-email" type="email" autocomplete="username" placeholder="nom@exemple.com" disabled></div>
                        <div class="mb-4"><label class="form-label" for="login-password">Mot de passe</label><input class="form-control" id="login-password" type="password" autocomplete="current-password" placeholder="Votre mot de passe" disabled></div>
                        <button class="btn btn-brand btn-lg w-100" type="button" disabled>Se connecter</button>
                    </form>

                    {{-- Il n'existe volontairement aucun lien d'inscription publique. --}}
                    <a class="back-link" href="{{ route('home') }}"><span aria-hidden="true">←</span> Retour au site public</a>
                </div>
            </div>
        </div>
    </section>
@endsection
