<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Contracts\View\View;

/** Affiche la synthèse commerciale et financière de l'exploitation. */
class CommerceDashboardController extends Controller
{
    /** Calcule les indicateurs à partir des ventes, encaissements et dépenses réels. */
    public function __invoke(): View
    {
        // Les agrégats globaux alimentent les quatre cartes de pilotage.
        $salesTotal = (float) Sale::query()->sum('total_amount');
        $cashCollected = (float) Sale::query()->sum('paid_amount');
        $receivables = (float) Sale::query()->sum('balance_due');
        $expensesTotal = (float) Expense::query()->sum('amount');

        return view('commerce.dashboard', [
            'salesTotal' => $salesTotal,
            'cashCollected' => $cashCollected,
            'receivables' => $receivables,
            'expensesTotal' => $expensesTotal,
            'netCash' => $cashCollected - $expensesTotal,
            // Les six ventes les plus récentes donnent une lecture immédiate de l'activité.
            'recentSales' => Sale::query()->with('client')->latest('sale_date')->latest('id')->limit(6)->get(),
        ]);
    }
}
