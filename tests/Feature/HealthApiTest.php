<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_endpoint_reports_that_the_api_is_available(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'status' => 'ok',
                    'service' => 'controle-acesso-api-laravel',
                ],
            ]);
    }
}
