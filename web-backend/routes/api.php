<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WasteReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/dashboard-summary', [WasteReportController::class, 'dashboardSummary']);
    Route::get('/reports', [WasteReportController::class, 'index']);
    Route::post('/reports/detect', [WasteReportController::class, 'detect'])->middleware('throttle:30,1');
    Route::post('/reports', [WasteReportController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/reports/{id}', [WasteReportController::class, 'show'])->whereNumber('id');
    Route::delete('/reports/{id}', [WasteReportController::class, 'destroy'])->whereNumber('id');
});
