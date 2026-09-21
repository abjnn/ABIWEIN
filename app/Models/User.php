<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// MODIFICATION : ajoutez ici uniquement les champs qu’un formulaire peut remplir.
#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
// Les informations sensibles ne doivent jamais apparaître dans une réponse sérialisée.
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** Indique si l’utilisateur possède le rôle demandé. */
    public function hasRole(UserRole|string $role): bool
    {
        $expectedRole = $role instanceof UserRole ? $role : UserRole::tryFrom($role);

        return $expectedRole !== null && $this->role === $expectedRole;
    }

    /** Raccourci utilisé dans les vues et contrôleurs d’administration. */
    public function isAdministrator(): bool
    {
        return $this->hasRole(UserRole::Administrator);
    }

    /** Indique si le compte peut gérer les stocks, ventes, finances et rapports. */
    public function canManageOperations(): bool
    {
        // MODIFICATION : ajoutez ici un rôle seulement s’il doit accéder aux données commerciales.
        return in_array($this->role, [UserRole::Administrator, UserRole::FarmManager], true);
    }

    /** Retourne les relevés quotidiens créés par cet utilisateur. */
    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class, 'recorded_by');
    }
}
