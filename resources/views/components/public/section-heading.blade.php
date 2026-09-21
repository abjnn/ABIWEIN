@props([
    'eyebrow',
    'title',
    'intro' => null,
    'align' => 'start',
])

{{--
    TITRE DE SECTION RÉUTILISABLE
    MODIFICATION : align="center" centre le bloc ; align="start" l'aligne à gauche.
--}}
<div {{ $attributes->class(['section-heading', 'section-heading--center' => $align === 'center']) }}>
    <p class="section-eyebrow">{{ $eyebrow }}</p>
    <h2 class="section-title">{{ $title }}</h2>
    @if ($intro)
        <p class="section-intro">{{ $intro }}</p>
    @endif
</div>
