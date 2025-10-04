<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\DeductionRule;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $today = Carbon::today();

        // Check if there's an open entry (clocked in but not clocked out)
        $openEntry = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->format('Y-m-d'))
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if ($openEntry) {
            return response()->json([
                'message' => 'You have an open entry. Please clock out first.',
                'attendance' => $openEntry
            ], 400);
        }

        $attendanceData = [
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => now(),
            'source' => $request->input('source', 'mobile'),
        ];

        // Add location data if provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $attendanceData['clock_in_latitude'] = $request->latitude;
            $attendanceData['clock_in_longitude'] = $request->longitude;
        }

        if ($request->has('location_id')) {
            $attendanceData['clock_in_location_id'] = $request->location_id;
        }

        // Check if late based on office time
        $lateMinutes = 0;
        if ($user->officeTime) {
            $lateMinutes = $user->officeTime->getLateMinutes(Carbon::parse($attendanceData['clock_in']));
        }

        $attendanceData['late_minutes'] = $lateMinutes;

        if ($lateMinutes > 0) {
            $attendanceData['status'] = 'late_in';

            // Calculate deduction based on rules
            $deductionRule = DeductionRule::where('threshold_minutes', '<=', $lateMinutes)
                ->where('active', true)
                ->orderBy('threshold_minutes', 'desc')
                ->first();

            if ($deductionRule) {
                $attendanceData['deduction_amount'] = $deductionRule->deduction_value;
            }
        } else {
            $attendanceData['status'] = 'present'; // Will be updated to 'full_present' or 'early_out' on clock out
        }

        $attendance = Attendance::create($attendanceData);

        return response()->json([
            'message' => 'Successfully clocked in',
            'attendance' => $attendance->load(['clockInLocation', 'user'])
        ], 201);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->format('Y-m-d'))
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No open entry found to clock out'], 400);
        }

        $attendanceData = [
            'clock_out' => now(),
        ];

        // Add location data if provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $attendanceData['clock_out_latitude'] = $request->latitude;
            $attendanceData['clock_out_longitude'] = $request->longitude;
        }

        if ($request->has('location_id')) {
            $attendanceData['clock_out_location_id'] = $request->location_id;
        }

        // Check if early leave based on office time
        $earlyMinutes = 0;
        if ($user->officeTime) {
            $earlyMinutes = $user->officeTime->getEarlyMinutes(Carbon::parse($attendanceData['clock_out']));
        }

        $attendanceData['early_minutes'] = $earlyMinutes;

        // Enhanced status classification
        $lateMinutes = $attendance->late_minutes ?? 0;
        
        if ($lateMinutes > 0 && $earlyMinutes > 0) {
            $attendanceData['status'] = 'late_in_early_out';
        } elseif ($lateMinutes > 0) {
            $attendanceData['status'] = 'late_in';
        } elseif ($earlyMinutes > 0) {
            $attendanceData['status'] = 'early_out';
        } else {
            $attendanceData['status'] = 'full_present';
        }

        $attendance->update($attendanceData);

        return response()->json([
            'message' => 'Successfully clocked out',
            'attendance' => $attendance->load(['clockInLocation', 'clockOutLocation', 'user'])
        ], 200);
    }

    /**
     * Get attendance history for the current user
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['clockInLocation', 'clockOutLocation'])
            ->orderBy('date', 'desc')
            ->paginate($perPage);

        return response()->json($attendances);
    }

    /**
     * Get today's attendance for the current user
     */
    public function today(): JsonResponse
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->format('Y-m-d'))
            ->with(['clockInLocation', 'clockOutLocation'])
            ->get();

        $openEntry = $attendance->whereNull('clock_out')->first();
        $isClockedIn = $openEntry ? true : false;
        $isClockedOut = $attendance->whereNotNull('clock_out')->isNotEmpty();

        return response()->json([
            'date' => $today->toDateString(),
            'attendance' => $attendance,
            'is_clocked_in' => $isClockedIn,
            'is_clocked_out' => $isClockedOut,
            'open_entry' => $openEntry,
        ]);
    }

    /**
     * Get monthly attendance records for the current user
     */
    public function monthly(Request $request): JsonResponse
    {
        $user = Auth::user();
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all attendance records for the month
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['clockInLocation', 'clockOutLocation'])
            ->orderBy('date')
            ->orderBy('clock_in')
            ->get();

        // Get leave applications for the month
        $leaveApplications = \App\Models\LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->with('leaveType')
            ->get();

        // Calculate working hours for each day
        $monthlyData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $dayAttendances = $attendances->where('date', $dateStr);
            
            // Check if it's weekend
            $isWeekend = $currentDate->isWeekend();
            
            // Check if it's a holiday
            $isHoliday = Holiday::isHoliday($currentDate);
            
            // Check if it's a working day based on office time
            $isWorkingDay = true;
            if ($user->officeTime) {
                $isWorkingDay = $user->officeTime->isWorkingDate($currentDate);
            }
            
            // Check if on leave
            $leaveApplication = $leaveApplications->first(function ($leave) use ($currentDate) {
                return $currentDate->between($leave->start_date, $leave->end_date);
            });

            $totalWorkingHours = 0;
            $entries = [];
            $firstClockIn = null;
            $lastClockOut = null;

            foreach ($dayAttendances as $attendance) {
                $entry = [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                    'early_minutes' => $attendance->early_minutes,
                    'deduction_amount' => $attendance->deduction_amount,
                    'clock_in_location' => $attendance->clockInLocation,
                    'clock_out_location' => $attendance->clockOutLocation,
                ];

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workingHours = Carbon::parse($attendance->clock_in)->diffInHours(Carbon::parse($attendance->clock_out));
                    $entry['working_hours'] = $workingHours;
                    $totalWorkingHours += $workingHours;
                }

                $entries[] = $entry;

                if (!$firstClockIn || $attendance->clock_in < $firstClockIn) {
                    $firstClockIn = $attendance->clock_in;
                }
                if ($attendance->clock_out && (!$lastClockOut || $attendance->clock_out > $lastClockOut)) {
                    $lastClockOut = $attendance->clock_out;
                }
            }

            $monthlyData[] = [
                'date' => $dateStr,
                'day_name' => $currentDate->format('l'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'is_working_day' => $isWorkingDay,
                'is_on_leave' => $leaveApplication ? true : false,
                'leave_type' => $leaveApplication ? $leaveApplication->leaveType->name : null,
                'entries' => $entries,
                'total_working_hours' => $totalWorkingHours,
                'first_clock_in' => $firstClockIn,
                'last_clock_out' => $lastClockOut,
                'total_entries' => count($entries),
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'month' => $month,
            'year' => $year,
            'month_name' => $startDate->format('F'),
            'data' => $monthlyData,
            'summary' => [
                'total_days' => $startDate->daysInMonth,
                'working_days' => $user->officeTime ? 
                    $startDate->copy()->startOfMonth()->diffInDaysFiltered(function ($date) use ($user) {
                        return $user->officeTime->isWorkingDate($date);
                    }, $endDate) : 
                    $startDate->copy()->startOfMonth()->diffInDaysFiltered(function ($date) {
                        return !$date->isWeekend();
                    }, $endDate),
                'weekend_days' => $startDate->copy()->startOfMonth()->diffInDaysFiltered(function ($date) {
                    return $date->isWeekend();
                }, $endDate),
                'holiday_days' => $startDate->copy()->startOfMonth()->diffInDaysFiltered(function ($date) {
                    return Holiday::isHoliday($date);
                }, $endDate),
                'leave_days' => $leaveApplications->sum('days_count'),
                'total_working_hours' => collect($monthlyData)->sum('total_working_hours'),
            ]
        ]);
    }

    /**
     * Get available locations for clock in/out
     */
    public function locations(): JsonResponse
    {
        $locations = Location::where('active', true)
            ->select('id', 'name', 'type', 'address', 'latitude', 'longitude', 'radius_meters')
            ->get();

        return response()->json($locations);
    }

    /**
     * Get attendance statistics for the current user
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $stats = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(late_minutes) as total_late_minutes,
                SUM(deduction_amount) as total_deductions
            ')
            ->first();

        return response()->json($stats);
    }

    /**
     * Get detailed information for a specific day with all time entries
     */
    public function getDayDetails($date)
    {
        $user = auth()->user();
        $dateObj = Carbon::parse($date);
        
        // Get daily attendance record for this date
        $dailyAttendance = \App\Models\DailyAttendance::where('user_id', $user->id)
            ->whereDate('date', $dateObj->format('Y-m-d'))
            ->with(['entries' => function ($query) {
                $query->orderBy('clock_in')->orderBy('created_at');
            }])
            ->first();
        
        if (!$dailyAttendance) {
            return response()->json([
                'date' => $date,
                'entries' => [],
                'summary' => [
                    'date' => $date,
                    'day_name' => $dateObj->format('l'),
                    'total_entries' => 0,
                    'first_clock_in' => null,
                    'last_clock_out' => null,
                    'total_working_hours' => '0.00h',
                    'total_late_minutes' => 0,
                    'total_early_minutes' => 0,
                    'is_holiday' => Holiday::isHoliday($dateObj),
                    'is_working_day' => $user->officeTime ? $user->officeTime->isWorkingDate($dateObj) : !$dateObj->isWeekend(),
                    'leave_info' => null,
                ],
                'message' => 'No attendance records found for this date'
            ]);
        }
        
        // Check if it's a holiday
        $isHoliday = Holiday::isHoliday($dateObj);
        
        // Check if it's a working day
        $isWorkingDay = true;
        if ($user->officeTime) {
            $isWorkingDay = $user->officeTime->isWorkingDate($dateObj);
        } else {
            $isWorkingDay = !$dateObj->isWeekend();
        }
        
        // Check if on leave
        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($dateObj) {
                $query->whereDate('start_date', '<=', $dateObj)
                    ->whereDate('end_date', '>=', $dateObj);
            })
            ->with('leaveType')
            ->first();
        
        // Prepare all time entries
        $entries = [];
        
        foreach ($dailyAttendance->entries as $entry) {
            $entryData = [
                'id' => $entry->id,
                'clock_in' => $entry->clock_in ? $entry->clock_in->format('H:i:s') : null,
                'clock_out' => $entry->clock_out ? $entry->clock_out->format('H:i:s') : null,
                'entry_status' => $entry->entry_status,
                'late_minutes' => $entry->late_minutes ?? 0,
                'early_minutes' => $entry->early_minutes ?? 0,
                'working_hours' => $entry->working_hours ? number_format($entry->working_hours, 2) . 'h' : null,
                'source' => $entry->source ?? 'Manual',
                'created_at' => $entry->created_at->format('H:i:s'),
                'entry_type' => $this->getEntryType($entry),
                'clock_in_location' => $entry->clockInLocation?->name,
                'clock_out_location' => $entry->clockOutLocation?->name,
                'notes' => $entry->notes,
            ];
            
            $entries[] = $entryData;
        }
        
        // Prepare summary information
        $summary = [
            'date' => $date,
            'day_name' => $dateObj->format('l'),
            'total_entries' => $dailyAttendance->total_entries,
            'first_clock_in' => $dailyAttendance->entries->where('clock_in', '!=', null)->first() ? 
                $dailyAttendance->entries->where('clock_in', '!=', null)->first()->clock_in->format('H:i') : null,
            'last_clock_out' => $dailyAttendance->entries->where('clock_out', '!=', null)->last() ? 
                $dailyAttendance->entries->where('clock_out', '!=', null)->last()->clock_out->format('H:i') : null,
            'total_working_hours' => number_format($dailyAttendance->total_working_hours, 2) . 'h',
            'total_late_minutes' => $dailyAttendance->total_late_minutes,
            'total_early_minutes' => $dailyAttendance->total_early_minutes,
            'is_holiday' => $isHoliday,
            'is_working_day' => $isWorkingDay,
            'leave_info' => null,
        ];
        
        // Add leave information if applicable
        if ($leaveApplication) {
            $summary['leave_info'] = [
                'type' => $leaveApplication->leaveType->name,
                'reason' => $leaveApplication->reason,
                'status' => ucfirst($leaveApplication->status),
                'start_date' => $leaveApplication->start_date,
                'end_date' => $leaveApplication->end_date,
            ];
        }
        
        return response()->json([
            'date' => $date,
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }
    
    /**
     * Get entry type description
     */
    private function getEntryType($entry)
    {
        return match ($entry->entry_status) {
            'complete' => 'Complete Entry',
            'clock_in_only' => 'Clock In Only',
            'clock_out_only' => 'Clock Out Only',
            'incomplete' => 'Incomplete Entry',
            default => 'Unknown',
        };
    }
    
    /**
     * Get display status based on various conditions
     */
    private function getStatusDisplay($status, $isHoliday, $isWorkingDay, $leaveApplication)
    {
        if ($isHoliday) {
            return 'Holiday';
        }
        
        if (!$isWorkingDay) {
            return 'Weekend';
        }
        
        if ($leaveApplication) {
            return $leaveApplication->leaveType->name;
        }
        
        // Check for incomplete attendance
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('date', \Carbon\Carbon::parse(request()->route('date'))->format('Y-m-d'))
            ->first();
            
        if ($attendance && ((!$attendance->clock_in && $attendance->clock_out) || ($attendance->clock_in && !$attendance->clock_out))) {
            return 'Absent';
        }
        
        // Show attendance status
        return match ($status) {
            'full_present' => 'Full Present',
            'late_in' => 'Late In',
            'early_out' => 'Early Out',
            'late_in_early_out' => 'Late In + Early Out',
            'present' => 'Present',
            'absent' => 'Absent',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}