<?php

namespace App\Filament\Widgets;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MyLeaveStatusWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->isEmployee()) {
            return [];
        }

        $pendingLeaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $approvedLeaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('approved_at', now()->year)
            ->count();

        $rejectedLeaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->whereYear('applied_at', now()->year)
            ->count();

        $totalLeaveDays = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('approved_at', now()->year)
            ->sum('days_count');

        return [
            Stat::make('Pending Applications', $pendingLeaves)
                ->description('Leave applications awaiting approval')
                ->color('warning'),
            Stat::make('Approved This Year', $approvedLeaves)
                ->description('Leave applications approved this year')
                ->color('success'),
            Stat::make('Rejected This Year', $rejectedLeaves)
                ->description('Leave applications rejected this year')
                ->color('danger'),
            Stat::make('Total Leave Days', $totalLeaveDays)
                ->description('Total approved leave days this year')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->isEmployee();
    }
}
