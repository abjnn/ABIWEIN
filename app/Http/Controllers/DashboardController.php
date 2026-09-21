<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\VeterinaryVisit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Prépare les indicateurs de synthèse de l’espace privé. */
class DashboardController extends Controller
{
    /** Charge uniquement les données nécessaires au premier écran. */
    public function __invoke(Request $request): View
    {
        // Le vétérinaire conserve une vue technique sans charger les données financières sensibles.
        $canViewFinancials = $request->user()->canViewOperations();

        // Les bandes actives alimentent les deux premiers indicateurs.
        $activeFlocks = Flock::query()
            ->with('currentBuilding')
            ->where('status', 'active')
            ->orderBy('arrival_date')
            ->get();

        // Le solde additionne seulement les créances qui ne sont pas annulées.
        $receivables = $canViewFinancials
            ? Sale::query()->whereNotIn('status', ['paid', 'cancelled'])->sum('balance_due')
            : 0;

        // Les six derniers mois forment une tendance lisible sans surcharger le tableau de bord.
        $months = collect(range(5, 0))->map(
            fn (int $monthsAgo): CarbonImmutable => CarbonImmutable::now()->startOfMonth()->subMonths($monthsAgo)
        );

        // Une seule lecture par domaine évite une requête différente pour chaque mois affiché.
        $chartSales = $canViewFinancials
            ? Sale::query()
                ->where('sale_date', '>=', $months->first()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->get()
            : collect();

        // Les dépenses sont alignées sur la même période que les ventes.
        $chartExpenses = $canViewFinancials
            ? Expense::query()->where('spent_on', '>=', $months->first()->toDateString())->get()
            : collect();

        // Chaque série partage exactement les mêmes libellés mensuels.
        $financialTrend = [
            'labels' => $months->map(fn (CarbonImmutable $month): string => ucfirst($month->translatedFormat('M')))->all(),
            'sales' => $months->map(fn (CarbonImmutable $month): float => (float) $chartSales
                ->filter(fn (Sale $sale): bool => $sale->sale_date->format('Y-m') === $month->format('Y-m'))
                ->sum('total_amount'))->all(),
            'expenses' => $months->map(fn (CarbonImmutable $month): float => (float) $chartExpenses
                ->filter(fn (Expense $expense): bool => $expense->spent_on->format('Y-m') === $month->format('Y-m'))
                ->sum('amount'))->all(),
        ];

        // Le mois courant alimente les indicateurs financiers placés en tête de page.
        $monthStart = now()->startOfMonth();
        $cashThisMonth = $canViewFinancials
            ? (float) Payment::query()->where('paid_at', '>=', $monthStart)->sum('amount')
            : 0;

        // Les dépenses restent à zéro pour un rôle sans permission financière.
        $expensesThisMonth = $canViewFinancials
            ? (float) Expense::query()->where('spent_on', '>=', $monthStart->toDateString())->sum('amount')
            : 0;

        // Le vétérinaire reçoit seulement les alertes techniques, jamais celles provenant des stocks.
        $openAlertsQuery = Alert::query()
            ->where('status', 'open')
            ->when(! $canViewFinancials, fn ($query) => $query
                ->where(fn ($sourceQuery) => $sourceQuery
                    ->whereNull('source_type')
                    ->orWhere('source_type', '!=', Product::class))
                ->where('type', 'not like', 'stock%'));

        return view('dashboard.index', [
            'activeFlocks' => $activeFlocks,
            'canViewFinancials' => $canViewFinancials,
            'totalBirds' => $activeFlocks->sum('current_quantity'),
            'openAlertsCount' => (clone $openAlertsQuery)->count(),
            'lowStockCount' => $canViewFinancials ? Product::query()->whereColumn('current_stock', '<=', 'alert_threshold')->count() : 0,
            'receivables' => $receivables,
            'cashThisMonth' => $cashThisMonth,
            'expensesThisMonth' => $expensesThisMonth,
            'netCashThisMonth' => $cashThisMonth - $expensesThisMonth,
            'financialTrend' => $financialTrend,
            'upcomingVisitsCount' => VeterinaryVisit::query()->whereDate('next_visit_date', '>=', today())->count(),
            'recentRecords' => DailyRecord::query()->with('flock')->latest('record_date')->limit(6)->get(),
            'openAlerts' => $openAlertsQuery->latest()->limit(4)->get(),
        ]);
    }
}
