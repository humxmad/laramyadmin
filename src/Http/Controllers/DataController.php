<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\DataService;

class DataController extends Controller
{
    public function __construct(
        protected DataService $dataService
    ) {}

    public function index(Request $request, string $table): JsonResponse
    {
        try {
            $data = $this->dataService->getRows($table, $request->all());
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'data' => 'required|array',
        ]);

        try {
            $this->dataService->insertRow($table, $request->input('data'));
            return response()->json([
                'success' => true,
                'message' => 'Row inserted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'where' => 'required|array',
            'data' => 'required|array',
        ]);

        try {
            $affected = $this->dataService->updateRow($table, $request->input('where'), $request->input('data'));
            return response()->json([
                'success' => true,
                'affected' => $affected,
                'message' => 'Row updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'where' => 'required|array',
        ]);

        try {
            $affected = $this->dataService->deleteRow($table, $request->input('where'));
            return response()->json([
                'success' => true,
                'affected' => $affected,
                'message' => 'Row deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function bulkDestroy(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'where_list' => 'required|array|min:1',
        ]);

        try {
            $affected = $this->dataService->bulkDeleteRows($table, $request->input('where_list'));
            return response()->json([
                'success' => true,
                'affected' => $affected,
                'message' => "{$affected} row(s) deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function duplicate(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'where' => 'required|array',
        ]);

        try {
            $this->dataService->duplicateRow($table, $request->input('where'));
            return response()->json([
                'success' => true,
                'message' => 'Row duplicated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
