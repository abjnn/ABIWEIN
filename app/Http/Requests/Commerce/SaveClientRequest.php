<?php

namespace App\Http\Requests\Commerce;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide les informations d'un client lors de la création ou de la modification. */
class SaveClientRequest extends FormRequest
{
    /** Les routes du module appliquent déjà les rôles autorisés. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chaque règle correspond à un champ du formulaire client.
     * La règle d'e-mail ignore le client modifié afin de conserver sa propre adresse.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['individual', 'business'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:150', Rule::unique('clients', 'email')->ignore($this->route('client'))],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** Les erreurs restent compréhensibles pour un utilisateur francophone. */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du client est obligatoire.',
            'type.required' => 'Veuillez choisir le type de client.',
            'type.in' => 'Le type de client sélectionné n’est pas valide.',
            'email.email' => 'L’adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail appartient déjà à un autre client.',
        ];
    }
}
