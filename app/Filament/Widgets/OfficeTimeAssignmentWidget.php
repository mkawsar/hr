<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\OfficeTime;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OfficeTimeAssignmentWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalEmployees = User::count();
        $employeesWithOfficeTime = User::whereNotNull('office_time_id')->count();
        $employeesWithoutOfficeTime = $totalEmployees - $employeesWithOfficeTime;
        
        $officeTimeStats = OfficeTime::select('office_times.name', 'office_times.code', DB::raw('COUNT(users.id) as employee_count'))
            ->leftJoin('users', 'office_times.id', '=', 'users.office_time_id')
            ->where('office_times.active', true)
            ->groupBy('office_times.id', 'office_times.name', 'office_times.code')
            ->orderBy('employee_count', 'desc')
            ->get();

        $stats = [
            Stat::make('Total Employees', $totalEmployees)
                ->description('All active and inactive employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('With Office Time', $employeesWithOfficeTime)
                ->description($totalEmployees > 0 ? round(($employeesWithOfficeTime / $totalEmployees) * 100, 1) . '% assigned' : '0% assigned')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Without Office Time', $employeesWithoutOfficeTime)
                ->description($totalEmployees > 0 ? round(($employeesWithoutOfficeTime / $totalEmployees) * 100, 1) . '% unassigned' : '0% unassigned')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];

        // Add top office time assignments
        if ($officeTimeStats->isNotEmpty()) {
            $topOfficeTime = $officeTimeStats->first();
            $stats[] = Stat::make('Most Used Schedule', $topOfficeTime->name)
                ->description($topOfficeTime->employee_count . ' employees assigned')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }
}
