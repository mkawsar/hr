<?php

namespace App\Filament\Widgets;

use App\Models\LeaveApplication;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TeamPendingLeavesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->isSupervisor()) {
            return [];
        }

        $teamPendingLeaves = LeaveApplication::whereIn('user_id', $user->subordinates->pluck('id'))
            ->where('status', 'pending')
            ->count();

        $teamApprovedLeaves = LeaveApplication::whereIn('user_id', $user->subordinates->pluck('id'))
            ->where('status', 'approved')
            ->whereMonth('approved_at', now()->month)
            ->count();

        $teamRejectedLeaves = LeaveApplication::whereIn('user_id', $user->subordinates->pluck('id'))
            ->where('status', 'rejected')
            ->whereMonth('approved_at', now()->month)
            ->count();

        $teamMembersCount = $user->subordinates->count();

        return [
            Stat::make('Team Members', $teamMembersCount)
                ->description('Total team members')
                ->color('info'),
            Stat::make('Pending Approvals', $teamPendingLeaves)
                ->description('Leave applications awaiting approval')
                ->color('warning'),
            Stat::make('Approved This Month', $teamApprovedLeaves)
                ->description('Leave applications approved this month')
                ->color('success'),
            Stat::make('Rejected This Month', $teamRejectedLeaves)
                ->description('Leave applications rejected this month')
                ->color('danger'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->isSupervisor();
    }
}
