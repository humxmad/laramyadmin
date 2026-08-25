<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure products and orders tables exist
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('sku')->unique();
                $table->decimal('price', 10, 2);
                $table->integer('stock')->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('order_number')->unique();
                $table->decimal('total_amount', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // Insert sample users
        $userId1 = DB::table('users')->insertGetId([
            'name' => 'Super Administrator',
            'email' => 'admin@laramyadmin.test',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId2 = DB::table('users')->insertGetId([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
        ]);

        $userId3 = DB::table('users')->insertGetId([
            'name' => 'Michael Chen',
            'email' => 'michael@example.com',
            'password' => bcrypt('password'),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(5),
        ]);

        // Insert sample products
        $prod1 = DB::table('products')->insertGetId([
            'title' => 'Ergonomic Mechanical Keyboard',
            'sku' => 'KB-MECH-01',
            'price' => 149.99,
            'stock' => 42,
            'description' => 'Custom hot-swappable mechanical keyboard with RGB backlighting.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $prod2 = DB::table('products')->insertGetId([
            'title' => 'UltraWide 4K Gaming Monitor',
            'sku' => 'MON-4K-UW34',
            'price' => 799.00,
            'stock' => 15,
            'description' => '34-inch curved OLED 175Hz gaming display.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert sample orders
        DB::table('orders')->insert([
            'user_id' => $userId1,
            'order_number' => 'ORD-2026-0001',
            'total_amount' => 948.99,
            'status' => 'completed',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(1),
        ]);

        DB::table('orders')->insert([
            'user_id' => $userId2,
            'order_number' => 'ORD-2026-0002',
            'total_amount' => 149.99,
            'status' => 'processing',
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);
    }
}
