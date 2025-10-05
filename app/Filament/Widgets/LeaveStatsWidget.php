<?php

namespace App\Filament\Widgets;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        $startOfMonth = now()->startOfMonth();
        
        // Show only logged-in user's leave data from first date of current month to today
        $pendingLeaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        $approvedThisMonth = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('approved_at', [$startOfMonth, now()])
            ->count();
            
        $totalLeaveDays = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$startOfMonth, now()])
            ->sum('days_count');
            
        $totalLeaveDaysThisYear = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days_count');

        return [
            Stat::make('Pending', $pendingLeaves)
                ->description('Leave requests')
                ->color('warning'),
            
            Stat::make('Approved', $approvedThisMonth)
                ->description('This month')
                ->color('success'),
            
            Stat::make('Leave Days', number_format($totalLeaveDaysThisYear, 0))
                ->description('This year')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user !== null;
    }
}
