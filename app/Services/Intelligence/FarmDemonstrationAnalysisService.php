<?php

namespace App\Services\Intelligence;

use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Produit une aide à la décision locale à partir des données ABIWEIN.
 *
 * Ce mode de démonstration n'appelle aucun modèle ni service externe : les
 * mêmes données et la même date produisent toujours les mêmes constats.
 */
final class FarmDemonstrationAnalysisService
{
    private const MORTALITY_WINDOW_DAYS = 7;

    private const MONITORING_RECENCY_DAYS = 2;

    private const EXPIRATION_WARNING_DAYS = 30;

    private const SANITARY_DUE_SOON_DAYS = 7;

    private const OLD_RECEIVABLE_DAYS = 30;

    /**
     * Construit les constats autorisés pour l'utilisateur connecté.
     *
     * @return array{
     *     mode: string,
     *     generated_at: CarbonImmutable,
     *     includes_financial_data: bool,
     *     analyses: list<array{
     *         domain: string,
     *         title: string,
     *         finding: string,
     *         level: string,
     *         level_label: string,
     *         tone: string,
     *         evidence: array<string, string>,
     *         recommendation: string,
     *         requires_human_validation: bool
     *     }>
     * }
     */
    public function analyzeFor(User $viewer): array
    {
        $today = CarbonImmutable::today();
        $activeFlocks = Flock::query()
            ->where('status', 'active')
            ->get(['id', 'initial_quantity', 'current_quantity']);

        // Tous les rôles reçoivent uniquement les analyses techniques communes.
        $analyses = [
            $this->analyzeMortalityAndMonitoring($activeFlocks, $today),
            $this->analyzeSanitaryFollowUp($activeFlocks, $today),
        ];

        /*
         * La confidentialité est appliquée avant toute requête financière.
         * Un vétérinaire ne reçoit donc pas un tableau ensuite masqué par la vue.
         */
        if ($viewer->canManageOperations()) {
            // Les données de stock suivent la même permission que le module Stocks.
            array_unshift($analyses, $this->analyzeStocks($today));
            $analyses[] = $this->analyzeReceivables($today);
        }

        return [
            'mode' => 'Démonstration locale',
            'generated_at' => CarbonImmutable::now(),
            'includes_financial_data' => $viewer->canManageOperations(),
            'analyses' => $analyses,
        ];
    }

    /** Analyse les seuils et échéances des produits encore actifs. */
    private function analyzeStocks(CarbonImmutable $today): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->get(['current_stock', 'alert_threshold', 'expires_at']);

        $lowStockCount = $products->filter(
            fn (Product $product): bool => (float) $product->current_stock <= (float) $product->alert_threshold
        )->count();
        $outOfStockCount = $products->filter(
            fn (Product $product): bool => (float) $product->current_stock <= 0
        )->count();
        $expiredCount = $products->filter(
            fn (Product $product): bool => $product->expires_at?->lt($today) ?? false
        )->count();
        $expiringSoonCount = $products->filter(function (Product $product) use ($today): bool {
            return $product->expires_at !== null
                && $product->expires_at->gte($today)
                && $product->expires_at->lte($today->addDays(self::EXPIRATION_WARNING_DAYS));
        })->count();

        if ($expiredCount > 0 || $outOfStockCount > 0) {
            $level = 'critical';
            $finding = "{$expiredCount} produit(s) expiré(s) et {$outOfStockCount} rupture(s) exigent un contrôle prioritaire.";
            $recommendation = 'Isoler les produits expirés, confirmer le comptage physique et faire valider le réapprovisionnement prioritaire.';
        } elseif ($lowStockCount > 0 || $expiringSoonCount > 0) {
            $level = 'warning';
            $finding = "{$lowStockCount} produit(s) ont atteint leur seuil et {$expiringSoonCount} arrivent à échéance sous 30 jours.";
            $recommendation = 'Vérifier les besoins des bandes et soumettre un ordre de consommation ou de réapprovisionnement au responsable.';
        } else {
            $level = 'stable';
            $finding = 'Aucun seuil de stock ni aucune échéance proche ne ressort des produits actifs.';
            $recommendation = 'Maintenir le contrôle physique régulier et confirmer que les seuils configurés restent adaptés.';
        }

