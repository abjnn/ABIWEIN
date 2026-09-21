<?php

namespace App\Services\Reports;

use App\Enums\ProductCategory;
use App\Enums\StockUnit;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\FarmReport;
use App\Models\Flock;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\VeterinaryVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Construit puis archive une photographie des données nécessaires au rapport. */
class FarmReportSnapshotService
{
    /** Génère le document dans une transaction afin de ne jamais archiver un rapport incomplet. */
    public function generate(array $data, User $generator): FarmReport
    {
        $periodStart = CarbonImmutable::parse($data['period_start'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($data['period_end'])->endOfDay();
        $type = (string) $data['type'];

        return DB::transaction(function () use ($data, $generator, $periodStart, $periodEnd, $type): FarmReport {
            return FarmReport::query()->create([
                'reference' => $this->nextReference(),
                'type' => $type,
                'title' => filled($data['title'] ?? null)
                    ? trim((string) $data['title'])
                    : FarmReport::TYPES[$type],
                'report_date' => $data['report_date'],
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'snapshot' => $this->buildSnapshot($type, $periodStart, $periodEnd),
                'generated_by' => $generator->id,
            ]);
        });
    }

    /** Réunit les données une seule fois ; le JSON sauvegardé ne sera plus recalculé ensuite. */
    private function buildSnapshot(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $dateStart = $periodStart->toDateString();
        $dateEnd = $periodEnd->toDateString();

        // Les relations affichées sont chargées maintenant pour figer aussi leurs libellés.
        $dailyRecords = DailyRecord::query()
            ->with('flock:id,code')
            ->whereBetween('record_date', [$dateStart, $dateEnd])
            ->orderBy('record_date')
            ->get();

        $visits = VeterinaryVisit::query()
            ->with(['flock:id,code', 'veterinarian:id,name'])
            ->whereBetween('visit_date', [$dateStart, $dateEnd])
            ->orderBy('visit_date')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $sales = Sale::query()
            ->with('client:id,name')
            ->whereBetween('sale_date', [$dateStart, $dateEnd])
            ->where('status', '!=', 'cancelled')
            ->orderBy('sale_date')
            ->get();

        $payments = Payment::query()
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->get();

        $expenses = Expense::query()
            ->whereBetween('spent_on', [$dateStart, $dateEnd])
            ->orderBy('spent_on')
            ->get();

        $sections = match ($type) {
            'daily' => [
                $this->technicalSection($dailyRecords),
                $this->healthSection($visits),
            ],
            'health' => [
                $this->healthSection($visits),
                $this->technicalSection($dailyRecords),
            ],
            'stocks' => [$this->stockSection($products)],
            'commercial_financial' => [$this->commercialSection($sales, $payments, $expenses)],
            default => [
                $this->technicalSection($dailyRecords),
                $this->healthSection($visits),
                $this->stockSection($products),
                $this->commercialSection($sales, $payments, $expenses),
            ],
        };

        // La conclusion donne un état de lecture rapide sans inventer d'interprétation médicale.
        $sections[] = [
            'title' => 'Conclusion administrative',
            'paragraphs' => [
                'Le présent rapport restitue exclusivement les opérations enregistrées dans ABIWEIN pendant la période indiquée. Toute décision sanitaire demeure soumise à l’appréciation du vétérinaire responsable.',
            ],
            'metrics' => [],
            'tables' => [],
        ];

        return [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'purpose' => sprintf(
                'Le présent document rend compte de la situation enregistrée du %s au %s.',
                $periodStart->format('d/m/Y'),
                $periodEnd->format('d/m/Y'),
            ),
            'sections' => $sections,
        ];
    }

    /** Prépare les mesures d'élevage et leur détail quotidien. */
    private function technicalSection(Collection $dailyRecords): array
    {
        $activeFlocks = Flock::query()->where('status', 'active')->get(['code', 'current_quantity']);
        $averageWeight = $dailyRecords->whereNotNull('average_weight_kg')->avg('average_weight_kg');

        return [
            'title' => 'Situation technique de l’élevage',
            'paragraphs' => [
                sprintf(
                    '%d relevé(s) quotidien(s) ont été enregistrés. L’effectif déclaré des bandes actives est de %s sujet(s).',
                    $dailyRecords->count(),
                    $this->number($activeFlocks->sum('current_quantity')),
                ),
            ],
            'metrics' => [
                ['label' => 'Bandes actives', 'value' => $this->number($activeFlocks->count())],
                ['label' => 'Mortalité constatée', 'value' => $this->number($dailyRecords->sum('mortality')).' sujet(s)'],
                ['label' => 'Aliment consommé', 'value' => $this->number($dailyRecords->sum('feed_kg'), 2).' kg'],
                ['label' => 'Eau consommée', 'value' => $this->number($dailyRecords->sum('water_liters'), 2).' L'],
                ['label' => 'Poids moyen relevé', 'value' => $averageWeight === null ? 'Non renseigné' : $this->number($averageWeight, 3).' kg'],
            ],
            'tables' => [[
                'title' => 'Relevés quotidiens',
                'headers' => ['Date', 'Bande', 'Mortalité', 'Aliment', 'Eau', 'Poids moyen', 'État'],
                'rows' => $dailyRecords->map(fn (DailyRecord $record): array => [
                    $record->record_date->format('d/m/Y'),
                    $record->flock?->code ?? '—',
                    $this->number($record->mortality),
                    $this->number($record->feed_kg, 2).' kg',
                    $this->number($record->water_liters, 2).' L',
                    $record->average_weight_kg === null ? '—' : $this->number($record->average_weight_kg, 3).' kg',
                    $this->healthStatusLabel($record->health_status),
                ])->all(),
            ]],
        ];
    }

    /** Présente les constats du vétérinaire sans les transformer ni les interpréter. */
    private function healthSection(Collection $visits): array
    {
        return [
            'title' => 'Situation sanitaire',
            'paragraphs' => [
                sprintf('%d visite(s) vétérinaire(s) figurent dans le registre pour la période.', $visits->count()),
            ],
            'metrics' => [
                ['label' => 'Visites enregistrées', 'value' => $this->number($visits->count())],
                ['label' => 'Suivis programmés', 'value' => $this->number($visits->whereNotNull('next_visit_date')->count())],
            ],
            'tables' => [[
                'title' => 'Registre des visites',
                'headers' => ['Date', 'Bande', 'Vétérinaire', 'Diagnostic', 'Recommandations', 'Prochaine visite'],
                'rows' => $visits->map(fn (VeterinaryVisit $visit): array => [
                    $visit->visit_date->format('d/m/Y'),
                    $visit->flock?->code ?? '—',
                    $visit->veterinarian?->name ?? '—',
                    $visit->diagnosis,
                    $visit->recommendations ?: 'Aucune recommandation consignée',
                    $visit->next_visit_date?->format('d/m/Y') ?? 'Non programmée',
                ])->all(),
            ]],
        ];
    }

    /** Dresse l'inventaire des intrants de la ferme et signale les seuils atteints. */
    private function stockSection(Collection $products): array
    {
        $lowStock = $products->filter(
            fn (Product $product): bool => (float) $product->current_stock <= (float) $product->alert_threshold,
        );

        return [
            'title' => 'État des stocks d’intrants',
            'paragraphs' => [
                'L’inventaire ci-dessous concerne les aliments, produits sanitaires, équipements et consommables utilisés par la ferme.',
            ],
            'metrics' => [
                ['label' => 'Références actives', 'value' => $this->number($products->count())],
                ['label' => 'Seuil atteint ou dépassé', 'value' => $this->number($lowStock->count())],
            ],
            'tables' => [[
                'title' => 'Inventaire disponible',
                'headers' => ['Code', 'Désignation', 'Catégorie', 'Disponible', 'Seuil', 'Situation'],
                'rows' => $products->map(function (Product $product): array {
                    $unit = StockUnit::tryFrom((string) $product->unit)?->value ?? (string) $product->unit;
                    $category = ProductCategory::tryFrom((string) $product->category)?->label() ?? (string) $product->category;
                    $isLow = (float) $product->current_stock <= (float) $product->alert_threshold;

                    return [
                        $product->code,
                        $product->name,
                        $category,
                        $this->number($product->current_stock, 2).' '.$unit,
                        $this->number($product->alert_threshold, 2).' '.$unit,
                        $isLow ? 'Réapprovisionnement requis' : 'Niveau normal',
                    ];
                })->all(),
            ]],
        ];
    }

    /** Consolide les ventes, encaissements, créances et dépenses de la période. */
    private function commercialSection(Collection $sales, Collection $payments, Collection $expenses): array
    {
        $turnover = (float) $sales->sum('total_amount');
        $cashCollected = (float) $payments->sum('amount');
        $totalExpenses = (float) $expenses->sum('amount');

        return [
            'title' => 'Situation commerciale et financière',
            'paragraphs' => [
                sprintf(
                    'La période totalise %d vente(s). Les encaissements et décaissements présentés proviennent des journaux saisis dans ABIWEIN.',
                    $sales->count(),
                ),
            ],
            'metrics' => [
                ['label' => 'Chiffre d’affaires', 'value' => $this->money($turnover)],
                ['label' => 'Espèces encaissées', 'value' => $this->money($cashCollected)],
                ['label' => 'Créances', 'value' => $this->money($sales->sum('balance_due'))],
                ['label' => 'Dépenses', 'value' => $this->money($totalExpenses)],
                ['label' => 'Solde de trésorerie', 'value' => $this->money($cashCollected - $totalExpenses)],
            ],
            'tables' => [
                [
                    'title' => 'Journal des ventes',
                    'headers' => ['Date', 'Référence', 'Client', 'Type', 'Montant', 'Reste dû'],
                    'rows' => $sales->map(fn (Sale $sale): array => [
                        $sale->sale_date->format('d/m/Y'),
                        $sale->reference,
                        $sale->client?->name ?? 'Client de passage',
                        $sale->sale_type === 'credit' ? 'Crédit' : 'Comptant',
                        $this->money($sale->total_amount),
                        $this->money($sale->balance_due),
                    ])->all(),
                ],
                [
                    'title' => 'Journal des dépenses',
                    'headers' => ['Date', 'Référence', 'Catégorie', 'Objet', 'Montant'],
                    'rows' => $expenses->map(fn (Expense $expense): array => [
                        $expense->spent_on->format('d/m/Y'),
                        $expense->reference,
                        $expense->category,
                        $expense->description,
                        $this->money($expense->amount),
                    ])->all(),
                ],
            ],
        ];
    }

    /** Produit une référence courte et vérifie sa disponibilité avant l'insertion. */
    private function nextReference(): string
    {
        do {
            $reference = 'RPT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (FarmReport::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /** Uniformise les nombres affichés dans le HTML et dans le PDF. */
    private function number(float|int|string|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', ' ');
    }

    /** Ajoute l'unité monétaire officielle utilisée par l'application. */
    private function money(float|int|string|null $value): string
    {
        return $this->number($value).' FCFA';
    }

    /** Rend les états sanitaires techniques lisibles en français. */
    private function healthStatusLabel(string $status): string
    {
        return match ($status) {
            'good' => 'Bon',
            'watch' => 'À surveiller',
            'critical' => 'Critique',
            default => Str::headline($status),
        };
    }
}
