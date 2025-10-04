<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class AttendanceActionWidget extends Widget
{
    protected static string $view = 'filament.widgets.attendance-action-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public $isClockedIn = false;
    public $isClockedOut = false;
    public $currentLocation = null;
    public $locationPermission = false;
    public $isWorkingDay = true;
    public $todayEntries = [];
    public $totalWorkingHours = 0;
    public $openEntry = null;

    public function mount(): void
    {
        $this->loadTodayStatus();
        $this->checkLocationPermission();
    }

    public function loadTodayStatus(): void
    {
        try {
            // Use the controller directly instead of HTTP call for better performance
            $controller = new \App\Http\Controllers\Api\AttendanceEntryController();
            $request = new \Illuminate\Http\Request();
            $request->setUserResolver(function () {
                return Auth::user();
            });
            
            $response = $controller->today();
            $data = $response->getData(true);

            $this->isClockedIn = $data['is_clocked_in'] ?? false;
            $this->isClockedOut = $data['is_clocked_out'] ?? false;
            $this->isWorkingDay = $data['is_working_day'] ?? true;
            $this->todayEntries = $data['entries'] ?? [];
            $this->totalWorkingHours = $data['total_working_hours'] ?? 0;
            $this->openEntry = $data['open_entry'] ?? null;
        } catch (\Exception $e) {
            // Handle error silently for now
            $this->isClockedIn = false;
            $this->isClockedOut = false;
            $this->isWorkingDay = true;
            $this->todayEntries = [];
            $this->totalWorkingHours = 0;
            $this->openEntry = null;
        }
    }

    public function checkLocationPermission(): void
    {
        // This will be handled by JavaScript
        $this->locationPermission = false;
    }

    public function getCurrentLocation(): void
    {
        // This will be handled by JavaScript
    }

    public function clockIn(): void
    {
        if (!$this->isWorkingDay) {
            Notification::make()
                ->title('Cannot Clock In')
                ->body('Today is not a working day.')
                ->danger()
                ->send();
            return;
        }

        if ($this->isClockedIn) {
            Notification::make()
                ->title('Already Clocked In')
                ->body('You are already clocked in. Please clock out first.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('clock-in-requested');
    }

    public function clockOut(): void
    {
        if (!$this->isClockedIn) {
            Notification::make()
                ->title('Not Clocked In')
                ->body('You are not currently clocked in.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('clock-out-requested');
    }

    public function refreshStatus(): void
    {
        $this->loadTodayStatus();
        Notification::make()
            ->title('Status Refreshed')
            ->body('Attendance status has been updated.')
            ->success()
            ->send();
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }
}
