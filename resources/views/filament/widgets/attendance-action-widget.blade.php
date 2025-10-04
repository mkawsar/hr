<x-filament-widgets::widget>
    @push('scripts')
        <script>
        // Define getCurrentLocation function globally if not already defined
        if (typeof getCurrentLocation === 'undefined') {
            let currentLocation = null;

            function getCurrentLocation() {
                const statusElement = document.getElementById('location-status');
                const locationText = document.getElementById('location-text');
                const coordsElement = document.getElementById('location-coords');
                const latitudeElement = document.getElementById('latitude');
                const longitudeElement = document.getElementById('longitude');
                
                if (!navigator.geolocation) {
                    if (locationText) {
                        locationText.textContent = 'Geolocation not supported';
                    } else {
                        console.warn('Geolocation not supported');
                    }
                    return;
                }

                if (locationText) {
                    locationText.textContent = 'Getting location...';
                }
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        currentLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        };
                        
                        if (locationText) {
                            locationText.textContent = 'Location obtained';
                        }
                        
                        if (latitudeElement && longitudeElement) {
                            latitudeElement.textContent = currentLocation.latitude.toFixed(6);
                            longitudeElement.textContent = currentLocation.longitude.toFixed(6);
                        }
                        
                        if (coordsElement) {
                            coordsElement.classList.remove('hidden');
                        }
                        
                        // Store location in window for use by Livewire and other components
                        window.currentLocation = currentLocation;
                        
                        // Dispatch a custom event for other components to listen to
                        window.dispatchEvent(new CustomEvent('locationObtained', {
                            detail: currentLocation
                        }));
                    },
                    function(error) {
                        let message = 'Location access denied';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                message = 'Location access denied';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = 'Location unavailable';
                                break;
                            case error.TIMEOUT:
                                message = 'Location request timeout';
                                break;
                        }
                        
                        if (locationText) {
                            locationText.textContent = message;
                        } else {
                            console.warn('Location error:', message);
                        }
                        
                        // Dispatch error event
                        window.dispatchEvent(new CustomEvent('locationError', {
                            detail: { error: error, message: message }
                        }));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000 // 5 minutes
                    }
                );
            }

            // Make function globally available
            window.getCurrentLocation = getCurrentLocation;
        }
        </script>
    @endpush
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    <span>Attendance</span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->format('M d, Y - l') }}
                </div>
            </div>
        </x-slot>

        <div class="space-y-6">
            <!-- Working Day Status -->
            @if(!$this->isWorkingDay)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                    <div class="flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                        <span class="text-yellow-800 dark:text-yellow-200 font-medium">Today is not a working day</span>
                    </div>
                </div>
            @endif

            <!-- Current Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Clock In Status -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Clock In</div>
                            <div class="text-lg font-semibold {{ $this->isClockedIn ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $this->isClockedIn ? '✓ Clocked In' : 'Not Clocked In' }}
                            </div>
                            @if($this->openEntry && $this->openEntry['clock_in'])
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($this->openEntry['clock_in'])->format('H:i:s') }}
                                </div>
                            @endif
                        </div>
                        <x-heroicon-o-arrow-down-circle class="w-8 h-8 {{ $this->isClockedIn ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" />
                    </div>
                </div>

                <!-- Clock Out Status -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Clock Out</div>
                            <div class="text-lg font-semibold {{ $this->isClockedOut ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $this->isClockedOut ? '✓ Clocked Out' : 'Not Clocked Out' }}
                            </div>
                            @if($this->isClockedOut && count($this->todayEntries) > 0)
                                @php
                                    $lastEntry = collect($this->todayEntries)->where('clock_out', '!=', null)->last();
                                @endphp
                                @if($lastEntry)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($lastEntry['clock_out'])->format('H:i:s') }}
                                    </div>
                                @endif
                            @endif
                        </div>
                        <x-heroicon-o-arrow-up-circle class="w-8 h-8 {{ $this->isClockedOut ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" />
                    </div>
                </div>

                <!-- Total Hours -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Hours</div>
                            <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                {{ number_format($this->totalWorkingHours, 2) }}h
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Today</div>
                        </div>
                        <x-heroicon-o-clock class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                @if(!$this->isClockedIn && $this->isWorkingDay)
                    <x-filament::button
                        wire:click="clockIn"
                        color="success"
                        size="lg"
                        class="flex-1"
                        icon="heroicon-o-arrow-down-circle"
                    >
                        Clock In
                    </x-filament::button>
                @elseif($this->isClockedIn && !$this->isClockedOut)
                    <x-filament::button
                        wire:click="clockOut"
                        color="warning"
                        size="lg"
                        class="flex-1"
                        icon="heroicon-o-arrow-up-circle"
                    >
                        Clock Out
                    </x-filament::button>
                @elseif($this->isClockedOut)
                    <div class="flex-1 text-center py-3 px-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-300 font-medium">Attendance Complete for Today</span>
                    </div>
                @endif

                <x-filament::button
                    wire:click="refreshStatus"
                    color="gray"
                    size="lg"
                    icon="heroicon-o-arrow-path"
                >
                    Refresh
                </x-filament::button>
            </div>

            <!-- Location Status -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Location Tracking</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div id="location-status" class="text-sm text-gray-500 dark:text-gray-400">
                            <span id="location-text">Requesting permission...</span>
                        </div>
                        <button 
                            id="get-location-btn"
                            class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded"
                            onclick="getCurrentLocation()"
                        >
                            Get Location
                        </button>
                    </div>
                </div>
                <div id="location-coords" class="text-xs text-gray-500 dark:text-gray-400 mt-2 hidden">
                    <span id="latitude"></span>, <span id="longitude"></span>
                </div>
            </div>

            <!-- Today's Entries -->
            @if(count($this->todayEntries) > 0)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Today's Entries</h4>
                    <div class="space-y-2">
                        @foreach($this->todayEntries as $entry)
                            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($entry['clock_in'])->format('H:i') }}
                                            @if($entry['clock_out'])
                                                - {{ \Carbon\Carbon::parse($entry['clock_out'])->format('H:i') }}
                                            @else
                                                - Present
                                            @endif
                                        </div>
                                        @if($entry['working_hours'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ number_format($entry['working_hours'], 2) }}h
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ ucfirst($entry['source']) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
// Widget-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize location for this widget
    if (document.getElementById('get-location-btn')) {
        getCurrentLocation();
    }
});
</script>
