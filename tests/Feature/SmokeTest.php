<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    /** Vérifie que l'accueil public répond et porte la marque correcte. */
    public function test_public_home_is_available_and_branded(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('ABIWEIN')
            ->assertSeeText('piloté avec clarté');
    }

    /** La route de santé permet de surveiller le démarrage de Laravel. */
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
