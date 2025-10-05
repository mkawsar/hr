<?php

namespace Database\Seeders;

use App\Models\EarnedLeaveConfig;
use Illuminate\Database\Seeder;

class EarnedLeaveConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default configuration
        EarnedLeaveConfig::create([
            'name' => 'Default Configuration',
            'description' => 'Default earned leave calculation configuration - 1 earned leave day for every 15 working days',
            'working_days_per_earned_leave' => 15,
            'max_earned_leave_days' => 40,
            'include_weekends' => false,
            'include_holidays' => false,
            'include_absent_days' => false,
            'active' => true,
            'year' => null, // Applies to all years
        ]);

        // Create 2024 specific configuration (example)
        EarnedLeaveConfig::create([
            'name' => '2024 Configuration',
            'description' => '2024 specific earned leave calculation - 1 earned leave day for every 12 working days',
            'working_days_per_earned_leave' => 12,
            'max_earned_leave_days' => 45,
            'include_weekends' => false,
            'include_holidays' => false,
            'include_absent_days' => false,
            'active' => true,
            'year' => 2024,
        ]);

        // Create 2025 specific configuration (example)
        EarnedLeaveConfig::create([
            'name' => '2025 Configuration',
            'description' => '2025 specific earned leave calculation - 1 earned leave day for every 10 working days',
            'working_days_per_earned_leave' => 10,
            'max_earned_leave_days' => 50,
            'include_weekends' => false,
            'include_holidays' => false,
            'include_absent_days' => false,
            'active' => true,
            'year' => 2025,
        ]);
    }
}