{{-- FORMULAIRE PRODUIT : ce fichier partagé garantit les mêmes champs en création et modification. --}}
<form method="POST" action="{{ $action }}">
    {{-- Le jeton CSRF protège l’enregistrement contre une requête provenant d’un autre site. --}}
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        {{-- La référence est convertie en majuscules côté serveur et doit rester unique. --}}
        <div class="col-md-5">
            <label class="form-label" for="code">Référence du produit</label>
            <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" type="text" maxlength="40" value="{{ old('code', $product?->code) }}" placeholder="Exemple : ALIM-CROISS" required>
            <div class="form-text">Lettres, chiffres, points et tirets uniquement.</div>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Le nom est celui qui apparaîtra dans les listes et les mouvements. --}}
        <div class="col-md-7">
            <label class="form-label" for="name">Nom du produit</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" maxlength="150" value="{{ old('name', $product?->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- La catégorie structure les recherches et les rapports futurs. --}}
        <div class="col-md-6">
            <label class="form-label" for="category">Catégorie</label>
            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                <option value="">Sélectionner une catégorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(old('category', $product?->category) === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- L’unité accompagne toutes les quantités ; sa modification ne convertit pas l’ancien stock. --}}
        <div class="col-md-6">
            <label class="form-label" for="unit">Unité de gestion</label>
            <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                <option value="">Sélectionner une unité</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->value }}" @selected(old('unit', $product?->unit) === $unit->value)>{{ $unit->label() }}</option>
                @endforeach
            </select>
            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @if (! $product)
            {{-- Le stock initial n’apparaît qu’à la création et génère une première entrée d’historique. --}}
            <div class="col-md-6">
                <label class="form-label" for="initial_stock">Stock initial</label>
                <input class="form-control @error('initial_stock') is-invalid @enderror" id="initial_stock" name="initial_stock" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('initial_stock', '0') }}" required>
                <div class="form-text">Mettez 0 si aucun stock n’est encore disponible.</div>
                @error('initial_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endif

        {{-- Le seuil déclenche une alerte dès que la quantité disponible lui devient inférieure ou égale. --}}
        <div class="col-md-6">
            <label class="form-label" for="alert_threshold">Seuil d’alerte</label>
            <input class="form-control @error('alert_threshold') is-invalid @enderror" id="alert_threshold" name="alert_threshold" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('alert_threshold', $product?->alert_threshold ?? '0') }}" required>
            @error('alert_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- La date reste facultative pour les équipements et autres produits non périssables. --}}
        <div class="col-md-6">
            <label class="form-label" for="expires_at">Date d’expiration</label>
            <input class="form-control @error('expires_at') is-invalid @enderror" id="expires_at" name="expires_at" type="date" value="{{ old('expires_at', $product?->expires_at?->format('Y-m-d')) }}">
            <div class="form-text">Laissez vide lorsque le produit n’a pas de date limite.</div>
            @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- La désactivation masque le produit du formulaire de mouvement sans effacer son passé. --}}
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product?->is_active ?? true))>
                <label class="form-check-label" for="is_active">Produit actif et disponible dans les opérations de stock</label>
            </div>
        </div>

        {{-- Cette zone contient les seules actions qui quittent ou enregistrent le formulaire. --}}
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-private-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-private-secondary" href="{{ $cancelUrl }}">Annuler</a>
        </div>
    </div>
</form>
