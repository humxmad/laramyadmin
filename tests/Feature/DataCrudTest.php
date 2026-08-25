<?php

namespace LaraMyAdmin\Tests\Feature;

use Illuminate\Support\Facades\DB;
use LaraMyAdmin\Tests\TestCase;

class DataCrudTest extends TestCase
{
    public function test_can_list_tables()
    {
        $response = $this->getJson('/laramyadmin/api/tables');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tables',
                'system_info',
            ]);
    }

    public function test_can_insert_and_browse_records()
    {
        // Insert record
        $response = $this->postJson('/laramyadmin/api/tables/users/rows', [
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Browse records
        $browseResponse = $this->getJson('/laramyadmin/api/tables/users/rows');
        $browseResponse->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.name', 'John Doe');
    }

    public function test_can_execute_raw_query()
    {
        DB::table('users')->insert([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $response = $this->postJson('/laramyadmin/api/query/execute', [
            'sql' => 'SELECT * FROM users WHERE email = "alice@example.com"',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('rows_count', 1);
    }
}
