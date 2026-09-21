@extends('layouts.public')

@section('title', 'Contact — ABIWEIN')
@section('meta_description', 'Contactez ABIWEIN pour toute question concernant la plateforme ou l’exploitation avicole.')

@section('content')
    {{-- EN-TÊTE SIMPLE : aucun visuel décoratif ne détourne l'attention du formulaire. --}}
    <section class="page-heading" aria-labelledby="contact-page-title">
        <div class="container">
            <p class="hero-eyebrow">Contact</p>
            <h1 id="contact-page-title">Parlons de votre demande.</h1>
            <p>Complétez le formulaire. Les champs marqués d’un astérisque sont obligatoires.</p>
        </div>
    </section>

    <section class="section-space contact-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="contact-information">
                        <p class="section-eyebrow">Nous joindre</p>
                        <h2>Une question claire mérite une réponse claire.</h2>
                        <p>Indiquez le sujet de votre demande et les informations nécessaires pour que l’équipe puisse vous répondre.</p>

                        {{-- Chaque coordonnée s'affiche uniquement après configuration dans .env. --}}
                        <dl class="contact-details">
                            @if (config('public_site.phone'))
                                <div><dt>Téléphone</dt><dd><a href="tel:{{ preg_replace('/\s+/', '', config('public_site.phone')) }}">{{ config('public_site.phone') }}</a></dd></div>
                            @endif
                            @if (config('public_site.email'))
                                <div><dt>E-mail</dt><dd><a href="mailto:{{ config('public_site.email') }}">{{ config('public_site.email') }}</a></dd></div>
                            @endif
                            @if (config('public_site.address'))
                                <div><dt>Adresse</dt><dd>{{ config('public_site.address') }}</dd></div>
                            @endif
                            @if (config('public_site.business_hours'))
                                <div><dt>Disponibilité</dt><dd>{{ config('public_site.business_hours') }}</dd></div>
                            @endif
                        </dl>

                        @unless (config('public_site.phone') || config('public_site.email') || config('public_site.address'))
                            <p class="configuration-note">Les coordonnées officielles seront affichées ici dès leur validation. Le formulaire reste disponible.</p>
                        @endunless

                        {{-- Le bouton WhatsApp reste absent tant qu'un numéro réel n'est pas fourni. --}}
                        @if (config('public_site.whatsapp'))
                            <a class="text-link" href="https://wa.me/{{ config('public_site.whatsapp') }}" rel="noopener noreferrer">Écrire sur WhatsApp <span aria-hidden="true">→</span></a>
                        @endif
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form-card">
                        {{-- Message de confirmation renvoyé par ContactMessageController. --}}
                        @if (session('contact_status'))
                            <div class="alert alert-success" role="status">{{ session('contact_status') }}</div>
                        @endif

                        {{-- Résumé général des erreurs, complété sous chaque champ concerné. --}}
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">Certaines informations doivent être corrigées avant l’envoi.</div>
                        @endif

                        <form method="POST" action="{{ route('contact.store') }}" novalidate>
                            @csrf

                            {{-- Champ anti-robot : un visiteur humain ne doit jamais le remplir. --}}
                            <div class="visually-hidden" aria-hidden="true"><label for="website">Site internet</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

                            <div class="row g-4">
                                {{-- MODIFICATION : gardez les attributs name synchronisés avec ContactMessageController. --}}
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Nom complet <span aria-hidden="true">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" autocomplete="name" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Adresse e-mail <span aria-hidden="true">*</span></label>
                                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Téléphone</label>
                                    <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="subject">Sujet <span aria-hidden="true">*</span></label>
                                    <select class="form-select @error('subject') is-invalid @enderror" id="subject" name="subject" required>
                                        <option value="">Sélectionner un sujet</option>
                                        <option value="Information sur ABIWEIN" @selected(old('subject') === 'Information sur ABIWEIN')>Information sur ABIWEIN</option>
                                        <option value="Activités de la ferme" @selected(old('subject') === 'Activités de la ferme')>Activités de la ferme</option>
                                        <option value="Demande commerciale" @selected(old('subject') === 'Demande commerciale')>Demande commerciale</option>
                                        <option value="Autre demande" @selected(old('subject') === 'Autre demande')>Autre demande</option>
                                    </select>
                                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="message">Votre message <span aria-hidden="true">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" maxlength="3000" required>{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input @error('consent') is-invalid @enderror" id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required>
                                        <label class="form-check-label" for="consent">J’accepte que mes informations soient utilisées pour répondre à cette demande.</label>
                                        @error('consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-12"><button class="btn btn-brand btn-lg" type="submit">Envoyer le message</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
