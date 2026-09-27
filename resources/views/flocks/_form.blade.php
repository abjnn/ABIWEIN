{{-- FORMULAIRE BANDE : les champs d'effectif et de bâtiment initial n'apparaissent qu'à la création. --}}
@php($editing = isset($flock))
<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="code">Référence de la bande</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $flock->code ?? '') }}" maxlength="40" required autofocus>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-7">
        <label class="form-label" for="breed">Souche ou race</label>
        <input class="form-control @error('breed') is-invalid @enderror" id="breed" name="breed" value="{{ old('breed', $flock->breed ?? '') }}" maxlength="100" required>
        @error('breed')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="arrival_date">Date d’arrivée</label>
        {{-- La date d'arrivée devient une référence historique non modifiable après la création. --}}
        <input class="form-control @error('arrival_date') is-invalid @enderror" type="date" id="arrival_date" name="arrival_date" value="{{ old('arrival_date', isset($flock) ? $flock->arrival_date->toDateString() : today()->toDateString()) }}" max="{{ today()->toDateString() }}" @readonly($editing) required>
        @error('arrival_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="expected_end_date">Fin prévisionnelle</label>
        <input class="form-control @error('expected_end_date') is-invalid @enderror" type="date" id="expected_end_date" name="expected_end_date" value="{{ old('expected_end_date', isset($flock) ? $flock->expected_end_date?->toDateString() : '') }}">
        @error('expected_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @unless ($editing)
        <div class="col-md-4">
            <label class="form-label" for="initial_quantity">Effectif initial</label>
            <input class="form-control @error('initial_quantity') is-invalid @enderror" type="number" id="initial_quantity" name="initial_quantity" value="{{ old('initial_quantity') }}" min="1" step="1" required>
            @error('initial_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endunless
    <div class="col-md-6">
        <label class="form-label" for="supplier_id">Fournisseur d’origine</label>
        <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
            <option value="">Non renseigné</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $flock->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @unless ($editing)
        <div class="col-md-6">
            <label class="form-label" for="current_building_id">Bâtiment initial</label>
            <select class="form-select @error('current_building_id') is-invalid @enderror" id="current_building_id" name="current_building_id">
                <option value="">Affecter plus tard</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building->id }}" @selected((string) old('current_building_id') === (string) $building->id)>{{ $building->code }} — {{ $building->name }} ({{ number_format($building->capacity, 0, ',', ' ') }} places)</option>
                @endforeach
            </select>
            @error('current_building_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endunless
    <div class="col-12">
        <label class="form-label" for="notes">Observations générales</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4" maxlength="2000">{{ old('notes', $flock->notes ?? '') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
