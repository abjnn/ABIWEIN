{{-- CHAMPS DU FORMULAIRE : chaque bloc ci-dessous contrôle une donnée précise de la visite. --}}
<div class="row g-4">
    {{-- Ce sélecteur rattache le diagnostic à une bande non archivée. --}}
    <div class="col-md-6">
        <label class="form-label" for="flock_id">Bande examinée</label>
        <select class="form-select @error('flock_id') is-invalid @enderror" id="flock_id" name="flock_id" required autofocus>
            <option value="">Sélectionner une bande</option>
            @foreach ($flocks as $flock)
                <option value="{{ $flock->id }}" @selected((string) old('flock_id') === (string) $flock->id)>
                    {{ $flock->code }} — {{ $flock->breed }}
                </option>
            @endforeach
        </select>
        @error('flock_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Ce champ indique le jour réel de l’examen et interdit les dates futures côté navigateur. --}}
    <div class="col-md-6">
        <label class="form-label" for="visit_date">Date de la visite</label>
        <input class="form-control @error('visit_date') is-invalid @enderror" id="visit_date" name="visit_date" type="date" value="{{ old('visit_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
        @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if (auth()->user()->isAdministrator())
        {{-- L’administrateur utilise ce sélecteur pour attribuer la fiche au vétérinaire signataire. --}}
        <div class="col-md-6">
            <label class="form-label" for="veterinarian_id">Vétérinaire responsable</label>
            <select class="form-select @error('veterinarian_id') is-invalid @enderror" id="veterinarian_id" name="veterinarian_id" required>
                <option value="">Sélectionner un vétérinaire</option>
                @foreach ($veterinarians as $veterinarian)
                    <option value="{{ $veterinarian->id }}" @selected((string) old('veterinarian_id') === (string) $veterinarian->id)>{{ $veterinarian->name }}</option>
                @endforeach
            </select>
            @error('veterinarian_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        {{-- Pour un vétérinaire connecté, la signature est imposée côté serveur et ne peut pas être modifiée. --}}
        <div class="col-md-6">
            <label class="form-label" for="veterinarian_display">Vétérinaire responsable</label>
            <input class="form-control" id="veterinarian_display" type="text" value="{{ auth()->user()->name }}" disabled>
        </div>
    @endif

    {{-- Cette date facultative planifie le contrôle suivant ; elle doit suivre la visite actuelle. --}}
    <div class="col-md-6">
        <label class="form-label" for="next_visit_date">Prochaine visite prévue</label>
        <input class="form-control @error('next_visit_date') is-invalid @enderror" id="next_visit_date" name="next_visit_date" type="date" value="{{ old('next_visit_date') }}">
        @error('next_visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Ce champ obligatoire contient le diagnostic ou le constat clinique détaillé. --}}
    <div class="col-12">
        <label class="form-label" for="diagnosis">Diagnostic ou constat clinique</label>
        <textarea class="form-control @error('diagnosis') is-invalid @enderror" id="diagnosis" name="diagnosis" rows="6" maxlength="5000" required>{{ old('diagnosis') }}</textarea>
        <div class="form-text">Décrivez les signes observés, l’état général et les conclusions de l’examen.</div>
        @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Ce champ facultatif rassemble les mesures préventives ou correctives recommandées. --}}
    <div class="col-12">
        <label class="form-label" for="recommendations">Recommandations</label>
        <textarea class="form-control @error('recommendations') is-invalid @enderror" id="recommendations" name="recommendations" rows="5" maxlength="5000">{{ old('recommendations') }}</textarea>
        <div class="form-text">Précisez les actions à réaliser, la surveillance nécessaire et les consignes de prévention.</div>
        @error('recommendations')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
