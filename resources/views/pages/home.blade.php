@extends('layouts.public')

@section('title', 'ABIWEIN — Gestion intelligente d’exploitation avicole')
@section('meta_description', 'Découvrez ABIWEIN, une plateforme centralisée pour le suivi technique, sanitaire, commercial et financier d’une exploitation avicole.')

@section('content')
    {{--
        HERO D'ACCUEIL
        MODIFICATION : remplacez hero-poultry.jpg dans public/images/public pour changer la photo.
        Les voiles de lisibilité sont réglés dans .public-hero de _public.scss.
    --}}
    <section class="public-hero" aria-labelledby="home-title" style="--hero-image: url('{{ asset('images/public/hero-poultry.jpg') }}')">
        <div class="container public-hero__container">
            <div class="public-hero__content">
                <p class="hero-eyebrow">Gestion avicole centralisée</p>
                <h1 id="home-title">L’élevage avicole, <span>piloté avec clarté.</span></h1>
                <p class="public-hero__lead">ABIWEIN réunit les informations essentielles de la ferme pour suivre les bandes, anticiper les risques et prendre des décisions mieux informées.</p>
                <div class="public-hero__actions">
                    <a class="btn btn-brand btn-lg" href="{{ route('about') }}">Découvrir ABIWEIN</a>
                    <a class="btn btn-light-outline btn-lg" href="{{ route('contact') }}">Nous contacter</a>
                </div>
            </div>

            {{-- Ce panneau résume les domaines couverts sans afficher de données privées. --}}
            <aside class="hero-summary" aria-label="Domaines couverts par ABIWEIN">
                <p class="hero-summary__label">Une vision unifiée</p>
                <p class="hero-summary__title">De l’élevage à la gestion</p>
                <ul>
                    <li>Suivi technique</li>
                    <li>Gestion sanitaire</li>
                    <li>Stocks et ventes</li>
                    <li>Analyse assistée</li>
                </ul>
            </aside>
        </div>
    </section>

    {{-- BANDEAU DE REPÈRES : aucune statistique fictive n'est présentée. --}}
    <section class="trust-strip" aria-label="Principes de la plateforme">
        <div class="container">
            <div class="row g-0">
                <div class="col-md-4 trust-strip__item"><span>01</span><div><strong>Centraliser</strong><small>Une information mieux organisée</small></div></div>
                <div class="col-md-4 trust-strip__item"><span>02</span><div><strong>Suivre</strong><small>Une lecture claire du quotidien</small></div></div>
                <div class="col-md-4 trust-strip__item"><span>03</span><div><strong>Décider</strong><small>Des indicateurs utiles à l’action</small></div></div>
            </div>
        </div>
    </section>

    {{-- PRÉSENTATION : la photo locale est créditée dans docs/IMAGE_CREDITS.md. --}}
    <section class="section-space" aria-labelledby="approche-title">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <figure class="editorial-image">
                        {{-- Cette image montre la croissance des jeunes poulets autour de leur mangeoire. --}}
                        <img src="{{ asset('images/public/farmer-poultry.jpg') }}" alt="Jeunes poulets de chair rassemblés autour d’une mangeoire" width="1400" height="933" loading="lazy">
                        <figcaption>Des observations quotidiennes au plus près de la croissance.</figcaption>
                    </figure>
                </div>
                <div class="col-lg-6">
                    <x-public.section-heading
                        eyebrow="Notre approche"
                        title="La technologie au service du travail de terrain."
                        intro="ABIWEIN accompagne l’exploitation sans remplacer l’expérience humaine. La plateforme rassemble les observations quotidiennes et transforme les données en repères compréhensibles."
                    />

                    <div class="text-points">
                        <article><span>01</span><div><h3>Une information centralisée</h3><p>Les équipes retrouvent les données utiles au même endroit, selon leurs responsabilités.</p></div></article>
                        <article><span>02</span><div><h3>Un suivi continu</h3><p>Les observations techniques, sanitaires et commerciales restent reliées à chaque bande.</p></div></article>
                        <article><span>03</span><div><h3>Une assistance maîtrisée</h3><p>L’intelligence artificielle aide à analyser ; la validation finale reste humaine.</p></div></article>
                    </div>

                    <a class="text-link" href="{{ route('about') }}">En savoir plus sur le projet <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </div>
    </section>

    {{-- ACTIVITÉS : ces cartes décrivent l'activité sans révéler de données internes. --}}
    <section class="section-space section-space--soft" id="activites" aria-labelledby="activites-title">
        <div class="container">
            <x-public.section-heading
                eyebrow="Nos activités"
                title="Un cycle avicole suivi dans son ensemble."
                intro="De l’accueil des poussins à la commercialisation, ABIWEIN structure les informations nécessaires au pilotage de l’exploitation."
                align="center"
            />

            <div class="row g-4 activity-grid">
                <div class="col-md-6 col-xl-4">
                    {{-- La première photo montre une exploitation réelle de poulets de chair. --}}
                    <article class="activity-card activity-card--image">
                        <img src="{{ asset('images/public/activity-growth.jpg') }}" alt="Poulets de chair dans un grand bâtiment équipé de mangeoires" width="1600" height="1067" loading="lazy">
                        <div class="activity-card__overlay">
                            <div class="activity-card__number">01</div>
                            <h3>Élevage et croissance</h3>
                            <p>Suivi des bâtiments, bandes, effectifs, consommations, poids et observations quotidiennes.</p>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    {{-- La seconde photo illustre l’examen manuel attentif d’un poulet. --}}
                    <article class="activity-card activity-card--image">
                        <img src="{{ asset('images/public/activity-health.jpg') }}" alt="Poulet tenu avec précaution pendant un contrôle sanitaire" width="1600" height="1067" loading="lazy">
                        <div class="activity-card__overlay">
                            <div class="activity-card__number">02</div>
                            <h3>Santé et prévention</h3>
                            <p>Organisation des visites vétérinaires, diagnostics, programmes et interventions sanitaires.</p>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="activity-card activity-card--image">
                        {{-- Cette image illustre le produit fini sans montrer de personne ni la phase d’abattage. --}}
                        <img src="{{ asset('images/public/prepared-chicken.jpg') }}" alt="Poulets entiers déplumés et conditionnés sous emballage" width="1400" height="933" loading="lazy">
                        <div class="activity-card__overlay">
                            <div class="activity-card__number">03</div>
                            <h3>Préparation et vente</h3>
                            <p>Gestion des produits, stocks, ventes au comptant ou à crédit et paiements en espèces.</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    {{-- ESPACE PRIVÉ : présentation publique uniquement, aucune donnée métier n'est exposée. --}}
    <section class="platform-callout" aria-labelledby="platform-title">
        <div class="container">
            <div class="platform-callout__inner">
                <div>
                    <p class="section-eyebrow">Plateforme sécurisée</p>
                    <h2 id="platform-title">Un espace de travail réservé à l’équipe.</h2>
                    <p>Responsable d’exploitation, vétérinaire et administrateur disposent chacun d’un accès adapté à leur rôle.</p>
                </div>
                <a class="btn btn-white btn-lg" href="{{ route('login') }}">Accéder à l’espace privé</a>
            </div>
        </div>
    </section>

    {{-- APPEL FINAL : le bouton mène au formulaire public dédié. --}}
    <section class="section-space contact-invitation" aria-labelledby="contact-title">
        <div class="container text-center">
            <p class="section-eyebrow">Échangeons</p>
            <h2 id="contact-title">Une question sur ABIWEIN ou l’exploitation&nbsp;?</h2>
            <p>Présentez-nous votre demande à travers le formulaire de contact.</p>
            <a class="btn btn-brand btn-lg" href="{{ route('contact') }}">Contacter ABIWEIN</a>
        </div>
    </section>
@endsection
