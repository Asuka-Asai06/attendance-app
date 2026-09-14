<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->as('api.v1.')->group(function () {

    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['index', 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('attendance-records', AttendanceRecordController::class)
            ->only(['store', 'update', 'destroy']);
    });
});
