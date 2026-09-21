<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** Valide les informations envoyées par le formulaire de connexion. */
class LoginRequest extends FormRequest
{
    /** La connexion est publique ; l’autorisation dépend ensuite des identifiants. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * MODIFICATION : gardez les noms email, password et remember synchronisés
     * avec resources/views/auth/login.blade.php.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** Messages français affichés directement sous les champs concernés. */
    public function messages(): array
    {
        return [
            'email.required' => 'Veuillez indiquer votre adresse e-mail.',
            'email.email' => 'Veuillez indiquer une adresse e-mail valide.',
            'password.required' => 'Veuillez indiquer votre mot de passe.',
        ];
    }
}
