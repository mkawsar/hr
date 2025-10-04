<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Department;
use App\Models\User;
use App\Models\OfficeTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'System Administrator with full access',
                'permissions' => ['*'],
            ]
        );

        $supervisorRole = Role::firstOrCreate(
            ['slug' => 'supervisor'],
            [
                'name' => 'Supervisor',
                'description' => 'Team Supervisor with approval rights',
                'permissions' => [
                    'view_own_data',
                    'view_team_data',
                    'approve_leave',
                    'reject_leave',
                    'view_team_attendance',
                    'view_team_reports',
                    'manage_own_attendance'
                ],
            ]
        );

        $employeeRole = Role::firstOrCreate(
            ['slug' => 'employee'],
            [
                'name' => 'Employee',
                'description' => 'Regular Employee',
                'permissions' => [
                    'view_own_data',
                    'apply_leave',
                    'view_own_leave',
                    'view_own_attendance',
                    'clock_in_out'
                ],
            ]
        );

        // Create departments
        $itDepartment = Department::firstOrCreate(
            ['name' => 'Information Technology'],
            ['description' => 'IT Department']
        );

        $hrDepartment = Department::firstOrCreate(
            ['name' => 'Human Resources'],
            ['description' => 'HR Department']
        );

        // Get default office time
        $standardOfficeTime = OfficeTime::where('code', 'STD')->first();

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@hr.com'],
            [
                'employee_id' => 'EMP001',
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'department_id' => $itDepartment->id,
                'office_time_id' => $standardOfficeTime?->id,
                'designation' => 'System Administrator',
                'date_of_joining' => now()->subYear(),
                'status' => 'active',
            ]
        );

        // Update existing users to new role structure
        $existingHrUser = User::where('email', 'hr@hr.com')->first();
        if ($existingHrUser) {
            $existingHrUser->update([
                'role_id' => $supervisorRole->id,
                'name' => 'Jane Supervisor',
                'employee_id' => 'EMP002',
                'designation' => 'Team Lead',
                'office_time_id' => $standardOfficeTime?->id,
            ]);
            $supervisorUser = $existingHrUser;
        } else {
            // Create supervisor user if it doesn't exist
            $supervisorUser = User::firstOrCreate(
                ['email' => 'supervisor@hr.com'],
                [
                    'employee_id' => 'EMP002',
                    'name' => 'Jane Supervisor',
                    'password' => Hash::make('password'),
                    'role_id' => $supervisorRole->id,
                    'department_id' => $itDepartment->id,
                    'office_time_id' => $standardOfficeTime?->id,
                    'designation' => 'Team Lead',
                    'date_of_joining' => now()->subMonths(6),
                    'status' => 'active',
                ]
            );
        }

        // Create sample employees with supervisor relationship
        $employee1 = User::firstOrCreate(
            ['email' => 'john@hr.com'],
            [
                'employee_id' => 'EMP003',
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role_id' => $employeeRole->id,
                'department_id' => $itDepartment->id,
                'office_time_id' => $standardOfficeTime?->id,
                'designation' => 'Software Developer',
                'date_of_joining' => now()->subMonths(3),
                'status' => 'active',
            ]
        );

        // Update existing employee to have supervisor relationship
        $employee1->update(['manager_id' => $supervisorUser->id]);

        $employee2 = User::firstOrCreate(
            ['email' => 'sarah@hr.com'],
            [
                'employee_id' => 'EMP004',
                'name' => 'Sarah Smith',
                'password' => Hash::make('password'),
                'role_id' => $employeeRole->id,
                'department_id' => $itDepartment->id,
                'office_time_id' => $standardOfficeTime?->id,
                'designation' => 'UI/UX Designer',
                'date_of_joining' => now()->subMonths(2),
                'status' => 'active',
            ]
        );
        $employee2->update(['manager_id' => $supervisorUser->id]);

        $employee3 = User::firstOrCreate(
            ['email' => 'mike@hr.com'],
            [
                'employee_id' => 'EMP005',
                'name' => 'Mike Johnson',
                'password' => Hash::make('password'),
                'role_id' => $employeeRole->id,
                'department_id' => $itDepartment->id,
                'office_time_id' => $standardOfficeTime?->id,
                'designation' => 'QA Engineer',
                'date_of_joining' => now()->subMonths(1),
                'status' => 'active',
            ]
        );
        $employee3->update(['manager_id' => $supervisorUser->id]);
    }
}