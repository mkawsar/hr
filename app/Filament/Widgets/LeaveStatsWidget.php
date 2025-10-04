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
        
        // Only show company-wide leave stats to admins
        if (!$user || !$user->isAdmin()) {
            return [];
        }

        $pendingLeaves = LeaveApplication::where('status', 'pending')->count();
        $approvedThisMonth = LeaveApplication::where('status', 'approved')
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();
        $totalLeaveDays = LeaveApplication::where('status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->sum('days_count');
        $averageLeaveDays = LeaveApplication::where('status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->avg('days_count');

        return [
            Stat::make('Pending Leave Requests', $pendingLeaves)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            
            Stat::make('Approved This Month', $approvedThisMonth)
                ->description('Leave applications approved')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Total Leave Days', number_format($totalLeaveDays, 1))
                ->description('Days taken this month')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
            
            Stat::make('Average Leave Duration', number_format($averageLeaveDays ?? 0, 1))
                ->description('Days per application')
                ->descriptionIcon('heroicon-o-chart-bar-square')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }
}
