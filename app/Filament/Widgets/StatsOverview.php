<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalEmployees = User::count();
        $activeEmployees = User::where('status', 'active')->count();
        $presentToday = Attendance::whereDate('date', today())->where('status', 'present')->count();
        $pendingLeaves = LeaveApplication::where('status', 'pending')->count();

        return [
            Stat::make('Total Employees', $totalEmployees)
                ->description('All employees in the system')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            
            Stat::make('Active Employees', $activeEmployees)
                ->description('Currently active employees')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Present Today', $presentToday)
                ->description('Employees present today')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
            
            Stat::make('Pending Leave Requests', $pendingLeaves)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
