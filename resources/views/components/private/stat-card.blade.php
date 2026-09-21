@props(['label', 'value', 'detail' => null, 'tone' => 'default'])

{{-- MODIFICATION : tone="alert" ajoute un accent d’attention sans changer la valeur. --}}
<article {{ $attributes->class(['private-stat', 'private-stat--alert' => $tone === 'alert']) }}>
    <p>{{ $label }}</p>
    <strong>{{ $value }}</strong>
    @if ($detail)
        <small>{{ $detail }}</small>
    @endif
</article>
