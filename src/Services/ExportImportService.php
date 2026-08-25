<?php

namespace LaraMyAdmin\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportService
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function exportSql(?string $table = null, bool $includeStructure = true, bool $includeData = true): StreamedResponse
    {
        $connection = $this->connectionManager->getConnection();
        $dbName = $connection->getDatabaseName();
        $driver = $this->connectionManager->getDriver();

        $tables = $table ? [$table] : array_map(fn($t) => $t['name'], $driver->getTables());
        $filename = ($table ?: $dbName) . '_' . date('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () use ($tables, $driver, $connection, $includeStructure, $includeData, $dbName) {
            echo "-- LaraMyAdmin SQL Dump\n";
            echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Database: {$dbName}\n";
            echo "-- ------------------------------------------------------\n\n";

            foreach ($tables as $t) {
                if ($includeStructure) {
                    echo "-- Table structure for `{$t}`\n";
                    echo "DROP TABLE IF EXISTS `{$t}`;\n";
                    $createSql = $driver->getTableCreateSql($t);
                    if ($createSql) {
                        echo "{$createSql};\n\n";
                    }
                }

                if ($includeData) {
                    echo "-- Dumping data for table `{$t}`\n";
                    $rows = $connection->table($t)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows as $row) {
                            $rowArr = (array) $row;
                            $cols = array_map(fn($c) => "`{$c}`", array_keys($rowArr));
                            $vals = array_map(function ($val) {
                                if ($val === null) return 'NULL';
                                return "'" . addslashes((string)$val) . "'";
                            }, array_values($rowArr));

                            echo "INSERT INTO `{$t}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                        }
                    }
                    echo "\n";
                }
            }
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function exportCsv(string $table): StreamedResponse
    {
        $connection = $this->connectionManager->getConnection();
        $filename = "{$table}_" . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($connection, $table) {
            $handle = fopen('php://output', 'w');
            $rows = $connection->table($table)->get();

            if ($rows->isNotEmpty()) {
                // Header
                $header = array_keys((array) $rows[0]);
                fputcsv($handle, $header);

                foreach ($rows as $row) {
                    fputcsv($handle, (array) $row);
                }
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportJson(string $table): StreamedResponse
    {
        $connection = $this->connectionManager->getConnection();
        $filename = "{$table}_" . date('Y-m-d_His') . '.json';

        return response()->streamDownload(function () use ($connection, $table) {
            $rows = $connection->table($table)->get();
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function importSql(string $sql): array
    {
        $connection = $this->connectionManager->getConnection();
        $statements = array_filter(
            array_map('trim', explode(";\n", $sql)),
            fn($s) => !empty($s) && !str_starts_with($s, '--')
        );

        $executed = 0;
        $errors = [];

        foreach ($statements as $stmt) {
            try {
                $connection->unprepared($stmt);
                $executed++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'statement' => substr($stmt, 0, 100) . '...',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_statements' => count($statements),
            'executed' => $executed,
            'errors' => $errors,
        ];
    }

    public function importCsv(string $table, string $csvContent): array
    {
        $connection = $this->connectionManager->getConnection();
        $lines = explode("\n", trim($csvContent));
        if (empty($lines)) {
            throw new \Exception("CSV content is empty");
        }

        $header = str_getcsv(array_shift($lines));
        $inserted = 0;

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $rowValues = str_getcsv($line);
            if (count($rowValues) !== count($header)) continue;

            $data = array_combine($header, $rowValues);
            $connection->table($table)->insert($data);
            $inserted++;
        }

        return ['inserted_rows' => $inserted];
    }
}
