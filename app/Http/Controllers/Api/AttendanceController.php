<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\DeductionRule;
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

        // Check if already clocked in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->clock_in) {
            return response()->json([
                'message' => 'Already clocked in today',
                'attendance' => $existingAttendance
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

        // Check if late (assuming 9 AM as standard start time)
        $standardStartTime = Carbon::today()->setTime(9, 0);
        if (now()->gt($standardStartTime)) {
            $lateMinutes = now()->diffInMinutes($standardStartTime);
            $attendanceData['late_minutes'] = $lateMinutes;
            $attendanceData['status'] = 'late';

            // Calculate deduction based on rules
            $deductionRule = DeductionRule::where('threshold_minutes', '<=', $lateMinutes)
                ->where('active', true)
                ->orderBy('threshold_minutes', 'desc')
                ->first();

            if ($deductionRule) {
                $attendanceData['deduction_amount'] = $deductionRule->deduction_value;
            }
        } else {
            $attendanceData['status'] = 'present';
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            $attendanceData
        );

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
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return response()->json(['message' => 'Not clocked in today'], 400);
        }

        if ($attendance->clock_out) {
            return response()->json(['message' => 'Already clocked out today'], 400);
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

        // Check if early leave (assuming 6 PM as standard end time)
        $standardEndTime = Carbon::today()->setTime(18, 0);
        if (now()->lt($standardEndTime)) {
            $earlyMinutes = $standardEndTime->diffInMinutes(now());
            $attendanceData['early_minutes'] = $earlyMinutes;

            if ($attendance->status === 'present') {
                $attendanceData['status'] = 'early_leave';
            }
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
            ->whereDate('date', $today)
            ->with(['clockInLocation', 'clockOutLocation'])
            ->first();

        return response()->json([
            'attendance' => $attendance,
            'is_clocked_in' => $attendance && $attendance->clock_in && !$attendance->clock_out,
            'is_clocked_out' => $attendance && $attendance->clock_out,
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
}