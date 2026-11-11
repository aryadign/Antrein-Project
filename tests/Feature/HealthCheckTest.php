<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_healthy_status_and_database_connection(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'application',
                'environment',
                'timezone',
                'database' => [
                    'connected',
                    'driver',
                ],
                'timestamp',
            ])
            ->assertJson([
                'status' => 'healthy',
                'application' => 'AntreIn',
                'database' => [
                    'connected' => true,
                ],
            ]);
    }
}

