@extends('layouts.private')

@section('title', $sale->reference)
@section('page_title', 'Détail de la vente')

@section('content')
    {{-- EN-TÊTE : identifie la vente et permet de revenir au journal. --}}
    <section class="private-intro private-intro--compact" aria-labelledby="sale-title"><div><p class="private-kicker">{{ $sale->sale_type === 'cash' ? 'Vente au comptant' : 'Vente à crédit' }}</p><h2 id="sale-title">{{ $sale->reference }}</h2><p>{{ $sale->client?->name ?? 'Client comptoir' }} — {{ $sale->sale_date->format('d/m/Y') }}</p></div><a class="btn btn-private-secondary" href="{{ route('sales.index') }}">Retour aux ventes</a></section>

    @include('commerce._navigation')

    {{-- RÉSUMÉ : ces montants sont recalculés après chaque versement. --}}
    <dl class="private-summary-strip" aria-label="Montants de la vente">
        <div><dt>Total</dt><dd>{{ number_format((float) $sale->total_amount, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Encaissé</dt><dd>{{ number_format((float) $sale->paid_amount, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Solde</dt><dd>{{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA</dd></div>
        <div><dt>Statut</dt><dd>{{ ['paid' => 'Payée', 'partial' => 'Partielle', 'unpaid' => 'Impayée'][$sale->status] }}</dd></div>
    </dl>

    <div class="private-grid private-grid--main">
        {{-- LIGNES : conserve le libellé, la quantité et le prix appliqués lors de la vente. --}}
        <section class="private-panel" aria-labelledby="sale-items-title">
            <div class="private-panel__header"><div><p class="private-kicker">Poulets</p><h2 id="sale-items-title">Détail vendu</h2></div><span>{{ $sale->reference }}</span></div>
            <div class="table-responsive"><table class="table private-table align-middle"><thead><tr><th>Désignation</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr></thead><tbody>@foreach ($sale->items as $item)<tr><td><strong>{{ $item->description }}</strong></td><td>{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td><td>{{ number_format((float) $item->unit_price, 0, ',', ' ') }} FCFA</td><td>{{ number_format((float) $item->line_total, 0, ',', ' ') }} FCFA</td></tr>@endforeach</tbody></table></div>
        </section>

        {{-- PAIEMENT : le formulaire disparaît lorsque le solde atteint zéro. --}}
        <section class="private-form-card" aria-labelledby="payment-title">
            <p class="private-kicker">Caisse</p><h2 class="h5" id="payment-title">{{ (float) $sale->balance_due > 0 ? 'Ajouter un versement' : 'Vente soldée' }}</h2>
            @if ((float) $sale->balance_due > 0 && auth()->user()->isFarmManager())
                <p class="text-muted">Seules les espèces sont acceptées. Maximum : {{ number_format((float) $sale->balance_due, 0, ',', ' ') }} FCFA.</p>
                {{-- Le jeton CSRF protège l'encaissement contre les requêtes externes. --}}
                <form class="vstack gap-3" method="POST" action="{{ route('sales.payments.store', $sale) }}">
                    @csrf
                    {{-- Ce champ est comparé au solde sous verrou avant toute écriture. --}}
                    <div><label class="form-label" for="amount">Montant encaissé</label><input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" value="{{ old('amount') }}" min="1" max="{{ $sale->balance_due }}" step="1" required>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    {{-- La date et l'heure ne peuvent pas être situées dans le futur. --}}
                    <div><label class="form-label" for="paid_at">Date et heure</label><input class="form-control @error('paid_at') is-invalid @enderror" id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>@error('paid_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    {{-- La référence facultative accepte un numéro de reçu papier ; sinon ABIWEIN en génère une. --}}
                    <div><label class="form-label" for="reference">Référence du reçu</label><input class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference" type="text" value="{{ old('reference') }}" maxlength="80" placeholder="Facultatif">@error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-private-primary" type="submit">Confirmer l'encaissement</button>
                </form>
            @elseif ((float) $sale->balance_due <= 0)
                <p class="mb-0 text-muted">Aucun autre versement n'est attendu pour cette vente.</p>
            @else
                <p class="mb-0 text-muted">Le responsable d'exploitation enregistrera le prochain versement. Votre accès est consultatif.</p>
            @endif
        </section>
    </div>

    {{-- HISTORIQUE : tous les reçus en espèces restent consultables et attribués à leur auteur. --}}
    <section class="private-panel" aria-labelledby="payments-title">
        <div class="private-panel__header"><div><p class="private-kicker">Traçabilité</p><h2 id="payments-title">Historique des versements</h2></div><span>Espèces uniquement</span></div>
        <div class="table-responsive"><table class="table private-table align-middle"><thead><tr><th>Reçu</th><th>Date</th><th>Enregistré par</th><th>Montant</th></tr></thead><tbody>@forelse ($sale->payments->sortByDesc('paid_at') as $payment)<tr><td><strong>{{ $payment->reference }}</strong><small>Espèces</small></td><td>{{ $payment->paid_at->format('d/m/Y à H:i') }}</td><td>{{ $payment->recordedBy?->name ?? 'Utilisateur supprimé' }}</td><td>{{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA</td></tr>@empty<tr><td class="private-empty" colspan="4">Aucun versement n'a encore été enregistré.</td></tr>@endforelse</tbody></table></div>
    </section>
@endsection
