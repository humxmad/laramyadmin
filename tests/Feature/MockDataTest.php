<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class MockDataTest extends TestCase
{
    public function test_can_generate_mock_records()
    {
        $response = $this->postJson('/laramyadmin/api/tables/users/mock-data', [
            'count' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('inserted', 5);

        $browseResponse = $this->getJson('/laramyadmin/api/tables/users/rows');
        $browseResponse->assertStatus(200)
            ->assertJsonPath('total', 5);
    }
}
