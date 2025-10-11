<?php

namespace App\Console\Commands;

use App\Models\AttendanceEntry;
use App\Models\DailyAttendance;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessMonthlyDeductions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deductions:process-monthly {--month=} {--year=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly leave deductions for late/early attendance (no cash deductions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month') ?: Carbon::now()->subMonth()->month;
        $year = $this->option('year') ?: Carbon::now()->subMonth()->year;
        $dryRun = $this->option('dry-run');

        $this->info("Processing deductions for {$year}-{$month}");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual deductions will be processed');
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all active users with eager loading to prevent N+1 queries
        $users = User::where('status', 'active')
            ->with(['leaveBalances.leaveType'])
            ->get();
        
        // Pre-load leave types to avoid N+1 queries in the loop
        $leaveTypes = LeaveType::whereIn('code', ['casual', 'earned'])->get()->keyBy('code');
        
        $totalProcessed = 0;
        $totalDeductions = 0;

        foreach ($users as $user) {
            $this->processUserDeductions($user, $startDate, $endDate, $dryRun, $leaveTypes);
            $totalProcessed++;
        }

        $this->info("Processed {$totalProcessed} users");
        $this->info("Total deductions processed: {$totalDeductions}");
    }

    private function processUserDeductions(User $user, Carbon $startDate, Carbon $endDate, bool $dryRun = false, $leaveTypes = null)
    {
        // Check if deduction already processed for this month (check for existing leave applications)
        $existingDeduction = LeaveApplication::where('user_id', $user->id)
            ->where('reason', 'like', '%Automatic deduction%')
            ->where('start_date', '>=', $startDate->startOfMonth())
            ->where('start_date', '<=', $startDate->endOfMonth())
            ->first();

        if ($existingDeduction) {
            $this->line("Deduction already processed for {$user->name} ({$user->employee_id})");
            return;
        }

        // Get late/early entries for the month
        $lateEarlyEntries = AttendanceEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('late_minutes', '>', 0)
                      ->orWhere('early_minutes', '>', 0);
            })
            ->get();

        // Get absent days for the month
        $absentDays = DailyAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')
            ->get();

        $lateEarlyCount = $lateEarlyEntries->count();
        $absentCount = $absentDays->count();

        // Calculate total deduction days
        // New rule: If 4+ late days, start with 0.5 deduction, then 0.5 for each additional day
        $lateEarlyDeduction = 0;
        if ($lateEarlyCount >= 4) {
            $lateEarlyDeduction = 0.5 + (($lateEarlyCount - 4) * 0.5);
        }
        $absentDeduction = $absentCount * 1.0; // 1 day per absent day
        $totalDeductionDays = $lateEarlyDeduction + $absentDeduction;

        if ($totalDeductionDays <= 0) {
            $this->line("No deduction needed for {$user->name} ({$user->employee_id}) - {$lateEarlyCount} late/early entries (<4), {$absentCount} absent days");
            return;
        }

        $this->info("Processing deduction for {$user->name} ({$user->employee_id}):");
        $this->line("  Late/Early entries: {$lateEarlyCount} (deduction: {$lateEarlyDeduction} days)");
        $this->line("  Absent days: {$absentCount} (deduction: {$absentDeduction} days)");
        $this->line("  Total deduction: {$totalDeductionDays} days");

        if ($dryRun) {
            $this->line("  [DRY RUN] Would process {$totalDeductionDays} days deduction");
            return;
        }

        // Process the deduction
        $this->processDeduction($user, $startDate, $lateEarlyCount, $absentCount, $totalDeductionDays, $lateEarlyEntries, $absentDays, $leaveTypes);
    }

    private function processDeduction(User $user, Carbon $deductionMonth, int $lateEarlyCount, int $absentCount, float $totalDeductionDays, $lateEarlyEntries, $absentDays, $leaveTypes = null)
    {
        DB::beginTransaction();

        try {
            $currentYear = $deductionMonth->year;
            $deductionDetails = [];
            $leaveDeductionAmount = 0;
            $cashDeductionAmount = 0;

            // Get leave types in priority order (casual first, then earned)
            // Use pre-loaded leave types to avoid N+1 queries
            $casualLeaveType = $leaveTypes ? $leaveTypes->get('casual') : LeaveType::where('code', 'casual')->first();
            $earnedLeaveType = $leaveTypes ? $leaveTypes->get('earned') : LeaveType::where('code', 'earned')->first();

            $remainingDeduction = $totalDeductionDays;

            // First, try to deduct from casual leave
            if ($casualLeaveType && $remainingDeduction > 0) {
                $casualBalance = LeaveBalance::where('user_id', $user->id)
                    ->where('leave_type_id', $casualLeaveType->id)
                    ->where('year', $currentYear)
                    ->first();

                if ($casualBalance && $casualBalance->balance > 0) {
                    $casualDeduction = min($remainingDeduction, $casualBalance->balance);
                    
                    // Create leave application record for deduction
                    $this->createDeductionLeaveApplication(
                        $user, 
                        $casualLeaveType, 
                        $casualDeduction, 
                        $deductionMonth,
                        $lateEarlyCount,
                        $absentCount
                    );
                    
                    $casualBalance->balance -= $casualDeduction;
                    $casualBalance->consumed += $casualDeduction;
                    $casualBalance->save();

                    $remainingDeduction -= $casualDeduction;
                    $leaveDeductionAmount += $casualDeduction;

                    $deductionDetails[] = "Casual Leave: {$casualDeduction} days";
                    $this->line("  Deducted {$casualDeduction} days from casual leave");
                }
            }

            // Then, try to deduct from earned leave (can go negative)
            if ($earnedLeaveType && $remainingDeduction > 0) {
                $earnedBalance = LeaveBalance::where('user_id', $user->id)
                    ->where('leave_type_id', $earnedLeaveType->id)
                    ->where('year', $currentYear)
                    ->first();

                if (!$earnedBalance) {
                    // Create earned leave balance if it doesn't exist
                    $earnedBalance = LeaveBalance::create([
                        'user_id' => $user->id,
                        'leave_type_id' => $earnedLeaveType->id,
                        'year' => $currentYear,
                        'balance' => 0,
                        'consumed' => 0,
                        'accrued' => 0,
                        'carry_forward' => 0,
                    ]);
                }

                // Create leave application record for deduction
                $this->createDeductionLeaveApplication(
                    $user, 
                    $earnedLeaveType, 
                    $remainingDeduction, 
                    $deductionMonth,
                    $lateEarlyCount,
                    $absentCount
                );

                $earnedBalance->balance -= $remainingDeduction;
                $earnedBalance->consumed += $remainingDeduction;
                $earnedBalance->save();

                $leaveDeductionAmount += $remainingDeduction;
                $deductionDetails[] = "Earned Leave: {$remainingDeduction} days (can be negative)";
                $this->line("  Deducted {$remainingDeduction} days from earned leave");

                $remainingDeduction = 0;
            }

            // If still have remaining deduction and no leave available, log the issue
            if ($remainingDeduction > 0) {
                $deductionDetails[] = "Remaining deduction: {$remainingDeduction} days (no leave balance available)";
                $this->warn("  ⚠️  Remaining deduction: {$remainingDeduction} days - no leave balance available");
            }

            // Log deduction details for tracking
            $this->line("  Deduction Summary:");
            $this->line("    Late/Early entries: {$lateEarlyCount}");
            $this->line("    Absent days: {$absentCount}");
            $this->line("    Total deduction: {$totalDeductionDays} days");
            $this->line("    Leave deducted: {$leaveDeductionAmount} days");
            foreach ($deductionDetails as $detail) {
                $this->line("    - {$detail}");
            }

            DB::commit();
            $this->info("  ✓ Deduction processed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error processing deduction: " . $e->getMessage());
        }
    }

    /**
     * Create a leave application record for deduction
     */
    private function createDeductionLeaveApplication(User $user, LeaveType $leaveType, float $days, Carbon $deductionMonth, int $lateEarlyCount, int $absentCount)
    {
        $startDate = $deductionMonth->copy()->startOfMonth();
        $endDate = $deductionMonth->copy()->startOfMonth()->addDays($days - 1);

        // Create reason text based on what caused the deduction
        $reasonParts = [];
        if ($lateEarlyCount > 3) {
            $reasonParts[] = "{$lateEarlyCount} late/early attendance occurrences";
        }
        if ($absentCount > 0) {
            $reasonParts[] = "{$absentCount} absent day(s)";
        }
        $reason = "Automatic deduction for " . implode(" and ", $reasonParts) . " in {$deductionMonth->format('F Y')}";

        LeaveApplication::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $days,
            'status' => 'approved', // Auto-approved for deductions
            'reason' => $reason,
            'approved_by' => 1, // System user (you may want to create a system user)
            'approved_at' => now(),
            'approval_notes' => 'Automatically approved by monthly deduction system',
            'applied_at' => now(),
        ]);

        $this->line("Created leave application record for {$days} days {$leaveType->name}");
    }
}
