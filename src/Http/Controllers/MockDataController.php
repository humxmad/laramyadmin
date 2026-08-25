<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\MockDataService;

class MockDataController extends Controller
{
    public function __construct(
        protected MockDataService $mockDataService
    ) {}

    public function generate(Request $request, string $table): JsonResponse
    {
        if (config('laramyadmin.read_only', false)) {
            return response()->json(['error' => 'Action disabled in read-only mode.'], 403);
        }

        $request->validate([
            'count' => 'nullable|integer|min:1|max:500',
        ]);

        $count = (int) $request->input('count', 10);

        try {
            $inserted = $this->mockDataService->generate($table, $count);
            return response()->json([
                'success' => true,
                'inserted' => $inserted,
                'message' => "Generated and inserted {$inserted} test records into [{$table}].",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
