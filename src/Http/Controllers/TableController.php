<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\SchemaService;

class TableController extends Controller
{
    public function __construct(
        protected SchemaService $schemaService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $tables = $this->schemaService->getTables();
            $systemInfo = $this->schemaService->getSystemInfo();

            return response()->json([
                'tables' => $tables,
                'system_info' => $systemInfo,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $table): JsonResponse
    {
        try {
            $columns = $this->schemaService->getTableColumns($table);
            $indexes = $this->schemaService->getTableIndexes($table);
            $foreignKeys = $this->schemaService->getTableForeignKeys($table);
            $createSql = $this->schemaService->getTableCreateSql($table);

            return response()->json([
                'table' => $table,
                'columns' => $columns,
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
                'create_sql' => $createSql,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'table' => 'required|string',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string',
            'columns.*.type' => 'required|string',
        ]);

        try {
            $this->schemaService->createTable(
                $request->input('table'),
                $request->input('columns'),
                $request->input('options', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Table [{$request->input('table')}] created successfully.",
                'tables' => $this->schemaService->getTables(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        try {
            $this->schemaService->dropTable($table);
            return response()->json([
                'success' => true,
                'message' => "Table [{$table}] dropped successfully.",
                'tables' => $this->schemaService->getTables(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function truncate(string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        try {
            $this->schemaService->truncateTable($table);
            return response()->json([
                'success' => true,
                'message' => "Table [{$table}] truncated successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function rename(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate(['new_name' => 'required|string']);

        try {
            $newName = $request->input('new_name');
            $this->schemaService->renameTable($table, $newName);
            return response()->json([
                'success' => true,
                'message' => "Table [{$table}] renamed to [{$newName}].",
                'tables' => $this->schemaService->getTables(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function optimize(string $table): JsonResponse
    {
        try {
            $result = $this->schemaService->optimizeTable($table);
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function repair(string $table): JsonResponse
    {
        try {
            $result = $this->schemaService->repairTable($table);
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
