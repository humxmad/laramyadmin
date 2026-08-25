<?php

namespace LaraMyAdmin\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use LaraMyAdmin\Contracts\DatabaseDriverInterface;
use LaraMyAdmin\Drivers\MySqlDriver;
use LaraMyAdmin\Drivers\PostgresDriver;
use LaraMyAdmin\Drivers\SqliteDriver;
use LaraMyAdmin\Drivers\SqlServerDriver;

class ConnectionManager
{
    protected const SESSION_DYNAMIC_KEY = 'laramyadmin_dynamic_connections';
    protected const SESSION_ACTIVE_KEY = 'laramyadmin_active_connection';

    public function getAllConnections(): array
    {
        $configured = array_keys(Config::get('database.connections', []));
        $dynamic = array_keys($this->getDynamicConnections());
        
        $all = [];
        foreach ($configured as $name) {
            $cfg = Config::get("database.connections.{$name}", []);
            $all[] = [
                'name' => $name,
                'driver' => $cfg['driver'] ?? 'unknown',
                'database' => $cfg['database'] ?? '',
                'host' => $cfg['host'] ?? '127.0.0.1',
                'is_dynamic' => false,
                'is_default' => $name === Config::get('database.default'),
            ];
        }

        foreach ($dynamic as $name) {
            $dyn = $this->getDynamicConnections()[$name] ?? [];
            $all[] = [
                'name' => $name,
                'driver' => $dyn['driver'] ?? 'unknown',
                'database' => $dyn['database'] ?? '',
                'host' => $dyn['host'] ?? '127.0.0.1',
                'is_dynamic' => true,
                'is_default' => false,
            ];
        }

        return $all;
    }

    public function getActiveConnectionName(): string
    {
        $active = Session::get(self::SESSION_ACTIVE_KEY);
        if ($active && $this->connectionExists($active)) {
            return $active;
        }

        return Config::get('database.default', 'mysql');
    }

    public function setActiveConnection(string $name): void
    {
        if ($this->connectionExists($name)) {
            Session::put(self::SESSION_ACTIVE_KEY, $name);
            $this->ensureConnectionConfigured($name);
        }
    }

    public function connectionExists(string $name): bool
    {
        if (Config::has("database.connections.{$name}")) {
            return true;
        }
        $dynamic = $this->getDynamicConnections();
        return isset($dynamic[$name]);
    }

    public function getDynamicConnections(): array
    {
        return Session::get(self::SESSION_DYNAMIC_KEY, []);
    }

    public function addDynamicConnection(array $params): string
    {
        $name = $params['name'] ?? ('dynamic_' . uniqid());
        $driver = $params['driver'] ?? 'mysql';

        $config = [
            'driver' => $driver,
            'host' => $params['host'] ?? '127.0.0.1',
            'port' => $params['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'),
            'database' => $params['database'] ?? '',
            'username' => $params['username'] ?? '',
            'password' => $params['password'] ?? '',
            'charset' => $params['charset'] ?? 'utf8mb4',
            'collation' => $params['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];

        if ($driver === 'sqlite') {
            $config = [
                'driver' => 'sqlite',
                'database' => $params['database'] ?? database_path('database.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        // Test connection before saving
        $this->testRawConnection($config);

        $dynamic = $this->getDynamicConnections();
        $dynamic[$name] = $config;
        Session::put(self::SESSION_DYNAMIC_KEY, $dynamic);

        $this->ensureConnectionConfigured($name);
        $this->setActiveConnection($name);

        return $name;
    }

    public function removeDynamicConnection(string $name): void
    {
        $dynamic = $this->getDynamicConnections();
        unset($dynamic[$name]);
        Session::put(self::SESSION_DYNAMIC_KEY, $dynamic);

        if ($this->getActiveConnectionName() === $name) {
            Session::forget(self::SESSION_ACTIVE_KEY);
        }
    }

    public function testRawConnection(array $config): bool
    {
        $tempName = 'laramyadmin_test_' . uniqid();
        Config::set("database.connections.{$tempName}", $config);

        try {
            DB::connection($tempName)->getPdo();
            DB::purge($tempName);
            return true;
        } catch (\Throwable $e) {
            DB::purge($tempName);
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function ensureConnectionConfigured(string $name): void
    {
        $dynamic = $this->getDynamicConnections();
        if (isset($dynamic[$name])) {
            Config::set("database.connections.{$name}", $dynamic[$name]);
        }
    }

    public function getConnection(?string $name = null): ConnectionInterface
    {
        $name = $name ?: $this->getActiveConnectionName();
        $this->ensureConnectionConfigured($name);

        return DB::connection($name);
    }

    public function getDriver(?string $name = null): DatabaseDriverInterface
    {
        $connection = $this->getConnection($name);
        $driverName = $connection->getDriverName();

        $driver = match ($driverName) {
            'mysql', 'mariadb' => new MySqlDriver(),
            'pgsql', 'postgres' => new PostgresDriver(),
            'sqlite' => new SqliteDriver(),
            'sqlsrv', 'sqlserver' => new SqlServerDriver(),
            default => new MySqlDriver(),
        };

        $driver->setConnection($connection);
        return $driver;
    }
}
