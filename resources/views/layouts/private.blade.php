<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- Ces métadonnées garantissent un rendu responsive et protègent les formulaires privés. --}}
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#234B35">
        <title>@yield('title', 'Espace privé') — ABIWEIN</title>

        {{-- Le logo officiel sert de favicon sans être modifié. --}}
        <link rel="icon" type="image/png" href="{{ asset('brand/abiwein-logo.png') }}">

        {{-- Roboto reste la police commune aux espaces public et privé. --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=roboto:400,500,600,700,800" rel="stylesheet">

        {{-- Vite charge la palette, Bootstrap et les styles privés compilés. --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        @endif
    </head>
    <body class="private-site">
        {{-- Le lien d’évitement améliore la navigation au clavier. --}}
        <a class="skip-link" href="#private-content">Aller au contenu principal</a>

        {{-- Cette grille contient la navigation latérale et le contenu de travail. --}}
        <div class="private-shell">
            @include('partials.private.sidebar')

            <div class="private-workspace">
                @include('partials.private.header')

                <main class="private-content" id="private-content" tabindex="-1">
                    {{-- Les confirmations apparaissent au même endroit sur toutes les pages. --}}
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    {{-- Les erreurs générales, notamment l’auto-blocage, sont annoncées ici. --}}
                    @if ($errors->has('account'))
                        <div class="alert alert-danger" role="alert">{{ $errors->first('account') }}</div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
