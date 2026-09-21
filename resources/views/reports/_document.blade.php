{{-- Ce fragment unique garantit que l'écran et le PDF présentent exactement les mêmes données figées. --}}
<article class="administrative-document">
    <header class="administrative-document__header">
        <div class="administrative-document__brand">
            @if (!empty($logoSource))
                <img src="{{ $logoSource }}" alt="Logo ABIWEIN">
            @endif
            <div class="administrative-document__brand-copy">
                <h1>ABIWEIN</h1>
                <p>Gestion intégrée de l’exploitation avicole</p>
            </div>
        </div>
        <div class="administrative-document__identity">
            <p><strong>Document administratif</strong></p>
            <p>Généré le {{ $report->created_at->format('d/m/Y à H:i') }}</p>
        </div>
    </header>

    <h1 class="administrative-document__title">{{ $report->title }}</h1>
    <p class="administrative-document__reference">Référence : <strong>{{ $report->reference }}</strong></p>

    {{-- Le cartouche identifie le document avant son contenu opérationnel. --}}
    <table class="administrative-document__meta">
        <tbody>
            <tr><th>Nature</th><td>{{ $report->typeLabel() }}</td><th>Date</th><td>{{ $report->report_date->format('d/m/Y') }}</td></tr>
            <tr><th>Période</th><td>{{ $report->period_start->format('d/m/Y') }} au {{ $report->period_end->format('d/m/Y') }}</td><th>Établi par</th><td>{{ $report->generator?->name ?? 'Compte supprimé' }}</td></tr>
        </tbody>
    </table>

    <p class="administrative-document__purpose">{{ data_get($report->snapshot, 'purpose') }}</p>

    {{-- Chaque section provient du snapshot JSON, jamais d'une requête recalculée à l'ouverture. --}}
    @foreach (data_get($report->snapshot, 'sections', []) as $section)
        <section class="administrative-section">
            <h2>{{ $loop->iteration }}. {{ $section['title'] }}</h2>

            @foreach ($section['paragraphs'] ?? [] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach

            @if (!empty($section['metrics']))
                <table class="administrative-metrics">
                    <tbody>
                        @foreach ($section['metrics'] as $metric)
                            <tr><th>{{ $metric['label'] }}</th><td>{{ $metric['value'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @foreach ($section['tables'] ?? [] as $table)
                <table class="administrative-table">
                    <caption>{{ $table['title'] }}</caption>
                    <thead>
                        <tr>
                            @foreach ($table['headers'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($table['rows'] as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td class="administrative-empty" colspan="{{ count($table['headers']) }}">Aucune donnée enregistrée pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach
        </section>
    @endforeach

    {{-- Les emplacements de visa rendent le PDF directement imprimable et signable. --}}
    <div class="administrative-signatures">
        <div><strong>Le responsable d’exploitation</strong></div>
        <div><strong>Visa de l’administrateur</strong></div>
    </div>

    <footer class="administrative-footer">
        ABIWEIN — {{ $report->reference }} — Données figées lors de la génération du document
    </footer>
</article>
