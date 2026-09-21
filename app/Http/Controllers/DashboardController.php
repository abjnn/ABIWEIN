<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Contracts\View\View;

/** Prépare les indicateurs de synthèse de l’espace privé. */
class DashboardController extends Controller
{
    /** Charge uniquement les données nécessaires au premier écran. */
    public function __invoke(): View
    {
        // Les bandes actives alimentent les deux premiers indicateurs.
        $activeFlocks = Flock::query()
            ->with('currentBuilding')
            ->where('status', 'active')
            ->orderBy('arrival_date')
            ->get();

        // Le solde additionne seulement les créances qui ne sont pas annulées.
        $receivables = Sale::query()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('balance_due');

        return view('dashboard.index', [
            'activeFlocks' => $activeFlocks,
            'totalBirds' => $activeFlocks->sum('current_quantity'),
            'openAlertsCount' => Alert::query()->where('status', 'open')->count(),
            'lowStockCount' => Product::query()->whereColumn('current_stock', '<=', 'alert_threshold')->count(),
            'receivables' => $receivables,
            'recentRecords' => DailyRecord::query()->with('flock')->latest('record_date')->limit(6)->get(),
            'openAlerts' => Alert::query()->where('status', 'open')->latest()->limit(4)->get(),
        ]);
    }
}
