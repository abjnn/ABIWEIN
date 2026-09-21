@extends('layouts.private')

@section('title', 'Analyse intelligente')
@section('page_title', 'Analyse intelligente')

@section('content')
    {{-- PRÉSENTATION : le mode local et sa limite décisionnelle sont visibles dès l'ouverture. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="intelligence-title">
        <div>
            <p class="private-kicker">{{ $mode }}</p>
            <h2 id="intelligence-title">Aide à la décision pour l’exploitation</h2>
            <p>
                Cette démonstration applique des règles explicites aux données enregistrées dans ABIWEIN.
                Elle n’utilise aucune API externe et ne remplace pas l’avis d’un responsable ou du vétérinaire.
            </p>
        </div>
        <div class="private-intro__aside">
            <span class="private-status private-status--watch">Validation humaine obligatoire</span>
            <p class="private-intro__date">Calcul du {{ $generated_at->translatedFormat('d F Y à H:i') }}</p>
        </div>
    </section>

    {{-- SYNTHÈSE : les compteurs décrivent seulement les domaines réellement autorisés. --}}
    @php
        $criticalCount = collect($analyses)->where('level', 'critical')->count();
        $warningCount = collect($analyses)->where('level', 'warning')->count();
    @endphp
    <section class="private-stats" aria-label="Synthèse de l’analyse">
        <x-private.stat-card label="Domaines analysés" :value="count($analyses)" detail="Données disponibles et autorisées" />
        <x-private.stat-card label="Niveaux critiques" :value="$criticalCount" detail="Contrôles prioritaires proposés" :tone="$criticalCount > 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="À surveiller" :value="$warningCount" detail="Points à faire confirmer" :tone="$warningCount > 0 ? 'alert' : 'default'" />
        <x-private.stat-card label="Décisions automatiques" value="0" detail="Toutes les recommandations sont à valider" />
    </section>

    {{--
        ANALYSES : chaque carte sépare nettement constat, preuves et recommandation.
        L'échappement Blade reste actif sur toutes les valeurs issues de la base.
    --}}
    <div class="row g-3 mb-3">
        @foreach ($analyses as $analysis)
            <div class="col-12 col-xl-6">
                <section class="private-panel h-100" aria-labelledby="analysis-{{ $analysis['domain'] }}">
                    <div class="private-panel__header">
                        <div>
                            <p class="private-kicker">Constat calculé</p>
                            <h2 id="analysis-{{ $analysis['domain'] }}">{{ $analysis['title'] }}</h2>
                        </div>
                        <span class="private-status private-status--{{ $analysis['tone'] }}">
                            Niveau : {{ $analysis['level_label'] }}
                        </span>
                    </div>

                    <div class="private-alert-list">
                        <article>
                            <span class="private-alert-list__severity">Interprétation</span>
                            <p>{{ $analysis['finding'] }}</p>
                        </article>
                    </div>

                    <div class="table-responsive">
                        <table class="table private-table align-middle">
                            <caption class="visually-hidden">Preuves chiffrées pour {{ $analysis['title'] }}</caption>
                            <thead>
                                <tr><th scope="col">Preuve chiffrée</th><th class="text-end" scope="col">Valeur</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($analysis['evidence'] as $label => $value)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end"><strong>{{ $value }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="private-alert-list border-top">
                        <article>
                            <span class="private-alert-list__severity">Recommandation à valider</span>
                            <h3>Proposition, sans exécution automatique</h3>
                            <p>{{ $analysis['recommendation'] }}</p>
                        </article>
                    </div>
                </section>
            </div>
        @endforeach
    </div>

    {{-- GARDE-FOU : rappelle la portée du résultat sans exposer de domaine non autorisé. --}}
    <section class="private-panel" aria-labelledby="method-title">
        <div class="private-panel__header">
            <div>
                <p class="private-kicker">Traçabilité</p>
                <h2 id="method-title">Méthode de démonstration</h2>
            </div>
            <span>Aucun envoi externe</span>
        </div>
        <div class="private-alert-list">
            <article>
                <h3>Règles déterministes et lecture seule</h3>
                <p>
                    Les seuils sont documentés dans le service local. Le calcul lit la base courante, ne modifie
                    aucun enregistrement et ne déclenche ni commande, ni traitement, ni relance client.
                </p>
            </article>
            <article>
                <h3>Périmètre adapté au rôle</h3>
                <p>
                    Les résultats affichés couvrent uniquement les données autorisées pour ce compte.
                    Toute recommandation doit être vérifiée à partir des preuves chiffrées avant décision.
                </p>
            </article>
        </div>
    </section>
@endsection
