<?php

namespace App\Http\Controllers\Stock;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockMovementIndexRequest;
use App\Http\Requests\Stock\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Stock\StockAlertService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Enregistre les variations de stock et expose leur journal chronologique. */
class StockMovementController extends Controller
{
    /** Le même service synchronise les alertes après chaque nouvelle quantité. */
    public function __construct(private readonly StockAlertService $alerts) {}

    /** Affiche l’historique filtrable avec le produit et l’auteur de chaque opération. */
    public function index(StockMovementIndexRequest $request): View
    {
        $filters = $request->validated();

        $movements = StockMovement::query()
            ->with(['product', 'recordedBy'])
            ->when($filters['product_id'] ?? null, fn (Builder $query, int|string $productId): Builder => $query->where('product_id', $productId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '<=', $date))
            ->latest('occurred_at')
            ->paginate(25)
            ->withQueryString();

        return view('stocks.movements.index', [
            'movements' => $movements,
            'filters' => $filters,
            'products' => Product::query()->orderBy('name')->get(['id', 'code', 'name']),
            'types' => StockMovementType::cases(),
        ]);
    }

    /** Affiche le formulaire et préselectionne le produit transmis dans l’URL si possible. */
    public function create(Request $request): View
    {
        $this->ensureManagerAccess($request);

        return view('stocks.movements.create', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'types' => StockMovementType::cases(),
            'selectedProductId' => $request->integer('product') ?: null,
        ]);
    }

    /**
     * Calcule la nouvelle quantité sous verrou de base de données.
     * Ce verrou empêche deux utilisateurs de valider une sortie sur le même ancien stock.
     */
    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $movementType = StockMovementType::from($validated['type']);
        $quantityHundredths = (int) round((float) $validated['quantity'] * 100);

        $product = DB::transaction(function () use ($validated, $movementType, $quantityHundredths, $request): Product {
            $product = Product::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($validated['product_id']);

            $stockBeforeHundredths = (int) round((float) $product->current_stock * 100);

            // Chaque type utilise une règle volontairement explicite et testable.
            $stockAfterHundredths = match ($movementType) {
                StockMovementType::Entry => $stockBeforeHundredths + $quantityHundredths,
                StockMovementType::Exit => $stockBeforeHundredths - $quantityHundredths,
                StockMovementType::Adjustment => $quantityHundredths,
            };

            if ($stockAfterHundredths < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "La sortie dépasse le stock disponible de {$product->current_stock} {$product->unit}.",
                ]);
            }

            // Le journal est créé avant la mise à jour afin que les deux écritures soient atomiques.
            $product->stockMovements()->create([
                'type' => $movementType->value,
                'quantity' => number_format($quantityHundredths / 100, 2, '.', ''),
                'occurred_at' => $validated['occurred_at'],
                'reason' => $validated['reason'],
                'recorded_by' => $request->user()->id,
            ]);

            $product->update([
                'current_stock' => number_format($stockAfterHundredths / 100, 2, '.', ''),
            ]);

            $this->alerts->sync($product->fresh());

            return $product;
        });

        return to_route('stocks.show', $product)
            ->with('status', 'Le mouvement de stock a été enregistré.');
    }

    /** Sécurise également les actions appelées hors d’un FormRequest. */
    private function ensureManagerAccess(Request $request): void
    {
        abort_unless($request->user()?->hasRole(UserRole::FarmManager), 403);
    }
}
