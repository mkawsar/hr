<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AttendanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        
        // Today's attendance stats
        $presentToday = Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->count();
            
        $lateToday = Attendance::whereDate('date', $today)
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
                ->color('warning'),
            
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
}
