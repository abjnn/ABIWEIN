@props(['label', 'value', 'detail' => null, 'tone' => 'default'])

{{-- INDICATEUR COMPACT : chaque instance produit une seule ligne dans la vue d'ensemble. --}}
<div {{ $attributes->class(['private-metric', 'private-metric--alert' => $tone === 'alert']) }}>
    {{-- Le libellé et son explication restent groupés à gauche pour faciliter le balayage visuel. --}}
    <dt>
        {{ $label }}
        @if ($detail)
            <small>{{ $detail }}</small>
        @endif
    </dt>
    {{-- La valeur est isolée à droite ; modifier value change uniquement ce nombre. --}}
    <dd>{{ $value }}</dd>
</div>
