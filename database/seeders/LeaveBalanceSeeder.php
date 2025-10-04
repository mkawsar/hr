<?php

namespace Database\Seeders;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = now()->year;
        
        // Get all active users
        $users = User::where('status', 'active')->get();
        
        // Get all active leave types
        $leaveTypes = LeaveType::where('active', true)->get();
        
        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                // Check if leave balance already exists for this user, leave type, and year
                $existingBalance = LeaveBalance::where('user_id', $user->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $currentYear)
                    ->first();
                
                if (!$existingBalance) {
                    // Calculate accrued days for this year
                    $accruedDays = $leaveType->accrual_days_per_year ?? 0;
                    
                    // Calculate carry forward from previous year if allowed
                    $carryForwardDays = 0;
                    if ($leaveType->carry_forward_allowed) {
                        $previousYearBalance = LeaveBalance::where('user_id', $user->id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('year', $currentYear - 1)
                            ->first();
                        
                        if ($previousYearBalance && $previousYearBalance->balance > 0) {
                            $maxCarryForward = $leaveType->max_carry_forward_days ?? 40;
                            $carryForwardDays = min($previousYearBalance->balance, $maxCarryForward);
                        }
                    }
                    
                    // Calculate consumed days from approved leave applications
                    $consumedDays = $this->calculateUsedDays($user, $leaveType, $currentYear);
                    
                    // Calculate total balance (accrued + carry forward - consumed)
                    $totalBalance = $accruedDays + $carryForwardDays - $consumedDays;
                    
                    LeaveBalance::create([
                        'user_id' => $user->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $currentYear,
                        'accrued' => $accruedDays,
                        'carry_forward' => $carryForwardDays,
                        'consumed' => $consumedDays,
                        'balance' => max(0, $totalBalance), // Ensure non-negative
                    ]);
                }
            }
        }
    }
    
    
    /**
     * Calculate used days from approved leave applications
     */
    private function calculateUsedDays(User $user, LeaveType $leaveType, int $year): float
    {
        return \App\Models\LeaveApplication::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_count');
    }
}