<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- MODIFICATION : chaque page définit son titre et sa description dans son propre fichier Blade. --}}
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description', 'ABIWEIN centralise le suivi d’une exploitation avicole et facilite la prise de décision.')">
        <meta name="theme-color" content="#234B35">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'ABIWEIN')</title>

        {{-- Le logo officiel est utilisé sans recoloration ni déformation. --}}
        <link rel="icon" type="image/png" href="{{ asset('brand/abiwein-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('brand/abiwein-logo.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        {{-- Roboto est la police officielle ; une police système prend le relais hors connexion. --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=roboto:400,500,600,700,800" rel="stylesheet">

        {{-- Vite charge les styles et scripts compilés. --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/scss/app.scss', 'resources/js/app.js'])
        @endif

        @stack('head')
    </head>
    <body class="public-site">
        {{-- Ce lien apparaît au focus clavier pour contourner directement le menu. --}}
        <a class="skip-link" href="#contenu-principal">Aller au contenu principal</a>

        {{-- Les éléments partagés sont isolés afin d'être modifiés une seule fois. --}}
        @include('partials.public.header')

        <main id="contenu-principal" tabindex="-1">
            @yield('content')
        </main>

        @include('partials.public.footer')
    </body>
</html>
