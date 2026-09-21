<?php

namespace App\Http\Controllers\Health;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\IndexVeterinaryVisitRequest;
use App\Http\Requests\Health\StoreVeterinaryVisitRequest;
use App\Models\Flock;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pilote la consultation et l’enregistrement des visites sanitaires ABIWEIN. */
class VeterinaryVisitController extends Controller
{
    /** Affiche l’historique filtrable des visites et leurs prochaines échéances. */
    public function index(IndexVeterinaryVisitRequest $request): View
    {
        // validated() garantit que seuls les filtres attendus atteignent la requête SQL.
        $filters = $request->validated();

        // La requête charge les relations en une fois afin d’éviter les requêtes répétées.
        $visits = VeterinaryVisit::query()
            ->with(['flock.currentBuilding', 'veterinarian'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                // Le groupe évite que les conditions OR contournent les autres filtres.
                $query->where(function ($query) use ($search): void {
                    $query->where('diagnosis', 'like', "%{$search}%")
                        ->orWhere('recommendations', 'like', "%{$search}%")
                        ->orWhereHas('flock', fn ($query) => $query->where('code', 'like', "%{$search}%"));
                });
            })
            // Ce filtre affiche uniquement la bande choisie.
            ->when($filters['flock_id'] ?? null, fn ($query, $flockId) => $query->where('flock_id', $flockId))
            // Ce filtre affiche uniquement les visites signées par le vétérinaire choisi.
            ->when($filters['veterinarian_id'] ?? null, fn ($query, $veterinarianId) => $query->where('veterinarian_id', $veterinarianId))
            // Ces deux filtres délimitent la période de consultation.
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('visit_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('visit_date', '<=', $date))
            // Les fiches les plus récentes restent visibles en premier.
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('health.veterinary_visits.index', [
            // Les visites constituent les lignes du tableau principal.
            'visits' => $visits,
            // La liste des bandes alimente le filtre sans charger de colonnes inutiles.
            'flocks' => Flock::query()->orderByDesc('arrival_date')->get(['id', 'code', 'status']),
            // Seuls les vétérinaires actifs apparaissent dans le filtre.
            'veterinarians' => $this->activeVeterinarians(),
            // Ce compteur alimente l’indicateur de contrôles à venir.
            'upcomingCount' => VeterinaryVisit::query()
                ->whereNotNull('next_visit_date')
                ->whereDate('next_visit_date', '>=', today())
                ->count(),
            // Cette permission contrôle uniquement l’affichage du bouton de création.
            'canCreate' => $this->canCreate($request->user()),
        ]);
    }

    /** Affiche le formulaire de saisie réservé aux profils habilités. */
    public function create(Request $request): View
    {
        // La vérification dans le contrôleur complète la protection définie dans les routes.
        abort_unless($this->canCreate($request->user()), 403);

        return view('health.veterinary_visits.create', [
            // Les bandes archivées sont exclues pour éviter une nouvelle saisie accidentelle.
            'flocks' => Flock::query()
                ->where('status', '!=', 'archived')
                ->orderByDesc('arrival_date')
                ->get(['id', 'code', 'breed', 'status']),
            // Le vétérinaire connecté sera assigné automatiquement par le FormRequest.
            'veterinarians' => $this->activeVeterinarians(),
        ]);
    }

    /** Enregistre une visite validée puis ouvre immédiatement sa fiche. */
    public function store(StoreVeterinaryVisitRequest $request): RedirectResponse
    {
        // Le FormRequest fournit uniquement les champs autorisés et déjà contrôlés.
        $visit = VeterinaryVisit::query()->create($request->validated());

        return to_route('health.visits.show', $visit)
            ->with('status', 'La visite vétérinaire a été enregistrée.');
    }

    /** Marque la recommandation comme exécutée par le responsable d'exploitation. */
    public function markRecommendationApplied(Request $request, VeterinaryVisit $veterinaryVisit): RedirectResponse
    {
        abort_unless($request->user()?->isFarmManager(), 403);
        abort_if(blank($veterinaryVisit->recommendations), 422, 'Cette visite ne contient aucune recommandation à appliquer.');

        // Une seconde validation ne remplace jamais la première preuve d'exécution.
        if ($veterinaryVisit->recommendation_status === 'applied') {
            return back()->with('status', 'Cette recommandation est déjà marquée comme appliquée.');
        }

        // L'horodatage et l'auteur rendent la validation opérationnelle vérifiable.
        $veterinaryVisit->update([
            'recommendation_status' => 'applied',
            'recommendation_applied_at' => now(),
            'recommendation_applied_by' => $request->user()->getKey(),
        ]);

        return back()->with('status', 'La recommandation a été marquée comme appliquée.');
    }

    /** Présente le diagnostic complet, les recommandations et le prochain contrôle. */
    public function show(Request $request, VeterinaryVisit $veterinaryVisit): View
    {
        // Tous les rôles métier peuvent lire une fiche, mais aucun autre profil ne passe ce contrôle.
        abort_unless($this->canRead($request->user()), 403);

        // Le chargement explicite prépare toutes les données affichées dans la fiche.
        $veterinaryVisit->load(['flock.currentBuilding', 'veterinarian', 'recommendationAppliedBy']);

        return view('health.veterinary_visits.show', ['visit' => $veterinaryVisit]);
    }

    /** Retourne les comptes vétérinaires actifs utilisés par les listes de sélection. */
    private function activeVeterinarians()
    {
        return User::query()
            ->where('role', UserRole::Veterinarian->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Centralise la permission de lecture du module sanitaire. */
    private function canRead(?User $user): bool
    {
        return $user !== null
            && $user->is_active
            && ($user->hasRole(UserRole::Administrator)
                || $user->hasRole(UserRole::FarmManager)
                || $user->hasRole(UserRole::Veterinarian));
    }

    /** Centralise la permission de création afin de garder la règle facile à modifier. */
    private function canCreate(?User $user): bool
    {
        return $user !== null && $user->is_active && $user->hasRole(UserRole::Veterinarian);
    }
}
