@extends('layouts.private')

@section('title', 'Assistant ABIWEIN')
@section('page_title', 'Assistant ABIWEIN')

@section('content')
    {{-- EN-TÊTE : un texte court remplace les anciennes cartes d'analyse. --}}
    <header class="ai-heading">
        <div>
            <p class="private-kicker">Aide au pilotage</p>
            <h2>Discutez avec les données de l’exploitation.</h2>
            <p>L’assistant lit uniquement des indicateurs agrégés. Il ne modifie aucune donnée et toute décision reste humaine.</p>
        </div>
        <span class="ai-provider-status">
            {{ $providerConfigured ? 'Gemini configuré' : 'Mode local actif' }}
        </span>
    </header>

    {{-- SUGGESTIONS : chaque proposition est une vraie question immédiatement envoyable. --}}
    <section class="ai-suggestions" aria-labelledby="ai-suggestions-title">
        <h3 id="ai-suggestions-title">Exemples de demandes</h3>
        <div class="ai-suggestions__list">
            @foreach ($suggestions as $suggestion)
                <form method="POST" action="{{ route('intelligence.messages.store') }}">
                    @csrf
                    <input name="message" type="hidden" value="{{ $suggestion }}">
                    <button type="submit">{{ $suggestion }}</button>
                </form>
            @endforeach
        </div>
    </section>

    {{-- CONVERSATION : les messages sont affichés dans l'ordre, sans cartes de tableau de bord. --}}
    <section class="ai-conversation" aria-labelledby="ai-conversation-title">
        <div class="ai-conversation__header">
            <h3 id="ai-conversation-title">Conversation</h3>

            @if ($messages->isNotEmpty())
                <form method="POST" action="{{ route('intelligence.conversation.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button class="ai-clear-button" type="submit">Effacer la conversation</button>
                </form>
            @endif
        </div>

        <div class="ai-messages" aria-live="polite">
            @forelse ($messages as $message)
                <article class="ai-message ai-message--{{ $message->role }}">
                    <div class="ai-message__meta">
                        <strong>{{ $message->role === 'assistant' ? 'Assistant ABIWEIN' : 'Vous' }}</strong>
                        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                    </div>
                    <p>{!! nl2br(e($message->content)) !!}</p>
                    @if ($message->role === 'assistant' && $message->provider === 'local')
                        <small>Réponse locale — aucune donnée envoyée à un service externe.</small>
                    @endif
                </article>
            @empty
                <div class="ai-empty-state">
                    <h4>Commencez par une question concrète.</h4>
                    <p>Vous pouvez demander un point sur les bandes, les intrants, la santé, les ventes, les créances ou les rapports.</p>
                </div>
            @endforelse
        </div>

        {{-- SAISIE : le nom du champ reste synchronisé avec StoreChatMessageRequest. --}}
        <form class="ai-composer" method="POST" action="{{ route('intelligence.messages.store') }}">
            @csrf
            <label class="visually-hidden" for="ai-message">Votre demande</label>
            <textarea
                class="form-control @error('message') is-invalid @enderror"
                id="ai-message"
                name="message"
                rows="3"
                maxlength="1000"
                placeholder="Exemple : quels points exigent mon attention aujourd’hui ?"
                required
            >{{ old('message') }}</textarea>
            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="ai-composer__footer">
                <p>Ne saisissez aucune donnée personnelle ni aucun secret.</p>
                <button class="btn btn-brand" type="submit">Envoyer</button>
            </div>
        </form>
    </section>
@endsection
