<?php

namespace LaraMyAdmin\Tests\Feature;

use Illuminate\Support\Facades\DB;
use LaraMyAdmin\Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    public function test_can_search_across_all_tables()
    {
        DB::table('users')->insert([
            'name' => 'Special Secret User',
            'email' => 'secret_user@example.com',
        ]);

        $response = $this->postJson('/laramyadmin/api/search', [
            'keyword' => 'Secret',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('keyword', 'Secret')
            ->assertJsonPath('total_matches', 1)
            ->assertJsonPath('results.0.table', 'users');
    }
}
