<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Only check if it redirects or loads (200 or 302 depending on auth)
        $this->assertContains($response->status(), [200, 302]);
    }
}
