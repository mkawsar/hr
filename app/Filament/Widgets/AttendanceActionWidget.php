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
    
    // Location data properties
    public $latitude = null;
    public $longitude = null;
    public $address = null;

    public function mount(): void
    {
        $this->loadTodayStatus();
        $this->checkLocationPermission();
        
        // Automatically request GPS location when widget loads
        $this->dispatch('requestLocation');
    }

    protected $listeners = ['setLocation'];

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

    public function updateLocation($latitude, $longitude, $address = null): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
    }
    
    public function setLocation($latitude, $longitude, $address = null): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
        
        // Show notification to confirm location was set
        Notification::make()
            ->title('📍 Location Set')
            ->body("Coordinates: {$latitude}, {$longitude}")
            ->success()
            ->send();
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

        // Allow multiple clock ins - no need to check if already clocked in

        // Check if we have real GPS location
        $hasLocation = $this->latitude && $this->longitude;
        
        if (!$hasLocation) {
            // Try to get location from JavaScript first
            $this->dispatch('requestLocation');
            
            // Block clock in without location
            Notification::make()
                ->title('❌ GPS Location Required')
                ->body('You must allow location access to clock in. Please enable GPS location and try again.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Use the controller directly to handle clock in
            $controller = new \App\Http\Controllers\Api\AttendanceEntryController();
            $request = new \Illuminate\Http\Request();
            $request->setUserResolver(function () {
                return Auth::user();
            });
            
            // Add location data if available
            $requestData = ['source' => 'office'];
            if ($hasLocation) {
                $requestData['latitude'] = $this->latitude;
                $requestData['longitude'] = $this->longitude;
                // Address will be automatically generated from GPS coordinates by the controller
            }
            $request->merge($requestData);
            
            $response = $controller->clockIn($request);
            $data = $response->getData(true);
            
            if (isset($data['message']) && str_contains($data['message'], 'Successfully')) {
                Notification::make()
                    ->title('✅ Clock In Successful!')
                    ->body('You have successfully clocked in. You can now clock out when you finish work.')
                    ->success()
                    ->send();
                
                // Refresh the widget data
                $this->loadTodayStatus();
            } else {
                Notification::make()
                    ->title('Clock In Failed')
                    ->body($data['message'] ?? 'An error occurred while clocking in.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Clock In Failed')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }
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

        // Check if we have real GPS location
        $hasLocation = $this->latitude && $this->longitude;
        
        if (!$hasLocation) {
            // Try to get location from JavaScript first
            $this->dispatch('requestLocation');
            
            // Block clock out without location
            Notification::make()
                ->title('❌ GPS Location Required')
                ->body('You must allow location access to clock out. Please enable GPS location and try again.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Use the controller directly to handle clock out
            $controller = new \App\Http\Controllers\Api\AttendanceEntryController();
            $request = new \Illuminate\Http\Request();
            $request->setUserResolver(function () {
                return Auth::user();
            });
            
            // Add location data if available
            $requestData = ['source' => 'office'];
            if ($hasLocation) {
                $requestData['latitude'] = $this->latitude;
                $requestData['longitude'] = $this->longitude;
                // Address will be automatically generated from GPS coordinates by the controller
            }
            $request->merge($requestData);
            
            $response = $controller->clockOut($request);
            $data = $response->getData(true);
            
            if (isset($data['message']) && str_contains($data['message'], 'Successfully')) {
                Notification::make()
                    ->title('✅ Clock Out Successful!')
                    ->body('You have successfully clocked out. Have a great day!')
                    ->success()
                    ->send();
                
                // Refresh the widget data
                $this->loadTodayStatus();
            } else {
                Notification::make()
                    ->title('Clock Out Failed')
                    ->body($data['message'] ?? 'An error occurred while clocking out.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Clock Out Failed')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshStatus(): void
    {
        $this->loadTodayStatus();
        // Don't show notification on refresh as it's called automatically after clock in/out
    }

    public function manualRefresh(): void
    {
        $this->loadTodayStatus();
        Notification::make()
            ->title('Status Refreshed')
            ->body('Attendance status has been updated.')
            ->success()
            ->send();
    }

    public function getLocation(): void
    {
        // For now, let's just set a test location to enable the button
        $this->latitude = 23.8103;
        $this->longitude = 90.4125;
        $this->address = 'Test Location';
        
        Notification::make()
            ->title('📍 Location Set')
            ->body('Test location set - Clock In button should now be enabled')
            ->success()
            ->send();
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }
}
