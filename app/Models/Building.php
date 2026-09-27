<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Représente un bâtiment physique de l’exploitation. */
#[Fillable(['code', 'name', 'type', 'capacity', 'status', 'notes'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    /** Bandes actuellement rattachées à ce bâtiment. */
    public function currentFlocks(): HasMany
    {
        return $this->hasMany(Flock::class, 'current_building_id');
    }

    /** Périodes d'occupation conservées même après le départ d'une bande. */
    public function flockAssignments(): HasMany
    {
        return $this->hasMany(FlockBuildingAssignment::class);
    }
}
