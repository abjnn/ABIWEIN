<?php

namespace App\Services\Intelligence;

use App\Models\DailyRecord;
use App\Models\Flock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\VeterinaryVisit;
use Carbon\CarbonImmutable;

/** Prépare uniquement des indicateurs agrégés, sans données personnelles. */
final class FarmChatContextService
{
    /**
     * Produit le contexte minimal nécessaire à une réponse de pilotage.
     * Aucun nom de client, e-mail, numéro ou texte libre n'est transmis.
     *
     * @return array<string, int|float|string>
     */
    public function snapshot(): array
    {
        $today = CarbonImmutable::today();
        $activeFlocks = Flock::query()->where('status', 'active');
        $products = Product::query()->where('is_active', true);

        // Les relevés récents mesurent le suivi sans exposer leurs observations libres.
        $recentRecords = DailyRecord::query()
            ->whereDate('record_date', '>=', $today->subDays(6)->toDateString())
            ->whereDate('record_date', '<=', $today->toDateString());

        // Les ventes restent agrégées en montants globaux pour préserver la confidentialité.
        $recentSales = Sale::query()
            ->whereDate('sale_date', '>=', $today->subDays(29)->toDateString())
            ->whereNotIn('status', ['cancelled']);

        return [
            'date' => $today->toDateString(),
            'bandes_actives' => (int) (clone $activeFlocks)->count(),
            'effectif_actuel' => (int) (clone $activeFlocks)->sum('current_quantity'),
            'releves_7_jours' => (int) (clone $recentRecords)->count(),
            'mortalite_7_jours' => (int) (clone $recentRecords)->sum('mortality'),
            'aliment_7_jours_kg' => round((float) (clone $recentRecords)->sum('feed_kg'), 2),
            'eau_7_jours_litres' => round((float) (clone $recentRecords)->sum('water_liters'), 2),
            'intrants_actifs' => (int) (clone $products)->count(),
            'intrants_au_seuil' => (int) (clone $products)
                ->whereColumn('current_stock', '<=', 'alert_threshold')
                ->count(),
            'visites_30_jours' => (int) VeterinaryVisit::query()
                ->whereDate('visit_date', '>=', $today->subDays(29)->toDateString())
                ->count(),
            'controles_sanitaires_en_retard' => (int) VeterinaryVisit::query()
                ->whereNotNull('next_visit_date')
                ->whereDate('next_visit_date', '<', $today->toDateString())
                ->count(),
            'ventes_30_jours' => (int) (clone $recentSales)->count(),
            'chiffre_affaires_30_jours_fcfa' => round((float) (clone $recentSales)->sum('total_amount'), 0),
            'creances_fcfa' => round((float) Sale::query()
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('balance_due'), 0),
        ];
    }

    /** Transforme le tableau en texte stable et facile à contrôler avant envoi. */
    public function asPromptContext(array $snapshot): string
    {
        return collect($snapshot)
            ->map(fn (int|float|string $value, string $key): string => "- {$key}: {$value}")
            ->implode("\n");
    }
}
