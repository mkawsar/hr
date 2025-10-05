<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\DailyAttendance;
use App\Models\Holiday;
use App\Models\EarnedLeaveConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateEarnedLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:calculate-earned 
                            {--year= : The year to calculate earned leave for (defaults to current year)}
                            {--user= : Calculate for specific user ID only}
                            {--dry-run : Show what would be calculated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate earned leave for all users based on attendance, holidays, and weekends. Maximum 40 days with carry forward from previous year.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $calculationYear = $this->option('year') ?: (date('Y') - 1); // Default to previous year
        $currentYear = date('Y'); // Current year for balance updates
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info("Calculating earned leave from attendance in year: {$calculationYear}");
        $this->info("Adding earned leave to balance for year: {$currentYear}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made to the database");
        }

        // Get earned leave configuration
        $config = EarnedLeaveConfig::getActiveConfigForYear($calculationYear);
        if (!$config) {
            $this->error('No active earned leave configuration found. Please create a configuration first.');
            return 1;
        }

        $this->info("Using configuration: {$config->name}");
        $this->info("Working days per earned leave: {$config->working_days_per_earned_leave}");
        $this->info("Maximum earned leave days: {$config->max_earned_leave_days}");

        // Get earned leave type
        $earnedLeaveType = LeaveType::where('code', 'earned')
            ->orWhere('code', 'EL')
            ->orWhere('name', 'like', '%earned%')
            ->orWhere('name', 'like', '%Earn Leave%')
            ->first();
        
        if (!$earnedLeaveType) {
            $this->error('Earned leave type not found. Please create a leave type with code "earned" or name containing "earned".');
            return 1;
        }

        $this->info("Using leave type: {$earnedLeaveType->name} (ID: {$earnedLeaveType->id})");

        // Get users to process
        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }
        $users = $query->get();

        if ($users->isEmpty()) {
            $this->error('No users found to process.');
            return 1;
        }

        $this->info("Processing {$users->count()} users...");

        // Batch load all attendance records to prevent N+1 queries
        $userIds = $users->pluck('id')->toArray();
        $allAttendanceRecords = DailyAttendance::whereIn('user_id', $userIds)
            ->whereBetween('date', [
                Carbon::create($calculationYear, 1, 1),
                Carbon::create($calculationYear, 12, 31)
            ])
            ->get()
            ->groupBy('user_id');

        $results = [];
        $totalProcessed = 0;
        $totalUpdated = 0;

        foreach ($users as $user) {
            $this->line("Processing user: {$user->name} (ID: {$user->id})");
            
            try {
                $result = $this->calculateEarnedLeaveForUser($user, $calculationYear, $currentYear, $earnedLeaveType, $config, $dryRun, $allAttendanceRecords->get($user->id, collect()));
                $results[] = $result;
                $totalProcessed++;
                
                if ($result['updated']) {
                    $totalUpdated++;
                }
                
                $this->line("  - Days worked: {$result['days_worked']}");
                $this->line("  - Previous year balance: {$result['previous_balance']}");
                $this->line("  - Carry forward: {$result['carry_forward']}");
                $this->line("  - New earned leave: {$result['new_earned']}");
                $this->line("  - Total balance: {$result['total_balance']}");
                $this->line("  - Status: " . ($result['updated'] ? 'Updated' : 'No change needed'));
                
            } catch (\Exception $e) {
                $this->error("  - Error processing user {$user->name}: " . $e->getMessage());
                Log::error("Earned leave calculation error for user {$user->id}: " . $e->getMessage());
            }
            
            $this->line('');
        }

        // Summary
        $this->info("=== CALCULATION SUMMARY ===");
        $this->info("Total users processed: {$totalProcessed}");
        $this->info("Total records updated: {$totalUpdated}");
        
        if ($dryRun) {
            $this->warn("This was a dry run - no actual changes were made.");
        }

        return 0;
    }

    /**
     * Calculate earned leave for a specific user
     */
    private function calculateEarnedLeaveForUser(User $user, int $calculationYear, int $currentYear, LeaveType $earnedLeaveType, EarnedLeaveConfig $config, bool $dryRun = false, $attendanceRecords = null): array
    {
        // Get previous year's earned leave balance (from the year before calculation year)
        $previousYearBalance = $this->getPreviousYearBalance($user, $calculationYear - 1, $earnedLeaveType);
        
        // Calculate days worked in the calculation year (using configuration settings)
        $daysWorked = $this->calculateDaysWorked($user, $calculationYear, $config, $attendanceRecords);
        
        // Calculate carry forward (using configured maximum)
        $carryForward = min($previousYearBalance, $config->max_earned_leave_days);
        
        // Calculate new earned leave based on days worked (using configured rate)
        $newEarned = floor($daysWorked / $config->working_days_per_earned_leave);
        
        // Total balance cannot exceed configured maximum
        $totalBalance = min($carryForward + $newEarned, $config->max_earned_leave_days);
        
        // Get or create current year's leave balance record
        $currentBalance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $earnedLeaveType->id)
            ->where('year', $currentYear)
            ->first();

        $result = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'days_worked' => $daysWorked,
            'previous_balance' => $previousYearBalance,
            'carry_forward' => $carryForward,
            'new_earned' => $newEarned,
            'total_balance' => $totalBalance,
            'updated' => false,
        ];

        if (!$currentBalance) {
            // Create new record
            if (!$dryRun) {
                LeaveBalance::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $earnedLeaveType->id,
                    'year' => $currentYear,
                    'balance' => $totalBalance,
                    'consumed' => 0,
                    'accrued' => $newEarned,
                    'carry_forward' => $carryForward,
                ]);
            }
            $result['updated'] = true;
        } else {
            // Update existing record if values have changed
            $needsUpdate = false;
            $updates = [];
            
            if ($currentBalance->balance != $totalBalance) {
                $updates['balance'] = $totalBalance;
                $needsUpdate = true;
            }
            
            if ($currentBalance->accrued != $newEarned) {
                $updates['accrued'] = $newEarned;
                $needsUpdate = true;
            }
            
            if ($currentBalance->carry_forward != $carryForward) {
                $updates['carry_forward'] = $carryForward;
                $needsUpdate = true;
            }
            
            if ($needsUpdate && !$dryRun) {
                $currentBalance->update($updates);
            }
            
            $result['updated'] = $needsUpdate;
        }

        return $result;
    }

    /**
     * Get previous year's earned leave balance
     */
    private function getPreviousYearBalance(User $user, int $year, LeaveType $earnedLeaveType): float
    {
        $previousBalance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $earnedLeaveType->id)
            ->where('year', $year)
            ->first();

        return $previousBalance ? $previousBalance->balance : 0;
    }

    /**
     * Calculate days worked in a year (excluding weekends, holidays, and absent days)
     */
    private function calculateDaysWorked(User $user, int $year, EarnedLeaveConfig $config, $attendanceRecords = null): int
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        // Get all holidays for the year
        $holidays = Holiday::whereYear('date', $year)
            ->where('active', true)
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Use preloaded attendance records or load them if not provided
        if ($attendanceRecords === null) {
            $attendanceRecords = DailyAttendance::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->keyBy(function ($record) {
                    return Carbon::parse($record->date)->format('Y-m-d');
                });
        } else {
            // Convert collection to keyed collection
            $attendanceRecords = $attendanceRecords->keyBy(function ($record) {
                return Carbon::parse($record->date)->format('Y-m-d');
            });
        }

        $daysWorked = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $shouldCount = true;
            
            // Skip weekends if not configured to include them
            if ($currentDate->isWeekend() && !$config->include_weekends) {
                $shouldCount = false;
            }
            
            // Skip holidays if not configured to include them
            if (in_array($dateString, $holidays) && !$config->include_holidays) {
                $shouldCount = false;
            }
            
            // Check attendance for this day
            $attendance = $attendanceRecords->get($dateString);
            
            if ($attendance) {
                // If attendance record exists, check status
                if (in_array($attendance->status, ['absent', 'holiday']) && !$config->include_absent_days) {
                    $shouldCount = false;
                }
            } else {
                // If no attendance record exists, treat as absent
                if (!$config->include_absent_days) {
                    $shouldCount = false;
                }
            }
            
            if ($shouldCount) {
                $daysWorked++;
            }
            
            $currentDate->addDay();
        }

        return $daysWorked;
    }
}