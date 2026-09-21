{{-- FORMULAIRE DÉPENSE : utilisé par les pages de création et de correction. --}}
<div class="row g-4">
    {{-- Cette catégorie sert à regrouper les charges dans les futurs rapports. --}}
    <div class="col-md-6"><label class="form-label" for="category">Catégorie</label><input class="form-control @error('category') is-invalid @enderror" id="category" name="category" type="text" value="{{ old('category', $expense?->category) }}" maxlength="80" list="expense-categories" required><datalist id="expense-categories"><option value="Alimentation"><option value="Santé"><option value="Transport"><option value="Entretien"><option value="Énergie"><option value="Personnel"><option value="Autre"></datalist>@error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Le montant est exprimé en francs CFA et doit être strictement positif. --}}
    <div class="col-md-6"><label class="form-label" for="amount">Montant</label><div class="input-group"><input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" value="{{ old('amount', $expense?->amount) }}" min="1" step="1" required><span class="input-group-text">FCFA</span>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>

    {{-- La date décrit le jour réel de la sortie et ne peut pas être future. --}}
    <div class="col-md-6"><label class="form-label" for="spent_on">Date de la dépense</label><input class="form-control @error('spent_on') is-invalid @enderror" id="spent_on" name="spent_on" type="date" value="{{ old('spent_on', $expense?->spent_on?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>@error('spent_on')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Le motif doit permettre de comprendre la charge sans ouvrir une pièce externe. --}}
    <div class="col-12"><label class="form-label" for="description">Motif détaillé</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="255" required>{{ old('description', $expense?->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Ce bouton utilise le texte d'action défini par la page parente. --}}
    <div class="col-12"><button class="btn btn-private-primary" type="submit">{{ $submitLabel }}</button></div>
</div>
