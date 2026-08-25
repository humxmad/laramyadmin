<?php

namespace LaraMyAdmin\Drivers;

class PostgresDriver extends BaseDriver
{
    public function getServerVersion(): string
    {
        try {
            $result = $this->connection->selectOne('SHOW server_version');
            return 'PostgreSQL ' . ($result->server_version ?? '');
        } catch (\Throwable $e) {
            return 'PostgreSQL';
        }
    }

    public function getDatabaseSize(): ?string
    {
        try {
            $result = $this->connection->selectOne('SELECT pg_size_pretty(pg_database_size(current_database())) as size');
            return $result->size ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTables(): array
    {
        $tables = [];

        try {
            $rows = $this->connection->select(
                "SELECT 
                    table_name as name, 
                    table_type as type,
                    pg_size_pretty(pg_total_relation_size('\"' || table_schema || '\".\"' || table_name || '\"')) as size,
                    pg_total_relation_size('\"' || table_schema || '\".\"' || table_name || '\"') as size_bytes
                 FROM information_schema.tables 
                 WHERE table_schema = 'public' 
                 ORDER BY table_name ASC"
            );

            foreach ($rows as $row) {
                $isView = stripos($row->type, 'VIEW') !== false;
                $rowCount = 0;

                if (!$isView) {
                    try {
                        $countRes = $this->connection->selectOne("SELECT COUNT(*) as count FROM \"{$row->name}\"");
                        $rowCount = (int) ($countRes->count ?? 0);
                    } catch (\Throwable $e) {
                        $rowCount = 0;
                    }
                }

                $tables[] = [
                    'name' => $row->name,
                    'type' => $isView ? 'view' : 'table',
                    'engine' => 'PostgreSQL',
                    'rows_count' => $rowCount,
                    'size' => $row->size ?? '0 B',
                    'size_bytes' => (int) ($row->size_bytes ?? 0),
                    'data_size' => $row->size ?? '0 B',
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
                    column_name as name,
                    udt_name as data_type,
                    data_type as full_type,
                    is_nullable,
                    column_default,
                    character_maximum_length as max_len
                 FROM information_schema.columns 
                 WHERE table_schema = 'public' AND table_name = ? 
                 ORDER BY ordinal_position ASC",
                [$table]
            );

            $pks = $this->getPrimaryKeyColumns($table);

            foreach ($rows as $row) {
                $isPk = in_array($row->name, $pks, true);
                $columns[] = [
                    'name' => $row->name,
                    'type' => $row->data_type,
                    'full_type' => $row->max_len ? "{$row->data_type}({$row->max_len})" : $row->full_type,
                    'nullable' => strtoupper($row->is_nullable) === 'YES',
                    'default' => $row->column_default,
                    'primary' => $isPk,
                    'unique' => false,
                    'auto_increment' => stripos($row->column_default ?? '', 'nextval') !== false,
                    'extra' => $isPk ? 'PRIMARY KEY' : '',
                    'comment' => '',
                    'collation' => '',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $columns;
    }

    protected function getPrimaryKeyColumns(string $table): array
    {
        try {
            $rows = $this->connection->select(
                "SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                 WHERE tc.constraint_type = 'PRIMARY KEY'
                   AND tc.table_name = ?
                   AND tc.table_schema = 'public'",
                [$table]
            );
            return array_map(fn($r) => $r->column_name, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getTableIndexes(string $table): array
    {
        $indexes = [];
        try {
            $rows = $this->connection->select(
                "SELECT indexname, indexdef 
                 FROM pg_indexes 
                 WHERE schemaname = 'public' AND tablename = ?",
                [$table]
            );

            foreach ($rows as $row) {
                $indexes[] = [
                    'name' => $row->indexname,
                    'columns' => [],
                    'unique' => stripos($row->indexdef, 'UNIQUE') !== false,
                    'primary' => stripos($row->indexname, 'pkey') !== false,
                    'type' => 'INDEX',
                    'comment' => $row->indexdef,
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $indexes;
    }

    public function getTableForeignKeys(string $table): array
    {
        $foreignKeys = [];
        try {
            $rows = $this->connection->select(
                "SELECT
                    tc.constraint_name as name,
                    kcu.column_name as column_name,
                    ccu.table_name AS foreign_table,
                    ccu.column_name AS foreign_column,
                    rc.update_rule as on_update,
                    rc.delete_rule as on_delete
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                 AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                 AND ccu.table_schema = tc.table_schema
                JOIN information_schema.referential_constraints AS rc
                  ON rc.constraint_name = tc.constraint_name
                WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ?",
                [$table]
            );

            foreach ($rows as $row) {
                $foreignKeys[] = [
                    'name' => $row->name,
                    'column' => $row->column_name,
                    'foreign_table' => $row->foreign_table,
                    'foreign_column' => $row->foreign_column,
                    'on_update' => $row->on_update,
                    'on_delete' => $row->on_delete,
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $foreignKeys;
    }

    public function getTableCreateSql(string $table): string
    {
        return "-- Create SQL generator for PostgreSQL table: {$table}";
    }

    public function getViews(): array
    {
        try {
            $rows = $this->connection->select(
                "SELECT table_name as name FROM information_schema.views WHERE table_schema = 'public'"
            );
            return array_map(fn($r) => $r->name, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function createTable(string $table, array $columns, array $options = []): void
    {
        $colDefs = [];
        $pks = [];

        foreach ($columns as $col) {
            $def = "\"{$col['name']}\" " . ($col['type'] ?? 'VARCHAR');
            if (!empty($col['length'])) {
                $def .= "({$col['length']})";
            }
            if (empty($col['nullable'])) {
                $def .= " NOT NULL";
            }
            if (isset($col['default']) && $col['default'] !== '') {
                $def .= " DEFAULT '" . addslashes($col['default']) . "'";
            }
            if (!empty($col['primary'])) {
                $pks[] = "\"{$col['name']}\"";
            }
            $colDefs[] = $def;
        }

        if (!empty($pks)) {
            $colDefs[] = "PRIMARY KEY (" . implode(', ', $pks) . ")";
        }

        $sql = "CREATE TABLE \"{$table}\" (\n  " . implode(",\n  ", $colDefs) . "\n);";
        $this->connection->statement($sql);
    }

    public function dropTable(string $table): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS \"{$table}\" CASCADE");
    }

    public function truncateTable(string $table): void
    {
        $this->connection->statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
    }

    public function renameTable(string $from, string $to): void
    {
        $this->connection->statement("ALTER TABLE \"{$from}\" RENAME TO \"{$to}\"");
    }

    public function addColumn(string $table, array $column): void
    {
        $def = "\"{$column['name']}\" " . ($column['type'] ?? 'VARCHAR');
        if (!empty($column['length'])) {
            $def .= "({$column['length']})";
        }
        if (empty($column['nullable'])) {
            $def .= " NOT NULL";
        }
        $this->connection->statement("ALTER TABLE \"{$table}\" ADD COLUMN {$def}");
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->connection->statement("ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\"");
    }

    public function getSystemInfo(): array
    {
        return [
            'Driver' => 'PostgreSQL',
            'Version' => $this->getServerVersion(),
            'Database' => $this->getDatabaseName(),
            'Database Size' => $this->getDatabaseSize() ?? 'Unknown',
            'Connection Name' => $this->connection->getName(),
        ];
    }
}
