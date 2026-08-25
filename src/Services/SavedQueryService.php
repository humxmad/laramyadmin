<?php

namespace LaraMyAdmin\Services;

use Illuminate\Support\Facades\Session;

class SavedQueryService
{
    protected const SESSION_KEY = 'laramyadmin_saved_queries';

    public function all(): array
    {
        return Session::get(self::SESSION_KEY, [
            [
                'id' => 'sample-1',
                'title' => 'Users with Recent Orders',
                'sql' => "SELECT u.id, u.name, u.email, COUNT(o.id) as total_orders, SUM(o.total_amount) as total_spent\nFROM users u\nJOIN orders o ON u.id = o.user_id\nGROUP BY u.id, u.name, u.email\nORDER BY total_spent DESC;",
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'sample-2',
                'title' => 'Top Products by Stock',
                'sql' => "SELECT title, sku, price, stock FROM products ORDER BY stock DESC LIMIT 10;",
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function save(string $title, string $sql): array
    {
        $queries = $this->all();
        $id = uniqid('sq_');

        $item = [
            'id' => $id,
            'title' => $title,
            'sql' => $sql,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        array_unshift($queries, $item);
        Session::put(self::SESSION_KEY, $queries);

        return $item;
    }

    public function delete(string $id): void
    {
        $queries = array_values(array_filter(
            $this->all(),
            fn($q) => $q['id'] !== $id
        ));

        Session::put(self::SESSION_KEY, $queries);
    }
}
