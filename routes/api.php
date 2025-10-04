<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Attendance API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('attendance')->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/monthly', [AttendanceController::class, 'monthly']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::get('/statistics', [AttendanceController::class, 'statistics']);
        Route::get('/locations', [AttendanceController::class, 'locations']);
    });
});
