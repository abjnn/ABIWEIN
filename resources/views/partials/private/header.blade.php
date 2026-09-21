{{-- EN-TÊTE PRIVÉ : titre de la page, identité active et action de déconnexion. --}}
<header class="private-header">
    <div class="private-header__left">
        {{-- Ce bouton ouvre la barre latérale uniquement sur petit écran. --}}
        <button class="private-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#privateNavigation" aria-controls="privateNavigation" aria-label="Ouvrir la navigation">
            <span></span><span></span><span></span>
        </button>
        <div>
            <p class="private-header__eyebrow">ABIWEIN</p>
            <h1>@yield('page_title', 'Espace privé')</h1>
        </div>
    </div>

    <div class="private-header__account">
        {{-- Les informations proviennent exclusivement de l’utilisateur authentifié. --}}
        <div class="private-header__identity">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ auth()->user()->role->label() }}</span>
        </div>

        {{-- La déconnexion utilise POST et un jeton CSRF car elle modifie la session. --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-private-secondary" type="submit">Déconnexion</button>
        </form>
    </div>
</header>
