<?php

use Illuminate\Support\Facades\Route;
use NikunjKothiya\QueueMonitor\Http\Controllers\DashboardController;
use NikunjKothiya\QueueMonitor\Http\Controllers\FailureController;

Route::group([
    'prefix' => config('queue-monitor.route_prefix', 'queue-monitor'),
    'as' => 'queue-monitor.',
    'middleware' => config('queue-monitor.middleware', ['web', 'auth', 'queue-monitor']),
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/failures', [FailureController::class, 'index'])->name('failures.index');
    Route::get('/failures/{failure}', [FailureController::class, 'show'])->name('failures.show');
    Route::post('/failures/{failure}/retry', [FailureController::class, 'retry'])->name('failures.retry');
    Route::post('/failures/{failure}/resolve', [FailureController::class, 'resolve'])->name('failures.resolve');
    Route::post('/failures/bulk-resolve', [FailureController::class, 'bulkResolve'])->name('failures.bulk-resolve');
    Route::post('/failures/clear', [FailureController::class, 'clearAll'])->name('failures.clear');
});


