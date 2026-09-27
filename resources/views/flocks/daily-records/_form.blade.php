{{-- RELEVÉ QUOTIDIEN : ces valeurs alimentent directement le tableau de bord et les rapports. --}}
@php($editing = isset($dailyRecord))
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="record_date">Date du relevé</label>
        <input class="form-control @error('record_date') is-invalid @enderror" type="date" id="record_date" name="record_date" value="{{ old('record_date', $editing ? $dailyRecord->record_date->toDateString() : today()->toDateString()) }}" min="{{ $flock->arrival_date->toDateString() }}" max="{{ today()->toDateString() }}" @readonly($editing) required>
        @error('record_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="mortality">Mortalité cumulée du jour</label>
        <input class="form-control @error('mortality') is-invalid @enderror" type="number" id="mortality" name="mortality" value="{{ old('mortality', $dailyRecord->mortality ?? 0) }}" min="0" step="1" required>
        @error('mortality')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="health_status">État sanitaire observé</label>
        <select class="form-select @error('health_status') is-invalid @enderror" id="health_status" name="health_status" required>
            @foreach ($healthStatuses as $status)
                <option value="{{ $status->value }}" @selected(old('health_status', $dailyRecord->health_status ?? 'good') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('health_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="feed_kg">Aliment consommé (kg)</label>
        <input class="form-control @error('feed_kg') is-invalid @enderror" type="number" id="feed_kg" name="feed_kg" value="{{ old('feed_kg', $dailyRecord->feed_kg ?? 0) }}" min="0" step="0.01" required>
        @error('feed_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="water_liters">Eau consommée (L)</label>
        <input class="form-control @error('water_liters') is-invalid @enderror" type="number" id="water_liters" name="water_liters" value="{{ old('water_liters', $dailyRecord->water_liters ?? 0) }}" min="0" step="0.01" required>
        @error('water_liters')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="average_weight_kg">Poids moyen (kg)</label>
        <input class="form-control @error('average_weight_kg') is-invalid @enderror" type="number" id="average_weight_kg" name="average_weight_kg" value="{{ old('average_weight_kg', $dailyRecord->average_weight_kg ?? '') }}" min="0.001" step="0.001">
        @error('average_weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="observations">Observations générales de la journée</label>
        <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="5" maxlength="3000">{{ old('observations', $dailyRecord->observations ?? '') }}</textarea>
        @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
