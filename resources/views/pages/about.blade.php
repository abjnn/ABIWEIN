@extends('layouts.public')

@section('title', 'À propos — ABIWEIN')
@section('meta_description', 'Comprendre la mission d’ABIWEIN, les activités de la ferme et l’organisation du suivi avicole.')

@section('content')
    {{-- EN-TÊTE DE PAGE : le fond reprend la photo professionnelle d'élevage. --}}
    <section class="page-hero" aria-labelledby="about-title" style="--page-image: url('{{ asset('images/public/hero-poultry.jpg') }}')">
        <div class="container">
            <p class="hero-eyebrow">À propos d’ABIWEIN</p>
            <h1 id="about-title">Mieux comprendre la ferme pour mieux la piloter.</h1>
            <p>Une plateforme née des besoins concrets de l’élevage de poulets de chair.</p>
        </div>
    </section>

    {{-- MISSION : adaptez ici la formulation institutionnelle si elle évolue. --}}
    <section class="section-space" aria-labelledby="mission-title">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <x-public.section-heading eyebrow="Notre mission" title="Donner une vue fiable de l’exploitation." intro="ABIWEIN centralise les données techniques, sanitaires, commerciales et financières afin de soutenir le suivi quotidien et la prise de décision." />
                    <p class="body-copy">L’application suit le cycle réel de la ferme. Elle relie les bâtiments, les bandes, la santé animale, les stocks, les ventes et les dépenses, tout en séparant clairement l’espace public des données internes.</p>
                    <p class="body-copy">Les analyses automatiques servent d’appui. Les décisions importantes restent examinées et validées par les responsables de l’exploitation.</p>
                </div>
                <div class="col-lg-6 order-1 order-lg-2">
                    <figure class="editorial-image editorial-image--portrait">
                        <img src="{{ asset('images/public/farmer-poultry.jpg') }}" alt="Travail d’un éleveur auprès de ses poulets" width="1400" height="933">
                        <figcaption>L’expertise humaine demeure au centre de chaque décision.</figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    {{-- INFRASTRUCTURES : éléments issus du cadrage, sans chiffre inventé. --}}
    <section class="section-space section-space--soft" aria-labelledby="sites-title">
        <div class="container">
            <x-public.section-heading eyebrow="L’exploitation" title="Des espaces complémentaires, un même suivi." intro="Le fonctionnement de la ferme s’organise autour de plusieurs zones dont les activités seront reliées dans ABIWEIN." align="center" />
            <div class="facility-list">
                <article><span>01</span><div><h3>Accueil des poussins</h3><p>Réception et phase de chauffage avant la croissance.</p></div></article>
                <article><span>02</span><div><h3>Bâtiments d’élevage</h3><p>Suivi des bandes vivantes et de leurs indicateurs quotidiens.</p></div></article>
                <article><span>03</span><div><h3>Préparation et conservation</h3><p>Organisation des opérations après élevage et conservation au froid.</p></div></article>
                <article><span>04</span><div><h3>Stocks de la ferme</h3><p>Aliments, produits sanitaires, équipements et autres intrants.</p></div></article>
            </div>
        </div>
    </section>

    {{-- PRINCIPES : titres courts pour conserver une lecture rapide sur téléphone. --}}
    <section class="section-space" aria-labelledby="principes-title">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <x-public.section-heading eyebrow="Nos principes" title="Une gestion sobre, claire et responsable." intro="L’outil accompagne les pratiques de la ferme avec une information lisible, traçable et accessible selon le rôle de chacun." />
                </div>
                <div class="col-lg-7">
                    <div class="principle-grid">
                        <article><span>01</span><h3>Clarté</h3><p>Présenter l’essentiel sans surcharger les écrans.</p></article>
                        <article><span>02</span><h3>Traçabilité</h3><p>Conserver l’origine et l’historique des opérations sensibles.</p></article>
                        <article><span>03</span><h3>Prévention</h3><p>Faire remonter les seuils, échéances et anomalies utiles.</p></article>
                        <article><span>04</span><h3>Contrôle humain</h3><p>Garder la validation des décisions entre les mains des responsables.</p></article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- APPEL À L'ACTION : lien vers la page de contact distincte. --}}
    <section class="platform-callout" aria-labelledby="about-contact-title">
        <div class="container">
            <div class="platform-callout__inner">
                <div><p class="section-eyebrow">Prendre contact</p><h2 id="about-contact-title">Vous souhaitez en savoir davantage&nbsp;?</h2><p>Notre formulaire public vous permet de transmettre votre question à l’équipe.</p></div>
                <a class="btn btn-white btn-lg" href="{{ route('contact') }}">Écrire à ABIWEIN</a>
            </div>
        </div>
    </section>
@endsection
