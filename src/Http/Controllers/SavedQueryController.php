<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\SavedQueryService;

class SavedQueryController extends Controller
{
    public function __construct(
        protected SavedQueryService $savedQueryService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'saved_queries' => $this->savedQueryService->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'sql' => 'required|string',
        ]);

        $item = $this->savedQueryService->save(
            $request->input('title'),
            $request->input('sql')
        );

        return response()->json([
            'success' => true,
            'item' => $item,
            'saved_queries' => $this->savedQueryService->all(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->savedQueryService->delete($id);

        return response()->json([
            'success' => true,
            'saved_queries' => $this->savedQueryService->all(),
        ]);
    }
}
