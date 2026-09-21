<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>{{ $report->reference }} — ABIWEIN</title>
        @include('reports._document-styles')
        <style>
            /* Le document PDF utilise les marges physiques de la feuille A4. */
            @page { margin: 18mm 14mm 16mm; }
            body { margin: 0; padding: 0; background: #fff; }
            .administrative-document { max-width: none; padding: 0; border: 0; }
        </style>
    </head>
    <body>
        @include('reports._document', ['logoSource' => $logoDataUri])
    </body>
</html>
