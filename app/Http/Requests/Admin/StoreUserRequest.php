<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Valide la création d’un compte par un administrateur. */
class StoreUserRequest extends FormRequest
{
    /** La route est déjà protégée par le middleware de rôle administrateur. */
    public function authorize(): bool
    {
        return true;
    }

    /** Chaque règle protège une donnée avant son écriture en base. */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::enum(UserRole::class)],
            // Cette règle exige un secret robuste sans l’envoyer vers un service extérieur.
            'password' => ['required', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols(), 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** Les messages restent compréhensibles sans dépendre des traductions du framework. */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'role.required' => 'Veuillez sélectionner un rôle.',
            'role.enum' => 'Le rôle sélectionné n’est pas autorisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
