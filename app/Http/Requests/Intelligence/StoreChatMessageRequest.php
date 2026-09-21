<?php

namespace App\Http\Requests\Intelligence;

use Illuminate\Foundation\Http\FormRequest;

/** Valide une question avant tout enregistrement ou appel au fournisseur IA. */
class StoreChatMessageRequest extends FormRequest
{
    /** Le middleware de rôle décide déjà quels comptes peuvent poser une question. */
    public function authorize(): bool
    {
        return true;
    }

    /** Nettoie les espaces afin d'éviter les messages visuellement vides. */
    protected function prepareForValidation(): void
    {
        $this->merge(['message' => trim((string) $this->input('message'))]);
    }

    /** Limite la taille envoyée au service externe et protège l'interface. */
    public function rules(): array
    {
        return ['message' => ['required', 'string', 'min:3', 'max:1000']];
    }

    /** Affiche des indications courtes directement sous la zone de saisie. */
    public function messages(): array
    {
        return [
            'message.required' => 'Écrivez votre demande avant de l’envoyer.',
            'message.min' => 'La demande doit contenir au moins 3 caractères.',
            'message.max' => 'La demande ne doit pas dépasser 1 000 caractères.',
        ];
    }
}
