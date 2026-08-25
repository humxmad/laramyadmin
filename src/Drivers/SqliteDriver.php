<?php

namespace LaraMyAdmin\Drivers;

class SqliteDriver extends BaseDriver
{
    public function getServerVersion(): string
    {
        try {
            $result = $this->connection->selectOne('SELECT sqlite_version() as version');
            return 'SQLite ' . ($result->version ?? '3.x');
        } catch (\Throwable $e) {
            return 'SQLite 3.x';
        }
    }

    public function getDatabaseSize(): ?string
    {
        try {
            $dbPath = $this->connection->getDatabaseName();
            if ($dbPath && file_exists($dbPath)) {
                return $this->formatBytes(filesize($dbPath));
            }

            $pageCount = $this->connection->selectOne('PRAGMA page_count');
            $pageSize = $this->connection->selectOne('PRAGMA page_size');
            $arr1 = (array) $pageCount;
            $arr2 = (array) $pageSize;
            $pCount = reset($arr1) ?: 0;
            $pSize = reset($arr2) ?: 4096;

            return $this->formatBytes($pCount * $pSize);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTables(): array
    {
        $tables = [];

        try {
            $rows = $this->connection->select(
                "SELECT name, type, sql 
                 FROM sqlite_master 
                 WHERE type IN ('table', 'view') 
                   AND name NOT LIKE 'sqlite_%' 
                 ORDER BY name ASC"
            );

            foreach ($rows as $row) {
                $isView = $row->type === 'view';
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
                    'engine' => 'SQLite',
                    'rows_count' => $rowCount,
                    'size' => '-',
                    'size_bytes' => 0,
                    'data_size' => '-',
                    'index_size' => '-',
                    'collation' => 'BINARY',
                    'comment' => '',
                    'created_at' => null,
                ];
            }
        } catch (\Throwable $e) {
            // Error handling
        }

        return $tables;
    }

    public function getTableColumns(string $table): array
    {
        $columns = [];

        try {
            $rows = $this->connection->select("PRAGMA table_info(\"{$table}\")");

            foreach ($rows as $row) {
                $type = strtolower($row->type ?? 'text');
                $columns[] = [
                    'name' => $row->name,
                    'type' => preg_replace('/\(.*/', '', $type),
                    'full_type' => $row->type ?: 'TEXT',
                    'nullable' => (int) $row->notnull === 0,
                    'default' => $row->dflt_value,
                    'primary' => (int) $row->pk > 0,
                    'unique' => false,
                    'auto_increment' => (int) $row->pk > 0 && stripos($row->type, 'int') !== false,
                    'extra' => (int) $row->pk > 0 ? 'PRIMARY KEY' : '',
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
        $indexes = [];

        try {
            $rows = $this->connection->select("PRAGMA index_list(\"{$table}\")");

            foreach ($rows as $row) {
                $idxName = $row->name;
                $cols = [];
                try {
                    $colRows = $this->connection->select("PRAGMA index_info(\"{$idxName}\")");
                    foreach ($colRows as $cr) {
                        $cols[] = $cr->name;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }

                $indexes[] = [
                    'name' => $idxName,
                    'columns' => $cols,
                    'unique' => (bool) $row->unique,
                    'primary' => stripos($row->origin ?? '', 'pk') !== false,
                    'type' => 'INDEX',
                    'comment' => '',
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
            $rows = $this->connection->select("PRAGMA foreign_key_list(\"{$table}\")");

            foreach ($rows as $row) {
                $foreignKeys[] = [
                    'name' => "fk_{$table}_{$row->from}",
                    'column' => $row->from,
                    'foreign_table' => $row->table,
                    'foreign_column' => $row->to,
                    'on_update' => $row->on_update ?? 'NO ACTION',
                    'on_delete' => $row->on_delete ?? 'NO ACTION',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $foreignKeys;
    }

    public function getTableCreateSql(string $table): string
    {
        try {
            $row = $this->connection->selectOne(
                "SELECT sql FROM sqlite_master WHERE name = ?",
                [$table]
            );
            return $row->sql ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getViews(): array
    {
        try {
            $rows = $this->connection->select("SELECT name FROM sqlite_master WHERE type = 'view'");
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
            $def = "\"{$col['name']}\" " . ($col['type'] ?? 'TEXT');
            if (empty($col['nullable'])) {
                $def .= " NOT NULL";
            }
            if (isset($col['default']) && $col['default'] !== '') {
                $def .= " DEFAULT " . ($col['default'] === 'CURRENT_TIMESTAMP' ? 'CURRENT_TIMESTAMP' : "'" . addslashes($col['default']) . "'");
            }
            if (!empty($col['primary'])) {
                if (!empty($col['auto_increment']) || stripos($col['type'], 'int') !== false) {
                    $def .= " PRIMARY KEY AUTOINCREMENT";
                } else {
                    $pks[] = "\"{$col['name']}\"";
                }
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
        $this->connection->statement("DROP TABLE IF EXISTS \"{$table}\"");
    }

    public function truncateTable(string $table): void
    {
        $this->connection->statement("DELETE FROM \"{$table}\"");
        $this->connection->statement("VACUUM");
    }

    public function renameTable(string $from, string $to): void
    {
        $this->connection->statement("ALTER TABLE \"{$from}\" RENAME TO \"{$to}\"");
    }

    public function addColumn(string $table, array $column): void
    {
        $def = "\"{$column['name']}\" " . ($column['type'] ?? 'TEXT');
        if (empty($column['nullable'])) {
            $def .= " NOT NULL";
        }
        if (isset($column['default']) && $column['default'] !== '') {
            $def .= " DEFAULT '" . addslashes($column['default']) . "'";
        }
        $this->connection->statement("ALTER TABLE \"{$table}\" ADD COLUMN {$def}");
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->connection->statement("ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\"");
    }

    public function optimizeTable(string $table): ?string
    {
        $this->connection->statement("VACUUM");
        return 'Database VACUUM completed.';
    }

    public function getSystemInfo(): array
    {
        return [
            'Driver' => 'SQLite',
            'Version' => $this->getServerVersion(),
            'Database Path' => $this->connection->getDatabaseName(),
            'Database Size' => $this->getDatabaseSize() ?? 'Unknown',
            'Connection Name' => $this->connection->getName(),
        ];
    }
}
