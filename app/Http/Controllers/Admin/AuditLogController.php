<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\FlockActivity;
use App\Models\FlockBuildingAssignment;
use App\Models\FlockMovement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Permet aux administrateurs de rechercher les mutations sensibles. */
class AuditLogController extends Controller
{
    /** La clé courte est utilisée dans l’URL, jamais un nom de classe fourni par le navigateur. */
    private const AUDITABLE_TYPES = [
        'user' => ['model' => User::class, 'label' => 'Utilisateur'],
        'product' => ['model' => Product::class, 'label' => 'Produit'],
        'stock_movement' => ['model' => StockMovement::class, 'label' => 'Mouvement de stock'],
        'veterinary_visit' => ['model' => VeterinaryVisit::class, 'label' => 'Visite vétérinaire'],
        'client' => ['model' => Client::class, 'label' => 'Client'],
        'sale' => ['model' => Sale::class, 'label' => 'Vente'],
        'payment' => ['model' => Payment::class, 'label' => 'Paiement'],
        'expense' => ['model' => Expense::class, 'label' => 'Dépense'],
        'flock' => ['model' => Flock::class, 'label' => 'Bande'],
        'daily_record' => ['model' => DailyRecord::class, 'label' => 'Relevé quotidien'],
        'flock_activity' => ['model' => FlockActivity::class, 'label' => 'Action de bande'],
        'flock_movement' => ['model' => FlockMovement::class, 'label' => 'Mouvement de bande'],
        'flock_assignment' => ['model' => FlockBuildingAssignment::class, 'label' => 'Affectation de bâtiment'],
    ];

    /** Affiche le journal récent et applique uniquement des filtres autorisés. */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'event' => ['nullable', Rule::in(AuditLog::EVENTS)],
            'type' => ['nullable', Rule::in(array_keys(self::AUDITABLE_TYPES))],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $selectedModel = isset($filters['type'])
            ? self::AUDITABLE_TYPES[$filters['type']]['model']
            : null;
        $selectedType = $selectedModel ? (new $selectedModel)->getMorphClass() : null;

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($selectedType, fn ($query, $type) => $query->where('auditable_type', $type))
            ->when($filters['actor_id'] ?? null, fn ($query, $actorId) => $query->where('actor_id', $actorId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        // Le sélecteur ne propose que les comptes déjà présents dans le journal.
        $actors = User::query()
            ->whereIn('id', AuditLog::query()->select('actor_id')->whereNotNull('actor_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $typeLabels = collect(self::AUDITABLE_TYPES)
            ->mapWithKeys(function (array $type): array {
                $model = $type['model'];

                return [(new $model)->getMorphClass() => $type['label']];
            })
            ->all();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actors' => $actors,
            'filters' => $filters,
            'auditableTypes' => self::AUDITABLE_TYPES,
            'typeLabels' => $typeLabels,
        ]);
    }
}
