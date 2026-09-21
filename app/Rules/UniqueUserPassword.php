<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/** Empêche l’attribution du mot de passe déjà utilisé par un autre compte. */
class UniqueUserPassword implements ValidationRule
{
    /** Compare le secret proposé aux empreintes existantes sans jamais les déchiffrer. */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $plainPassword = (string) $value;

        // lazyById évite de charger tous les comptes en mémoire si la plateforme grandit.
        foreach (User::query()->select(['id', 'password'])->lazyById() as $user) {
            if (Hash::check($plainPassword, $user->password)) {
                $fail('Ce mot de passe est déjà attribué à un autre compte. Choisissez-en un autre.');

                return;
            }
        }
    }
}
