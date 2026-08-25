<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class ConnectionTest extends TestCase
{
    public function test_can_list_connections()
    {
        $response = $this->getJson('/laramyadmin/api/connections');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'active',
                'connections',
            ]);
    }

    public function test_can_switch_connection()
    {
        $response = $this->postJson('/laramyadmin/api/connections/switch', [
            'connection' => 'secondary',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'active' => 'secondary',
            ]);
    }
}
