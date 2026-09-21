<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Représente un fournisseur de poussins, aliments, soins ou équipements. */
#[Fillable(['name', 'contact_person', 'phone', 'email', 'address', 'is_active'])]
class Supplier extends Model
{
    use HasFactory;

    /** Convertit l’état actif en vrai booléen PHP. */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Bandes provenant de ce fournisseur. */
    public function flocks(): HasMany
    {
        return $this->hasMany(Flock::class);
    }
}
