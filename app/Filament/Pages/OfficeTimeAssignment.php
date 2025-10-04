<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\OfficeTime;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class OfficeTimeAssignment extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Office Time Assignment';
    protected static ?string $title = 'Office Time Assignment';
    protected static ?string $navigationGroup = 'Time Management';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.office-time-assignment';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Bulk Office Time Assignment')
                    ->description('Assign office time schedules to multiple employees at once')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('department_id')
                                    ->label('Filter by Department')
                                    ->options(function () {
                                        return \App\Models\Department::all()
                                            ->mapWithKeys(function ($department) {
                                                return [$department->id => $department->name];
                                            });
                                    })
                                    ->placeholder('All Departments')
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->loadEmployees()),
                                    
                                Select::make('office_time_id')
                                    ->label('Office Time Schedule')
                                    ->options(function () {
                                        return OfficeTime::where('active', true)
                                            ->get()
                                            ->mapWithKeys(function ($officeTime) {
                                                $startTime = $officeTime->start_time ? $officeTime->start_time->format('H:i') : 'N/A';
                                                $endTime = $officeTime->end_time ? $officeTime->end_time->format('H:i') : 'N/A';
                                                
                                                return [
                                                    $officeTime->id => $officeTime->name . ' (' . $officeTime->code . ') - ' . 
                                                                     $startTime . ' to ' . $endTime
                                                ];
                                            });
                                    })
                                    ->required()
                                    ->placeholder('Select office time schedule'),
                                    
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now()->startOfMonth())
                                    ->helperText('Start date for office time assignment'),
                                    
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->default(now()->endOfMonth())
                                    ->helperText('End date for office time assignment')
                                    ->after('start_date'),
                                    
                                Toggle::make('create_daily_attendance')
                                    ->label('Create Daily Attendance Records')
                                    ->default(true)
                                    ->helperText('Create daily attendance records for the date range')
                                    ->columnSpan(2),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }


    public function loadEmployees(): void
    {
        // This method can be used to load employees based on department filter
        // Implementation depends on your specific needs
    }

    public function assignOfficeTime(): void
    {
        $data = $this->form->getState();
        
        if (!$data['office_time_id']) {
            Notification::make()
                ->title('Error')
                ->body('Please select an office time schedule.')
                ->danger()
                ->send();
            return;
        }

        if (!$data['start_date'] || !$data['end_date']) {
            Notification::make()
                ->title('Error')
                ->body('Please select start and end dates.')
                ->danger()
                ->send();
            return;
        }

        $query = User::query();
        
        if (!empty($data['department_id'])) {
            $query->where('department_id', $data['department_id']);
        }

        $employees = $query->get();
        $updatedCount = 0;
        $dailyAttendanceCount = 0;

        // Get the office time details for snapshot
        $officeTime = OfficeTime::find($data['office_time_id']);
        if (!$officeTime) {
            Notification::make()
                ->title('Error')
                ->body('Selected office time schedule not found.')
                ->danger()
                ->send();
            return;
        }

        // Create office time snapshot
        $officeTimeSnapshot = [
            'name' => $officeTime->name,
            'code' => $officeTime->code,
            'start_time' => $officeTime->start_time ? $officeTime->start_time->format('H:i') : null,
            'end_time' => $officeTime->end_time ? $officeTime->end_time->format('H:i') : null,
            'break_start_time' => $officeTime->break_start_time ? $officeTime->break_start_time->format('H:i') : null,
            'break_end_time' => $officeTime->break_end_time ? $officeTime->break_end_time->format('H:i') : null,
            'break_duration_minutes' => $officeTime->break_duration_minutes,
            'working_hours_per_day' => $officeTime->working_hours_per_day,
            'working_days' => $officeTime->working_days,
            'late_grace_minutes' => $officeTime->late_grace_minutes,
            'early_grace_minutes' => $officeTime->early_grace_minutes,
        ];

        foreach ($employees as $employee) {
            // Update employee's office time
            $employee->update(['office_time_id' => $data['office_time_id']]);
            $updatedCount++;

            // Create daily attendance records if requested
            if ($data['create_daily_attendance'] ?? true) {
                $dailyAttendanceCount += $this->createDailyAttendanceRecords(
                    $employee, 
                    $data['start_date'], 
                    $data['end_date'], 
                    $data['office_time_id'], 
                    $officeTimeSnapshot
                );
            }
        }

        $message = "Office time assigned to {$updatedCount} employees successfully.";
        if ($data['create_daily_attendance'] ?? true) {
            $message .= " Created {$dailyAttendanceCount} daily attendance records.";
        }

        Notification::make()
            ->title('Success')
            ->body($message)
            ->success()
            ->send();

        $this->form->fill();
    }

    private function createDailyAttendanceRecords($employee, $startDate, $endDate, $officeTimeId, $officeTimeSnapshot): int
    {
        $createdCount = 0;
        $currentDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            // Check if this is a working day based on office time
            $dayName = strtolower($currentDate->format('l'));
            $isWorkingDay = in_array($dayName, $officeTimeSnapshot['working_days'] ?? []);

            // Check if daily attendance record already exists
            $existingRecord = \App\Models\DailyAttendance::where('user_id', $employee->id)
                ->where('date', $currentDate->format('Y-m-d'))
                ->first();

            if (!$existingRecord) {
                // Create new daily attendance record
                \App\Models\DailyAttendance::create([
                    'user_id' => $employee->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'first_clock_in' => null,
                    'last_clock_out' => null,
                    'total_entries' => 0,
                    'total_working_hours' => 0,
                    'total_late_minutes' => 0,
                    'total_early_minutes' => 0,
                    'status' => $isWorkingDay ? 'absent' : 'absent',
                    'source' => 'manual',
                    'adjusted_by' => auth()->id(),
                    'adjustment_reason' => 'Office time assignment - Daily attendance record created',
                    'office_time_id' => $officeTimeId,
                    'office_time_snapshot' => $officeTimeSnapshot,
                ]);
                $createdCount++;
            } else {
                // Update existing record with new office time information
                $existingRecord->update([
                    'office_time_id' => $officeTimeId,
                    'office_time_snapshot' => $officeTimeSnapshot,
                    'adjusted_by' => auth()->id(),
                    'adjustment_reason' => 'Office time assignment - Updated office time information',
                ]);
            }

            $currentDate->addDay();
        }

        return $createdCount;
    }

    public function getAssignmentStats(): array
    {
        $totalEmployees = User::count();
        $employeesWithOfficeTime = User::whereNotNull('office_time_id')->count();
        $employeesWithoutOfficeTime = $totalEmployees - $employeesWithOfficeTime;

        $officeTimeStats = OfficeTime::select('office_times.name', 'office_times.code', DB::raw('COUNT(users.id) as employee_count'))
            ->leftJoin('users', 'office_times.id', '=', 'users.office_time_id')
            ->where('office_times.active', true)
            ->groupBy('office_times.id', 'office_times.name', 'office_times.code')
            ->orderBy('employee_count', 'desc')
            ->get();

        $departmentStats = DB::table('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name as department_name', DB::raw('COUNT(users.id) as total_employees'), DB::raw('COUNT(CASE WHEN users.office_time_id IS NOT NULL THEN 1 END) as assigned_employees'))
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('total_employees', 'desc')
            ->get();

        return [
            'total_employees' => $totalEmployees,
            'employees_with_office_time' => $employeesWithOfficeTime,
            'employees_without_office_time' => $employeesWithoutOfficeTime,
            'office_time_stats' => $officeTimeStats,
            'department_stats' => $departmentStats,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }
}
