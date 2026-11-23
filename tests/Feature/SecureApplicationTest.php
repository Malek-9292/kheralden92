<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecureApplicationTest extends TestCase
{
    public function test_responses_include_baseline_security_headers(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_force_https_redirects_when_enabled(): void
    {
        config([
            'app.force_https' => true,
            'app.url' => 'https://staging.fatoosh.test',
            'app.env' => 'staging',
        ]);

        $response = $this->call('GET', 'http://staging.fatoosh.test/up');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://', $response->headers->get('Location') ?? '');
    }
}
