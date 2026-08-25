<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class SchemaTest extends TestCase
{
    public function test_can_show_table_structure()
    {
        $response = $this->getJson('/laramyadmin/api/tables/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'table',
                'columns',
                'indexes',
                'foreign_keys',
                'create_sql',
            ])
            ->assertJsonPath('table', 'users');
    }

    public function test_can_create_and_drop_table()
    {
        $response = $this->postJson('/laramyadmin/api/tables', [
            'table' => 'categories',
            'columns' => [
                ['name' => 'id', 'type' => 'INTEGER', 'primary' => true, 'auto_increment' => true, 'nullable' => false],
                ['name' => 'title', 'type' => 'VARCHAR', 'length' => '100', 'nullable' => false],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Drop table
        $dropResponse = $this->deleteJson('/laramyadmin/api/tables/categories');
        $dropResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_add_and_drop_column()
    {
        $response = $this->postJson('/laramyadmin/api/tables/users/columns', [
            'column' => [
                'name' => 'avatar',
                'type' => 'VARCHAR',
                'length' => '255',
                'nullable' => true,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $dropResponse = $this->deleteJson('/laramyadmin/api/tables/users/columns/avatar');
        $dropResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
