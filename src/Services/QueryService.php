<?php

namespace LaraMyAdmin\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class QueryService
{
    protected const SESSION_HISTORY_KEY = 'laramyadmin_query_history';

    public function __construct(
        protected ConnectionManager $connectionManager
    ) {}

    public function execute(string $sql, ?int $limit = null): array
    {
        $connection = $this->connectionManager->getConnection();
        $trimmedSql = trim($sql);
        $startTime = microtime(true);

        $this->logHistory($trimmedSql);

        $isSelect = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|PRAGMA)\b/i', $trimmedSql);

        try {
            if ($isSelect) {
                // Execute SELECT query
                $results = $connection->select($trimmedSql);
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                $columns = [];
                if (!empty($results)) {
                    $columns = array_keys((array) $results[0]);
                }

                return [
                    'success' => true,
                    'is_select' => true,
                    'columns' => $columns,
                    'rows' => $results,
                    'rows_count' => count($results),
                    'affected_rows' => 0,
                    'execution_time_ms' => $duration,
                    'sql' => $trimmedSql,
                ];
            } else {
                // Execute non-SELECT query
                $affected = $connection->affectingStatement($trimmedSql);
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                return [
                    'success' => true,
                    'is_select' => false,
                    'columns' => [],
                    'rows' => [],
                    'rows_count' => 0,
                    'affected_rows' => $affected,
                    'execution_time_ms' => $duration,
                    'sql' => $trimmedSql,
                ];
            }
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => $duration,
                'sql' => $trimmedSql,
            ];
        }
    }

    public function explain(string $sql): array
    {
        $connection = $this->connectionManager->getConnection();
        $explainSql = "EXPLAIN " . trim($sql);

        try {
            $results = $connection->select($explainSql);
            $columns = !empty($results) ? array_keys((array) $results[0]) : [];

            return [
                'success' => true,
                'columns' => $columns,
                'rows' => $results,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getHistory(): array
    {
        return Session::get(self::SESSION_HISTORY_KEY, []);
    }

    public function clearHistory(): void
    {
        Session::forget(self::SESSION_HISTORY_KEY);
    }

    protected function logHistory(string $sql): void
    {
        $history = $this->getHistory();
        array_unshift($history, [
            'sql' => $sql,
            'connection' => $this->connectionManager->getActiveConnectionName(),
            'executed_at' => date('Y-m-d H:i:s'),
        ]);

        // Keep maximum 50 queries in history
        $history = array_slice($history, 0, 50);
        Session::put(self::SESSION_HISTORY_KEY, $history);
    }
}
