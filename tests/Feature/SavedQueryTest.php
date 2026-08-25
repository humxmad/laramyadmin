<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class SavedQueryTest extends TestCase
{
    public function test_can_manage_saved_queries()
    {
        // 1. Get initial saved queries
        $response = $this->getJson('/laramyadmin/api/saved-queries');
        $response->assertStatus(200)
            ->assertJsonStructure(['saved_queries']);

        // 2. Save a new query bookmark
        $createRes = $this->postJson('/laramyadmin/api/saved-queries', [
            'title' => 'All Active Admins',
            'sql' => "SELECT * FROM users WHERE role = 'superadmin';",
        ]);

        $createRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $savedId = $createRes->json('item.id');

        // 3. Delete saved query bookmark
        $delRes = $this->deleteJson("/laramyadmin/api/saved-queries/{$savedId}");
        $delRes->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
