<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use App\Models\OfficeTime;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\DailyAttendance;
use App\Models\AttendanceEntry;
use App\Models\Holiday;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ComprehensiveDataSeeder extends Seeder
{
    private $faker;
    private $departments;
    private $roles;
    private $officeTimes;
    private $leaveTypes;
    private $locations;
    private $adminUsers;

    public function run(): void
    {
        $this->faker = Faker::create();
        
        // First, ensure we have the basic data
        $this->createBasicData();
        
        // Create 100 users with realistic data
        $this->createUsers();
        
        // Generate attendance data for 2024 and 2025
        $this->generateAttendanceData();
        
        // Generate leave applications and balances
        $this->generateLeaveData();
        
        $this->command->info('Comprehensive data seeding completed successfully!');
    }

    private function createBasicData()
    {
        // Create departments if they don't exist
        $this->departments = [
            Department::firstOrCreate(['name' => 'Human Resources'], ['description' => 'HR Department']),
            Department::firstOrCreate(['name' => 'Information Technology'], ['description' => 'IT Department']),
            Department::firstOrCreate(['name' => 'Finance'], ['description' => 'Finance Department']),
            Department::firstOrCreate(['name' => 'Marketing'], ['description' => 'Marketing Department']),
            Department::firstOrCreate(['name' => 'Sales'], ['description' => 'Sales Department']),
            Department::firstOrCreate(['name' => 'Operations'], ['description' => 'Operations Department']),
            Department::firstOrCreate(['name' => 'Customer Service'], ['description' => 'Customer Service Department']),
            Department::firstOrCreate(['name' => 'Research & Development'], ['description' => 'R&D Department']),
        ];

        // Create roles if they don't exist
        $this->roles = [
            Role::firstOrCreate(['slug' => 'admin'], [
                'name' => 'Administrator',
                'description' => 'System Administrator',
                'permissions' => ['*']
            ]),
            Role::firstOrCreate(['slug' => 'supervisor'], [
                'name' => 'Supervisor',
                'description' => 'Team Supervisor',
                'permissions' => ['view_team', 'approve_leaves', 'view_attendance']
            ]),
            Role::firstOrCreate(['slug' => 'employee'], [
                'name' => 'Employee',
                'description' => 'Regular Employee',
                'permissions' => ['view_own_data', 'apply_leave']
            ]),
        ];

        // Create office times if they don't exist
        $this->officeTimes = [
            OfficeTime::firstOrCreate(['code' => 'STANDARD'], [
                'name' => 'Standard Office Hours',
                'description' => '9 AM to 6 PM with 1 hour lunch break',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_start_time' => '13:00',
                'break_end_time' => '14:00',
                'break_duration_minutes' => 60,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 15,
                'early_grace_minutes' => 15,
                'active' => true,
            ]),
            OfficeTime::firstOrCreate(['code' => 'FLEXIBLE'], [
                'name' => 'Flexible Hours',
                'description' => '8 AM to 5 PM with flexible start time',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'break_duration_minutes' => 60,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_grace_minutes' => 30,
                'early_grace_minutes' => 15,
                'active' => true,
            ]),
        ];

        // Get leave types
        $this->leaveTypes = LeaveType::all();

        // Create locations if they don't exist
        $this->locations = [
            Location::firstOrCreate(['name' => 'Main Office'], [
                'address' => '123 Business Street, City Center',
                'latitude' => 23.8103,
                'longitude' => 90.4125,
                'radius_meters' => 100,
                'active' => true,
            ]),
            Location::firstOrCreate(['name' => 'Branch Office'], [
                'address' => '456 Commerce Avenue, Downtown',
                'latitude' => 23.8150,
                'longitude' => 90.4200,
                'radius_meters' => 100,
                'active' => true,
            ]),
        ];

        // Get admin users for approvals
        $this->adminUsers = User::whereHas('role', function($q) {
            $q->whereIn('slug', ['admin', 'supervisor']);
        })->get();
    }

    private function createUsers()
    {
        $this->command->info('Creating 100 users...');
        
        $users = [];
        
        for ($i = 1; $i <= 100; $i++) {
            $department = $this->departments[array_rand($this->departments)];
            $role = $this->roles[array_rand($this->roles)];
            $officeTime = $this->officeTimes[array_rand($this->officeTimes)];
            
            // Create some supervisors (about 20% of users)
            if ($i <= 20) {
                $role = $this->roles[1]; // supervisor role
            }
            
            $user = User::create([
                'employee_id' => 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'phone_1' => $this->faker->phoneNumber(),
                'phone_2' => $this->faker->optional(0.3)->phoneNumber(),
                'address' => $this->faker->address(),
                'designation' => $this->getRandomDesignation($department->name),
                'date_of_joining' => $this->faker->dateTimeBetween('-3 years', 'now'),
                'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']), // 75% active
                'role_id' => $role->id,
                'department_id' => $department->id,
                'manager_id' => $this->getRandomManager($users, $role),
                'office_time_id' => $officeTime->id,
            ]);
            
            $users[] = $user;
            
            // Create leave balances for each user for 2024 and 2025
            $this->createLeaveBalances($user);
        }
        
        $this->command->info('100 users created successfully!');
    }

    private function getRandomDesignation($department)
    {
        $designations = [
            'Human Resources' => ['HR Manager', 'HR Specialist', 'HR Coordinator', 'Recruiter', 'HR Assistant'],
            'Information Technology' => ['Software Engineer', 'Senior Developer', 'DevOps Engineer', 'System Administrator', 'QA Engineer', 'Tech Lead'],
            'Finance' => ['Finance Manager', 'Accountant', 'Financial Analyst', 'Accounts Payable', 'Accounts Receivable'],
            'Marketing' => ['Marketing Manager', 'Marketing Specialist', 'Content Creator', 'SEO Specialist', 'Social Media Manager'],
            'Sales' => ['Sales Manager', 'Sales Representative', 'Account Executive', 'Sales Coordinator', 'Business Development'],
            'Operations' => ['Operations Manager', 'Operations Coordinator', 'Process Analyst', 'Operations Specialist'],
            'Customer Service' => ['Customer Service Manager', 'Customer Service Representative', 'Support Specialist', 'Call Center Agent'],
            'Research & Development' => ['R&D Manager', 'Research Scientist', 'Product Manager', 'Innovation Specialist'],
        ];
        
        $deptDesignations = $designations[$department] ?? ['Employee', 'Specialist', 'Coordinator'];
        return $this->faker->randomElement($deptDesignations);
    }

    private function getRandomManager($existingUsers, $currentRole)
    {
        // If this is an admin or supervisor, they might not have a manager
        if ($currentRole->slug === 'admin' || $this->faker->boolean(30)) {
            return null;
        }
        
        // Get existing supervisors and admins as potential managers
        $managers = array_filter($existingUsers, function($user) {
            return $user->role && in_array($user->role->slug, ['admin', 'supervisor']);
        });
        
        return $managers ? $this->faker->randomElement($managers)->id : null;
    }

    private function createLeaveBalances($user)
    {
        foreach ($this->leaveTypes as $leaveType) {
            // Create balance for 2024
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => 2024,
                'balance' => $leaveType->accrual_days_per_year ?? 0,
                'consumed' => $this->faker->numberBetween(0, $leaveType->accrual_days_per_year ?? 0),
                'accrued' => $leaveType->accrual_days_per_year ?? 0,
                'carry_forward' => $this->faker->numberBetween(0, 5),
            ]);
            
            // Create balance for 2025
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => 2025,
                'balance' => $leaveType->accrual_days_per_year ?? 0,
                'consumed' => $this->faker->numberBetween(0, $leaveType->accrual_days_per_year ?? 0),
                'accrued' => $leaveType->accrual_days_per_year ?? 0,
                'carry_forward' => $this->faker->numberBetween(0, 5),
            ]);
        }
    }

    private function generateAttendanceData()
    {
        $this->command->info('Generating attendance data for 2024 and 2025...');
        
        $users = User::where('status', 'active')->get();
        $years = [2024, 2025];
        
        foreach ($users as $user) {
            foreach ($years as $year) {
                $this->generateYearlyAttendance($user, $year);
            }
        }
        
        $this->command->info('Attendance data generation completed!');
    }

    private function generateYearlyAttendance($user, $year)
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        $officeTime = $user->officeTime;
        if (!$officeTime) {
            $officeTime = $this->officeTimes[0]; // fallback to standard office time
        }
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Skip if it's not a working day
            if (!$officeTime->isWorkingDate($currentDate)) {
                $currentDate->addDay();
                continue;
            }
            
            // 95% attendance rate
            if ($this->faker->boolean(95)) {
                $this->generateDailyAttendance($user, $currentDate, $officeTime);
            }
            
            $currentDate->addDay();
        }
    }

    private function generateDailyAttendance($user, $date, $officeTime)
    {
        $clockInTime = $this->generateClockInTime($officeTime);
        $clockOutTime = $this->generateClockOutTime($officeTime, $clockInTime);
        
        // Set the correct date for both times
        $clockInTime = $date->copy()->setTimeFromTimeString($clockInTime->format('H:i:s'));
        $clockOutTime = $date->copy()->setTimeFromTimeString($clockOutTime->format('H:i:s'));
        
        // Ensure clock out is after clock in (at least 8 hours later)
        if ($clockOutTime->lte($clockInTime)) {
            $clockOutTime = $clockInTime->copy()->addHours(8)->addMinutes(30);
        }
        
        // Calculate working hours (subtract lunch break)
        $totalMinutes = $clockOutTime->diffInMinutes($clockInTime);
        $workingMinutes = $totalMinutes - 60; // subtract 1 hour lunch break
        $workingHours = max(0, $workingMinutes / 60); // ensure non-negative
        
        // Calculate late and early minutes
        $lateMinutes = $officeTime->getLateMinutes($clockInTime);
        $earlyMinutes = $officeTime->getEarlyMinutes($clockOutTime);
        
        // Determine status
        $status = 'present';
        if ($lateMinutes > 0 && $earlyMinutes > 0) {
            $status = 'late_in_early_out';
        } elseif ($lateMinutes > 0) {
            $status = 'late_in';
        } elseif ($earlyMinutes > 0) {
            $status = 'early_out';
        }
        
        // Create daily attendance record
        $dailyAttendance = DailyAttendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'first_clock_in' => $clockInTime->format('H:i:s'),
            'last_clock_out' => $clockOutTime->format('H:i:s'),
            'total_entries' => $this->faker->numberBetween(1, 3), // 1-3 entries per day
            'total_working_hours' => $workingHours,
            'total_late_minutes' => $lateMinutes,
            'total_early_minutes' => $earlyMinutes,
            'status' => $status,
            'source' => $this->faker->randomElement(['office', 'remote', 'mobile', 'manual']),
            'office_time_id' => $officeTime->id,
            'office_time_snapshot' => [
                'name' => $officeTime->name,
                'start_time' => $officeTime->start_time->format('H:i'),
                'end_time' => $officeTime->end_time->format('H:i'),
                'working_days' => $officeTime->working_days,
                'late_grace_minutes' => $officeTime->late_grace_minutes,
                'early_grace_minutes' => $officeTime->early_grace_minutes,
            ],
        ]);
        
        // Create attendance entries
        $this->generateAttendanceEntries($dailyAttendance, $clockInTime, $clockOutTime);
    }

    private function generateClockInTime($officeTime)
    {
        $expectedStart = Carbon::createFromTimeString($officeTime->start_time->format('H:i:s'));
        
        // 70% on time, 20% slightly late, 10% very late
        $random = $this->faker->numberBetween(1, 100);
        
        if ($random <= 70) {
            // On time or early
            return $expectedStart->copy()->addMinutes($this->faker->numberBetween(-15, 5));
        } elseif ($random <= 90) {
            // Slightly late
            return $expectedStart->copy()->addMinutes($this->faker->numberBetween(16, 45));
        } else {
            // Very late
            return $expectedStart->copy()->addMinutes($this->faker->numberBetween(46, 120));
        }
    }

    private function generateClockOutTime($officeTime, $clockInTime)
    {
        $expectedEnd = Carbon::createFromTimeString($officeTime->end_time->format('H:i:s'));
        
        // 80% on time, 15% slightly early, 5% very early
        $random = $this->faker->numberBetween(1, 100);
        
        if ($random <= 80) {
            // On time or slightly late
            return $expectedEnd->copy()->addMinutes($this->faker->numberBetween(-5, 30));
        } elseif ($random <= 95) {
            // Slightly early
            return $expectedEnd->copy()->subMinutes($this->faker->numberBetween(1, 30));
        } else {
            // Very early
            return $expectedEnd->copy()->subMinutes($this->faker->numberBetween(31, 120));
        }
    }

    private function generateAttendanceEntries($dailyAttendance, $clockInTime, $clockOutTime)
    {
        $entries = $dailyAttendance->total_entries;
        
        for ($i = 0; $i < $entries; $i++) {
            if ($i === 0) {
                // First entry - clock in
                AttendanceEntry::create([
                    'daily_attendance_id' => $dailyAttendance->id,
                    'user_id' => $dailyAttendance->user_id,
                    'date' => $dailyAttendance->date,
                    'clock_in' => $clockInTime,
                    'clock_in_latitude' => $this->locations[0]->latitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_in_longitude' => $this->locations[0]->longitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_in_location_id' => $this->locations[0]->id,
                    'entry_status' => 'clock_in_only',
                    'source' => $this->faker->randomElement(['office', 'remote', 'mobile', 'manual']),
                ]);
            } elseif ($i === $entries - 1) {
                // Last entry - clock out
                AttendanceEntry::create([
                    'daily_attendance_id' => $dailyAttendance->id,
                    'user_id' => $dailyAttendance->user_id,
                    'date' => $dailyAttendance->date,
                    'clock_out' => $clockOutTime,
                    'clock_out_latitude' => $this->locations[0]->latitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_out_longitude' => $this->locations[0]->longitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_out_location_id' => $this->locations[0]->id,
                    'entry_status' => 'clock_out_only',
                    'source' => $this->faker->randomElement(['office', 'remote', 'mobile', 'manual']),
                ]);
            } else {
                // Middle entries - both clock in and out (break entries)
                $breakStart = $clockInTime->copy()->addHours($this->faker->numberBetween(2, 4));
                $breakEnd = $breakStart->copy()->addMinutes($this->faker->numberBetween(15, 60));
                
                AttendanceEntry::create([
                    'daily_attendance_id' => $dailyAttendance->id,
                    'user_id' => $dailyAttendance->user_id,
                    'date' => $dailyAttendance->date,
                    'clock_out' => $breakStart,
                    'clock_out_latitude' => $this->locations[0]->latitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_out_longitude' => $this->locations[0]->longitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_out_location_id' => $this->locations[0]->id,
                    'entry_status' => 'clock_out_only',
                    'source' => $this->faker->randomElement(['office', 'remote', 'mobile', 'manual']),
                ]);
                
                AttendanceEntry::create([
                    'daily_attendance_id' => $dailyAttendance->id,
                    'user_id' => $dailyAttendance->user_id,
                    'date' => $dailyAttendance->date,
                    'clock_in' => $breakEnd,
                    'clock_in_latitude' => $this->locations[0]->latitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_in_longitude' => $this->locations[0]->longitude + $this->faker->randomFloat(4, -0.001, 0.001),
                    'clock_in_location_id' => $this->locations[0]->id,
                    'entry_status' => 'clock_in_only',
                    'source' => $this->faker->randomElement(['office', 'remote', 'mobile', 'manual']),
                ]);
            }
        }
    }

    private function generateLeaveData()
    {
        $this->command->info('Generating leave applications...');
        
        $users = User::where('status', 'active')->get();
        
        foreach ($users as $user) {
            // Generate 2-8 leave applications per user per year
            $applicationsPerYear = $this->faker->numberBetween(2, 8);
            
            for ($year = 2024; $year <= 2025; $year++) {
                for ($i = 0; $i < $applicationsPerYear; $i++) {
                    $this->generateLeaveApplication($user, $year);
                }
            }
        }
        
        $this->command->info('Leave applications generation completed!');
    }

    private function generateLeaveApplication($user, $year)
    {
        $leaveType = $this->faker->randomElement($this->leaveTypes);
        $startDate = Carbon::create($year, $this->faker->numberBetween(1, 12), $this->faker->numberBetween(1, 28));
        $daysCount = $this->faker->numberBetween(1, $leaveType->accrual_days_per_year ?? 10);
        $endDate = $startDate->copy()->addDays($daysCount - 1);
        
        // Don't create leave applications for future dates
        if ($startDate->isFuture()) {
            return;
        }
        
        $status = $this->faker->randomElement(['approved', 'approved', 'approved', 'pending', 'rejected']); // 60% approved
        $approvedBy = null;
        $approvedAt = null;
        $approvalNotes = null;
        
        if ($status === 'approved' && $this->adminUsers->isNotEmpty()) {
            $approvedBy = $this->faker->randomElement($this->adminUsers)->id;
            $approvedAt = $startDate->copy()->subDays($this->faker->numberBetween(1, 30));
            $approvalNotes = $this->faker->optional(0.3)->sentence();
        }
        
        LeaveApplication::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCount,
            'status' => $status,
            'reason' => $this->faker->sentence(),
            'attachment' => $this->faker->optional(0.2)->url(),
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'approval_notes' => $approvalNotes,
            'applied_at' => $startDate->copy()->subDays($this->faker->numberBetween(1, 60)),
        ]);
    }
}
