<?php

namespace LaraMyAdmin\Drivers;

use Illuminate\Database\ConnectionInterface;
use LaraMyAdmin\Contracts\DatabaseDriverInterface;

abstract class BaseDriver implements DatabaseDriverInterface
{
    protected ConnectionInterface $connection;

    public function setConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getDriverName(): string
    {
        return $this->connection->getDriverName();
    }

    public function getDatabaseName(): string
    {
        return $this->connection->getDatabaseName() ?? 'default';
    }

    public function optimizeTable(string $table): ?string
    {
        return 'Not supported for this database driver.';
    }

    public function repairTable(string $table): ?string
    {
        return 'Not supported for this database driver.';
    }

    protected function formatBytes(int|float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / pow(1024, $power);

        return round($value, $precision) . ' ' . $units[$power];
    }
}
