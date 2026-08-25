<?php

namespace LaraMyAdmin\Services;

use LaraMyAdmin\Contracts\DatabaseDriverInterface;

class SchemaService
{
    public function __construct(
        protected ConnectionManager $connectionManager
    ) {}

    protected function getDriver(): DatabaseDriverInterface
    {
        return $this->connectionManager->getDriver();
    }

    public function getTables(): array
    {
        return $this->getDriver()->getTables();
    }

    public function getTableColumns(string $table): array
    {
        return $this->getDriver()->getTableColumns($table);
    }

    public function getTableIndexes(string $table): array
    {
        return $this->getDriver()->getTableIndexes($table);
    }

    public function getTableForeignKeys(string $table): array
    {
        return $this->getDriver()->getTableForeignKeys($table);
    }

    public function getTableCreateSql(string $table): string
    {
        return $this->getDriver()->getTableCreateSql($table);
    }

    public function createTable(string $table, array $columns, array $options = []): void
    {
        $this->getDriver()->createTable($table, $columns, $options);
    }

    public function dropTable(string $table): void
    {
        $this->getDriver()->dropTable($table);
    }

    public function truncateTable(string $table): void
    {
        $this->getDriver()->truncateTable($table);
    }

    public function renameTable(string $from, string $to): void
    {
        $this->getDriver()->renameTable($from, $to);
    }

    public function addColumn(string $table, array $column): void
    {
        $this->getDriver()->addColumn($table, $column);
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->getDriver()->dropColumn($table, $column);
    }

    public function optimizeTable(string $table): ?string
    {
        return $this->getDriver()->optimizeTable($table);
    }

    public function repairTable(string $table): ?string
    {
        return $this->getDriver()->repairTable($table);
    }

    public function getSystemInfo(): array
    {
        return $this->getDriver()->getSystemInfo();
    }
}
