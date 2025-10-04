// Global attendance functions
let currentLocation = null;

// Get current location function
function getCurrentLocation() {
    // Check if we're in a widget context with specific elements
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

// Clock in function
function performClockIn() {
    const data = {
        source: 'office'
    };
    
    if (window.currentLocation) {
        data.latitude = window.currentLocation.latitude;
        data.longitude = window.currentLocation.longitude;
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

// Initialize location on page load if not already obtained
document.addEventListener('DOMContentLoaded', function() {
    // Only auto-get location if we're on a page that needs it
    if (document.getElementById('get-location-btn') || document.querySelector('[onclick*="getCurrentLocation"]')) {
        getCurrentLocation();
    }
});

// Listen for Livewire events if available
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('clock-in-requested', () => {
            performClockIn();
        });
        
        window.Livewire.on('clock-out-requested', () => {
            performClockOut();
        });
    }
});
