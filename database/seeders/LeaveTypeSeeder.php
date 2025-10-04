<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'AL',
                'description' => 'Annual vacation leave for rest and recreation',
                'encashable' => true,
                'carry_forward_allowed' => true,
                'max_carry_forward_days' => 5,
                'accrual_days_per_year' => 18,
                'accrual_frequency' => 'yearly',
                'requires_approval' => true,
                'active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave for illness or medical appointments',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => 12,
                'accrual_frequency' => 'yearly',
                'requires_approval' => false,
                'active' => true,
            ],
            [
                'name' => 'Casual Leave',
                'code' => 'CL',
                'description' => 'Short-term leave for personal reasons',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => 6,
                'accrual_frequency' => 'yearly',
                'requires_approval' => true,
                'active' => true,
            ],
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
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'description' => 'Leave for family emergencies',
                'encashable' => false,
                'carry_forward_allowed' => false,
                'max_carry_forward_days' => null,
                'accrual_days_per_year' => 3,
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
    }
}