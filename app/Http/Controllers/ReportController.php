<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/** Construit le rapport synthétique sans modifier les données de l’exploitation. */
class ReportController extends Controller
{
    /** Affiche les indicateurs techniques et financiers de la période choisie. */
    public function index(ReportFilterRequest $request): View
    {
        // MODIFICATION : remplacez startOfMonth() pour changer la période proposée par défaut.
        $dateFrom = CarbonImmutable::parse($request->validated('date_from') ?? now()->startOfMonth()->toDateString())->startOfDay();

        // La fin de journée permet d’inclure les paiements enregistrés avec une heure.
        $dateTo = CarbonImmutable::parse($request->validated('date_to') ?? now()->toDateString())->endOfDay();

        // Les ventes annulées ne participent jamais aux montants du rapport.
        $sales = Sale::query()
            ->with('client')
            // whereDate fonctionne de façon identique avec MySQL et la base SQLite des tests.
            ->whereDate('sale_date', '>=', $dateFrom->toDateString())
            ->whereDate('sale_date', '<=', $dateTo->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('sale_date')
            ->get();

        // Les encaissements sont calculés depuis le journal réel des paiements en espèces.
        $payments = Payment::query()
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->orderByDesc('paid_at')
            ->get();

        // Les dépenses de la période servent au solde de trésorerie simplifié.
        $expenses = Expense::query()
            ->whereDate('spent_on', '>=', $dateFrom->toDateString())
            ->whereDate('spent_on', '<=', $dateTo->toDateString())
            ->orderByDesc('spent_on')
            ->get();

        // Les relevés quotidiens alimentent la partie technique du rapport.
        $dailyRecords = DailyRecord::query()
            ->whereDate('record_date', '>=', $dateFrom->toDateString())
            ->whereDate('record_date', '<=', $dateTo->toDateString())
            ->get();

        // Cette méthode privée aligne les ventes et dépenses sur les mêmes dates du graphique.
        $financialChart = $this->buildFinancialChart($sales, $expenses);

        return view('reports.index', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sales' => $sales,
            'expenses' => $expenses,
            'totalSales' => (float) $sales->sum('total_amount'),
            'cashCollected' => (float) $payments->sum('amount'),
            'totalExpenses' => (float) $expenses->sum('amount'),
            'receivables' => (float) $sales->sum('balance_due'),
            'netCash' => (float) $payments->sum('amount') - (float) $expenses->sum('amount'),
            'totalMortality' => (int) $dailyRecords->sum('mortality'),
            'totalFeed' => (float) $dailyRecords->sum('feed_kg'),
            'financialChart' => $financialChart,
            'salesTypeChart' => [
                'labels' => ['Comptant', 'Crédit'],
                'values' => [
                    (float) $sales->where('sale_type', 'cash')->sum('total_amount'),
                    (float) $sales->where('sale_type', 'credit')->sum('total_amount'),
                ],
            ],
        ]);
    }

    /** Produit des séries ordonnées uniquement pour les journées contenant une opération. */
    private function buildFinancialChart(Collection $sales, Collection $expenses): array
    {
        // Les clés utilisent le format ISO afin que le tri chronologique reste fiable.
        $salesByDate = $sales->groupBy(fn (Sale $sale): string => $sale->sale_date->format('Y-m-d'))
            ->map(fn (Collection $items): float => (float) $items->sum('total_amount'));

        // Les dépenses sont regroupées avec le même format pour permettre l’alignement.
        $expensesByDate = $expenses->groupBy(fn (Expense $expense): string => $expense->spent_on->format('Y-m-d'))
            ->map(fn (Collection $items): float => (float) $items->sum('amount'));

        // L’union conserve toutes les dates, même si une seule série contient une valeur.
        $dates = $salesByDate->keys()->merge($expensesByDate->keys())->unique()->sort()->values();

        return [
            'labels' => $dates->map(fn (string $date): string => CarbonImmutable::parse($date)->format('d/m'))->all(),
            'sales' => $dates->map(fn (string $date): float => (float) $salesByDate->get($date, 0))->all(),
            'expenses' => $dates->map(fn (string $date): float => (float) $expensesByDate->get($date, 0))->all(),
        ];
    }
}
