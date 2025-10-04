@if (isset($data))
    <script>
        window.filamentData = @js($data)
    </script>
@endif

<script>
// Global getCurrentLocation function for attendance functionality
if (typeof getCurrentLocation === 'undefined') {
    let currentLocation = null;

    function getCurrentLocation() {
        const statusElement = document.getElementById('location-status');
        const locationText = document.getElementById('location-text');
        const coordsElement = document.getElementById('location-coords');
        const latitudeElement = document.getElementById('latitude');
        const longitudeElement = document.getElementById('longitude');
        const addressElement = document.getElementById('location-address');
        
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
                
                // Get address from coordinates using reverse geocoding
                getAddressFromCoordinates(currentLocation.latitude, currentLocation.longitude)
                    .then(address => {
                        currentLocation.address = address;
                        
                        if (addressElement) {
                            const addressTextElement = document.getElementById('address-text');
                            if (addressTextElement) {
                                addressTextElement.textContent = address;
                            }
                            addressElement.classList.remove('hidden');
                        }
                        
                        // Store location in window for use by Livewire and other components
                        window.currentLocation = currentLocation;
                        
                        // Dispatch a custom event for other components to listen to
                        window.dispatchEvent(new CustomEvent('locationObtained', {
                            detail: currentLocation
                        }));
                    })
                    .catch(error => {
                        console.warn('Failed to get address:', error);
                        // Still store location even if address lookup fails
                        window.currentLocation = currentLocation;
                        window.dispatchEvent(new CustomEvent('locationObtained', {
                            detail: currentLocation
                        }));
                    });
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

    // Function to get address from coordinates using reverse geocoding
    async function getAddressFromCoordinates(lat, lng) {
        try {
            // Using OpenStreetMap Nominatim API (free, no API key required)
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                {
                    headers: {
                        'User-Agent': 'Laravel-HR-System/1.0'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error('Reverse geocoding failed');
            }
            
            const data = await response.json();
            
            if (data && data.display_name) {
                // Format the address nicely
                const address = data.display_name;
                return address;
            } else {
                throw new Error('No address found');
            }
        } catch (error) {
            console.warn('Reverse geocoding error:', error);
            // Fallback: return coordinates as address
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    // Clock in function
    function performClockIn() {
        const data = {
            source: 'office'
        };
        
        if (window.currentLocation) {
            data.latitude = window.currentLocation.latitude;
            data.longitude = window.currentLocation.longitude;
            if (window.currentLocation.address) {
                data.address = window.currentLocation.address;
            }
        }
        
        fetch('/api/attendance-entry/clock-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.message) {
                // Show notification
                if (data.message.includes('Successfully')) {
                    showNotification('success', 'Clock In Successful', data.message);
                } else {
                    showNotification('error', 'Clock In Failed', data.message);
                }
            }
            // Refresh the widget if Livewire is available
            if (window.Livewire) {
                window.Livewire.dispatch('refreshStatus');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Clock In Failed', 'An error occurred while clocking in: ' + error.message);
        });
    }

    // Clock out function
    function performClockOut() {
        const data = {
            source: 'office'
        };
        
        if (window.currentLocation) {
            data.latitude = window.currentLocation.latitude;
            data.longitude = window.currentLocation.longitude;
            if (window.currentLocation.address) {
                data.address = window.currentLocation.address;
            }
        }
        
        fetch('/api/attendance-entry/clock-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.message) {
                // Show notification
                if (data.message.includes('Successfully')) {
                    showNotification('success', 'Clock Out Successful', data.message);
                } else {
                    showNotification('error', 'Clock Out Failed', data.message);
                }
            }
            // Refresh the widget if Livewire is available
            if (window.Livewire) {
                window.Livewire.dispatch('refreshStatus');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Clock Out Failed', 'An error occurred while clocking out: ' + error.message);
        });
    }

    // Show notification function
    function showNotification(type, title, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="font-semibold">${title}</div>
            <div class="text-sm">${message}</div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Make functions globally available
    window.getCurrentLocation = getCurrentLocation;
    window.performClockIn = performClockIn;
    window.performClockOut = performClockOut;
}
</script>

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

<style>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }
</style>
