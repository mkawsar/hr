<?php

namespace App\Services;

use App\Models\User;
use App\Models\DailyAttendance;
use App\Models\LeaveApplication;
use App\Models\AttendanceEntry;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class YearlyReportService
{
    /**
     * Generate yearly attendance report for all users
     */
    public function generateYearlyAttendanceReport(int $year): Collection
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        // Get all active users
        $users = User::where('status', 'active')
            ->with(['department', 'role'])
            ->get();
        
        $report = collect();
        
        foreach ($users as $user) {
            $userReport = $this->generateUserYearlyReport($user, $year, $startDate, $endDate);
            $report->push($userReport);
        }
        
        return $report;
    }
    
    /**
     * Generate yearly report for a specific user
     */
    public function generateUserYearlyReport(User $user, int $year, Carbon $startDate = null, Carbon $endDate = null): array
    {
        if (!$startDate) {
            $startDate = Carbon::create($year, 1, 1);
        }
        if (!$endDate) {
            $endDate = Carbon::create($year, 12, 31);
        }
        
        // Get attendance data
        $attendanceData = $this->getAttendanceData($user, $startDate, $endDate);
        
        // Get leave data
        $leaveData = $this->getLeaveData($user, $startDate, $endDate);
        
        // Get early/late data
        $earlyLateData = $this->getEarlyLateData($user, $startDate, $endDate);
        
        // Calculate working days
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);
        
        // Calculate holidays
        $holidays = $this->getHolidaysInPeriod($startDate, $endDate);
        
        return [
            'user' => [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department?->name,
                'designation' => $user->designation,
                'date_of_joining' => $user->date_of_joining,
            ],
            'year' => $year,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $startDate->diffInDays($endDate) + 1,
                'working_days' => $workingDays,
                'holidays' => $holidays,
            ],
            'attendance' => $attendanceData,
            'leave' => $leaveData,
            'early_late' => $earlyLateData,
            'summary' => $this->calculateSummary($attendanceData, $leaveData, $earlyLateData, $workingDays),
        ];
    }
    
    /**
     * Get attendance data for user in period
     */
    private function getAttendanceData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $attendance = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        $statusCounts = $attendance->groupBy('status')->map->count();
        
        return [
            'total_days' => $attendance->count(),
            'present' => $statusCounts->get('present', 0) + $statusCounts->get('late', 0),
            'absent' => $statusCounts->get('absent', 0),
            'late' => $statusCounts->get('late', 0),
            'half_day' => $statusCounts->get('half_day', 0),
            'full_present' => $statusCounts->get('full_present', 0),
            'total_working_hours' => $attendance->sum('total_working_hours'),
            'average_working_hours' => $attendance->avg('total_working_hours') ?? 0,
        ];
    }
    
    /**
     * Get leave data for user in period
     */
    private function getLeaveData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $leaves = LeaveApplication::where('user_id', $user->id)
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
        
        $leaveTypeCounts = $leaves->groupBy('leave_type_id')->map(function ($group) {
            return [
                'type' => $group->first()->leaveType->name,
                'days' => $group->sum('days_count'),
                'count' => $group->count(),
            ];
        });
        
        return [
            'total_leave_days' => $leaves->sum('days_count'),
            'total_leave_applications' => $leaves->count(),
            'by_type' => $leaveTypeCounts->values()->toArray(),
            'leaves' => $leaves->map(function ($leave) {
                return [
                    'type' => $leave->leaveType->name,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'days' => $leave->days_count,
                    'reason' => $leave->reason,
                ];
            })->toArray(),
        ];
    }
    
    /**
     * Get early/late data for user in period
     */
    private function getEarlyLateData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $entries = AttendanceEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        return [
            'total_late_minutes' => $entries->sum('late_minutes'),
            'total_early_minutes' => $entries->sum('early_minutes'),
            'average_late_minutes' => $entries->avg('late_minutes') ?? 0,
            'average_early_minutes' => $entries->avg('early_minutes') ?? 0,
            'late_days' => $entries->where('late_minutes', '>', 0)->count(),
            'early_days' => $entries->where('early_minutes', '>', 0)->count(),
            'total_entries' => $entries->count(),
        ];
    }
    
    /**
     * Calculate working days in period (excluding weekends)
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Get holidays in period
     */
    private function getHolidaysInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return Holiday::whereBetween('date', [$startDate, $endDate])
            ->where('active', true)
            ->count();
    }
    
    /**
     * Calculate summary statistics
     */
    private function calculateSummary(array $attendance, array $leave, array $earlyLate, int $workingDays): array
    {
        $presentDays = $attendance['present'] + $attendance['full_present'];
        $absentDays = $attendance['absent'];
        $leaveDays = $leave['total_leave_days'];
        
        $attendanceRate = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0;
        $absentRate = $workingDays > 0 ? round(($absentDays / $workingDays) * 100, 2) : 0;
        $leaveRate = $workingDays > 0 ? round(($leaveDays / $workingDays) * 100, 2) : 0;
        
        return [
            'attendance_rate' => $attendanceRate,
            'absent_rate' => $absentRate,
            'leave_rate' => $leaveRate,
            'punctuality_score' => $this->calculatePunctualityScore($earlyLate),
            'overall_performance' => $this->calculateOverallPerformance($attendanceRate, $absentRate, $earlyLate),
        ];
    }
    
    /**
     * Calculate punctuality score based on late/early minutes
     */
    private function calculatePunctualityScore(array $earlyLate): float
    {
        $totalLateMinutes = $earlyLate['total_late_minutes'];
        $totalEarlyMinutes = $earlyLate['total_early_minutes'];
        $totalEntries = $earlyLate['total_entries'];
        
        if ($totalEntries === 0) {
            return 100.0;
        }
        
        // Deduct points for late/early minutes
        $latePenalty = min($totalLateMinutes * 0.1, 50); // Max 50 points penalty
        $earlyPenalty = min($totalEarlyMinutes * 0.05, 25); // Max 25 points penalty
        
        $score = 100 - $latePenalty - $earlyPenalty;
        return max(0, round($score, 2));
    }
    
    /**
     * Calculate overall performance score
     */
    private function calculateOverallPerformance(float $attendanceRate, float $absentRate, array $earlyLate): string
    {
        $punctualityScore = $this->calculatePunctualityScore($earlyLate);
        
        $overallScore = ($attendanceRate * 0.4) + ($punctualityScore * 0.3) + ((100 - $absentRate) * 0.3);
        
        if ($overallScore >= 90) {
            return 'Excellent';
        } elseif ($overallScore >= 80) {
            return 'Good';
        } elseif ($overallScore >= 70) {
            return 'Average';
        } elseif ($overallScore >= 60) {
            return 'Below Average';
        } else {
            return 'Poor';
        }
    }
    
    /**
     * Generate department-wise summary
     */
    public function generateDepartmentSummary(int $year): Collection
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        $departments = User::where('status', 'active')
            ->with('department')
            ->get()
            ->groupBy('department_id');
        
        return $departments->map(function ($users, $departmentId) use ($year, $startDate, $endDate) {
            $department = $users->first()->department;
            $userReports = $users->map(function ($user) use ($year, $startDate, $endDate) {
                return $this->generateUserYearlyReport($user, $year, $startDate, $endDate);
            });
            
            $totalUsers = $users->count();
            $avgAttendanceRate = $userReports->avg('summary.attendance_rate');
            $avgPunctualityScore = $userReports->avg('summary.punctuality_score');
            $totalAbsentDays = $userReports->sum('attendance.absent');
            $totalLeaveDays = $userReports->sum('leave.total_leave_days');
            
            return [
                'department' => [
                    'id' => $departmentId,
                    'name' => $department?->name ?? 'No Department',
                ],
                'total_users' => $totalUsers,
                'average_attendance_rate' => round($avgAttendanceRate, 2),
                'average_punctuality_score' => round($avgPunctualityScore, 2),
                'total_absent_days' => $totalAbsentDays,
                'total_leave_days' => $totalLeaveDays,
                'users' => $userReports,
            ];
        })->values();
    }
}
