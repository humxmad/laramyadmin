<?php

namespace LaraMyAdmin\Tests\Feature;

use Illuminate\Support\Facades\DB;
use LaraMyAdmin\Tests\TestCase;

class ExportImportTest extends TestCase
{
    public function test_can_export_csv()
    {
        DB::table('users')->insert([
            'name' => 'John',
            'email' => 'john@test.com',
        ]);

        $response = $this->get('/laramyadmin/export/users/csv');
        $response->assertStatus(200);
        $this->assertStringContainsString('john@test.com', $response->streamedContent());
    }

    public function test_can_export_json()
    {
        DB::table('users')->insert([
            'name' => 'Jane',
            'email' => 'jane@test.com',
        ]);

        $response = $this->get('/laramyadmin/export/users/json');
        $response->assertStatus(200);
        $this->assertStringContainsString('jane@test.com', $response->streamedContent());
    }

    public function test_can_import_sql()
    {
        $response = $this->postJson('/laramyadmin/api/import/sql', [
            'sql' => "INSERT INTO users (name, email) VALUES ('Imported User', 'imported@test.com');",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'imported@test.com',
        ]);
    }
}
