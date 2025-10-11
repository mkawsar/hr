<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LeaveBalanceReportExport;
use App\Exports\LeaveSummaryReportExport;
use App\Exports\LeaveAnalysisReportExport;
use App\Exports\LeaveApprovalHistoryReportExport;

class LeaveReportsController extends Controller
{
    /**
     * Employee Leave Balance Report
     */
    public function leaveBalanceReport(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $departmentId = $request->get('department_id');
        $format = $request->get('format', 'json'); // json, excel, pdf

        $query = LeaveBalance::with(['user.department', 'leaveType'])
            ->where('year', $year)
            ->whereHas('user'); // Ensure user exists

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $leaveBalances = $query->get();
        
        // Pre-load all departments to avoid N+1 queries
        $departments = Department::all()->keyBy('id');

        // Group by user for better presentation
        $groupedData = $leaveBalances->groupBy('user_id')->map(function ($balances, $userId) use ($departments) {
            $user = $balances->first()->user;
            
            // Debug department information
            $departmentName = 'N/A';
            if ($user->department) {
                $departmentName = $user->department->name;
            } elseif ($user->department_id) {
                // Use pre-loaded departments to avoid N+1 queries
                $department = $departments->get($user->department_id);
                $departmentName = $department ? $department->name : 'Department ID: ' . $user->department_id;
            }
            
            $userData = [
                'employee_id' => $user->employee_id,
                'name' => $user->name,
                'department' => $departmentName,
                'designation' => $user->designation ?? 'N/A',
                'leave_balances' => $balances->map(function ($balance) {
                    return [
                        'leave_type' => $balance->leaveType->name,
                        'leave_code' => $balance->leaveType->code,
                        'balance' => $balance->balance,
                        'consumed' => $balance->consumed,
                        'accrued' => $balance->accrued,
                        'carry_forward' => $balance->carry_forward,
                        'total_available' => $balance->balance + $balance->carry_forward,
                    ];
                })->toArray(),
                'total_balance' => $balances->sum('balance'),
                'total_consumed' => $balances->sum('consumed'),
            ];
            return $userData;
        })->values();

        if ($format === 'excel') {
            return Excel::download(new LeaveBalanceReportExport($groupedData, $year), 
                "leave_balance_report_{$year}.xlsx");
        }

        if ($format === 'pdf') {
            $departmentName = $departmentId ? $departments->get($departmentId)->name ?? 'Unknown Department' : 'All Departments';
            $pdf = Pdf::loadView('reports.leave-balance-pdf', [
                'data' => $groupedData,
                'year' => $year,
                'department' => $departmentName
            ]);
            return $pdf->download("leave_balance_report_{$year}.pdf");
        }

        return response()->json([
            'success' => true,
            'data' => $groupedData,
            'year' => $year,
            'total_employees' => $groupedData->count(),
        ]);
    }

