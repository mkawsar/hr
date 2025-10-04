# Attendance Action Widget

## Overview
The Attendance Action Widget provides employees with a convenient way to clock in and clock out directly from their dashboard, with automatic location tracking from their browser.

## Features

### ✅ **Check-In/Check-Out Functionality**
- One-click clock in and clock out
- Real-time status updates
- Working day validation
- Multiple entries per day support

### ✅ **Location Tracking**
- Automatic browser location detection
- GPS coordinates capture (latitude/longitude)
- Location permission handling
- Manual location refresh option

### ✅ **Smart Status Display**
- Current clock in/out status
- Total working hours for the day
- Today's entry history
- Working day indicator

### ✅ **Role-Based Access**
- Available to employees and supervisors
- Admins can see company-wide statistics (separate widgets)
- Proper permission controls

## How It Works

### 1. **Location Permission**
When the widget loads, it automatically requests location permission from the browser. Users can also manually request location by clicking "Get Location".

### 2. **Clock In Process**
- Validates if it's a working day
- Checks for existing open entries
- Captures current location (if permission granted)
- Creates attendance entry with timestamp and coordinates
- Updates daily attendance summary

### 3. **Clock Out Process**
- Finds the current open entry
- Captures clock out time and location
- Calculates working hours
- Determines if early/late status
- Updates entry status to complete

### 4. **Data Storage**
- Uses the new `AttendanceEntry` model
- Stores location coordinates (latitude/longitude)
- Links to `DailyAttendance` for summary data
- Supports multiple entries per day

## API Endpoints

### Clock In
```
POST /api/attendance-entry/clock-in
Content-Type: application/json

{
    "latitude": 40.7128,
    "longitude": -74.0060,
    "location_id": 1,
    "notes": "Optional notes"
}
```

### Clock Out
```
POST /api/attendance-entry/clock-out
Content-Type: application/json

{
    "latitude": 40.7128,
    "longitude": -74.0060,
    "location_id": 1,
    "notes": "Optional notes"
}
```

### Get Today's Status
```
GET /api/attendance-entry/today
```

### Get Available Locations
```
GET /api/attendance-entry/locations
```

## Browser Compatibility

### Location Services
- **Chrome**: Full support
- **Firefox**: Full support
- **Safari**: Full support
- **Edge**: Full support

### Required Permissions
- **Geolocation API**: For location tracking
- **HTTPS**: Required for location services in production

## Security Features

### ✅ **Authentication**
- All API endpoints require authentication
- CSRF protection on all requests
- User session validation

### ✅ **Data Validation**
- Location coordinate validation
- Working day validation
- Duplicate entry prevention
- Input sanitization

### ✅ **Error Handling**
- Graceful location permission failures
- Network error handling
- User-friendly error messages

## Usage Instructions

### For Employees:
1. **Access the Dashboard**: Log in and navigate to the main dashboard
2. **View Widget**: The Attendance Action Widget appears at the top
3. **Grant Location Permission**: Allow browser to access your location
4. **Clock In**: Click "Clock In" button when you arrive
5. **Clock Out**: Click "Clock Out" button when you leave
6. **View Status**: Check your current status and total hours

### For Administrators:
- The widget shows company-wide statistics (separate widgets)
- Can view all employee attendance data through admin resources
- Can adjust attendance records if needed

## Technical Details

### Database Schema
- **attendance_entries**: Individual clock in/out records
- **daily_attendance**: Daily summary records
- **locations**: Predefined office locations

### Models Used
- `AttendanceEntry`: Individual entries
- `DailyAttendance`: Daily summaries
- `Location`: Office locations
- `User`: Employee information

### Key Features
- **Real-time Updates**: Livewire for instant UI updates
- **Location Accuracy**: High-precision GPS coordinates
- **Multiple Entries**: Support for break periods
- **Status Calculation**: Automatic late/early detection
- **Working Hours**: Automatic calculation and tracking

## Troubleshooting

### Location Not Working
1. Ensure HTTPS is enabled (required for geolocation)
2. Check browser permissions
3. Try refreshing the page
4. Use "Get Location" button manually

### Clock In/Out Issues
1. Check if it's a working day
2. Ensure you're not already clocked in/out
3. Check network connection
4. Refresh the widget status

### Permission Errors
1. Verify user has employee/supervisor role
2. Check authentication status
3. Ensure proper session is active

## Future Enhancements

### Planned Features
- **QR Code Check-in**: Office location QR codes
- **Photo Capture**: Selfie verification
- **Break Tracking**: Lunch and break periods
- **Mobile App**: Dedicated mobile application
- **Offline Support**: Work without internet connection
- **Biometric Integration**: Fingerprint/face recognition

### Integration Options
- **Calendar Integration**: Sync with work calendar
- **Slack/Teams**: Notification integration
- **Payroll Systems**: Direct payroll integration
- **Time Tracking**: Project-based time tracking
