// Global attendance functions
let currentLocation = null;

// No default location - only real GPS location is allowed

// No default location - only real GPS location is allowed

// No custom default location - only real GPS location is allowed

// No fallback location - only real GPS location is allowed

// Function to get address from GPS coordinates using reverse geocoding
async function getAddressFromCoordinates(latitude, longitude) {
    try {
        // Use OpenStreetMap Nominatim API for reverse geocoding (free)
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1`,
            {
                headers: {
                    'User-Agent': 'HR-Attendance-System/1.0'
                }
            }
        );
        
        if (!response.ok) {
            throw new Error('Geocoding service unavailable');
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
        console.warn('Reverse geocoding failed:', error);
        // Return a simple coordinate-based address
        return `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    }
}

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
            locationText.textContent = 'Geolocation not supported - Please use a modern browser';
        } else {
            console.warn('Geolocation not supported');
        }
        currentLocation = null;
        return;
    }

    if (locationText) {
        locationText.textContent = 'Getting location...';
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            currentLocation = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                address: 'Getting address...'
            };
            
            if (locationText) {
                locationText.textContent = 'GPS location obtained';
            }
            
            if (latitudeElement && longitudeElement) {
                latitudeElement.textContent = currentLocation.latitude.toFixed(6);
                longitudeElement.textContent = currentLocation.longitude.toFixed(6);
            }
            
            if (coordsElement) {
                coordsElement.classList.remove('hidden');
            }
            
            // Get address using reverse geocoding
            getAddressFromCoordinates(currentLocation.latitude, currentLocation.longitude)
                .then(address => {
                    currentLocation.address = address;
                    
                    // Update address display
                    const addressElement = document.getElementById('address-text');
                    if (addressElement) {
                        addressElement.textContent = address;
                        document.getElementById('location-address').classList.remove('hidden');
                    }
                    
                    // Update Livewire widget with complete location data
                    if (window.Livewire) {
                        window.Livewire.dispatch('setLocation', {
                            latitude: currentLocation.latitude,
                            longitude: currentLocation.longitude,
                            address: currentLocation.address
                        });
                    }
                })
                .catch(error => {
                    console.warn('Could not get address:', error);
                    currentLocation.address = 'GPS Location';
                    
                    // Update Livewire widget with GPS coordinates only
                    if (window.Livewire) {
                        window.Livewire.dispatch('setLocation', {
                            latitude: currentLocation.latitude,
                            longitude: currentLocation.longitude,
                            address: 'GPS Location'
                        });
                    }
                });
            
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
                    message = 'Location access denied - Please allow location access';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location unavailable - Please check your device';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timeout - Please try again';
                    break;
            }
            
            if (locationText) {
                locationText.textContent = message;
            } else {
                console.warn('Location error:', message);
            }
            
            // Don't set any fallback - require real GPS location
            currentLocation = null;
            
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
    // Use Livewire widget method instead of direct API call
    if (window.Livewire) {
        // Dispatch to the widget to handle clock in
        window.Livewire.dispatch('clock-in-requested');
    } else {
        // Fallback to direct API call if Livewire is not available
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
                    showNotification('success', '✅ Clock In Successful!', 'You have successfully clocked in. You can now clock out when you finish work.');
                } else {
                    showNotification('error', 'Clock In Failed', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Clock In Failed', 'An error occurred while clocking in: ' + error.message);
        });
    }
}

// Clock out function
function performClockOut() {
    // Use Livewire widget method instead of direct API call
    if (window.Livewire) {
        // Dispatch to the widget to handle clock out
        window.Livewire.dispatch('clock-out-requested');
    } else {
        // Fallback to direct API call if Livewire is not available
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
                    showNotification('success', '✅ Clock Out Successful!', 'You have successfully clocked out. Have a great day!');
                } else {
                    showNotification('error', 'Clock Out Failed', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Clock Out Failed', 'An error occurred while clocking out: ' + error.message);
        });
    }
}

// Show notification function
function showNotification(type, title, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-xl z-50 max-w-sm transform transition-all duration-300 ease-in-out ${
        type === 'success' ? 'bg-green-500 text-white border-l-4 border-green-600' : 'bg-red-500 text-white border-l-4 border-red-600'
    }`;
    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                ${type === 'success' ? 
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
                    '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'
                }
            </div>
            <div class="ml-3 flex-1">
                <div class="font-semibold text-sm">${title}</div>
                <div class="text-sm opacity-90 mt-1">${message}</div>
            </div>
        </div>
    `;
    
    // Add animation classes
    notification.style.transform = 'translateX(100%)';
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove after 6 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 6000);
}

// Note: Button state updates are now handled automatically by Livewire
// No need for manual JavaScript button updates

// Initialize location on page load if not already obtained
document.addEventListener('DOMContentLoaded', function() {
    // Always try to get real GPS location automatically
    if (document.getElementById('get-location-btn') || document.querySelector('[onclick*="getCurrentLocation"]')) {
        // Get real GPS location only
        getCurrentLocation();
    }
});

// Listen for Livewire events if available
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        // Listen for location request from Livewire widget
        window.Livewire.on('requestLocation', () => {
            console.log('Livewire requested location, getting GPS...');
            getCurrentLocation();
        });
    }
});
