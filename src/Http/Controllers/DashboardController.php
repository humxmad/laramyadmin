<?php

namespace LaraMyAdmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraMyAdmin\Services\ConnectionManager;
use LaraMyAdmin\Services\SchemaService;

class DashboardController extends Controller
{
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected SchemaService $schemaService
    ) {}

    public function index()
    {
        $activeConnection = $this->connectionManager->getActiveConnectionName();
        $connections = $this->connectionManager->getAllConnections();

        $systemInfo = [];
        $tables = [];

        try {
            $systemInfo = $this->schemaService->getSystemInfo();
            $tables = $this->schemaService->getTables();
        } catch (\Throwable $e) {
            $systemInfo = [
                'Error' => $e->getMessage(),
                'Connection' => $activeConnection,
            ];
        }

        return view('laramyadmin::studio', [
            'activeConnection' => $activeConnection,
            'connections' => $connections,
            'systemInfo' => $systemInfo,
            'tables' => $tables,
            'config' => [
                'path' => config('laramyadmin.path', 'laramyadmin'),
                'readOnly' => config('laramyadmin.read_only', false),
                'allowDynamic' => config('laramyadmin.allow_dynamic_connections', true),
                'defaultLimit' => config('laramyadmin.default_limit', 100),
            ],
        ]);
    }
}
