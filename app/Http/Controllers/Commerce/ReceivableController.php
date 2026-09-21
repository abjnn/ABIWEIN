<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Affiche les ventes dont le solde constitue encore une créance client. */
class ReceivableController extends Controller
{
    /** Présente les créances par ancienneté et calcule leur total global. */
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('recherche', ''));

        $baseQuery = Sale::query()->where('balance_due', '>', 0);
        $totalDue = (clone $baseQuery)->sum('balance_due');

        $receivables = $baseQuery
            ->with('client')
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                // Les parenthèses garantissent que la condition "solde supérieur à zéro" reste obligatoire.
                $searchQuery->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
            }))
            ->oldest('sale_date')
            ->paginate(15)
            ->withQueryString();

        return view('commerce.receivables.index', compact('receivables', 'search', 'totalDue'));
    }
}
