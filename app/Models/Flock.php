<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\FlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Groupe de poulets suivi depuis son arrivée jusqu’à sa clôture. */
#[Fillable(['code', 'breed', 'arrival_date', 'expected_end_date', 'initial_quantity', 'current_quantity', 'supplier_id', 'current_building_id', 'status', 'closed_at', 'notes'])]
class Flock extends Model
{
    /** @use HasFactory<FlockFactory> */
    use Auditable, HasFactory;

    /** Les dates sont transformées en objets Carbon pour les calculs et affichages. */
    protected function casts(): array
    {
        return [
            'arrival_date' => 'date',
            'expected_end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /** Fournisseur d’origine des poussins. */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Bâtiment occupé actuellement. */
    public function currentBuilding(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'current_building_id');
    }

    /** Mesures techniques quotidiennes de la bande. */
    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class);
    }

    /** Chronologie détaillée des actions réalisées pendant les journées d'élevage. */
    public function activities(): HasMany
    {
        return $this->hasMany(FlockActivity::class);
    }

    /** Historique technique de l'effectif et des transferts. */
    public function movements(): HasMany
    {
        return $this->hasMany(FlockMovement::class);
    }

    /** Historique des bâtiments occupés par la bande. */
    public function buildingAssignments(): HasMany
    {
        return $this->hasMany(FlockBuildingAssignment::class);
    }

    /** Visites vétérinaires rattachées à la bande. */
    public function veterinaryVisits(): HasMany
    {
        return $this->hasMany(VeterinaryVisit::class);
    }
}
