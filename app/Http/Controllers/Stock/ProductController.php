<?php

namespace App\Http\Controllers\Stock;

use App\Enums\ProductCategory;
use App\Enums\StockMovementType;
use App\Enums\StockUnit;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ProductIndexRequest;
use App\Http\Requests\Stock\StoreProductRequest;
use App\Http\Requests\Stock\UpdateProductRequest;
use App\Models\Product;
use App\Services\Stock\StockAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** Gère le catalogue des produits et présente leur niveau de stock. */
class ProductController extends Controller
{
    /** Le service d’alertes est réutilisé après chaque changement pouvant modifier un risque. */
    public function __construct(private readonly StockAlertService $alerts) {}

    /** Affiche les indicateurs, filtres et produits suivis. */
    public function index(ProductIndexRequest $request): View
    {
        $filters = $request->validated();
        // Sans filtre explicite, la page montre seulement les intrants réellement utilisés.
        $selectedStatus = $filters['status'] ?? 'active';
        $today = today()->toDateString();
        $expirationLimit = today()->addDays(30)->toDateString();

        $products = Product::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category): Builder => $query->where('category', $category))
            ->when($selectedStatus === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when($selectedStatus === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when($selectedStatus === 'low', fn (Builder $query): Builder => $query->where('is_active', true)->whereColumn('current_stock', '<=', 'alert_threshold'))
            ->when($selectedStatus === 'expired', fn (Builder $query): Builder => $query->where('is_active', true)->whereDate('expires_at', '<', $today))
            ->when($selectedStatus === 'expiring', fn (Builder $query): Builder => $query->where('is_active', true)->whereBetween('expires_at', [$today, $expirationLimit]))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Ces compteurs ignorent les filtres afin de toujours présenter l’état global du stock.
        $statistics = [
            'active' => Product::query()->where('is_active', true)->count(),
            'low' => Product::query()->where('is_active', true)->whereColumn('current_stock', '<=', 'alert_threshold')->count(),
            'expired' => Product::query()->where('is_active', true)->whereDate('expires_at', '<', $today)->count(),
            'expiring' => Product::query()->where('is_active', true)->whereBetween('expires_at', [$today, $expirationLimit])->count(),
        ];

        return view('stocks.products.index', [
            'products' => $products,
            'statistics' => $statistics,
            'filters' => array_merge(['status' => $selectedStatus], $filters),
            'categories' => ProductCategory::cases(),
        ]);
    }

    /** Affiche un formulaire distinct pour créer un produit. */
    public function create(Request $request): View
    {
        $this->ensureManagerAccess($request);

        return view('stocks.products.create', [
            'categories' => ProductCategory::cases(),
            'units' => StockUnit::cases(),
        ]);
    }

    /** Crée le produit et journalise son éventuel stock initial dans une transaction. */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $initialStock = round((float) $validated['initial_stock'], 2);
        $attributes = Arr::except($validated, ['initial_stock', 'is_active']);
        $attributes['current_stock'] = number_format($initialStock, 2, '.', '');
        $attributes['is_active'] = $request->boolean('is_active');

        $product = DB::transaction(function () use ($attributes, $initialStock, $request): Product {
            $product = Product::query()->create($attributes);

            // Un stock initial positif devient une entrée consultable dans l’historique.
            if ($initialStock > 0) {
                $product->stockMovements()->create([
                    'type' => StockMovementType::Entry->value,
                    'quantity' => number_format($initialStock, 2, '.', ''),
                    'occurred_at' => now(),
                    'reason' => 'Stock initial lors de la création du produit',
                    'recorded_by' => $request->user()->id,
                ]);
            }

            $this->alerts->sync($product);

            return $product;
        });

        return to_route('stocks.show', $product)
            ->with('status', 'Le produit et son stock initial ont été enregistrés.');
    }

    /** Présente la fiche du produit et son historique paginé. */
    public function show(Request $request, Product $product): View
    {
        // La route autorise l'administrateur à consulter cette fiche sans action d'écriture.

        return view('stocks.products.show', [
            'product' => $product,
            'movements' => $product->stockMovements()
                ->with('recordedBy')
                ->latest('occurred_at')
                ->paginate(12),
        ]);
    }

    /** Affiche le formulaire de modification sans exposer le stock courant. */
    public function edit(Request $request, Product $product): View
    {
        $this->ensureManagerAccess($request);

        return view('stocks.products.edit', [
            'product' => $product,
            'categories' => ProductCategory::cases(),
            'units' => StockUnit::cases(),
        ]);
    }

    /** Met à jour les informations descriptives puis recalcule les alertes. */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['is_active']);
        $attributes['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($product, $attributes): void {
            $product->update($attributes);
            $this->alerts->sync($product->fresh());
        });

        return to_route('stocks.show', $product)
            ->with('status', 'Les informations du produit ont été mises à jour.');
    }

    /** Active ou désactive un produit sans supprimer son historique. */
    public function toggleStatus(Request $request, Product $product): RedirectResponse
    {
        $this->ensureManagerAccess($request);

        DB::transaction(function () use ($product): void {
            $product->update(['is_active' => ! $product->is_active]);
            $this->alerts->sync($product->fresh());
        });

        $message = $product->fresh()->is_active ? 'Le produit a été réactivé.' : 'Le produit a été désactivé.';

        return back()->with('status', $message);
    }

    /** Renforce la sécurité si une route est ajoutée sans le middleware de rôle attendu. */
    private function ensureManagerAccess(Request $request): void
    {
        abort_unless($request->user()?->hasRole(UserRole::FarmManager), 403);
    }
}
