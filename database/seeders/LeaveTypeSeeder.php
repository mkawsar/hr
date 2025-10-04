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
                'name' => 'Earn Leave',
                'code' => 'EL',
                'description' => 'Annual earned leave for rest and recreation',
                'encashable' => true,
                'carry_forward_allowed' => true,
                'max_carry_forward_days' => 40,
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
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['code' => $leaveType['code']],
                $leaveType
            );
        }
    }
}