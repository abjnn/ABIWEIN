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

// Seules les données d'identité ordinaires sont assignables en masse.
// Le rôle, l'état et la dernière connexion sont toujours affectés explicitement.
#[Fillable(['name', 'email', 'password'])]
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

    /** Indique si le compte réalise les opérations quotidiennes de la ferme. */
    public function canManageOperations(): bool
    {
        // L'administrateur supervise les données mais ne les modifie jamais.
        return $this->isFarmManager();
    }

    /** Raccourci utilisé pour les boutons et routes du responsable d'exploitation. */
    public function isFarmManager(): bool
    {
        return $this->hasRole(UserRole::FarmManager);
    }

    /** Raccourci utilisé pour les écrans sanitaires réservés au vétérinaire. */
    public function isVeterinarian(): bool
    {
        return $this->hasRole(UserRole::Veterinarian);
    }

    /** L'administrateur et le responsable peuvent consulter les données opérationnelles. */
    public function canViewOperations(): bool
    {
        return $this->isAdministrator() || $this->isFarmManager();
    }

    /** Les trois rôles privés consultent les données techniques utiles des bandes. */
    public function canViewFlocks(): bool
    {
        return $this->isAdministrator() || $this->isFarmManager() || $this->isVeterinarian();
    }

    /** Retourne les relevés quotidiens créés par cet utilisateur. */
    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class, 'recorded_by');
    }

    /** Actions quotidiennes consignées par le responsable connecté. */
    public function flockActivities(): HasMany
    {
        return $this->hasMany(FlockActivity::class, 'recorded_by');
    }
}
