{{--
    EN-TÊTE PUBLIC
    MODIFICATION : ajoutez ou retirez un lien dans la liste <ul>. Les noms de
    routes viennent de routes/web.php.
--}}
<header class="site-header" data-site-header>
    <nav class="navbar navbar-expand-lg" aria-label="Navigation principale">
        <div class="container">
            {{-- Le logo complet conserve ses proportions grâce à object-fit: contain. --}}
            <a class="site-brand" href="{{ route('home') }}" aria-label="ABIWEIN — revenir à l’accueil">
                <img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="118" height="118">
            </a>

            {{-- Ce bouton apparaît sur téléphone et commande le menu Bootstrap. --}}
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navigationPublique"
                aria-controls="navigationPublique"
                aria-expanded="false"
                aria-label="Afficher ou masquer le menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navigationPublique">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    {{-- La classe active signale la page courante visuellement et aux lecteurs d'écran. --}}
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Accueil</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}" @if(request()->routeIs('about')) aria-current="page" @endif>À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#activites">Activités</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}" @if(request()->routeIs('contact')) aria-current="page" @endif>Contact</a></li>
                    <li class="nav-item nav-item--private"><a class="btn btn-brand" href="{{ route('login') }}">Espace privé</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
