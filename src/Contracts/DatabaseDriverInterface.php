<?php

namespace LaraMyAdmin\Contracts;

use Illuminate\Database\ConnectionInterface;

interface DatabaseDriverInterface
{
    public function setConnection(ConnectionInterface $connection): self;

    public function getConnection(): ConnectionInterface;

    public function getDriverName(): string;

    public function getServerVersion(): string;

    public function getDatabaseName(): string;

    public function getDatabaseSize(): ?string;

    public function getTables(): array;

    public function getTableColumns(string $table): array;

    public function getTableIndexes(string $table): array;

    public function getTableForeignKeys(string $table): array;

    public function getTableCreateSql(string $table): string;

    public function getViews(): array;

    public function createTable(string $table, array $columns, array $options = []): void;

    public function dropTable(string $table): void;

    public function truncateTable(string $table): void;

    public function renameTable(string $from, string $to): void;

    public function addColumn(string $table, array $column): void;

    public function dropColumn(string $table, string $column): void;

    public function optimizeTable(string $table): ?string;

    public function repairTable(string $table): ?string;

    public function getSystemInfo(): array;
}
