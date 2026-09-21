<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Acheteur particulier ou professionnel de la ferme. */
#[Fillable(['name', 'type', 'phone', 'email', 'address', 'is_active'])]
class Client extends Model
{
    use Auditable;

    /** Convertit l’état actif en booléen. */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Ventes rattachées au client. */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
