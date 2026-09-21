<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_dashboard_is_available_and_branded(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('ABIWEIN');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
