<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\SchemaDiffService;

class DiffController extends Controller
{
    public function __construct(
        protected SchemaDiffService $diffService
    ) {}

    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string',
            'target' => 'required|string',
        ]);

        try {
            $diff = $this->diffService->compare(
                $request->input('source'),
                $request->input('target')
            );

            return response()->json($diff);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
