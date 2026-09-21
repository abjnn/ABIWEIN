{{-- FORMULAIRE CLIENT : cette vue partielle est partagée par la création et la modification. --}}
<div class="row g-4">
    {{-- Ce champ contrôle le nom affiché dans les ventes et les créances. --}}
    <div class="col-md-8"><label class="form-label" for="name">Nom complet ou raison sociale</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $client?->name) }}" maxlength="150" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Ce sélecteur distingue les particuliers des entreprises dans le répertoire. --}}
    <div class="col-md-4"><label class="form-label" for="type">Type de client</label><select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required><option value="individual" @selected(old('type', $client?->type ?? 'individual') === 'individual')>Particulier</option><option value="business" @selected(old('type', $client?->type) === 'business')>Entreprise</option></select>@error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Le téléphone reste facultatif mais facilite les relances de créances. --}}
    <div class="col-md-6"><label class="form-label" for="phone">Téléphone</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" value="{{ old('phone', $client?->phone) }}" maxlength="30">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- L'e-mail, lorsqu'il est fourni, doit rester unique dans le répertoire. --}}
    <div class="col-md-6"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $client?->email) }}" maxlength="150">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Cette zone accepte l'adresse de livraison ou de facturation du client. --}}
    <div class="col-12"><label class="form-label" for="address">Adresse</label><textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" maxlength="1000">{{ old('address', $client?->address) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

    {{-- Une fiche inactive reste dans l'historique mais disparaît du formulaire des nouvelles ventes. --}}
    <div class="col-12"><div class="form-check"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $client?->is_active ?? true))><label class="form-check-label" for="is_active">Client actif et disponible pour les nouvelles ventes</label></div></div>

    {{-- Le bouton utilise le libellé fourni par la vue qui inclut ce formulaire. --}}
    <div class="col-12"><button class="btn btn-private-primary" type="submit">{{ $submitLabel }}</button></div>
</div>
