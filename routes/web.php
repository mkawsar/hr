<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\LeaveReportsController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Day details route for Filament pages (uses session auth)
Route::middleware('auth')->get('/attendance/day-details/{date}', [AttendanceController::class, 'getDayDetails']);

// Leave Reports Routes
Route::middleware('auth')->prefix('reports/leave')->name('reports.leave.')->group(function () {
    // Employee Leave Balance Report
    Route::get('/balance', [LeaveReportsController::class, 'leaveBalanceReport'])->name('balance');
    
    // Leave Summary Report with Date Range
    Route::get('/summary', [LeaveReportsController::class, 'leaveSummaryReport'])->name('summary');
    
    // Leave Analysis Report
    Route::get('/analysis', [LeaveReportsController::class, 'leaveAnalysisReport'])->name('analysis');
    
    // Leave Approval History Report
    Route::get('/approval-history', [LeaveReportsController::class, 'leaveApprovalHistoryReport'])->name('approval-history');
    
    // Get filter options for reports
    Route::get('/filter-options', [LeaveReportsController::class, 'getFilterOptions'])->name('filter-options');
});
