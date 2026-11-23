<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_application_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('timezone', 'Asia/Kuwait');
    }
}
