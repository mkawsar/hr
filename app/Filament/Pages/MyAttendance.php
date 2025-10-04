<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class MyAttendance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'My Attendance';
    protected static ?string $title = 'My Attendance';
    protected static string $view = 'filament.pages.my-attendance';

    protected static ?string $navigationGroup = 'Attendance';
    protected static ?int $navigationSort = 1;

    public $selectedMonth;
    public $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function getTableQuery(): Builder
    {
        // For now, return the basic query and we'll enhance the columns to show comprehensive data
        return Attendance::query()
            ->where('user_id', auth()->id())
            ->whereMonth('date', $this->selectedMonth)
            ->whereYear('date', $this->selectedYear)
            ->with(['clockInLocation', 'clockOutLocation']);
    }
    

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('day_status')
                    ->label('Day Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday (from holiday table)
                        if (\App\Models\Holiday::isHoliday($date)) {
                            $holiday = \App\Models\Holiday::getHoliday($date);
                            return $holiday ? $holiday->name : 'Holiday';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            // Fallback to standard weekend check if no office time
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return $date->format('l'); // Saturday, Sunday, or non-working day
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->with('leaveType')
                            ->first();
                        
                        if ($leaveApplication) {
                            return $leaveApplication->leaveType->name;
                        }
                        
                        // If has attendance, it's a working day with attendance
                        return 'Working Day';
                    })
                    ->color(fn ($state): string => match (true) {
                        str_contains($state, 'Holiday') => 'danger',
                        in_array($state, ['Saturday', 'Sunday']) => 'warning',
                        str_contains($state, 'Leave') => 'info',
                        $state === 'Working Day' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return '-';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return '-';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return '-';
                        }
                        
                        return $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '-';
                    }),
                TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return '-';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return '-';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return '-';
                        }
                        
                        return $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '-';
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            $holiday = \App\Models\Holiday::getHoliday($date);
                            return $holiday ? $holiday->name : 'Holiday';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return $date->format('l'); // Friday, Saturday, or non-working day
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->with('leaveType')
                            ->first();
                        
                        if ($leaveApplication) {
                            return $leaveApplication->leaveType->name;
                        }
                        
                        // Check for incomplete attendance (only clock in OR only clock out)
                        if ((!$record->clock_in && $record->clock_out) || ($record->clock_in && !$record->clock_out)) {
                            return 'Absent';
                        }
                        
                        // Show attendance status for working days with complete attendance
                        return match ($record->status) {
                            'full_present' => 'Full Present',
                            'late_in' => 'Late In',
                            'early_out' => 'Early Out',
                            'late_in_early_out' => 'Late In + Early Out',
                            'present' => 'Present',
                            'late' => 'Late',
                            'early_leave' => 'Early Leave',
                            'absent' => 'Absent',
                            'half_day' => 'Half Day',
                            default => ucfirst(str_replace('_', ' ', $record->status)),
                        };
                    })
                    ->color(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return 'danger';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return 'warning';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return 'info';
                        }
                        
                        // Check for incomplete attendance (only clock in OR only clock out)
                        if ((!$record->clock_in && $record->clock_out) || ($record->clock_in && !$record->clock_out)) {
                            return 'danger'; // Red color for absent
                        }
                        
                        // Show attendance status colors for working days with complete attendance
                        return match ($record->status) {
                            'full_present' => 'success',
                            'late_in' => 'warning',
                            'early_out' => 'warning',
                            'late_in_early_out' => 'danger',
                            'present' => 'success',
                            'late' => 'danger',
                            'early_leave' => 'danger',
                            'absent' => 'danger',
                            'half_day' => 'info',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->getStateUsing(function (Attendance $record): string {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return '-';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return '-';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return '-';
                        }
                        
                        // Show working hours for working days with attendance
                        if ($record->clock_in && $record->clock_out) {
                            $totalMinutes = Carbon::parse($record->clock_in)->diffInMinutes(Carbon::parse($record->clock_out));
                            $totalHours = round($totalMinutes / 60, 2);
                            return number_format($totalHours, 2) . 'h';
                        }
                        return '-';
                    }),
                TextColumn::make('late_minutes')
                    ->label('Late (min)')
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return '-';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return '-';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return '-';
                        }
                        
                        return $record->late_minutes ?? 0;
                    }),
                TextColumn::make('early_minutes')
                    ->label('Early (min)')
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        if (\App\Models\Holiday::isHoliday($date)) {
                            return '-';
                        }
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        if (!$isWorkingDay) {
                            return '-';
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        if ($leaveApplication) {
                            return '-';
                        }
                        
                        return $record->early_minutes ?? 0;
                    }),
                TextColumn::make('clockInLocation.name')
                    ->label('Location')
                    ->placeholder('N/A'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'full_present' => 'Full Present',
                        'late_in' => 'Late In',
                        'early_out' => 'Early Out',
                        'late_in_early_out' => 'Late In + Early Out',
                        'present' => 'Present (Legacy)',
                        'late' => 'Late (Legacy)',
                        'early_leave' => 'Early Leave (Legacy)',
                        'absent' => 'Absent',
                        'half_day' => 'Half Day',
                    ]),
            ])
            ->headerActions([
                Action::make('apply_leave')
                    ->label('Apply for Leave')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Select::make('leave_type_id')
                            ->label('Leave Type')
                            ->options(LeaveType::where('active', true)->pluck('name', 'id'))
                            ->required(),
                        DatePicker::make('start_date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $endDate = $get('end_date');
                                if ($state && $endDate) {
                                    $start = Carbon::parse($state);
                                    $end = Carbon::parse($endDate);
                                    $days = $start->diffInDays($end) + 1;
                                    $set('days_count', $days);
                                }
                            }),
                        DatePicker::make('end_date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $startDate = $get('start_date');
                                if ($state && $startDate) {
                                    $start = Carbon::parse($startDate);
                                    $end = Carbon::parse($state);
                                    $days = $start->diffInDays($end) + 1;
                                    $set('days_count', $days);
                                }
                            }),
                        \Filament\Forms\Components\TextInput::make('days_count')
                            ->label('Number of Days')
                            ->disabled(),
                        Textarea::make('reason')
                            ->label('Reason for Leave')
                            ->rows(3)
                            ->required(),
                        FileUpload::make('attachment')
                            ->label('Supporting Document')
                            ->directory('leave-attachments')
                            ->visibility('private'),
                    ])
                    ->action(function (array $data) {
                        LeaveApplication::create([
                            'user_id' => auth()->id(),
                            'leave_type_id' => $data['leave_type_id'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                            'days_count' => $data['days_count'],
                            'reason' => $data['reason'],
                            'attachment' => $data['attachment'] ?? null,
                            'status' => 'pending',
                            'applied_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Leave Application Submitted')
                            ->body('Your leave application has been submitted for approval.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('change_month')
                ->label('Change Month')
                ->icon('heroicon-o-calendar')
                ->form([
                    Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->default($this->selectedMonth),
                    Select::make('year')
                        ->label('Year')
                        ->options(array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))
                        ->default($this->selectedYear),
                ])
                ->action(function (array $data) {
                    $this->selectedMonth = $data['month'];
                    $this->selectedYear = $data['year'];
                }),
        ];
    }
}