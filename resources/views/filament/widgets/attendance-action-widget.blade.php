<x-filament-widgets::widget>
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
                            @php
                                $firstClockIn = null;
                                if($this->openEntry && $this->openEntry['clock_in']) {
                                    $firstClockIn = $this->openEntry['clock_in'];
                                } elseif(count($this->todayEntries) > 0) {
                                    $firstClockIn = collect($this->todayEntries)->first()['clock_in'];
                                }
                            @endphp
                            @if($firstClockIn)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($firstClockIn)->format('H:i:s') }}
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
                            @php
                                $lastClockOut = null;
                                if(count($this->todayEntries) > 0) {
                                    $lastEntry = collect($this->todayEntries)->where('clock_out', '!=', null)->last();
                                    if($lastEntry) {
                                        $lastClockOut = $lastEntry['clock_out'];
                                    }
                                }
                            @endphp
                            @if($lastClockOut)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($lastClockOut)->format('H:i:s') }}
                                </div>
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
                @if($this->isWorkingDay)
                    @php
                        $hasLocation = $this->latitude && $this->longitude;
                    @endphp
                    
                    @if($this->isClockedIn && !$this->isClockedOut)
                        <!-- Has open entry - show Clock Out button -->
                        <x-filament::button
                            wire:click="clockOut"
                            color="warning"
                            size="lg"
                            class="flex-1"
                            icon="heroicon-o-arrow-up-circle"
                            :disabled="!$hasLocation"
                        >
                            @if($hasLocation)
                                Clock Out
                            @else
                                Clock Out (Location Required)
                            @endif
                        </x-filament::button>
                    @else
                        <!-- No open entry - show Clock In button -->
                        <x-filament::button
                            wire:click="clockIn"
                            color="success"
                            size="lg"
                            class="flex-1"
                            icon="heroicon-o-arrow-down-circle"
                            :disabled="!$hasLocation"
                        >
                            @if($hasLocation)
                                Clock In
                            @else
                                Clock In (Location Required)
                            @endif
                        </x-filament::button>
                    @endif
                @endif

                <x-filament::button
                    wire:click="manualRefresh"
                    color="gray"
                    size="lg"
                    icon="heroicon-o-arrow-path"
                >
                    Refresh
                </x-filament::button>
            </div>

            <!-- Location Status -->
            @php
                $hasLocation = $this->latitude && $this->longitude;
            @endphp
            <div class="bg-{{ $hasLocation ? 'green' : 'red' }}-50 dark:bg-{{ $hasLocation ? 'green' : 'red' }}-900/20 border border-{{ $hasLocation ? 'green' : 'red' }}-200 dark:border-{{ $hasLocation ? 'green' : 'red' }}-700 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-{{ $hasLocation ? 'green' : 'red' }}-600 dark:text-{{ $hasLocation ? 'green' : 'red' }}-400" />
                        <span class="text-sm font-medium text-{{ $hasLocation ? 'green' : 'red' }}-800 dark:text-{{ $hasLocation ? 'green' : 'red' }}-200">
                            @if($hasLocation)
                                ✅ GPS Location Active
                            @else
                                ❌ GPS Location Required
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div id="location-status" class="text-sm text-{{ $hasLocation ? 'green' : 'red' }}-600 dark:text-{{ $hasLocation ? 'green' : 'red' }}-400">
                            <span id="location-text">
                                @if($hasLocation)
                                    Location captured
                                @else
                                    Getting GPS location...
                                @endif
                            </span>
                        </div>
                        <button 
                            id="get-location-btn"
                            class="text-xs bg-{{ $hasLocation ? 'green' : 'red' }}-100 dark:bg-{{ $hasLocation ? 'green' : 'red' }}-900 text-{{ $hasLocation ? 'green' : 'red' }}-800 dark:text-{{ $hasLocation ? 'green' : 'red' }}-200 px-2 py-1 rounded hover:bg-{{ $hasLocation ? 'green' : 'red' }}-200 dark:hover:bg-{{ $hasLocation ? 'green' : 'red' }}-800 transition-colors"
                            wire:click="getLocation"
                            title="Click to get GPS location"
                        >
                            @if($hasLocation)
                                Update GPS
                            @else
                                Get GPS
                            @endif
                        </button>
                    </div>
                </div>
                <div id="location-coords" class="text-xs text-gray-500 dark:text-gray-400 mt-2 hidden">
                    <div class="mb-1">
                        <span class="font-medium">Coordinates:</span> <span id="latitude"></span>, <span id="longitude"></span>
                    </div>
                    <div id="location-address" class="hidden">
                        <span class="font-medium">Address:</span> <span class="text-gray-600 dark:text-gray-300 break-words" id="address-text"></span>
                    </div>
                    <div class="mt-1 text-xs text-{{ $hasLocation ? 'green' : 'red' }}-600 dark:text-{{ $hasLocation ? 'green' : 'red' }}-400">
                        <span class="font-medium">Note:</span> GPS location is <strong>REQUIRED</strong> for all clock in/out actions. Please enable location access to use attendance features.
                    </div>
                    @if($this->latitude && $this->longitude)
                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-medium">Widget Location:</span> {{ $this->latitude }}, {{ $this->longitude }}
                                @if($this->address)
                                    <br><span class="font-medium">Address:</span> {{ $this->address }}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Today's Entries -->
            @if(count($this->todayEntries) > 0)
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Today's Entries</h4>
                    <div class="space-y-2">
                        @foreach($this->todayEntries as $entry)
                            <div class="py-3 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between mb-2">
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
                                
                                @if($entry['clock_in_address'] || $entry['clock_out_address'])
                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                        <div class="space-y-1">
                                            @if($entry['clock_in_address'])
                                                <div class="text-xs">
                                                    <span class="text-blue-600 dark:text-blue-400 font-medium">Clock In:</span>
                                                    <span class="text-gray-600 dark:text-gray-300 ml-1 break-words">{{ $entry['clock_in_address'] }}</span>
                                                </div>
                                            @endif
                                            @if($entry['clock_out_address'])
                                                <div class="text-xs">
                                                    <span class="text-green-600 dark:text-green-400 font-medium">Clock Out:</span>
                                                    <span class="text-gray-600 dark:text-gray-300 ml-1 break-words">{{ $entry['clock_out_address'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
document.addEventListener('livewire:init', () => {
    // Listen for location request from widget
    Livewire.on('requestLocation', () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by this browser.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                // Update the display
                const locationText = document.getElementById('location-text');
                const latitudeElement = document.getElementById('latitude');
                const longitudeElement = document.getElementById('longitude');
                const coordsElement = document.getElementById('location-coords');
                
                if (locationText) locationText.textContent = 'Location captured';
                if (latitudeElement) latitudeElement.textContent = latitude.toFixed(6);
                if (longitudeElement) longitudeElement.textContent = longitude.toFixed(6);
                if (coordsElement) coordsElement.classList.remove('hidden');
                
                // Directly call the widget method
                @this.call('setLocation', latitude, longitude, 'GPS Location');
            },
            function(error) {
                alert('Error getting location: ' + error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    });
});
</script>
