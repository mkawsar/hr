<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceEntryController;

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

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

// Test endpoint to check authentication status
Route::get('/auth-test', function (Request $request) {
    return response()->json([
        'authenticated' => Auth::check(),
        'user' => Auth::user() ? Auth::user()->only(['id', 'name', 'email']) : null,
        'session_id' => session()->getId()
    ]);
});

// Attendance API routes (temporarily without auth for testing)
// Route::middleware(['auth:web'])->group(function () {
Route::group([], function () {
    Route::prefix('attendance')->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/monthly', [AttendanceController::class, 'monthly']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::get('/statistics', [AttendanceController::class, 'statistics']);
        Route::get('/locations', [AttendanceController::class, 'locations']);
        Route::get('/entries/{date}', [AttendanceController::class, 'getEntriesForDate']);
        Route::get('/day-details/{date}', [AttendanceController::class, 'getDayDetails']);
    });
    
    // New attendance entry API routes
    Route::prefix('attendance-entry')->group(function () {
        Route::post('/clock-in', [AttendanceEntryController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceEntryController::class, 'clockOut']);
        Route::get('/today', [AttendanceEntryController::class, 'today']);
        Route::get('/locations', [AttendanceEntryController::class, 'locations']);
    });
});
