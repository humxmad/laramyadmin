<?php

namespace LaraMyAdmin\Drivers;

class SqlServerDriver extends BaseDriver
{
    public function getServerVersion(): string
    {
        try {
            $result = $this->connection->selectOne('SELECT @@VERSION as version');
            return 'SQL Server ' . substr($result->version ?? '', 0, 30);
        } catch (\Throwable $e) {
            return 'SQL Server';
        }
    }

    public function getDatabaseSize(): ?string
    {
        try {
            $result = $this->connection->selectOne('sp_spaceused');
            return $result->database_size ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTables(): array
    {
        $tables = [];

        try {
            $rows = $this->connection->select(
                "SELECT TABLE_NAME as name, TABLE_TYPE as type 
                 FROM INFORMATION_SCHEMA.TABLES 
                 WHERE TABLE_TYPE IN ('BASE TABLE', 'VIEW') 
                 ORDER BY TABLE_NAME ASC"
            );

            foreach ($rows as $row) {
                $isView = stripos($row->type, 'VIEW') !== false;
                $rowCount = 0;
                if (!$isView) {
                    try {
                        $countRes = $this->connection->selectOne("SELECT COUNT(*) as count FROM [{$row->name}]");
                        $rowCount = (int) ($countRes->count ?? 0);
                    } catch (\Throwable $e) {
                        $rowCount = 0;
                    }
                }

                $tables[] = [
                    'name' => $row->name,
                    'type' => $isView ? 'view' : 'table',
                    'engine' => 'SQL Server',
                    'rows_count' => $rowCount,
                    'size' => '-',
                    'size_bytes' => 0,
                    'data_size' => '-',
                    'index_size' => '-',
                    'collation' => 'default',
                    'comment' => '',
                    'created_at' => null,
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $tables;
    }

    public function getTableColumns(string $table): array
    {
        $columns = [];
        try {
            $rows = $this->connection->select(
                "SELECT 
                    COLUMN_NAME as name,
                    DATA_TYPE as data_type,
                    IS_NULLABLE as is_nullable,
                    COLUMN_DEFAULT as column_default,
                    CHARACTER_MAXIMUM_LENGTH as max_len
                 FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = ? 
                 ORDER BY ORDINAL_POSITION ASC",
                [$table]
            );

            foreach ($rows as $row) {
                $columns[] = [
                    'name' => $row->name,
                    'type' => $row->data_type,
                    'full_type' => $row->max_len ? "{$row->data_type}({$row->max_len})" : $row->data_type,
                    'nullable' => strtoupper($row->is_nullable) === 'YES',
                    'default' => $row->column_default,
                    'primary' => false,
                    'unique' => false,
                    'auto_increment' => false,
                    'extra' => '',
                    'comment' => '',
                    'collation' => '',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $columns;
    }

    public function getTableIndexes(string $table): array
    {
        return [];
    }

    public function getTableForeignKeys(string $table): array
    {
        return [];
    }

    public function getTableCreateSql(string $table): string
    {
        return "-- Create SQL for SQL Server table [{$table}]";
    }

    public function getViews(): array
    {
        return [];
    }

    public function createTable(string $table, array $columns, array $options = []): void
    {
        $colDefs = [];
        foreach ($columns as $col) {
            $def = "[{$col['name']}] " . ($col['type'] ?? 'VARCHAR(255)');
            if (empty($col['nullable'])) {
                $def .= " NOT NULL";
            }
            $colDefs[] = $def;
        }
        $sql = "CREATE TABLE [{$table}] (\n  " . implode(",\n  ", $colDefs) . "\n);";
        $this->connection->statement($sql);
    }

    public function dropTable(string $table): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS [{$table}]");
    }

    public function truncateTable(string $table): void
    {
        $this->connection->statement("TRUNCATE TABLE [{$table}]");
    }

    public function renameTable(string $from, string $to): void
    {
        $this->connection->statement("sp_rename '{$from}', '{$to}'");
    }

    public function addColumn(string $table, array $column): void
    {
        $def = "[{$column['name']}] " . ($column['type'] ?? 'VARCHAR(255)');
        $this->connection->statement("ALTER TABLE [{$table}] ADD {$def}");
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->connection->statement("ALTER TABLE [{$table}] DROP COLUMN [{$column}]");
    }

    public function getSystemInfo(): array
    {
        return [
            'Driver' => 'SQL Server',
            'Version' => $this->getServerVersion(),
            'Database' => $this->getDatabaseName(),
            'Database Size' => $this->getDatabaseSize() ?? 'Unknown',
            'Connection Name' => $this->connection->getName(),
        ];
    }
}
