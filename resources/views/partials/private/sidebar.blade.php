{{-- NAVIGATION PRIVÉE : les droits déterminent les modules visibles et les opérations possibles. --}}
<aside class="offcanvas-lg offcanvas-start private-sidebar" tabindex="-1" id="privateNavigation" aria-labelledby="privateNavigationLabel">
    <div class="offcanvas-header private-sidebar__mobile-header">
        <h2 class="offcanvas-title" id="privateNavigationLabel">Navigation ABIWEIN</h2>
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#privateNavigation" aria-label="Fermer le menu"></button>
    </div>

    <div class="private-sidebar__body">
        {{-- IDENTITÉ : le logo ouvre toujours la vue d'ensemble. --}}
        <a class="private-brand" href="{{ route('dashboard') }}" aria-label="ABIWEIN — tableau de bord">
            <img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="120" height="120">
        </a>

        <nav class="private-nav" aria-label="Navigation de l’espace privé">
            <p class="private-nav__label">Pilotage</p>
            <a class="private-nav__link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <x-private.nav-icon name="dashboard" />
                <span>Vue d’ensemble</span>
            </a>

            {{-- L'assistant est accessible à l'administrateur et au responsable, jamais au vétérinaire. --}}
            @if (auth()->user()->canUseIntelligence())
                <a class="private-nav__link {{ request()->routeIs('intelligence.*') ? 'active' : '' }}" href="{{ route('intelligence.demo') }}">
                    <x-private.nav-icon name="assistant" />
                    <span>Assistant ABIWEIN</span>
                </a>
            @endif

            @if (auth()->user()->canViewOperations())
                <a class="private-nav__link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                    <x-private.nav-icon name="report" />
                    <span>Rapports</span>
                </a>

                <p class="private-nav__label private-nav__label--spaced">Exploitation</p>
                <a class="private-nav__link {{ request()->routeIs('stocks.*') ? 'active' : '' }}" href="{{ route('stocks.index') }}">
                    <x-private.nav-icon name="stock" />
                    <span>Stocks d’intrants</span>
                </a>

                {{-- GROUPE COMMERCIAL : les sous-pages restent repliables comme dans une application professionnelle. --}}
                @php($commercialActive = request()->routeIs('commerce.dashboard', 'clients.*', 'sales.*', 'receivables.*', 'expenses.*'))
                <details class="private-nav__group" @if($commercialActive) open @endif>
                    <summary class="private-nav__link {{ $commercialActive ? 'active' : '' }}">
                        <x-private.nav-icon name="commerce" />
                        <span>Vue commerciale</span>
                        <span class="private-nav__chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="private-nav__subnav">
                        <a class="private-nav__sublink {{ request()->routeIs('commerce.dashboard') ? 'active' : '' }}" href="{{ route('commerce.dashboard') }}">Synthèse</a>
                        <a class="private-nav__sublink {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a>
                        <a class="private-nav__sublink {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">Ventes</a>
                        <a class="private-nav__sublink {{ request()->routeIs('receivables.*') ? 'active' : '' }}" href="{{ route('receivables.index') }}">Créances</a>
                        <a class="private-nav__sublink {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Dépenses</a>
                    </div>
                </details>
            @endif

            {{-- SANTÉ : lecture pour tous, création réservée au vétérinaire. --}}
            <p class="private-nav__label private-nav__label--spaced">Santé</p>
            <a class="private-nav__link {{ request()->routeIs('health.*') ? 'active' : '' }}" href="{{ route('health.visits.index') }}">
                <x-private.nav-icon name="health" />
                <span>Visites vétérinaires</span>
            </a>

            {{-- ADMINISTRATION : ces deux écrans restent exclusivement administratifs. --}}
            @if (auth()->user()->isAdministrator())
                <p class="private-nav__label private-nav__label--spaced">Administration</p>
                <a class="private-nav__link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <x-private.nav-icon name="users" />
                    <span>Utilisateurs</span>
                </a>
                <a class="private-nav__link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                    <x-private.nav-icon name="audit" />
                    <span>Journal d’audit</span>
                </a>
            @endif
        </nav>

        <a class="private-sidebar__public-link" href="{{ route('home') }}">Voir le site public</a>
    </div>
</aside>
