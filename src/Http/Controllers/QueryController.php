<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\QueryService;

class QueryController extends Controller
{
    public function __construct(
        protected QueryService $queryService
    ) {}

    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string',
        ]);

        $sql = $request->input('sql');

        if (config('laramyadmin.read_only', false)) {
            $isDestructive = preg_match('/^\s*(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE)\b/i', $sql);
            if ($isDestructive) {
                return response()->json(['error' => 'Destructive queries are disabled in read-only mode.'], 403);
            }
        }

        $result = $this->queryService->execute($sql);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function explain(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string',
        ]);

        $result = $this->queryService->explain($request->input('sql'));
        return response()->json($result);
    }

    public function history(): JsonResponse
    {
        return response()->json([
            'history' => $this->queryService->getHistory(),
        ]);
    }

    public function clearHistory(): JsonResponse
    {
        $this->queryService->clearHistory();
        return response()->json(['success' => true]);
    }
}
