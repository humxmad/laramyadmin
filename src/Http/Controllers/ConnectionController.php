<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\ConnectionManager;
use LaraMyAdmin\Services\SchemaService;

class ConnectionController extends Controller
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'active' => $this->connectionManager->getActiveConnectionName(),
            'connections' => $this->connectionManager->getAllConnections(),
        ]);
    }

    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'connection' => 'required|string',
        ]);

        $name = $request->input('connection');
        if (!$this->connectionManager->connectionExists($name)) {
            return response()->json(['error' => "Connection [{$name}] does not exist."], 404);
        }

        $this->connectionManager->setActiveConnection($name);

        try {
            $systemInfo = $this->schemaService->getSystemInfo();
            $tables = $this->schemaService->getTables();
            return response()->json([
                'success' => true,
                'active' => $name,
                'system_info' => $systemInfo,
                'tables' => $tables,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'active' => $name,
            ], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (!config('laramyadmin.allow_dynamic_connections', true)) {
            return response()->json(['error' => 'Dynamic connections are disabled in config.'], 403);
        }

        $request->validate([
            'name' => 'required|string|alpha_dash',
            'driver' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'host' => 'nullable|string',
            'port' => 'nullable|numeric',
            'database' => 'required|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        try {
            $name = $this->connectionManager->addDynamicConnection($request->all());
            $systemInfo = $this->schemaService->getSystemInfo();
            $tables = $this->schemaService->getTables();

            return response()->json([
                'success' => true,
                'message' => "Connected to [{$name}] successfully.",
                'name' => $name,
                'connections' => $this->connectionManager->getAllConnections(),
                'system_info' => $systemInfo,
                'tables' => $tables,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $name): JsonResponse
    {
        $this->connectionManager->removeDynamicConnection($name);
        return response()->json([
            'success' => true,
            'connections' => $this->connectionManager->getAllConnections(),
            'active' => $this->connectionManager->getActiveConnectionName(),
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'driver' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'database' => 'required|string',
        ]);

        try {
            $config = [
                'driver' => $request->input('driver'),
                'host' => $request->input('host', '127.0.0.1'),
                'port' => $request->input('port', $request->input('driver') === 'pgsql' ? '5432' : '3306'),
                'database' => $request->input('database'),
                'username' => $request->input('username', ''),
                'password' => $request->input('password', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];

            if ($request->input('driver') === 'sqlite') {
                $config = [
                    'driver' => 'sqlite',
                    'database' => $request->input('database'),
                    'prefix' => '',
                ];
            }

            $this->connectionManager->testRawConnection($config);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful!',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
