<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Tests de comportement de l'espace accessible sans authentification. */
class PublicSiteTest extends TestCase
{
    /** Toutes les pages publiques principales doivent être accessibles. */
    public function test_public_pages_are_available(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk()->assertSeeText('Notre mission');
        $this->get(route('contact'))->assertOk()->assertSeeText('Parlons de votre demande');
        $this->get(route('login'))->assertOk()->assertSeeText('Se connecter');
    }

    /** Aucun parcours d'inscription publique ne doit exister. */
    public function test_public_registration_route_does_not_exist(): void
    {
        $this->get('/inscription')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    /** Le formulaire refuse un envoi vide et retourne des erreurs lisibles. */
    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), []);

        $response
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message', 'consent']);
    }

    /** Un message valide déclenche le parcours d'envoi et une confirmation. */
    public function test_contact_form_accepts_a_valid_message(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'Visiteur ABIWEIN',
            'email' => 'visiteur@example.com',
            'phone' => '+237 600 000 000',
            'subject' => 'Information sur ABIWEIN',
            'message' => 'Je souhaite obtenir davantage d’informations sur la plateforme.',
            'consent' => '1',
        ]);

        $response
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_status');
    }
}
