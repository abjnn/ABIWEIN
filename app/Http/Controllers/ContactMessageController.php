<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Http\RedirectResponse;
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
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $fullName = $validated['first_name'].' '.$validated['last_name'];

        /* PUBLIC_CONTACT_EMAIL est prioritaire ; l'adresse locale sert de repli en développement. */
        $recipient = config('public_site.email') ?: config('mail.from.address');

        /* Le format texte reste lisible avec tous les serveurs de messagerie. */
        $body = implode(PHP_EOL, [
            'Nouveau message reçu depuis le site public ABIWEIN',
            '',
            'Nom : '.$validated['last_name'],
            'Prénom : '.$validated['first_name'],
            'E-mail : '.$validated['email'],
            'Téléphone : '.$validated['phone'],
            'Sujet : '.$validated['subject'],
            '',
            'Message :',
            $validated['message'],
        ]);

        Mail::raw($body, function ($mail) use ($recipient, $validated, $fullName): void {
            $mail->to($recipient)
                ->replyTo($validated['email'], $fullName)
                ->subject('[ABIWEIN] '.$validated['subject']);
        });

        /* La redirection empêche un double envoi lors d'une actualisation. */
        return to_route('contact')->with(
            'contact_status',
            'Votre message a bien été transmis. L’équipe ABIWEIN vous répondra dès que possible.'
        );
    }
}
