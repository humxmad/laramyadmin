<?php

namespace LaraMyAdmin\Services;

use Illuminate\Support\Facades\DB;

class DataService
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function getRows(string $table, array $params = []): array
    {
        $connection = $this->connectionManager->getConnection();
        $query = $connection->table($table);

        $columns = $this->schemaService->getTableColumns($table);
        $primaryKeys = array_values(array_map(
            fn($c) => $c['name'],
            array_filter($columns, fn($c) => !empty($c['primary']))
        ));

        // Filtering
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $filter) {
                $col = $filter['column'] ?? null;
                $op = strtoupper($filter['operator'] ?? '=');
                $val = $filter['value'] ?? null;

                if (!$col) {
                    continue;
                }

                match ($op) {
                    'IS NULL' => $query->whereNull($col),
                    'IS NOT NULL' => $query->whereNotNull($col),
                    'LIKE' => $query->where($col, 'LIKE', "%{$val}%"),
                    'NOT LIKE' => $query->where($col, 'NOT LIKE', "%{$val}%"),
                    'IN' => $query->whereIn($col, array_map('trim', explode(',', $val ?? ''))),
                    'NOT IN' => $query->whereNotIn($col, array_map('trim', explode(',', $val ?? ''))),
                    default => $query->where($col, $op, $val),
                };
            }
        }

        // Global search across columns
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $index => $col) {
                    if ($index === 0) {
                        $q->where($col['name'], 'LIKE', "%{$search}%");
                    } else {
                        $q->orWhere($col['name'], 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // Total count before pagination
        try {
            $total = $query->count();
        } catch (\Throwable $e) {
            $total = 0;
        }

        // Sorting
        $sortCol = $params['sort_col'] ?? (!empty($primaryKeys) ? $primaryKeys[0] : ($columns[0]['name'] ?? null));
        $sortDir = strtolower($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        if ($sortCol) {
            $query->orderBy($sortCol, $sortDir);
        }

        // Pagination
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(500, max(1, (int) ($params['per_page'] ?? config('laramyadmin.default_limit', 100))));
        $rows = $query->forPage($page, $perPage)->get();

        return [
            'columns' => $columns,
            'primary_keys' => $primaryKeys,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            'sort_col' => $sortCol,
            'sort_dir' => $sortDir,
        ];
    }

    public function insertRow(string $table, array $data): bool
    {
        $connection = $this->connectionManager->getConnection();
        return $connection->table($table)->insert($data);
    }

    public function updateRow(string $table, array $where, array $data): int
    {
        $connection = $this->connectionManager->getConnection();
        $query = $connection->table($table);

        foreach ($where as $key => $val) {
            if ($val === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, '=', $val);
            }
        }

        return $query->update($data);
    }

    public function deleteRow(string $table, array $where): int
    {
        $connection = $this->connectionManager->getConnection();
        $query = $connection->table($table);

        foreach ($where as $key => $val) {
            if ($val === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, '=', $val);
            }
        }

        return $query->delete();
    }

    public function bulkDeleteRows(string $table, array $whereList): int
    {
        $deleted = 0;
        foreach ($whereList as $where) {
            $deleted += $this->deleteRow($table, $where);
        }
        return $deleted;
    }

    public function duplicateRow(string $table, array $where): bool
    {
        $connection = $this->connectionManager->getConnection();
        $query = $connection->table($table);

        foreach ($where as $key => $val) {
            if ($val === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, '=', $val);
            }
        }

        $row = (array) $query->first();
        if (!$row) {
            return false;
        }

        $columns = $this->schemaService->getTableColumns($table);
        foreach ($columns as $col) {
            if (!empty($col['auto_increment']) || (!empty($col['primary']) && count($where) === 1)) {
                unset($row[$col['name']]);
            }
        }

        return $connection->table($table)->insert($row);
    }
}
