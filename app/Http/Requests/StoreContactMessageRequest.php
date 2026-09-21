<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Valide et normalise une demande envoyée depuis le formulaire public. */
class StoreContactMessageRequest extends FormRequest
{
    /** Le formulaire est public ; la limitation de débit reste appliquée sur la route. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Uniformise les numéros camerounais avant la validation.
     * Les formats 6XXXXXXXX, 2376XXXXXXXX et +237 6XX XX XX XX deviennent +2376XXXXXXXX.
     */
    protected function prepareForValidation(): void
    {
        $rawPhone = trim((string) $this->input('phone', ''));

        // Les coordonnées textuelles sont uniformisées avant l'envoi du courriel.
        $this->merge([
            'last_name' => trim((string) $this->input('last_name')),
            'first_name' => trim((string) $this->input('first_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);

        if ($rawPhone === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        if (str_starts_with($digits, '00237')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            $digits = '237'.$digits;
        }

        $this->merge(['phone' => '+'.$digits]);
    }

    /** Les domaines e-mail sans enregistrement DNS sont refusés côté serveur. */
    public function rules(): array
    {
        $personNameRule = '/^[\pL\pM][\pL\pM\'’ -]*$/u';

        return [
            'last_name' => ['required', 'string', 'max:60', 'regex:'.$personNameRule],
            'first_name' => ['required', 'string', 'max:60', 'regex:'.$personNameRule],
            'email' => ['required', 'email:rfc,dns', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+237[26]\d{8}$/'],
            'subject' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:3000'],
            'consent' => ['accepted'],
            // Ce champ invisible doit rester vide afin de bloquer les robots simples.
            'website' => ['prohibited'],
        ];
    }

    /** Les messages restent compréhensibles sans dépendre des traductions du framework. */
    public function messages(): array
    {
        return [
            'last_name.required' => 'Veuillez indiquer votre nom.',
            'last_name.regex' => 'Le nom contient des caractères non autorisés.',
            'last_name.max' => 'Le nom ne doit pas dépasser 60 caractères.',
            'first_name.required' => 'Veuillez indiquer votre prénom.',
            'first_name.regex' => 'Le prénom contient des caractères non autorisés.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 60 caractères.',
            'email.required' => 'Veuillez indiquer votre adresse e-mail.',
            'email.email' => 'Indiquez une adresse e-mail valide dont le domaine existe.',
            'email.max' => 'L’adresse e-mail ne doit pas dépasser 150 caractères.',
            'phone.required' => 'Veuillez indiquer votre numéro de téléphone.',
            'phone.regex' => 'Indiquez un numéro camerounais valide, par exemple +237 699 12 34 56.',
            'subject.required' => 'Veuillez sélectionner un sujet.',
            'subject.max' => 'Le sujet ne doit pas dépasser 120 caractères.',
            'message.required' => 'Veuillez rédiger votre message.',
            'message.max' => 'Le message ne doit pas dépasser 3 000 caractères.',
            'consent.accepted' => 'Votre accord est nécessaire pour envoyer la demande.',
            'website.prohibited' => 'Le formulaire n’a pas pu être envoyé.',
        ];
    }
}
