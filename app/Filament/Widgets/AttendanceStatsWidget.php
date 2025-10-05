<?php

namespace App\Filament\Widgets;

use App\Models\DailyAttendance;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AttendanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Only show company-wide attendance stats to admins
        if (!$user || !$user->isAdmin()) {
            return [];
        }

        $today = now()->toDateString();
        
        // Today's attendance stats
        $presentToday = DailyAttendance::where('date', $today)
            ->where('status', 'present')
            ->count();
            
        $lateToday = DailyAttendance::where('date', $today)
            ->where('status', 'late')
            ->count();
            
        $absentToday = User::where('status', 'active')
            ->whereDoesntHave('attendance', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->count();
            
        $totalActiveEmployees = User::where('status', 'active')->count();

        return [
            Stat::make('Present Today', $presentToday)
                ->description('Employees present today')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Late Today', $lateToday)
                ->description('Employees late today')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'), // Red color for late employees
            
            Stat::make('Absent Today', $absentToday)
                ->description('Employees absent today')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
            
            Stat::make('Total Active Employees', $totalActiveEmployees)
                ->description('All active employees')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }
}
