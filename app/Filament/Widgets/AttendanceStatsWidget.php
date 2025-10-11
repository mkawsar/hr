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
        
        if (!$user) {
            return [];
        }

        $startOfMonth = now()->startOfMonth();
        
        // User's attendance stats from first date of current month to today
        $presentThisMonth = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->where('status', 'present')
            ->count();
            
        $lateThisMonth = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->where('status', 'late')
            ->count();
            
        // Count absent days as working days with no attendance entries
        // Optimize by getting all attendance records for the month in one query
        $attendanceRecords = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->pluck('date')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->toDateString();
            })
            ->toArray();
            
        $absentThisMonth = 0;
        $currentDate = $startOfMonth->copy();
        while ($currentDate->lte(now())) {
            // Check if it's a working day (Monday to Friday)
            if ($currentDate->isWeekday()) {
                // Check if user has no attendance record for this date
                if (!in_array($currentDate->toDateString(), $attendanceRecords)) {
                    $absentThisMonth++;
                }
            }
            $currentDate->addDay();
        }
            
        $totalWorkingDays = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->whereIn('status', ['present', 'late'])
            ->count();

        return [
            Stat::make('Present', $presentThisMonth)
                ->description('This month')
                ->color('success'),
            
            Stat::make('Late', $lateThisMonth)
                ->description('This month')
                ->color('warning'),
            
            Stat::make('Absent', $absentThisMonth)
                ->description('This month')
                ->color('danger'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user !== null;
    }
}
