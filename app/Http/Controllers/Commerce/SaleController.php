<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreSaleRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Commerce\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Orchestre l'affichage et la création des ventes au comptant ou à crédit. */
class SaleController extends Controller
{
    /** Affiche le journal des ventes avec des filtres simples. */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('recherche', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('statut', '');

        $sales = Sale::query()
            ->with('client')
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                // Le regroupement conserve les filtres de type et de statut sur les deux branches de recherche.
                $searchQuery->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
            }))
            ->when(in_array($type, ['cash', 'credit'], true), fn ($query) => $query->where('sale_type', $type))
            ->when(in_array($status, ['paid', 'partial', 'unpaid'], true), fn ($query) => $query->where('status', $status))
            ->latest('sale_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('commerce.sales.index', compact('sales', 'search', 'type', 'status'));
    }

    /** Fournit au formulaire les clients actifs et les produits vendables. */
    public function create(): View
    {
        return view('commerce.sales.create', [
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->where('current_stock', '>', 0)->orderBy('name')->get(),
        ]);
    }

    /** Confie la transaction complète au service de vente après validation. */
    public function store(StoreSaleRequest $request, SaleService $saleService): RedirectResponse
    {
        $sale = $saleService->create($request->validated(), $request->user());

        return to_route('sales.show', $sale)->with('status', 'La vente a été enregistrée et le stock a été mis à jour.');
    }

    /** Affiche le détail, les lignes et tous les versements d'une vente. */
    public function show(Sale $sale): View
    {
        $sale->load(['client', 'items.product', 'payments.recordedBy', 'creator']);

        return view('commerce.sales.show', compact('sale'));
    }
}
