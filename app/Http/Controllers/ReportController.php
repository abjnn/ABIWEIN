<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ReportFilterRequest;
use App\Http\Requests\StoreFarmReportRequest;
use App\Models\FarmReport;
use App\Services\Reports\FarmReportSnapshotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/** Gère l'archive, la lecture et l'export des rapports administratifs ABIWEIN. */
class ReportController extends Controller
{
    /** Affiche une liste compacte triée par date officielle la plus récente. */
    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        $reports = FarmReport::query()
            ->with('generator:id,name')
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('report_date', '<=', $date))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'types' => FarmReport::TYPES,
            'filters' => $filters,
            'canGenerate' => $request->user()?->hasRole(UserRole::FarmManager) === true,
        ]);
    }

    /** Présente le formulaire réservé au responsable d'exploitation. */
    public function create(): View
    {
        return view('reports.create', ['types' => FarmReport::TYPES]);
    }

    /** Produit et archive une photographie des données au moment de la demande. */
    public function store(StoreFarmReportRequest $request, FarmReportSnapshotService $service): RedirectResponse
    {
        $report = $service->generate($request->validated(), $request->user());

        return redirect()
            ->route('reports.show', $report)
            ->with('status', 'Le rapport administratif a été généré et archivé.');
    }

    /** Ouvre le contenu figé sous la forme d'un document administratif. */
    public function show(FarmReport $farmReport): View
    {
        $farmReport->load('generator:id,name');

        return view('reports.show', ['report' => $farmReport]);
    }

    /** Télécharge le même contenu en PDF A4 sans charger de ressource distante. */
    public function download(FarmReport $farmReport): Response
    {
        $farmReport->load('generator:id,name');

        // Le logo est incorporé en base64 : Dompdf n'a donc aucun accès réseau à effectuer.
        $logoPath = public_path('brand/abiwein-logo.png');
        $logoDataUri = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $farmReport,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false);

        return $pdf->download($farmReport->reference.'.pdf');
    }
}
