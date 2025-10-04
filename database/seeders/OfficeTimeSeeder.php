<?php

namespace Database\Seeders;

use App\Models\OfficeTime;
use Illuminate\Database\Seeder;

class OfficeTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officeTimes = [
            [
                'name' => 'Standard Office Hours',
                'code' => 'STD',
                'description' => 'Regular 9 AM to 5 PM office hours with 1 hour lunch break',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'break_duration_minutes' => 60,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 15,
                'early_grace_minutes' => 15,
                'active' => true,
            ],
            [
                'name' => 'Flexible Hours',
                'code' => 'FLEX',
                'description' => 'Flexible working hours from 8 AM to 6 PM with core hours 10 AM to 4 PM',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'break_duration_minutes' => 60,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 30,
                'early_grace_minutes' => 30,
                'active' => true,
            ],
            [
                'name' => 'Shift A (Morning)',
                'code' => 'SHIFT_A',
                'description' => 'Morning shift from 6 AM to 2 PM',
                'start_time' => '06:00',
                'end_time' => '14:00',
                'break_start_time' => '10:00',
                'break_end_time' => '10:30',
                'break_duration_minutes' => 30,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 15,
                'early_grace_minutes' => 15,
                'active' => true,
            ],
            [
                'name' => 'Shift B (Evening)',
                'code' => 'SHIFT_B',
                'description' => 'Evening shift from 2 PM to 10 PM',
                'start_time' => '14:00',
                'end_time' => '22:00',
                'break_start_time' => '18:00',
                'break_end_time' => '18:30',
                'break_duration_minutes' => 30,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 15,
                'early_grace_minutes' => 15,
                'active' => true,
            ],
            [
                'name' => 'Part Time',
                'code' => 'PART',
                'description' => 'Part time hours from 10 AM to 2 PM',
                'start_time' => '10:00',
                'end_time' => '14:00',
                'break_start_time' => null,
                'break_end_time' => null,
                'break_duration_minutes' => 0,
                'working_hours_per_day' => 4,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 10,
                'early_grace_minutes' => 10,
                'active' => true,
            ],
        ];

        foreach ($officeTimes as $officeTime) {
            OfficeTime::firstOrCreate(
                ['code' => $officeTime['code']],
                $officeTime
            );
        }
    }
}