<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Valide puis transmet un message envoyé depuis l'espace visiteur.
 *
 * Aucun enregistrement en base n'est créé avant la conception du schéma métier.
 * En local, Laravel écrit les courriels dans son journal ; une configuration
 * SMTP assurera la livraison dans l'environnement de production.
 */
class ContactMessageController extends Controller
{
    /** Valide le formulaire et envoie un courriel au contact configuré. */
    public function store(Request $request): RedirectResponse
    {
        /* MODIFICATION : ajustez les valeurs "max" pour changer les longueurs autorisées. */
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email:rfc', 'max:150'],
                'phone' => ['nullable', 'string', 'max:30'],
                'subject' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
                'message' => ['required', 'string', 'max:3000'],
                'consent' => ['accepted'],
                /* Ce champ invisible doit rester vide : il piège les robots simples. */
                'website' => ['prohibited'],
            ],
            [
                /* Messages explicites en français, indépendants des traductions du framework. */
                'name.required' => 'Veuillez indiquer votre nom complet.',
                'name.max' => 'Le nom ne doit pas dépasser 100 caractères.',
                'email.required' => 'Veuillez indiquer votre adresse e-mail.',
                'email.email' => 'Veuillez indiquer une adresse e-mail valide.',
                'email.max' => 'L’adresse e-mail ne doit pas dépasser 150 caractères.',
                'phone.max' => 'Le numéro de téléphone ne doit pas dépasser 30 caractères.',
                'subject.required' => 'Veuillez sélectionner un sujet.',
                'subject.max' => 'Le sujet ne doit pas dépasser 120 caractères.',
                'message.required' => 'Veuillez rédiger votre message.',
                'message.max' => 'Le message ne doit pas dépasser 3 000 caractères.',
                'consent.accepted' => 'Votre accord est nécessaire pour envoyer la demande.',
                'website.prohibited' => 'Le formulaire n’a pas pu être envoyé.',
            ]
        );

        /* PUBLIC_CONTACT_EMAIL est prioritaire ; l'adresse locale sert de repli en développement. */
        $recipient = config('public_site.email') ?: config('mail.from.address');

        /* Le format texte reste lisible avec tous les serveurs de messagerie. */
        $body = implode(PHP_EOL, [
            'Nouveau message reçu depuis le site public ABIWEIN',
            '',
            'Nom : '.$validated['name'],
            'E-mail : '.$validated['email'],
            'Téléphone : '.($validated['phone'] ?? 'Non renseigné'),
            'Sujet : '.$validated['subject'],
            '',
            'Message :',
            $validated['message'],
        ]);

        Mail::raw($body, function ($mail) use ($recipient, $validated): void {
            $mail->to($recipient)
                ->replyTo($validated['email'], $validated['name'])
                ->subject('[ABIWEIN] '.$validated['subject']);
        });

        /* La redirection empêche un double envoi lors d'une actualisation. */
        return to_route('contact')->with(
            'contact_status',
            'Votre message a bien été transmis. L’équipe ABIWEIN vous répondra dès que possible.'
        );
    }
}
