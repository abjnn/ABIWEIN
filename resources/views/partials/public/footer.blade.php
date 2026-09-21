{{--
    PIED DE PAGE PUBLIC
    MODIFICATION : les coordonnées viennent de config/public_site.php et de .env.
    Une donnée vide n'est jamais remplacée par une information inventée.
--}}
<footer class="site-footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <a class="footer-brand" href="{{ route('home') }}" aria-label="ABIWEIN — accueil">
                    <img src="{{ asset('brand/abiwein-logo.png') }}" alt="ABIWEIN" width="132" height="132">
                </a>
                <p class="footer-description">Une plateforme conçue pour centraliser le suivi technique, sanitaire, commercial et financier d’une exploitation avicole.</p>
            </div>

            <div class="col-6 col-lg-3">
                <h2 class="footer-title">Navigation</h2>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li><a href="{{ route('about') }}">À propos</a></li>
                    <li><a href="{{ route('home') }}#activites">Activités</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h2 class="footer-title">Nous joindre</h2>
                <ul class="footer-links footer-links--contact">
                    {{-- Chaque ligne est conditionnelle pour ne jamais afficher une fausse coordonnée. --}}
                    @if (config('public_site.phone'))
                        <li><a href="tel:{{ preg_replace('/\s+/', '', config('public_site.phone')) }}">{{ config('public_site.phone') }}</a></li>
                    @endif
                    @if (config('public_site.email'))
                        <li><a href="mailto:{{ config('public_site.email') }}">{{ config('public_site.email') }}</a></li>
                    @endif
                    @if (config('public_site.address'))
                        <li>{{ config('public_site.address') }}</li>
                    @endif
                    @unless (config('public_site.phone') || config('public_site.email') || config('public_site.address'))
                        <li><a href="{{ route('contact') }}">Utiliser le formulaire de contact</a></li>
                    @endunless
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            {{-- date('Y') maintient automatiquement l'année à jour. --}}
            <p>© {{ date('Y') }} ABIWEIN. Tous droits réservés.</p>
            <p>Photographies professionnelles sous licence Pexels.</p>
        </div>
    </div>
</footer>