    /**
     * Leave Summary Report with Date Range
     */
    public function leaveSummaryReport(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
        $departmentId = $request->get('department_id');
        $status = $request->get('status'); // pending, approved, rejected, cancelled
        $format = $request->get('format', 'json');

        $query = LeaveApplication::with(['user.department', 'leaveType', 'approvedBy'])
            ->whereBetween('start_date', [$startDate, $endDate]);

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $leaveApplications = $query->orderBy('start_date', 'desc')->get();

        $summaryData = $leaveApplications->map(function ($application) {
            return [
                'id' => $application->id,
                'employee_id' => $application->user->employee_id,
                'employee_name' => $application->user->name,
                'department' => $application->user->department->name ?? 'N/A',
                'leave_type' => $application->leaveType->name,
                'start_date' => $application->start_date ? $application->start_date->format('Y-m-d') : null,
                'end_date' => $application->end_date ? $application->end_date->format('Y-m-d') : null,
                'days_count' => $application->days_count,
                'status' => ucfirst($application->status),
                'reason' => $application->reason,
                'applied_at' => $application->applied_at ? $application->applied_at->format('Y-m-d H:i:s') : null,
                'approved_by' => $application->approvedBy->name ?? 'N/A',
                'approved_at' => $application->approved_at ? $application->approved_at->format('Y-m-d H:i:s') : 'N/A',
                'approval_notes' => $application->approval_notes,
            ];
        });

        // Calculate summary statistics
        $statistics = [
            'total_applications' => $leaveApplications->count(),
            'approved_applications' => $leaveApplications->where('status', 'approved')->count(),
            'pending_applications' => $leaveApplications->where('status', 'pending')->count(),
            'rejected_applications' => $leaveApplications->where('status', 'rejected')->count(),
            'cancelled_applications' => $leaveApplications->where('status', 'cancelled')->count(),
            'total_leave_days' => $leaveApplications->where('status', 'approved')->sum('days_count'),
            'by_leave_type' => $leaveApplications->groupBy('leaveType.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_days' => $group->sum('days_count'),
                    'approved_days' => $group->where('status', 'approved')->sum('days_count'),
                ];
            }),
            'by_department' => $leaveApplications->groupBy('user.department.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_days' => $group->sum('days_count'),
                    'approved_days' => $group->where('status', 'approved')->sum('days_count'),
                ];
            }),
        ];

        if ($format === 'excel') {
            return Excel::download(new LeaveSummaryReportExport($summaryData, $statistics, $startDate, $endDate), 
                "leave_summary_report_{$startDate}_{$endDate}.xlsx");
        }

        if ($format === 'pdf') {
            // Pre-load department to avoid N+1 query
            $departmentName = $departmentId ? Department::find($departmentId)->name ?? 'Unknown Department' : 'All Departments';
            $pdf = Pdf::loadView('reports.leave-summary-pdf', [
                'data' => $summaryData,
                'statistics' => $statistics,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'department' => $departmentName
            ]);
            return $pdf->download("leave_summary_report_{$startDate}_{$endDate}.pdf");
        }

        return response()->json([
            'success' => true,
            'data' => $summaryData,
            'statistics' => $statistics,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Leave Analysis Report
     */
    public function leaveAnalysisReport(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $departmentId = $request->get('department_id');
        $format = $request->get('format', 'json');

        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        // Get leave applications for the year
        $query = LeaveApplication::with(['user.department', 'leaveType'])
            ->whereBetween('start_date', [$startDate, $endDate]);

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $leaveApplications = $query->get();

        // Get leave balances for the year
        $leaveBalancesQuery = LeaveBalance::with(['user.department', 'leaveType'])
            ->where('year', $year);

        if ($departmentId) {
            $leaveBalancesQuery->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $leaveBalances = $leaveBalancesQuery->get();

        // Analysis by leave type
        $leaveTypeAnalysis = $leaveBalances->groupBy('leaveType.name')->map(function ($balances, $leaveTypeName) use ($leaveApplications) {
            $typeApplications = $leaveApplications->where('leaveType.name', $leaveTypeName);
            
            return [
                'leave_type' => $leaveTypeName,
                'total_allocated' => $balances->sum('accrued'),
                'total_consumed' => $balances->sum('consumed'),
                'total_balance' => $balances->sum('balance'),
                'total_carry_forward' => $balances->sum('carry_forward'),
                'utilization_rate' => $balances->sum('accrued') > 0 ? 
                    round(($balances->sum('consumed') / $balances->sum('accrued')) * 100, 2) : 0,
                'applications_count' => $typeApplications->count(),
                'approved_applications' => $typeApplications->where('status', 'approved')->count(),
                'pending_applications' => $typeApplications->where('status', 'pending')->count(),
                'rejected_applications' => $typeApplications->where('status', 'rejected')->count(),
            ];
        });

        // Analysis by department
        $departmentAnalysis = $leaveBalances->groupBy('user.department.name')->map(function ($balances, $deptName) use ($leaveApplications) {
            $deptApplications = $leaveApplications->where('user.department.name', $deptName);
            
            return [
                'department' => $deptName,
                'total_employees' => $balances->groupBy('user_id')->count(),
                'total_allocated' => $balances->sum('accrued'),
                'total_consumed' => $balances->sum('consumed'),
                'total_balance' => $balances->sum('balance'),
                'average_utilization' => $balances->groupBy('user_id')->map(function ($userBalances) {
                    $totalAllocated = $userBalances->sum('accrued');
                    $totalConsumed = $userBalances->sum('consumed');
                    return $totalAllocated > 0 ? ($totalConsumed / $totalAllocated) * 100 : 0;
                })->avg(),
                'applications_count' => $deptApplications->count(),
                'approved_applications' => $deptApplications->where('status', 'approved')->count(),
            ];
        });

        // Monthly trends
        $monthlyTrends = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1);
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
            
            $monthApplications = $leaveApplications->filter(function ($app) use ($monthStart, $monthEnd) {
                return $app->start_date && $app->start_date->between($monthStart, $monthEnd);
            });

            $monthlyTrends[] = [
                'month' => $monthStart->format('F'),
                'month_number' => $month,
                'applications_count' => $monthApplications->count(),
                'approved_count' => $monthApplications->where('status', 'approved')->count(),
                'total_days' => $monthApplications->where('status', 'approved')->sum('days_count'),
                'by_leave_type' => $monthApplications->groupBy('leaveType.name')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'days' => $group->where('status', 'approved')->sum('days_count'),
                    ];
                }),
            ];
        }

        $analysisData = [
            'year' => $year,
            'total_employees' => $leaveBalances->groupBy('user_id')->count(),
            'total_leave_types' => $leaveBalances->groupBy('leaveType.name')->count(),
            'total_departments' => $leaveBalances->groupBy('user.department.name')->count(),
            'leave_type_analysis' => $leaveTypeAnalysis,
            'department_analysis' => $departmentAnalysis,
            'monthly_trends' => $monthlyTrends,
            'summary' => [
                'total_allocated_days' => $leaveBalances->sum('accrued'),
                'total_consumed_days' => $leaveBalances->sum('consumed'),
                'total_remaining_days' => $leaveBalances->sum('balance'),
                'overall_utilization_rate' => $leaveBalances->sum('accrued') > 0 ? 
                    round(($leaveBalances->sum('consumed') / $leaveBalances->sum('accrued')) * 100, 2) : 0,
                'total_applications' => $leaveApplications->count(),
                'approval_rate' => $leaveApplications->count() > 0 ? 
                    round(($leaveApplications->where('status', 'approved')->count() / $leaveApplications->count()) * 100, 2) : 0,
            ],
        ];

        if ($format === 'excel') {
            return Excel::download(new LeaveAnalysisReportExport($analysisData), 
                "leave_analysis_report_{$year}.xlsx");
        }

        if ($format === 'pdf') {
            // Pre-load department to avoid N+1 query
            $departmentName = $departmentId ? Department::find($departmentId)->name ?? 'Unknown Department' : 'All Departments';
            $pdf = Pdf::loadView('reports.leave-analysis-pdf', [
                'data' => $analysisData,
                'department' => $departmentName
            ]);
            return $pdf->download("leave_analysis_report_{$year}.pdf");
        }

        return response()->json([
            'success' => true,
            'data' => $analysisData,
        ]);
    }

    /**
     * Leave Approval History Report
     */
    public function leaveApprovalHistoryReport(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonths(6));
        $endDate = $request->get('end_date', Carbon::now());
        $approverId = $request->get('approver_id');
        $status = $request->get('status'); // approved, rejected
        $format = $request->get('format', 'json');

        $query = LeaveApplication::with(['user.department', 'leaveType', 'approvedBy'])
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->whereNotNull('approved_at');

        if ($approverId) {
            $query->where('approved_by', $approverId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $approvalHistory = $query->orderBy('approved_at', 'desc')->get();

        $historyData = $approvalHistory->map(function ($application) {
            return [
                'id' => $application->id,
                'employee_id' => $application->user->employee_id,
                'employee_name' => $application->user->name,
                'department' => $application->user->department->name ?? 'N/A',
                'leave_type' => $application->leaveType->name,
                'start_date' => $application->start_date ? $application->start_date->format('Y-m-d') : null,
                'end_date' => $application->end_date ? $application->end_date->format('Y-m-d') : null,
                'days_count' => $application->days_count,
                'status' => ucfirst($application->status),
                'reason' => $application->reason,
                'applied_at' => $application->applied_at ? $application->applied_at->format('Y-m-d H:i:s') : null,
                'approved_by' => $application->approvedBy->name ?? 'N/A',
                'approved_at' => $application->approved_at ? $application->approved_at->format('Y-m-d H:i:s') : null,
                'approval_notes' => $application->approval_notes,
                'processing_time_hours' => $application->approved_at ? 
                    round($application->approved_at->diffInHours($application->applied_at), 2) : 0,
            ];
        });

        // Approval statistics
        $approvalStats = [
            'total_processed' => $approvalHistory->count(),
            'approved_count' => $approvalHistory->where('status', 'approved')->count(),
            'rejected_count' => $approvalHistory->where('status', 'rejected')->count(),
            'average_processing_time_hours' => $approvalHistory->avg(function ($app) {
                return $app->approved_at ? $app->approved_at->diffInHours($app->applied_at) : 0;
            }),
            'by_approver' => $approvalHistory->groupBy('approvedBy.name')->map(function ($group) {
                return [
                    'approver' => $group->first()->approvedBy->name ?? 'N/A',
                    'total_processed' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                    'approval_rate' => $group->count() > 0 ? 
                        round(($group->where('status', 'approved')->count() / $group->count()) * 100, 2) : 0,
                    'average_processing_time' => $group->avg(function ($app) {
                        return $app->approved_at ? $app->approved_at->diffInHours($app->applied_at) : 0;
                    }),
                ];
            }),
            'by_department' => $approvalHistory->groupBy('user.department.name')->map(function ($group) {
                return [
                    'department' => $group->first()->user->department->name ?? 'N/A',
                    'total_processed' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                    'approval_rate' => $group->count() > 0 ? 
                        round(($group->where('status', 'approved')->count() / $group->count()) * 100, 2) : 0,
                ];
            }),
        ];

        if ($format === 'excel') {
            return Excel::download(new LeaveApprovalHistoryReportExport($historyData, $approvalStats, $startDate, $endDate), 
                "leave_approval_history_{$startDate}_{$endDate}.xlsx");
        }

        if ($format === 'pdf') {
            // Pre-load approver to avoid N+1 query
            $approverName = $approverId ? User::find($approverId)->name ?? 'Unknown Approver' : 'All Approvers';
            $pdf = Pdf::loadView('reports.leave-approval-history-pdf', [
                'data' => $historyData,
                'statistics' => $approvalStats,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'approver' => $approverName
            ]);
            return $pdf->download("leave_approval_history_{$startDate}_{$endDate}.pdf");
        }

        return response()->json([
            'success' => true,
            'data' => $historyData,
            'statistics' => $approvalStats,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get filter options for reports
     */
    public function getFilterOptions()
    {
        $departments = Department::select('id', 'name')->get();
        $leaveTypes = LeaveType::select('id', 'name', 'code')->where('active', true)->get();
        
        // Optimize approvers query to avoid N+1
        $approvers = User::whereHas('approvedLeaveApplications')
            ->select('id', 'name')
            ->distinct()
            ->get();

        return response()->json([
            'departments' => $departments,
            'leave_types' => $leaveTypes,
            'approvers' => $approvers,
            'years' => range(Carbon::now()->year - 5, Carbon::now()->year),
        ]);
    }
}
