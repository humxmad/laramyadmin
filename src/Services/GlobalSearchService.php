<?php

namespace LaraMyAdmin\Services;

class GlobalSearchService
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function search(string $keyword, int $limitPerTable = 10): array
    {
        $connection = $this->connectionManager->getConnection();
        $tables = $this->schemaService->getTables();
        $results = [];
        $totalMatches = 0;

        foreach ($tables as $t) {
            $tableName = $t['name'];
            if ($t['type'] === 'view') continue;

            try {
                $columns = $this->schemaService->getTableColumns($tableName);
                $searchableCols = array_filter($columns, function ($c) {
                    $t = strtolower($c['type']);
                    return in_array($t, ['varchar', 'string', 'text', 'mediumtext', 'longtext', 'json', 'int', 'integer', 'bigint']);
                });

                if (empty($searchableCols)) continue;

                $query = $connection->table($tableName);
                $query->where(function ($q) use ($searchableCols, $keyword) {
                    $first = true;
                    foreach ($searchableCols as $col) {
                        if ($first) {
                            $q->where($col['name'], 'LIKE', "%{$keyword}%");
                            $first = false;
                        } else {
                            $q->orWhere($col['name'], 'LIKE', "%{$keyword}%");
                        }
                    }
                });

                $count = (clone $query)->count();
                if ($count > 0) {
                    $rows = $query->limit($limitPerTable)->get();
                    $results[] = [
                        'table' => $tableName,
                        'matches_count' => $count,
                        'columns' => array_map(fn($c) => $c['name'], $columns),
                        'rows' => $rows,
                    ];
                    $totalMatches += $count;
                }
            } catch (\Throwable $e) {
                // Ignore query failure on specific table
            }
        }

        return [
            'keyword' => $keyword,
            'total_matches' => $totalMatches,
            'tables_matched_count' => count($results),
            'results' => $results,
        ];
    }
}
