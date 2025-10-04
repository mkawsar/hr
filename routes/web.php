<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Day details route for Filament pages (uses session auth)
Route::middleware('auth')->get('/attendance/day-details/{date}', [AttendanceController::class, 'getDayDetails']);
