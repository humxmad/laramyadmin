<?php

namespace LaraMyAdmin\Tests\Feature;

use LaraMyAdmin\Tests\TestCase;

class CodeGeneratorTest extends TestCase
{
    public function test_can_generate_laravel_migration_model_factory()
    {
        $response = $this->getJson('/laramyadmin/api/tables/users/generate?type=all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'table',
                'code' => [
                    'migration',
                    'model',
                    'factory',
                ],
            ]);

        $migrationCode = $response->json('code.migration');
        $modelCode = $response->json('code.model');
        $factoryCode = $response->json('code.factory');

        $this->assertStringContainsString("Schema::create('users'", $migrationCode);
        $this->assertStringContainsString("class User extends Model", $modelCode);
        $this->assertStringContainsString("class UserFactory extends Factory", $factoryCode);
    }
}
