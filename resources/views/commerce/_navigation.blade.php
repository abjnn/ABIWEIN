{{-- NAVIGATION LOCALE : ces boutons relient les écrans du module sans alourdir la barre latérale globale. --}}
<nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Navigation commerciale et financière">
    {{-- Ce lien ouvre la synthèse des ventes, encaissements, créances et dépenses. --}}
    <a class="btn {{ request()->routeIs('commerce.dashboard') ? 'btn-private-primary' : 'btn-private-secondary' }}" href="{{ route('commerce.dashboard') }}">Vue commerciale</a>

    {{-- Ce lien ouvre le répertoire des acheteurs. --}}
    <a class="btn {{ request()->routeIs('clients.*') ? 'btn-private-primary' : 'btn-private-secondary' }}" href="{{ route('clients.index') }}">Clients</a>

    {{-- Ce lien ouvre toutes les ventes au comptant et à crédit. --}}
    <a class="btn {{ request()->routeIs('sales.*') ? 'btn-private-primary' : 'btn-private-secondary' }}" href="{{ route('sales.index') }}">Ventes</a>

    {{-- Ce lien isole les sommes qui restent dues par les clients. --}}
    <a class="btn {{ request()->routeIs('receivables.*') ? 'btn-private-primary' : 'btn-private-secondary' }}" href="{{ route('receivables.index') }}">Créances</a>

    {{-- Ce lien ouvre le registre des sorties de caisse. --}}
    <a class="btn {{ request()->routeIs('expenses.*') ? 'btn-private-primary' : 'btn-private-secondary' }}" href="{{ route('expenses.index') }}">Dépenses</a>
</nav>
