<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\SchemaService;

class SchemaController extends Controller
{
    public function __construct(
        protected SchemaService $schemaService
    ) {}

    public function addColumn(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'column' => 'required|array',
            'column.name' => 'required|string',
            'column.type' => 'required|string',
        ]);

        try {
            $this->schemaService->addColumn($table, $request->input('column'));
            return response()->json([
                'success' => true,
                'message' => "Column [{$request->input('column.name')}] added successfully.",
                'columns' => $this->schemaService->getTableColumns($table),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function dropColumn(Request $request, string $table, string $column): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        try {
            $this->schemaService->dropColumn($table, $column);
            return response()->json([
                'success' => true,
                'message' => "Column [{$column}] dropped successfully.",
                'columns' => $this->schemaService->getTableColumns($table),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
