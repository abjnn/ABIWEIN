@extends('layouts.private')

@section('title', $report->reference)
@section('page_title', 'Consultation du rapport')

@section('content')
    {{-- Les actions restent hors du document afin de ne pas apparaître à l'impression. --}}
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3 no-print">
        <a class="btn btn-private-secondary" href="{{ route('reports.index') }}">Retour à la liste</a>
        <a class="btn btn-private-primary" href="{{ route('reports.pdf', $report) }}">Télécharger le PDF</a>
    </div>

    @include('reports._document-styles')
    @include('reports._document', ['logoSource' => asset('brand/abiwein-logo.png')])
@endsection
