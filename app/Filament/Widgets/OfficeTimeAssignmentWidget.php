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
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        // Show only logged-in user's office time information
        $hasOfficeTime = $user->office_time_id !== null;
        $officeTime = $user->officeTime;
        
        if ($hasOfficeTime && $officeTime) {
            $startTime = $officeTime->start_time ? $officeTime->start_time->format('H:i') : 'N/A';
            $endTime = $officeTime->end_time ? $officeTime->end_time->format('H:i') : 'N/A';
            
            return [
                Stat::make('Schedule', $officeTime->name)
                    ->description($startTime . ' - ' . $endTime)
                    ->color('info'),
                    
                Stat::make('Working Hours', $officeTime->working_hours_per_day . 'h/day')
                    ->description('Daily hours')
                    ->color('success'),
            ];
        }

        return [
            Stat::make('Office Time', 'Not Assigned')
                ->description('No schedule assigned')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user !== null;
    }
}
