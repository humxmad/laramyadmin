<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\ExportImportService;
use Symfony\Component\HttpFoundation\Response;

class ExportImportController extends Controller
{
    public function __construct(
        protected ExportImportService $exportImportService
    ) {}

    public function exportSql(Request $request): Response
    {
        $table = $request->query('table');
        $structure = filter_var($request->query('structure', true), FILTER_VALIDATE_BOOLEAN);
        $data = filter_var($request->query('data', true), FILTER_VALIDATE_BOOLEAN);

        return $this->exportImportService->exportSql($table, $structure, $data);
    }

    public function exportCsv(Request $request, string $table): Response
    {
        return $this->exportImportService->exportCsv($table);
    }

    public function exportJson(Request $request, string $table): Response
    {
        return $this->exportImportService->exportJson($table);
    }

    public function importSql(Request $request): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'file' => 'nullable|file',
            'sql' => 'nullable|string',
        ]);

        $sql = '';
        if ($request->hasFile('file')) {
            $sql = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('sql')) {
            $sql = $request->input('sql');
        }

        if (empty($sql)) {
            return response()->json(['error' => 'No SQL content provided.'], 422);
        }

        try {
            $result = $this->exportImportService->importSql($sql);
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function importCsv(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'file' => 'nullable|file',
            'csv' => 'nullable|string',
        ]);

        $content = '';
        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('csv')) {
            $content = $request->input('csv');
        }

        if (empty($content)) {
            return response()->json(['error' => 'No CSV content provided.'], 422);
        }

        try {
            $result = $this->exportImportService->importCsv($table, $content);
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
