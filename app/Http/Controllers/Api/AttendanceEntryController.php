<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEntry;
use App\Models\DailyAttendance;
use App\Models\Location;
use App\Models\DeductionRule;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceEntryController extends Controller
{
    /**
     * Clock in for the current user
     */
    public function clockIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_id' => 'nullable|exists:locations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $today = Carbon::today();

        // Check if it's a working day
        if (!$this->isWorkingDay($today, $user)) {
            return response()->json([
                'message' => 'Today is not a working day',
                'is_working_day' => false
            ], 400);
        }

        // Check if there's an open entry (clocked in but not clocked out)
        $openEntry = AttendanceEntry::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if ($openEntry) {
            return response()->json([
                'message' => 'You have an open entry. Please clock out first.',
                'attendance' => $openEntry
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Get or create daily attendance record
            $dailyAttendance = DailyAttendance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'office_time_id' => $user->office_time_id,
                    'office_time_snapshot' => $user->officeTime ? $user->officeTime->toArray() : null,
                    'source' => 'office',
                ]
            );

            // Create attendance entry
            $entryData = [
                'daily_attendance_id' => $dailyAttendance->id,
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => now(),
                'source' => 'office',
                'entry_status' => 'clock_in_only',
                'notes' => $request->input('notes'),
            ];

            // Add location data if provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $entryData['clock_in_latitude'] = $request->latitude;
                $entryData['clock_in_longitude'] = $request->longitude;
            }

            if ($request->has('location_id')) {
                $entryData['clock_in_location_id'] = $request->location_id;
            }

            // Check if late based on office time
            $lateMinutes = 0;
            if ($user->officeTime) {
                $lateMinutes = $user->officeTime->getLateMinutes(Carbon::parse($entryData['clock_in']));
            }

            $entryData['late_minutes'] = $lateMinutes;

            $entry = AttendanceEntry::create($entryData);

            // Update daily attendance record
            $this->updateDailyAttendance($dailyAttendance);

            DB::commit();

            return response()->json([
                'message' => 'Successfully clocked in',
                'attendance' => $entry->load(['clockInLocation', 'user']),
                'is_late' => $lateMinutes > 0,
                'late_minutes' => $lateMinutes,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to clock in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clock out for the current user
     */
    public function clockOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_id' => 'nullable|exists:locations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $today = Carbon::today();

        $entry = AttendanceEntry::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'No open entry found to clock out'], 400);
        }

        try {
            DB::beginTransaction();

            $entryData = [
                'clock_out' => now(),
            ];

            // Add location data if provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $entryData['clock_out_latitude'] = $request->latitude;
                $entryData['clock_out_longitude'] = $request->longitude;
            }

            if ($request->has('location_id')) {
                $entryData['clock_out_location_id'] = $request->location_id;
            }

            if ($request->has('notes')) {
                $entryData['notes'] = $request->notes;
            }

            // Check if early leave based on office time
            $earlyMinutes = 0;
            if ($user->officeTime) {
                $earlyMinutes = $user->officeTime->getEarlyMinutes(Carbon::parse($entryData['clock_out']));
            }

            $entryData['early_minutes'] = $earlyMinutes;

            // Calculate working hours
            if ($entry->clock_in) {
                $workingHours = Carbon::parse($entry->clock_in)->diffInMinutes(Carbon::parse($entryData['clock_out'])) / 60;
                $entryData['working_hours'] = round($workingHours, 2);
            }

            // Determine entry status
            $lateMinutes = $entry->late_minutes ?? 0;
            
            if ($lateMinutes > 0 && $earlyMinutes > 0) {
                $entryData['entry_status'] = 'complete';
            } elseif ($lateMinutes > 0) {
                $entryData['entry_status'] = 'complete';
            } elseif ($earlyMinutes > 0) {
                $entryData['entry_status'] = 'complete';
            } else {
                $entryData['entry_status'] = 'complete';
            }

            $entry->update($entryData);

            // Update daily attendance record
            $this->updateDailyAttendance($entry->dailyAttendance);

            DB::commit();

            return response()->json([
                'message' => 'Successfully clocked out',
                'attendance' => $entry->load(['clockInLocation', 'clockOutLocation', 'user']),
                'working_hours' => $entryData['working_hours'] ?? 0,
                'is_early' => $earlyMinutes > 0,
                'early_minutes' => $earlyMinutes,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to clock out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance status for the current user
     */
    public function today(): JsonResponse
    {
        $user = Auth::user();
        $today = Carbon::today();

        $entries = AttendanceEntry::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->with(['clockInLocation', 'clockOutLocation'])
            ->orderBy('clock_in')
            ->get();

        $openEntry = $entries->whereNull('clock_out')->first();
        $isClockedIn = $openEntry ? true : false;
        $isClockedOut = $entries->whereNotNull('clock_out')->isNotEmpty();

        $totalWorkingHours = $entries->whereNotNull('working_hours')->sum('working_hours');

        return response()->json([
            'date' => $today->toDateString(),
            'entries' => $entries,
            'is_clocked_in' => $isClockedIn,
            'is_clocked_out' => $isClockedOut,
            'open_entry' => $openEntry,
            'total_working_hours' => $totalWorkingHours,
            'is_working_day' => $this->isWorkingDay($today, $user),
        ]);
    }

    /**
     * Get available locations
     */
    public function locations(): JsonResponse
    {
        $locations = Location::where('active', true)
            ->select('id', 'name', 'type', 'address', 'latitude', 'longitude', 'radius_meters')
            ->get();

        return response()->json($locations);
    }

    /**
     * Check if a date is a working day for the user
     */
    private function isWorkingDay(Carbon $date, $user): bool
    {
        // Check if it's a holiday
        if (Holiday::isHoliday($date)) {
            return false;
        }

        // Check if it's a working day based on office time
        if ($user->officeTime) {
            return $user->officeTime->isWorkingDate($date);
        } else {
            return !$date->isWeekend();
        }
    }

    /**
     * Update daily attendance record based on entries
     */
    private function updateDailyAttendance(DailyAttendance $dailyAttendance): void
    {
        $entries = $dailyAttendance->entries;

        if ($entries->isEmpty()) {
            return;
        }

        $firstClockIn = $entries->where('clock_in', '!=', null)->first()?->clock_in;
        $lastClockOut = $entries->where('clock_out', '!=', null)->last()?->clock_out;
        $totalWorkingHours = $entries->whereNotNull('working_hours')->sum('working_hours');
        $totalLateMinutes = $entries->sum('late_minutes');
        $totalEarlyMinutes = $entries->sum('early_minutes');

        $dailyAttendance->update([
            'first_clock_in' => $firstClockIn,
            'last_clock_out' => $lastClockOut,
            'total_entries' => $entries->count(),
            'total_working_hours' => $totalWorkingHours,
            'total_late_minutes' => $totalLateMinutes,
            'total_early_minutes' => $totalEarlyMinutes,
        ]);

        // Update status based on entries
        $this->updateDailyAttendanceStatus($dailyAttendance);
    }

    /**
     * Update daily attendance status
     */
    private function updateDailyAttendanceStatus(DailyAttendance $dailyAttendance): void
    {
        $entries = $dailyAttendance->entries;
        $user = $dailyAttendance->user;

        if ($entries->isEmpty()) {
            $dailyAttendance->update(['status' => 'absent']);
            return;
        }

        $hasCompleteEntry = $entries->where('entry_status', 'complete')->isNotEmpty();
        $hasLateEntry = $entries->where('late_minutes', '>', 0)->isNotEmpty();
        $hasEarlyEntry = $entries->where('early_minutes', '>', 0)->isNotEmpty();

        if ($hasLateEntry && $hasEarlyEntry) {
            $status = 'late_in_early_out';
        } elseif ($hasLateEntry) {
            $status = 'late_in';
        } elseif ($hasEarlyEntry) {
            $status = 'early_out';
        } elseif ($hasCompleteEntry) {
            $status = 'full_present';
        } else {
            $status = 'present';
        }

        $dailyAttendance->update(['status' => $status]);
    }
}
