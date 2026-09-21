{{--
    NAVIGATION PRIVÉE
    Les groupes ci-dessous séparent le pilotage, les opérations et l’administration.
    MODIFICATION : le nom placé entre route(...) détermine l’écran ouvert par un lien.
--}}
<aside class="offcanvas-lg offcanvas-start private-sidebar" tabindex="-1" id="privateNavigation" aria-labelledby="privateNavigationLabel">
    {{-- Cet en-tête est visible uniquement lorsque le menu s’ouvre sur téléphone. --}}
    <div class="offcanvas-header private-sidebar__mobile-header">
        <h2 class="offcanvas-title" id="privateNavigationLabel">Navigation ABIWEIN</h2>
        {{-- Le bouton sombre reste visible puisque le fond du menu mobile est blanc. --}}
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#privateNavigation" aria-label="Fermer le menu"></button>
    </div>

    <div class="private-sidebar__body">
        {{-- Le logo ramène vers le tableau de bord. --}}
        <a class="private-brand" href="{{ route('dashboard') }}" aria-label="ABIWEIN — tableau de bord">
            <img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="120" height="120">
        </a>

        {{-- PILOTAGE : synthèse générale et rapports consolidés. --}}
        <nav class="private-nav" aria-label="Navigation de l’espace privé">
            <p class="private-nav__label">Pilotage</p>
            <a class="private-nav__link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <span class="private-nav__marker" aria-hidden="true"></span>
                Vue d’ensemble
            </a>
            <a class="private-nav__link {{ request()->routeIs('intelligence.*') ? 'active' : '' }}" href="{{ route('intelligence.demo') }}" @if(request()->routeIs('intelligence.*')) aria-current="page" @endif>
                <span class="private-nav__marker" aria-hidden="true"></span>
                Analyse intelligente
            </a>

            {{-- Les rapports financiers restent limités aux rôles qui pilotent la ferme. --}}
            @if (auth()->user()->canManageOperations())
                <a class="private-nav__link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Rapports
                </a>

                {{-- OPÉRATIONS : chaque entrée mène à un module désormais fonctionnel. --}}
                <p class="private-nav__label private-nav__label--spaced">Opérations</p>
                <a class="private-nav__link {{ request()->routeIs('stocks.*') ? 'active' : '' }}" href="{{ route('stocks.index') }}" @if(request()->routeIs('stocks.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Stocks
                </a>
                <a class="private-nav__link {{ request()->routeIs('commerce.dashboard') || request()->routeIs('clients.*', 'sales.*', 'receivables.*', 'expenses.*') ? 'active' : '' }}" href="{{ route('commerce.dashboard') }}" @if(request()->routeIs('commerce.dashboard', 'clients.*', 'sales.*', 'receivables.*', 'expenses.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Ventes et finances
                </a>
                <a class="private-nav__link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}" @if(request()->routeIs('clients.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Clients
                </a>
                <a class="private-nav__link {{ request()->routeIs('receivables.*') ? 'active' : '' }}" href="{{ route('receivables.index') }}" @if(request()->routeIs('receivables.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Créances
                </a>
            @endif

            {{-- SANTÉ : accessible en lecture aux trois rôles autorisés. --}}
            <p class="private-nav__label private-nav__label--spaced">Santé</p>
            <a class="private-nav__link {{ request()->routeIs('health.*') ? 'active' : '' }}" href="{{ route('health.visits.index') }}" @if(request()->routeIs('health.*')) aria-current="page" @endif>
                <span class="private-nav__marker" aria-hidden="true"></span>
                Visites vétérinaires
            </a>

            {{-- ADMINISTRATION : seul l’administrateur peut gérer les comptes. --}}
            @if (auth()->user()->isAdministrator())
                <p class="private-nav__label private-nav__label--spaced">Administration</p>
                <a class="private-nav__link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Utilisateurs
                </a>
                <a class="private-nav__link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}" @if(request()->routeIs('admin.audit-logs.*')) aria-current="page" @endif>
                    <span class="private-nav__marker" aria-hidden="true"></span>
                    Journal d’audit
                </a>
            @endif
        </nav>

        {{-- Ce lien permet de consulter l’espace visiteur sans fermer la session. --}}
        <a class="private-sidebar__public-link" href="{{ route('home') }}">Voir le site public</a>
    </div>
</aside>
