<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\GlobalSearchService;

class SearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:1',
        ]);

        $results = $this->searchService->search(
            $request->input('keyword'),
            (int) $request->input('limit_per_table', 10)
        );

        return response()->json($results);
    }
}
