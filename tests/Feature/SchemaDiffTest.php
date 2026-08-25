<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class SchemaDiffTest extends TestCase
{
    public function test_can_compare_schemas_between_connections()
    {
        $response = $this->postJson('/laramyadmin/api/diff', [
            'source' => 'testing',
            'target' => 'secondary',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'source_connection',
                'target_connection',
                'missing_tables_in_target',
                'missing_tables_in_source',
                'table_differences',
                'has_differences',
            ]);
    }
}
