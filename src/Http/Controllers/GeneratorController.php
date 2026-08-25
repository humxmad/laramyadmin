<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\LaravelCodeGeneratorService;

class GeneratorController extends Controller
{
    public function __construct(
        protected LaravelCodeGeneratorService $generatorService
    ) {}

    public function generate(Request $request, string $table): JsonResponse
    {
        $type = $request->query('type', 'all');

        $result = [];
        if ($type === 'migration' || $type === 'all') {
            $result['migration'] = $this->generatorService->generateMigration($table);
        }
        if ($type === 'model' || $type === 'all') {
            $result['model'] = $this->generatorService->generateModel($table);
        }
        if ($type === 'factory' || $type === 'all') {
            $result['factory'] = $this->generatorService->generateFactory($table);
        }

        return response()->json([
            'table' => $table,
            'code' => $result,
        ]);
    }
}
