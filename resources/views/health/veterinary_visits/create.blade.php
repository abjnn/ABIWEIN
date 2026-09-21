@extends('layouts.private')

{{-- Ce titre apparaît dans l’onglet du navigateur. --}}
@section('title', 'Nouvelle visite vétérinaire')
{{-- Ce titre apparaît dans l’en-tête fixe de l’espace privé. --}}
@section('page_title', 'Nouvelle visite vétérinaire')

@section('content')
    {{-- EN-TÊTE : le lien de retour n’enregistre aucune donnée du formulaire. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="create-visit-title">
        <div>
            <p class="private-kicker">Santé et prévention</p>
            <h2 id="create-visit-title">Enregistrer un diagnostic</h2>
            <p>Documentez le constat clinique, les recommandations et la date du prochain contrôle.</p>
        </div>
        <a class="btn btn-private-secondary" href="{{ route('health.visits.index') }}">Retour à l’historique</a>
    </section>

    {{-- CARTE DU FORMULAIRE : le contenu des champs est isolé dans un fichier partiel. --}}
    <section class="private-form-card" aria-label="Formulaire de visite vétérinaire">
        {{-- Ce résumé annonce les erreurs avant les messages détaillés placés sous chaque champ. --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées.</div>
        @endif

        {{-- Tout le code qui envoie la nouvelle visite commence ici. --}}
        <form method="POST" action="{{ route('health.visits.store') }}">
            {{-- Le jeton CSRF bloque les soumissions provenant d’un site externe. --}}
            @csrf

            {{-- Ce fichier partiel contient un bloc clairement commenté pour chaque donnée sanitaire. --}}
            @include('health.veterinary_visits.partials.form-fields')

            {{-- Le bouton suivant constitue l’unique action d’enregistrement définitif. --}}
            <div class="mt-4 d-flex flex-wrap gap-2">
                <button class="btn btn-private-primary" type="submit">Enregistrer la visite</button>
                <a class="btn btn-private-secondary" href="{{ route('health.visits.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
