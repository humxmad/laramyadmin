<?php

namespace LaraMyAdmin\Services;

class SchemaDiffService
{
    public function __construct(
        protected ConnectionManager $connectionManager
    ) {}

    public function compare(string $sourceConn, string $targetConn): array
    {
        $sourceDriver = $this->connectionManager->getDriver($sourceConn);
        $targetDriver = $this->connectionManager->getDriver($targetConn);

        $sourceTables = array_column($sourceDriver->getTables(), 'name');
        $targetTables = array_column($targetDriver->getTables(), 'name');

        $missingInTarget = array_values(array_diff($sourceTables, $targetTables));
        $missingInSource = array_values(array_diff($targetTables, $sourceTables));
        $commonTables = array_values(array_intersect($sourceTables, $targetTables));

        $tableDiffs = [];

        foreach ($commonTables as $table) {
            $sourceCols = $sourceDriver->getTableColumns($table);
            $targetCols = $targetDriver->getTableColumns($table);

            $sourceColMap = array_column($sourceCols, null, 'name');
            $targetColMap = array_column($targetCols, null, 'name');

            $colsMissingInTarget = array_values(array_diff(array_keys($sourceColMap), array_keys($targetColMap)));
            $colsMissingInSource = array_values(array_diff(array_keys($targetColMap), array_keys($sourceColMap)));

            $typeMismatches = [];
            foreach ($sourceColMap as $colName => $sCol) {
                if (isset($targetColMap[$colName])) {
                    $tCol = $targetColMap[$colName];
                    if ($sCol['type'] !== $tCol['type'] || $sCol['nullable'] !== $tCol['nullable']) {
                        $typeMismatches[] = [
                            'column' => $colName,
                            'source' => "{$sCol['full_type']} " . ($sCol['nullable'] ? 'NULL' : 'NOT NULL'),
                            'target' => "{$tCol['full_type']} " . ($tCol['nullable'] ? 'NULL' : 'NOT NULL'),
                        ];
                    }
                }
            }

            if (!empty($colsMissingInTarget) || !empty($colsMissingInSource) || !empty($typeMismatches)) {
                $tableDiffs[] = [
                    'table' => $table,
                    'missing_columns_in_target' => $colsMissingInTarget,
                    'missing_columns_in_source' => $colsMissingInSource,
                    'type_mismatches' => $typeMismatches,
                ];
            }
        }

        return [
            'source_connection' => $sourceConn,
            'target_connection' => $targetConn,
            'missing_tables_in_target' => $missingInTarget,
            'missing_tables_in_source' => $missingInSource,
            'table_differences' => $tableDiffs,
            'has_differences' => !empty($missingInTarget) || !empty($missingInSource) || !empty($tableDiffs),
        ];
    }
}
