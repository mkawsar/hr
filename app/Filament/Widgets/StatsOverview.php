<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\DailyAttendance;
use App\Models\LeaveApplication;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        // Show only logged-in user's data
        $today = today()->toDateString();
        
        // User's attendance today
        $attendanceToday = DailyAttendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        $presentToday = $attendanceToday && $attendanceToday->status === 'present' ? 1 : 0;
        
        // User's pending leave applications
        $pendingLeaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        // User's approved leaves from first date of current month to today
        $startOfMonth = now()->startOfMonth();
            
        // User's total working days from first date of current month to today
        $workingDaysThisMonth = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->whereIn('status', ['present', 'late'])
            ->count();
            
        // Count absent days using a single query instead of N+1 queries
        $absentThisMonth = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, now()])
            ->whereIn('status', ['absent'])
            ->count();

        return [
            Stat::make('Today\'s Status', $presentToday ? 'Present' : 'Absent')
                ->description($presentToday ? 'You are present today' : 'You are absent today')
                ->color($presentToday ? 'success' : 'danger'),
            
            Stat::make('Working Days', $workingDaysThisMonth)
                ->description('Present this month')
                ->color('primary'),
            
            Stat::make('Pending Leaves', $pendingLeaves)
                ->description('Awaiting approval')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user !== null;
    }
}
