@extends('layouts.app')

@section('title', 'ABIWEIN — Fondation de l’application')

@section('content')
    <section class="hero-section" id="accueil" aria-labelledby="titre-accueil">
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="eyebrow">Fondation numérique</span>
                    <h1 class="hero-title" id="titre-accueil">Une base claire pour faire grandir ABIWEIN.</h1>
                    <p class="hero-copy">
                        Cette interface pose les repères visuels et techniques de l’application. Elle est conçue
                        pour rester lisible, responsive et cohérente avant l’intégration progressive des fonctions métier.
                    </p>
                    <div class="hero-actions">
                        <a class="btn btn-abiwein" href="#fondation">Découvrir la fondation</a>
                        <a class="btn btn-outline-primary px-4 py-3 fw-bold" href="#identite">Voir la palette</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <figure class="brand-showcase mb-0">
                        <img
                            src="{{ asset('brand/abiwein-logo.png') }}"
                            alt="Logo ABIWEIN représentant une poule, des feuilles, du blé et un réseau de données"
                            width="256"
                            height="256"
                        >
                        <figcaption class="brand-showcase__caption">
                            Élevage avicole, gestion numérique et analyse réunis dans une même identité.
                        </figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space soft-panel" id="fondation" aria-labelledby="titre-fondation">
        <div class="container">
            <p class="section-kicker">Socle de l’interface</p>
            <h2 class="section-title" id="titre-fondation">Des composants prêts à évoluer avec le projet.</h2>
            <p class="section-intro mb-4 mb-lg-5">
                Ce tableau de bord est une référence de conception, pas un écran métier définitif. Il permet de
                valider dès maintenant la mise en page, la navigation, la marque et les visualisations.
            </p>

            <div class="row g-4">
                <div class="col-md-4">
                    <article class="foundation-card">
                        <span class="foundation-card__icon" aria-hidden="true">01</span>
                        <h3 class="h5">Identité cohérente</h3>
                        <p>Le logo et les cinq couleurs officielles sont centralisés pour garantir une présentation homogène.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="foundation-card">
                        <span class="foundation-card__icon" aria-hidden="true">02</span>
                        <h3 class="h5">Interface responsive</h3>
                        <p>La grille Bootstrap adapte naturellement le contenu du téléphone jusqu’aux grands écrans.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="foundation-card">
                        <span class="foundation-card__icon" aria-hidden="true">03</span>
                        <h3 class="h5">Visualisation accessible</h3>
                        <p>Chaque graphique peut être accompagné d’un résumé textuel afin que l’information reste disponible à tous.</p>
                    </article>
                </div>
            </div>

            <div class="row g-4 align-items-stretch mt-2 mt-lg-4">
                <div class="col-lg-7">
                    <article class="chart-card" aria-labelledby="titre-graphique">
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-start mb-3">
                            <div>
                                <p class="section-kicker mb-1">Composant graphique</p>
                                <h3 class="h4 mb-1" id="titre-graphique">Palette ABIWEIN</h3>
                                <p class="text-body-secondary mb-0 small">Jeu égalitaire de démonstration, sans données métier.</p>
                            </div>
                            <span class="badge rounded-pill text-bg-light border text-primary">Chart.js</span>
                        </div>

                        <div class="chart-stage">
                            <canvas
                                data-abiwein-brand-chart
                                role="img"
                                aria-label="Diagramme circulaire présentant à parts égales les cinq couleurs de la palette ABIWEIN"
                                aria-describedby="resume-graphique"
                            >
                                Le diagramme représente à parts égales les cinq couleurs officielles d’ABIWEIN.
                            </canvas>
                        </div>

                        <div id="resume-graphique">
                            <p class="visually-hidden">Résumé textuel du diagramme :</p>
                            <ul class="chart-summary">
                                <li><span class="color-dot" style="background:#31380F"></span>Vert olive foncé</li>
                                <li><span class="color-dot" style="background:#798057"></span>Vert sauge</li>
                                <li><span class="color-dot" style="background:#E2D4B9"></span>Beige clair</li>
                                <li><span class="color-dot" style="background:#C7AF97"></span>Beige taupe</li>
                                <li><span class="color-dot" style="background:#D8C7B5"></span>Beige rosé</li>
                            </ul>
                            <p class="small text-body-secondary mt-3 mb-0">
                                Les cinq couleurs sont représentées à parts égales uniquement pour valider l’intégration du composant.
                            </p>
                        </div>
                    </article>
                </div>

                <div class="col-lg-5">
                    <aside class="chart-card" aria-labelledby="titre-principes">
                        <p class="section-kicker mb-1">Principes intégrés</p>
                        <h3 class="h4" id="titre-principes">Une fondation maintenable</h3>
                        <ul class="status-list">
                            <li>Mise en page Blade réutilisable</li>
                            <li>Composants Bootstrap responsives</li>
                            <li>Thème centralisé avec Sass</li>
                            <li>Navigation au clavier et contrastes soignés</li>
                            <li>Graphique doublé d’un résumé textuel</li>
                        </ul>
                    </aside>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="identite" aria-labelledby="titre-identite">
        <div class="container">
            <p class="section-kicker">Identité visuelle</p>
            <h2 class="section-title" id="titre-identite">Une palette naturelle, sobre et harmonieuse.</h2>
            <p class="section-intro mb-4 mb-lg-5">
                Les teintes vertes évoquent la croissance et l’environnement agricole ; les beiges apportent chaleur,
                équilibre et lisibilité à l’ensemble de l’interface.
            </p>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3">
                <div class="col">
                    <article class="palette-card">
                        <div class="palette-swatch" style="background:#31380F" aria-label="Aperçu du vert olive foncé"></div>
                        <div class="palette-meta"><strong>Vert olive foncé</strong><code>#31380F</code></div>
                    </article>
                </div>
                <div class="col">
                    <article class="palette-card">
                        <div class="palette-swatch" style="background:#798057" aria-label="Aperçu du vert sauge"></div>
                        <div class="palette-meta"><strong>Vert sauge</strong><code>#798057</code></div>
                    </article>
                </div>
                <div class="col">
                    <article class="palette-card">
                        <div class="palette-swatch" style="background:#E2D4B9" aria-label="Aperçu du beige clair"></div>
                        <div class="palette-meta"><strong>Beige clair</strong><code>#E2D4B9</code></div>
                    </article>
                </div>
                <div class="col">
                    <article class="palette-card">
                        <div class="palette-swatch" style="background:#C7AF97" aria-label="Aperçu du beige taupe"></div>
                        <div class="palette-meta"><strong>Beige taupe</strong><code>#C7AF97</code></div>
                    </article>
                </div>
                <div class="col">
                    <article class="palette-card">
                        <div class="palette-swatch" style="background:#D8C7B5" aria-label="Aperçu du beige rosé"></div>
                        <div class="palette-meta"><strong>Beige rosé</strong><code>#D8C7B5</code></div>
                    </article>
                </div>
            </div>
        </div>
    </section>
@endsection
