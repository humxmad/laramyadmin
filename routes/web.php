<?php

use Illuminate\Support\Facades\Route;
use LaraMyAdmin\Http\Controllers\ConnectionController;
use LaraMyAdmin\Http\Controllers\DashboardController;
use LaraMyAdmin\Http\Controllers\DataController;
use LaraMyAdmin\Http\Controllers\ExportImportController;
use LaraMyAdmin\Http\Controllers\QueryController;
use LaraMyAdmin\Http\Controllers\SchemaController;
use LaraMyAdmin\Http\Controllers\TableController;

Route::get('/', [DashboardController::class, 'index'])->name('laramyadmin.dashboard');

Route::prefix('api')->name('laramyadmin.api.')->group(function () {
    // Connections
    Route::get('connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('connections/switch', [ConnectionController::class, 'switch'])->name('connections.switch');
    Route::post('connections', [ConnectionController::class, 'store'])->name('connections.store');
    Route::delete('connections/{name}', [ConnectionController::class, 'destroy'])->name('connections.destroy');
    Route::post('connections/test', [ConnectionController::class, 'test'])->name('connections.test');

    // Tables & Metadata
    Route::get('tables', [TableController::class, 'index'])->name('tables.index');
    Route::post('tables', [TableController::class, 'store'])->name('tables.store');
    Route::get('tables/{table}', [TableController::class, 'show'])->name('tables.show');
    Route::delete('tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    Route::post('tables/{table}/truncate', [TableController::class, 'truncate'])->name('tables.truncate');
    Route::post('tables/{table}/rename', [TableController::class, 'rename'])->name('tables.rename');
    Route::post('tables/{table}/optimize', [TableController::class, 'optimize'])->name('tables.optimize');
    Route::post('tables/{table}/repair', [TableController::class, 'repair'])->name('tables.repair');

    // Data CRUD
    Route::get('tables/{table}/rows', [DataController::class, 'index'])->name('data.index');
    Route::post('tables/{table}/rows', [DataController::class, 'store'])->name('data.store');
    Route::put('tables/{table}/rows', [DataController::class, 'update'])->name('data.update');
    Route::delete('tables/{table}/rows', [DataController::class, 'destroy'])->name('data.destroy');
    Route::post('tables/{table}/rows/bulk-delete', [DataController::class, 'bulkDestroy'])->name('data.bulk_destroy');
    Route::post('tables/{table}/rows/duplicate', [DataController::class, 'duplicate'])->name('data.duplicate');

    // Schema alterations
    Route::post('tables/{table}/columns', [SchemaController::class, 'addColumn'])->name('schema.add_column');
    Route::delete('tables/{table}/columns/{column}', [SchemaController::class, 'dropColumn'])->name('schema.drop_column');

    // Raw Query Runner & History
    Route::post('query/execute', [QueryController::class, 'execute'])->name('query.execute');
    Route::post('query/explain', [QueryController::class, 'explain'])->name('query.explain');
    Route::get('query/history', [QueryController::class, 'history'])->name('query.history');
    Route::delete('query/history', [QueryController::class, 'clearHistory'])->name('query.clear_history');

    // Import
    Route::post('import/sql', [ExportImportController::class, 'importSql'])->name('import.sql');
    Route::post('import/{table}/csv', [ExportImportController::class, 'importCsv'])->name('import.csv');
});

// Exports
Route::get('export/sql', [ExportImportController::class, 'exportSql'])->name('laramyadmin.export.sql');
Route::get('export/{table}/csv', [ExportImportController::class, 'exportCsv'])->name('laramyadmin.export.csv');
Route::get('export/{table}/json', [ExportImportController::class, 'exportJson'])->name('laramyadmin.export.json');
