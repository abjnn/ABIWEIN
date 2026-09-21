<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="ABIWEIN — socle numérique pour le suivi d’une exploitation avicole.">
        <meta name="theme-color" content="#31380F">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'ABIWEIN'))</title>

        <link rel="icon" type="image/png" href="{{ asset('brand/abiwein-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('brand/abiwein-logo.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=roboto:400,500,700,800" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        @endif

        @stack('head')
    </head>
    <body>
        <a class="skip-link" href="#contenu-principal">Aller au contenu principal</a>

        <nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top" aria-label="Navigation principale">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}" aria-label="ABIWEIN — accueil">
                    <img src="{{ asset('brand/abiwein-logo.png') }}" alt="" width="42" height="42">
                    <span>ABIWEIN</span>
                </a>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navigationAbiwein"
                    aria-controls="navigationAbiwein"
                    aria-expanded="false"
                    aria-label="Afficher ou masquer la navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navigationAbiwein">
                    <ul class="navbar-nav ms-auto gap-lg-2">
                        <li class="nav-item"><a class="nav-link active" aria-current="page" href="#accueil">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="#fondation">Fondation</a></li>
                        <li class="nav-item"><a class="nav-link" href="#identite">Identité visuelle</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main id="contenu-principal" tabindex="-1">
            @yield('content')
        </main>

        <footer class="app-footer">
            <div class="container d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                <p class="mb-0"><strong>ABIWEIN</strong> — application de gestion avicole.</p>
                <p class="mb-0 small">Interface de fondation · {{ date('Y') }}</p>
            </div>
        </footer>

        @stack('scripts')
    </body>
</html>
