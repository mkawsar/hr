<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveApplication;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove Emergency Leave to avoid code conflict
        $emergencyLeave = LeaveType::where('code', 'EL')->where('name', 'Emergency Leave')->first();
        if ($emergencyLeave) {
            // Delete related data
            LeaveBalance::where('leave_type_id', $emergencyLeave->id)->delete();
            LeaveApplication::where('leave_type_id', $emergencyLeave->id)->delete();
            $emergencyLeave->delete();
        }

        // Update the Annual Leave to Earn Leave
        $annualLeave = LeaveType::where('code', 'AL')->first();
        if ($annualLeave) {
            $annualLeave->update([
                'name' => 'Earn Leave',
                'code' => 'EL',
                'description' => 'Annual earned leave for rest and recreation',
                'encashable' => true,
                'carry_forward_allowed' => true,
                'max_carry_forward_days' => 40,
            ]);
        }

        // Remove unused leave types and their related data
        $unusedLeaveTypes = LeaveType::whereIn('code', ['ML', 'PL', 'UL'])->get();
        
        foreach ($unusedLeaveTypes as $leaveType) {
            // Delete related leave balances
            LeaveBalance::where('leave_type_id', $leaveType->id)->delete();
            
            // Delete related leave applications
            LeaveApplication::where('leave_type_id', $leaveType->id)->delete();
            
            // Delete the leave type
            $leaveType->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Earn Leave back to Annual Leave
        $earnLeave = LeaveType::where('code', 'EL')->where('name', 'Earn Leave')->first();
        if ($earnLeave) {
            $earnLeave->update([
                'name' => 'Annual Leave',
                'code' => 'AL',
                'description' => 'Annual vacation leave for rest and recreation',
                'max_carry_forward_days' => 5,
            ]);
        }

        // Recreate the deleted leave types
        $leaveTypes = [
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Leave for childbirth and childcare',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => 90,
                'accrual_frequency' => 'yearly',
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Leave for new fathers',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => 15,
                'accrual_frequency' => 'yearly',
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'UL',
                'description' => 'Leave without pay',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => null,
                'accrual_frequency' => 'yearly',
                'requires_approval' => true,
                'active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['code' => $leaveType['code']],
                $leaveType
            );
        }

        // Reactivate Emergency Leave
        $emergencyLeave = LeaveType::where('code', 'EL')->where('name', 'Emergency Leave')->first();
        if ($emergencyLeave) {
            $emergencyLeave->update(['active' => true]);
        }
    }
};