<?php

namespace LaraMyAdmin\Services;

class MockDataService
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function generate(string $table, int $count = 10): int
    {
        $connection = $this->connectionManager->getConnection();
        $columns = $this->schemaService->getTableColumns($table);
        $inserted = 0;

        $firstNames = ['Alex', 'Emma', 'Liam', 'Olivia', 'Noah', 'Sophia', 'James', 'Mia', 'Lucas', 'Charlotte', 'Ethan', 'Amelia'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Anderson', 'Taylor'];
        $domains = ['example.com', 'test.org', 'laramyadmin.io', 'mail.test'];
        $cities = ['New York', 'London', 'Tokyo', 'Paris', 'Dubai', 'Sydney', 'Toronto', 'Singapore', 'Berlin'];
        $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'proin', 'sapien'];

        for ($i = 0; $i < $count; $i++) {
            $row = [];
            $fn = $firstNames[array_rand($firstNames)];
            $ln = $lastNames[array_rand($lastNames)];

            foreach ($columns as $col) {
                $name = $col['name'];
                $lname = strtolower($name);
                $type = strtolower($col['type']);

                // Skip auto-increment primary keys
                if (!empty($col['auto_increment']) || ($name === 'id' && !empty($col['primary']))) {
                    continue;
                }

                if ($lname === 'created_at' || $lname === 'updated_at') {
                    $row[$name] = date('Y-m-d H:i:s', time() - rand(0, 86400 * 30));
                    continue;
                }

                if ($lname === 'name' || $lname === 'full_name') {
                    $row[$name] = "{$fn} {$ln}";
                } elseif ($lname === 'first_name') {
                    $row[$name] = $fn;
                } elseif ($lname === 'last_name') {
                    $row[$name] = $ln;
                } elseif ($lname === 'email') {
                    $row[$name] = strtolower("{$fn}.{$ln}." . rand(100, 9999) . "@" . $domains[array_rand($domains)]);
                } elseif ($lname === 'password') {
                    $row[$name] = password_hash('password', PASSWORD_DEFAULT);
                } elseif (str_contains($lname, 'phone')) {
                    $row[$name] = '+1 (' . rand(200, 999) . ') ' . rand(200, 999) . '-' . rand(1000, 9999);
                } elseif (str_contains($lname, 'city')) {
                    $row[$name] = $cities[array_rand($cities)];
                } elseif (str_contains($lname, 'price') || str_contains($lname, 'amount') || str_contains($lname, 'total') || str_contains($lname, 'balance')) {
                    $row[$name] = round(rand(1000, 99900) / 100, 2);
                } elseif (str_contains($lname, 'sku') || str_contains($lname, 'code') || str_contains($lname, 'order_number')) {
                    $row[$name] = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
                } elseif (str_contains($lname, 'status')) {
                    $statuses = ['active', 'pending', 'completed', 'inactive'];
                    $row[$name] = $statuses[array_rand($statuses)];
                } elseif (str_contains($lname, 'is_') || str_contains($lname, 'has_') || in_array($type, ['bool', 'boolean', 'tinyint(1)'])) {
                    $row[$name] = (rand(1, 10) > 2) ? 1 : 0;
                } elseif ($lname === 'title') {
                    $row[$name] = ucfirst($words[array_rand($words)]) . ' ' . $words[array_rand($words)] . ' ' . rand(1, 100);
                } elseif (in_array($type, ['text', 'longtext', 'mediumtext']) || str_contains($lname, 'description') || str_contains($lname, 'body')) {
                    $row[$name] = 'Sample description content for testing. ' . implode(' ', array_slice($words, 0, rand(4, 9))) . '.';
                } elseif (in_array($type, ['json', 'jsonb']) || str_contains($lname, 'preferences') || str_contains($lname, 'metadata')) {
                    $row[$name] = json_encode(['mock' => true, 'timestamp' => time(), 'code' => rand(1000, 9999)]);
                } elseif (in_array($type, ['datetime', 'timestamp'])) {
                    $row[$name] = date('Y-m-d H:i:s', time() - rand(0, 86400 * 60));
                } elseif ($type === 'date') {
                    $row[$name] = date('Y-m-d', time() - rand(0, 86400 * 60));
                } elseif (str_contains($type, 'int')) {
                    if (str_ends_with($lname, '_id')) {
                        $row[$name] = 1;
                    } else {
                        $row[$name] = rand(1, 250);
                    }
                } else {
                    $row[$name] = ucfirst($words[array_rand($words)]) . ' ' . rand(10, 99);
                }
            }

            try {
                $connection->table($table)->insert($row);
                $inserted++;
            } catch (\Throwable $e) {
                // Ignore individual row collision
            }
        }

        return $inserted;
    }
}
