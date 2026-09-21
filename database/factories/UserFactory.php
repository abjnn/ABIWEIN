<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            // MODIFICATION : le rôle par défaut représente l’utilisateur opérationnel courant.
            'role' => UserRole::FarmManager,
            // Les comptes créés par la factory peuvent se connecter sauf état contraire explicite.
            'is_active' => true,
            // Une nouvelle identité de test ne possède pas encore d’historique de connexion.
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Crée un compte administrateur pour les tests d’autorisation. */
    public function administrator(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Administrator]);
    }

    /** Crée un compte vétérinaire. */
    public function veterinarian(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Veterinarian]);
    }

    /** Crée un compte bloqué qui ne doit pas pouvoir se connecter. */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
