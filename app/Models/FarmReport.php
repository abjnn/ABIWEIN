<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Document administratif dont les données sont figées lors de sa génération. */
#[Fillable(['reference', 'type', 'title', 'report_date', 'period_start', 'period_end', 'snapshot', 'generated_by'])]
class FarmReport extends Model
{
    /** Les clés restent stables afin de conserver la compatibilité des rapports archivés. */
    public const TYPES = [
        'daily' => 'Rapport journalier',
        'health' => 'Rapport sanitaire',
        'stocks' => 'Rapport des stocks',
        'commercial_financial' => 'Rapport commercial et financier',
        'summary' => 'Rapport de synthèse',
    ];

    /** Convertit les dates et le document JSON en objets directement exploitables. */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'snapshot' => 'array',
        ];
    }

    /** Responsable d'exploitation qui a produit le document. */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** Libellé français du type conservé dans la base. */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Rapport administratif';
    }
}
