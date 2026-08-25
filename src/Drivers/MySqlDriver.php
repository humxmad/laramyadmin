<?php

namespace LaraMyAdmin\Drivers;

use Illuminate\Support\Facades\DB;

class MySqlDriver extends BaseDriver
{
    public function getServerVersion(): string
    {
        try {
            $result = $this->connection->selectOne('SELECT VERSION() as version');
            return $result->version ?? 'Unknown';
        } catch (\Throwable $e) {
            return 'Unknown';
        }
    }

    public function getDatabaseSize(): ?string
    {
        try {
            $dbName = $this->getDatabaseName();
            $result = $this->connection->selectOne(
                "SELECT SUM(data_length + index_length) AS size 
                 FROM information_schema.TABLES 
                 WHERE table_schema = ?",
                [$dbName]
            );
            return $this->formatBytes($result->size ?? 0);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTables(): array
    {
        $dbName = $this->getDatabaseName();
        $tables = [];

        try {
            $statuses = $this->connection->select(
                "SELECT 
                    TABLE_NAME as name, 
                    TABLE_TYPE as type, 
                    ENGINE as engine, 
                    TABLE_ROWS as rows_count, 
                    DATA_LENGTH as data_length, 
                    INDEX_LENGTH as index_length, 
                    (DATA_LENGTH + INDEX_LENGTH) as total_size,
                    TABLE_COLLATION as collation, 
                    TABLE_COMMENT as comment,
                    CREATE_TIME as created_at
                 FROM information_schema.TABLES 
                 WHERE table_schema = ? 
                 ORDER BY TABLE_NAME ASC",
                [$dbName]
            );

            foreach ($statuses as $row) {
                $isView = strtoupper($row->type ?? '') === 'VIEW';
                $tables[] = [
                    'name' => $row->name,
                    'type' => $isView ? 'view' : 'table',
                    'engine' => $row->engine ?? ($isView ? 'VIEW' : 'InnoDB'),
                    'rows_count' => (int) ($row->rows_count ?? 0),
                    'size' => $this->formatBytes((float) ($row->total_size ?? 0)),
                    'size_bytes' => (int) ($row->total_size ?? 0),
                    'data_size' => $this->formatBytes((float) ($row->data_length ?? 0)),
                    'index_size' => $this->formatBytes((float) ($row->index_length ?? 0)),
                    'collation' => $row->collation ?? 'utf8mb4_unicode_ci',
                    'comment' => $row->comment ?? '',
                    'created_at' => $row->created_at ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback to basic SHOW TABLES
            $rawTables = $this->connection->select('SHOW FULL TABLES');
            foreach ($rawTables as $row) {
                $arr = (array) $row;
                $tableName = array_values($arr)[0] ?? null;
                $tableType = array_values($arr)[1] ?? 'BASE TABLE';
                if ($tableName) {
                    $tables[] = [
                        'name' => $tableName,
                        'type' => stripos($tableType, 'VIEW') !== false ? 'view' : 'table',
                        'engine' => 'InnoDB',
                        'rows_count' => 0,
                        'size' => '0 B',
                        'size_bytes' => 0,
                        'collation' => 'utf8mb4_unicode_ci',
                        'comment' => '',
                        'created_at' => null,
                    ];
                }
            }
        }

        return $tables;
    }

    public function getTableColumns(string $table): array
    {
        $dbName = $this->getDatabaseName();
        $columns = [];

        try {
            $rows = $this->connection->select(
                "SELECT 
                    COLUMN_NAME as name,
                    COLUMN_TYPE as full_type,
                    DATA_TYPE as data_type,
                    IS_NULLABLE as is_nullable,
                    COLUMN_DEFAULT as column_default,
                    COLUMN_KEY as column_key,
                    EXTRA as extra,
                    COLUMN_COMMENT as comment,
                    COLLATION_NAME as collation
                 FROM information_schema.COLUMNS 
                 WHERE table_schema = ? AND table_name = ? 
                 ORDER BY ORDINAL_POSITION ASC",
                [$dbName, $table]
            );

            foreach ($rows as $row) {
                $columns[] = [
                    'name' => $row->name,
                    'type' => $row->data_type,
                    'full_type' => $row->full_type,
                    'nullable' => strtoupper($row->is_nullable) === 'YES',
                    'default' => $row->column_default,
                    'primary' => strtoupper($row->column_key) === 'PRI',
                    'unique' => strtoupper($row->column_key) === 'UNI',
                    'auto_increment' => stripos($row->extra, 'auto_increment') !== false,
                    'extra' => $row->extra,
                    'comment' => $row->comment,
                    'collation' => $row->collation,
                ];
            }
        } catch (\Throwable $e) {
            $rows = $this->connection->select("SHOW FULL COLUMNS FROM `{$table}`");
            foreach ($rows as $row) {
                $columns[] = [
                    'name' => $row->Field,
                    'type' => preg_replace('/\(.*/', '', $row->Type),
                    'full_type' => $row->Type,
                    'nullable' => strtoupper($row->Null) === 'YES',
                    'default' => $row->Default,
                    'primary' => strtoupper($row->Key) === 'PRI',
                    'unique' => strtoupper($row->Key) === 'UNI',
                    'auto_increment' => stripos($row->Extra, 'auto_increment') !== false,
                    'extra' => $row->Extra,
                    'comment' => $row->Comment ?? '',
                    'collation' => $row->Collation ?? '',
                ];
            }
        }

        return $columns;
    }

    public function getTableIndexes(string $table): array
    {
        $indexes = [];
        try {
            $rows = $this->connection->select("SHOW INDEX FROM `{$table}`");
            $grouped = [];
            foreach ($rows as $row) {
                $name = $row->Key_name;
                if (!isset($grouped[$name])) {
                    $grouped[$name] = [
                        'name' => $name,
                        'columns' => [],
                        'unique' => (int)$row->Non_unique === 0,
                        'primary' => $name === 'PRIMARY',
                        'type' => $row->Index_type ?? 'BTREE',
                        'comment' => $row->Comment ?? '',
                    ];
                }
                $grouped[$name]['columns'][] = $row->Column_name;
            }
            $indexes = array_values($grouped);
        } catch (\Throwable $e) {
            // Ignore if error
        }

        return $indexes;
    }

    public function getTableForeignKeys(string $table): array
    {
        $foreignKeys = [];
        $dbName = $this->getDatabaseName();

        try {
            $rows = $this->connection->select(
                "SELECT 
                    k.CONSTRAINT_NAME as name,
                    k.COLUMN_NAME as column_name,
                    k.REFERENCED_TABLE_NAME as foreign_table,
                    k.REFERENCED_COLUMN_NAME as foreign_column,
                    r.UPDATE_RULE as on_update,
                    r.DELETE_RULE as on_delete
                 FROM information_schema.KEY_COLUMN_USAGE k
                 JOIN information_schema.REFERENTIAL_CONSTRAINTS r 
                   ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
                 WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName, $table]
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
        try {
            $result = $this->connection->selectOne("SHOW CREATE TABLE `{$table}`");
            $arr = (array) $result;
            return $arr['Create Table'] ?? $arr['Create View'] ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getViews(): array
    {
        $dbName = $this->getDatabaseName();
        try {
            $rows = $this->connection->select(
                "SELECT TABLE_NAME as name FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ?",
                [$dbName]
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
            $def = "`{$col['name']}` {$col['type']}";
            if (!empty($col['length'])) {
                $def .= "({$col['length']})";
            }
            if (!empty($col['unsigned'])) {
                $def .= " UNSIGNED";
            }
            if (empty($col['nullable'])) {
                $def .= " NOT NULL";
            } else {
                $def .= " NULL";
            }
            if (isset($col['default']) && $col['default'] !== '') {
                $def .= " DEFAULT " . ($col['default'] === 'CURRENT_TIMESTAMP' ? 'CURRENT_TIMESTAMP' : "'" . addslashes($col['default']) . "'");
            }
            if (!empty($col['auto_increment'])) {
                $def .= " AUTO_INCREMENT";
            }
            if (!empty($col['comment'])) {
                $def .= " COMMENT '" . addslashes($col['comment']) . "'";
            }
            if (!empty($col['primary'])) {
                $pks[] = "`{$col['name']}`";
            }
            $colDefs[] = $def;
        }

        if (!empty($pks)) {
            $colDefs[] = "PRIMARY KEY (" . implode(', ', $pks) . ")";
        }

        $engine = $options['engine'] ?? 'InnoDB';
        $charset = $options['charset'] ?? 'utf8mb4';
        $collation = $options['collation'] ?? 'utf8mb4_unicode_ci';
        $comment = !empty($options['comment']) ? " COMMENT='" . addslashes($options['comment']) . "'" : '';

        $sql = "CREATE TABLE `{$table}` (\n  " . implode(",\n  ", $colDefs) . "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation}{$comment};";
        $this->connection->statement($sql);
    }

    public function dropTable(string $table): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS `{$table}`");
    }

    public function truncateTable(string $table): void
    {
        $this->connection->statement("TRUNCATE TABLE `{$table}`");
    }

    public function renameTable(string $from, string $to): void
    {
        $this->connection->statement("RENAME TABLE `{$from}` TO `{$to}`");
    }

    public function addColumn(string $table, array $column): void
    {
        $def = "`{$column['name']}` {$column['type']}";
        if (!empty($column['length'])) {
            $def .= "({$column['length']})";
        }
        if (!empty($column['unsigned'])) {
            $def .= " UNSIGNED";
        }
        if (empty($column['nullable'])) {
            $def .= " NOT NULL";
        } else {
            $def .= " NULL";
        }
        if (isset($column['default']) && $column['default'] !== '') {
            $def .= " DEFAULT " . ($column['default'] === 'CURRENT_TIMESTAMP' ? 'CURRENT_TIMESTAMP' : "'" . addslashes($column['default']) . "'");
        }
        if (!empty($column['auto_increment'])) {
            $def .= " AUTO_INCREMENT";
        }
        if (!empty($column['after'])) {
            $def .= " AFTER `{$column['after']}`";
        } elseif (!empty($column['first'])) {
            $def .= " FIRST";
        }

        $this->connection->statement("ALTER TABLE `{$table}` ADD {$def}");
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->connection->statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    public function optimizeTable(string $table): ?string
    {
        $res = $this->connection->select("OPTIMIZE TABLE `{$table}`");
        return $res[0]->Msg_text ?? 'OK';
    }

    public function repairTable(string $table): ?string
    {
        $res = $this->connection->select("REPAIR TABLE `{$table}`");
        return $res[0]->Msg_text ?? 'OK';
    }

    public function getSystemInfo(): array
    {
        return [
            'Driver' => 'MySQL / MariaDB',
            'Version' => $this->getServerVersion(),
            'Database' => $this->getDatabaseName(),
            'Database Size' => $this->getDatabaseSize() ?? 'Unknown',
            'Connection Name' => $this->connection->getName(),
        ];
    }
}