        return $this->makeAnalysis(
            domain: 'stocks',
            title: 'Stocks et expirations',
            finding: $finding,
            level: $level,
            evidence: [
                'Produits actifs' => (string) $products->count(),
                'Stocks au seuil' => (string) $lowStockCount,
                'Ruptures' => (string) $outOfStockCount,
                'Produits expirés' => (string) $expiredCount,
                'Échéances sous 30 jours' => (string) $expiringSoonCount,
            ],
            recommendation: $recommendation,
        );
    }

    /**
     * Compare la mortalité des sept derniers jours à l'effectif initial des
     * bandes actives et mesure la fraîcheur des relevés.
     */
    private function analyzeMortalityAndMonitoring(Collection $activeFlocks, CarbonImmutable $today): array
    {
        $flockIds = $activeFlocks->pluck('id');
        $windowStart = $today->subDays(self::MORTALITY_WINDOW_DAYS - 1);

        $records = $flockIds->isEmpty()
            ? collect()
            : DailyRecord::query()
                ->whereIn('flock_id', $flockIds)
                ->whereDate('record_date', '>=', $windowStart->toDateString())
                ->whereDate('record_date', '<=', $today->toDateString())
                ->get(['flock_id', 'record_date', 'mortality', 'health_status']);

        $mortality = (int) $records->sum('mortality');
        $referencePopulation = (int) $activeFlocks->sum('initial_quantity');
        $mortalityRate = $referencePopulation > 0
            ? round(($mortality / $referencePopulation) * 100, 2)
            : 0.0;
        $flaggedRecords = $records->where('health_status', '!=', 'good')->count();
        $recentCutoff = $today->subDays(self::MONITORING_RECENCY_DAYS - 1);
        $recentlyMonitoredFlockIds = $records
            ->filter(fn (DailyRecord $record): bool => $record->record_date->gte($recentCutoff))
            ->pluck('flock_id')
            ->unique();
        $flocksWithoutRecentRecord = $flockIds->diff($recentlyMonitoredFlockIds)->count();

        if ($mortalityRate >= 3) {
            $level = 'critical';
            $finding = "Le taux de mortalité sur 7 jours atteint {$this->formatPercent($mortalityRate)}, au-dessus du seuil critique de démonstration fixé à 3 %.";
            $recommendation = 'Faire confirmer les relevés, rechercher la cause avec le vétérinaire et valider les mesures correctives avant application.';
        } elseif ($mortalityRate >= 1 || $flaggedRecords > 0 || $flocksWithoutRecentRecord > 0) {
            $level = 'warning';
            $finding = "Le suivi récent signale {$flaggedRecords} relevé(s) à surveiller et {$flocksWithoutRecentRecord} bande(s) sans relevé depuis deux jours.";
            $recommendation = 'Compléter les relevés manquants et faire examiner les observations inhabituelles par le responsable ou le vétérinaire.';
        } else {
            $level = 'stable';
            $finding = 'La mortalité et la fréquence des relevés ne dépassent aucun seuil de démonstration.';
            $recommendation = 'Poursuivre les saisies quotidiennes et valider toute variation avec la personne responsable du suivi.';
        }

        return $this->makeAnalysis(
            domain: 'mortality_monitoring',
            title: 'Mortalité et suivi quotidien',
            finding: $finding,
            level: $level,
            evidence: [
                'Bandes actives' => (string) $activeFlocks->count(),
                'Mortalité sur 7 jours' => (string) $mortality,
                'Taux sur effectif initial' => $this->formatPercent($mortalityRate),
                'Relevés à surveiller' => (string) $flaggedRecords,
                'Bandes sans relevé depuis 2 jours' => (string) $flocksWithoutRecentRecord,
            ],
            recommendation: $recommendation,
        );
    }

    /** Analyse la dernière visite connue de chaque bande active. */
    private function analyzeSanitaryFollowUp(Collection $activeFlocks, CarbonImmutable $today): array
    {
        $flockIds = $activeFlocks->pluck('id');
        $latestVisits = $flockIds->isEmpty()
            ? collect()
            : VeterinaryVisit::query()
                ->whereIn('flock_id', $flockIds)
                ->orderByDesc('visit_date')
                ->orderByDesc('id')
                ->get(['id', 'flock_id', 'visit_date', 'next_visit_date'])
                ->unique('flock_id')
                ->values();

        $overdueControls = $latestVisits->filter(
            fn (VeterinaryVisit $visit): bool => $visit->next_visit_date?->lt($today) ?? false
        )->count();
        $dueSoonControls = $latestVisits->filter(function (VeterinaryVisit $visit) use ($today): bool {
            return $visit->next_visit_date !== null
                && $visit->next_visit_date->gte($today)
                && $visit->next_visit_date->lte($today->addDays(self::SANITARY_DUE_SOON_DAYS));
        })->count();
        $withoutVisit = $flockIds->diff($latestVisits->pluck('flock_id'))->count();
        $withoutNextVisit = $latestVisits->whereNull('next_visit_date')->count();
        $withoutSanitaryPlan = $withoutVisit + $withoutNextVisit;

        $flaggedHealthRecords = $flockIds->isEmpty()
            ? 0
            : DailyRecord::query()
                ->whereIn('flock_id', $flockIds)
                ->whereDate('record_date', '>=', $today->subDays(self::MORTALITY_WINDOW_DAYS - 1)->toDateString())
                ->whereDate('record_date', '<=', $today->toDateString())
                ->where('health_status', '!=', 'good')
                ->count();

        if ($overdueControls > 0 && $flaggedHealthRecords > 0) {
            $level = 'critical';
            $finding = "{$overdueControls} contrôle(s) sont échus alors que {$flaggedHealthRecords} relevé(s) sanitaires récents demandent une surveillance.";
            $recommendation = 'Soumettre en priorité les bandes concernées au vétérinaire et faire valider la date du prochain contrôle.';
        } elseif ($overdueControls > 0 || $withoutSanitaryPlan > 0 || $flaggedHealthRecords > 0) {
            $level = 'warning';
            $finding = "Le suivi comporte {$overdueControls} contrôle(s) échu(s) et {$withoutSanitaryPlan} bande(s) sans prochaine échéance documentée.";
            $recommendation = 'Faire vérifier le calendrier sanitaire et enregistrer une échéance validée pour chaque bande active.';
        } else {
            $level = 'stable';
            $finding = 'Le calendrier sanitaire des bandes actives ne présente pas de retard ou de signal récent.';
            $recommendation = 'Conserver le calendrier à jour et laisser le vétérinaire confirmer toute adaptation du protocole.';
        }

        return $this->makeAnalysis(
            domain: 'sanitary',
            title: 'Suivi sanitaire',
            finding: $finding,
            level: $level,
            evidence: [
                'Bandes actives' => (string) $activeFlocks->count(),
                'Contrôles échus' => (string) $overdueControls,
                'Contrôles sous 7 jours' => (string) $dueSoonControls,
                'Bandes sans prochaine échéance' => (string) $withoutSanitaryPlan,
                'Signaux sanitaires sur 7 jours' => (string) $flaggedHealthRecords,
            ],
            recommendation: $recommendation,
        );
    }

    /** Analyse financière réservée aux rôles de pilotage. */
    private function analyzeReceivables(CarbonImmutable $today): array
    {
        $receivables = Sale::query()
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get(['sale_date', 'balance_due']);

        $totalDue = (float) $receivables->sum('balance_due');
        $oldReceivables = $receivables->filter(
            fn (Sale $sale): bool => $sale->sale_date->lt($today->subDays(self::OLD_RECEIVABLE_DAYS))
        );
        $oldAmount = (float) $oldReceivables->sum('balance_due');

        if ($oldAmount > 0) {
            $level = 'critical';
            $finding = $this->formatCurrency($oldAmount).' de créances ont plus de 30 jours.';
            $recommendation = 'Vérifier les dossiers anciens et faire valider la priorité ainsi que le mode de relance par le responsable.';
        } elseif ($totalDue > 0) {
            $level = 'warning';
            $finding = $this->formatCurrency($totalDue).' restent à recouvrer sur les ventes ouvertes.';
            $recommendation = 'Rapprocher les versements saisis et soumettre le calendrier de relance à validation.';
        } else {
            $level = 'stable';
            $finding = 'Aucune créance ouverte ne ressort des ventes enregistrées.';
            $recommendation = 'Poursuivre le rapprochement régulier des ventes et des encaissements validés.';
        }

        return $this->makeAnalysis(
            domain: 'receivables',
            title: 'Créances clients',
            finding: $finding,
            level: $level,
            evidence: [
                'Dossiers ouverts' => (string) $receivables->count(),
                'Solde à recouvrer' => $this->formatCurrency($totalDue),
                'Dossiers de plus de 30 jours' => (string) $oldReceivables->count(),
                'Montant de plus de 30 jours' => $this->formatCurrency($oldAmount),
            ],
            recommendation: $recommendation,
        );
    }

    /** Normalise la structure consommée par la vue et rend la validation explicite. */
    private function makeAnalysis(
        string $domain,
        string $title,
        string $finding,
        string $level,
        array $evidence,
        string $recommendation,
    ): array {
        [$levelLabel, $tone] = match ($level) {
            'critical' => ['Critique', 'blocked'],
            'warning' => ['À surveiller', 'watch'],
            default => ['Stable', 'good'],
        };

        return [
            'domain' => $domain,
            'title' => $title,
            'finding' => $finding,
            'level' => $level,
            'level_label' => $levelLabel,
            'tone' => $tone,
            'evidence' => $evidence,
            'recommendation' => $recommendation,
            'requires_human_validation' => true,
        ];
    }

    /** Affiche un pourcentage français avec une précision stable. */
    private function formatPercent(float $value): string
    {
        return number_format($value, 2, ',', ' ').' %';
    }

    /** Centralise le format monétaire utilisé dans les preuves et constats. */
    private function formatCurrency(float $value): string
    {
        return number_format($value, 0, ',', ' ').' FCFA';
    }
}
