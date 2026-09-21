{{--
    NAVIGATION PRIVÉE
    MODIFICATION : ajoutez une route réelle seulement quand le module fonctionne.
    Les éléments "À venir" restent non cliquables et ne simulent aucune fonction.
--}}
<aside class="offcanvas-lg offcanvas-start private-sidebar" tabindex="-1" id="privateNavigation" aria-labelledby="privateNavigationLabel">
    {{-- Cet en-tête est visible uniquement lorsque le menu s’ouvre sur téléphone. --}}
    <div class="offcanvas-header private-sidebar__mobile-header">
        <h2 class="offcanvas-title" id="privateNavigationLabel">Navigation ABIWEIN</h2>
        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas" data-bs-target="#privateNavigation" aria-label="Fermer le menu"></button>
    </div>

    <div class="private-sidebar__body">
        {{-- Le logo ramène vers le tableau de bord. --}}
        <a class="private-brand" href="{{ route('dashboard') }}" aria-label="ABIWEIN — tableau de bord">
            <img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="120" height="120">
        </a>

        {{-- Le premier groupe contient uniquement les écrans opérationnels. --}}
        <nav class="private-nav" aria-label="Navigation de l’espace privé">
            <p class="private-nav__label">Espace de travail</p>
            <a class="private-nav__link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <span class="private-nav__marker" aria-hidden="true"></span>
                Vue d’ensemble
            </a>

            {{-- Seul l’administrateur voit la gestion des comptes. --}}
            @if (auth()->user()->isAdministrator())
                <a class="private-nav__link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Utilisateurs
                </a>
            @endif

            {{-- Ces libellés montrent l’organisation prévue sans faux liens. --}}
            <p class="private-nav__label private-nav__label--spaced">Modules préparés</p>
            @foreach (['Exploitation', 'Suivi quotidien', 'Stocks', 'Santé', 'Ventes', 'Finances', 'Alertes'] as $module)
                <span class="private-nav__link private-nav__link--disabled" aria-disabled="true">
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    {{ $module }}
                    <small>À venir</small>
                </span>
            @endforeach
        </nav>

        {{-- Ce lien permet de consulter l’espace visiteur sans fermer la session. --}}
        <a class="private-sidebar__public-link" href="{{ route('home') }}">Voir le site public</a>
    </div>
</aside>
