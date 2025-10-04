<?php

namespace App\Filament\Pages;

use App\Models\DailyAttendance;
use App\Models\LeaveApplication;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
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
        return DailyAttendance::query()
            ->where('user_id', auth()->id())
            ->whereMonth('date', $this->selectedMonth)
            ->whereYear('date', $this->selectedYear)
            ->with(['entries' => function ($query) {
                $query->orderBy('clock_in')->orderBy('created_at');
            }])
            ->orderBy('date', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->formatStateUsing(fn ($state) => $state->format('Y-m-d'))
                    ->sortable(),
                TextColumn::make('day_name')
                    ->label('Day')
                    ->getStateUsing(fn (DailyAttendance $record) => $record->date->format('l')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (DailyAttendance $record) => $this->getAttendanceStatus($record))
                    ->color(fn (DailyAttendance $record) => $this->getStatusColor($record)),
                TextColumn::make('first_clock_in')
                    ->label('First Clock In')
                    ->getStateUsing(fn (DailyAttendance $record) => $this->getFirstClockIn($record)),
                TextColumn::make('last_clock_out')
                    ->label('Last Clock Out')
                    ->getStateUsing(fn (DailyAttendance $record) => $this->getLastClockOut($record)),
                TextColumn::make('total_working_hours')
                    ->label('Total Hours')
                    ->getStateUsing(fn (DailyAttendance $record) => $this->getTotalHours($record)),
                TextColumn::make('total_entries')
                    ->label('Entries')
                    ->getStateUsing(fn (DailyAttendance $record) => $record->total_entries . ' entries'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view_details')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn (DailyAttendance $record) => $record->total_entries > 0)
                    ->modalContent(function (DailyAttendance $record) {
                        $entries = $record->entries()->orderBy('clock_in')->get();
                        
                        $html = '<div class="space-y-4">';
                        
                        // Day Summary
                        $html .= '<div class="bg-blue-50 p-4 rounded-lg">';
                        $html .= '<h4 class="font-semibold text-blue-900 mb-2">Day Summary</h4>';
                        $html .= '<div class="grid grid-cols-2 gap-2 text-sm">';
                        $html .= '<div><strong>Date:</strong> ' . $record->date->format('M d, Y') . '</div>';
                        $html .= '<div><strong>Total Entries:</strong> ' . $record->total_entries . '</div>';
                        $html .= '<div><strong>First Clock In:</strong> ' . ($record->first_clock_in ? \Carbon\Carbon::parse($record->first_clock_in)->format('H:i:s') : '-') . '</div>';
                        $html .= '<div><strong>Last Clock Out:</strong> ' . ($record->last_clock_out ? \Carbon\Carbon::parse($record->last_clock_out)->format('H:i:s') : '-') . '</div>';
                        $html .= '<div><strong>Total Hours:</strong> ' . number_format($record->total_working_hours, 2) . 'h</div>';
                        $html .= '<div><strong>Status:</strong> ' . ucwords(str_replace('_', ' ', $record->status)) . '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // All Entries
                        if ($entries->count() > 0) {
                            $html .= '<h4 class="font-semibold text-gray-900 mb-2">All Entries</h4>';
                            foreach ($entries as $index => $entry) {
                                $html .= '<div class="border border-gray-200 p-3 rounded-lg">';
                                $html .= '<h5 class="font-medium text-gray-900 mb-2">Entry ' . ($index + 1) . '</h5>';
                                $html .= '<div class="grid grid-cols-2 gap-2 text-sm">';
                                $html .= '<div><strong>Clock In:</strong> ' . ($entry->clock_in ? \Carbon\Carbon::parse($entry->clock_in)->format('H:i:s') : '-') . '</div>';
                                $html .= '<div><strong>Clock Out:</strong> ' . ($entry->clock_out ? \Carbon\Carbon::parse($entry->clock_out)->format('H:i:s') : '-') . '</div>';
                                $html .= '<div><strong>Working Hours:</strong> ' . ($entry->working_hours ? number_format($entry->working_hours, 2) . 'h' : '-') . '</div>';
                                $html .= '<div><strong>Status:</strong> ' . ucwords(str_replace('_', ' ', $entry->entry_status)) . '</div>';
                                $html .= '<div><strong>Source:</strong> ' . ucwords($entry->source) . '</div>';
                                if ($entry->notes) {
                                    $html .= '<div class="col-span-2"><strong>Notes:</strong> ' . $entry->notes . '</div>';
                                }
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                        }
                        
                        $html .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->modalHeading(fn (DailyAttendance $record) => 'Day Details - ' . $record->date->format('M d, Y'))
                    ->modalWidth('4xl'),
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
                ->form([
                    \Filament\Forms\Components\Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ])
                        ->default($this->selectedMonth),
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Year')
                        ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                        ->default($this->selectedYear),
                ])
                ->action(function (array $data): void {
                    $this->selectedMonth = $data['month'];
                    $this->selectedYear = $data['year'];
                }),
        ];
    }

    private function getAttendanceStatus(DailyAttendance $record): string
    {
        $date = $record->date;
        $user = auth()->user();

        // Check if it's a holiday
        $holiday = \App\Models\Holiday::getHoliday(Carbon::parse($date));
        if ($holiday) {
            return $holiday->name; // Show specific holiday name
        }

        // Check if this day was a working day based on stored office time snapshot
        if (!$record->wasWorkingDay()) {
            return 'Weekend'; // Show "Weekend" for non-working days
        }

        // Check if on leave
        $leaveApplication = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->with('leaveType')
            ->first();

        if ($leaveApplication) {
            return $leaveApplication->leaveType->name . ' Leave';
        }


        // Check if there's actual attendance data
        $firstEntry = $record->entries->where('clock_in', '!=', null)->first();
        $lastEntry = $record->entries->where('clock_out', '!=', null)->last();

        // If no clock in/out, it's absent
        if (!$firstEntry && !$lastEntry) {
            return 'Absent';
        }

        // If only clock in or only clock out, it's incomplete
        if (!$firstEntry || !$lastEntry) {
            return 'Incomplete';
        }

        // Show the actual attendance status
        return match ($record->status) {
            'full_present' => 'Present',
            'late_in' => 'Late In',
            'early_out' => 'Early Out',
            'late_in_early_out' => 'Late + Early',
            'present' => 'Present',
            'late' => 'Late',
            'early_leave' => 'Early Leave',
            'absent' => 'Absent',
            'half_day' => 'Half Day',
            default => ucfirst(str_replace('_', ' ', $record->status)),
        };
    }

    private function getStatusColor(DailyAttendance $record): string
    {
        $date = $record->date;
        $user = auth()->user();

        // Check if it's a holiday
        $holiday = \App\Models\Holiday::getHoliday(Carbon::parse($date));
        if ($holiday) {
            return 'danger'; // Red for holidays
        }

        // Check if this day was a working day based on stored office time snapshot
        if (!$record->wasWorkingDay()) {
            return 'warning'; // Yellow for weekend
        }

        // Check if on leave
        $leaveApplication = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->first();

        if ($leaveApplication) {
            return 'info';
        }


        // Check if there's actual attendance data
        $firstEntry = $record->entries->where('clock_in', '!=', null)->first();
        $lastEntry = $record->entries->where('clock_out', '!=', null)->last();

        // If no clock in/out, it's absent
        if (!$firstEntry && !$lastEntry) {
            return 'danger';
        }

        // If only clock in or only clock out, it's incomplete
        if (!$firstEntry || !$lastEntry) {
            return 'warning';
        }

        // Return color based on status
        return match ($record->status) {
            'full_present', 'present' => 'success',
            'late_in', 'early_out', 'late_in_early_out', 'late', 'early_leave' => 'warning',
            'absent' => 'danger',
            'half_day' => 'info',
            default => 'gray',
        };
    }

    private function getFirstClockIn(DailyAttendance $record): string
    {
        $date = $record->date;
        $user = auth()->user();

        // Check if it's a holiday
        $holiday = \App\Models\Holiday::getHoliday(Carbon::parse($date));
        if ($holiday) {
            return '-';
        }

        // Check if this day was a working day based on stored office time snapshot
        if (!$record->wasWorkingDay()) {
            return '-';
        }

        // Check if on leave
        if ($this->isUserOnLeave($date, $user)) {
            return '-';
        }

        // Get first clock in from entries
        $firstEntry = $record->entries->where('clock_in', '!=', null)->first();
        return $firstEntry ? $firstEntry->clock_in->format('H:i') : '-';
    }

    private function getLastClockOut(DailyAttendance $record): string
    {
        $date = $record->date;
        $user = auth()->user();

        // Check if it's a holiday
        $holiday = \App\Models\Holiday::getHoliday(Carbon::parse($date));
        if ($holiday) {
            return '-';
        }

        // Check if this day was a working day based on stored office time snapshot
        if (!$record->wasWorkingDay()) {
            return '-';
        }

        // Check if on leave
        if ($this->isUserOnLeave($date, $user)) {
            return '-';
        }

        // Get last clock out from entries
        $lastEntry = $record->entries->where('clock_out', '!=', null)->last();
        return $lastEntry ? $lastEntry->clock_out->format('H:i') : '-';
    }

    private function getTotalHours(DailyAttendance $record): string
    {
        $date = $record->date;
        $user = auth()->user();

        // Check if it's a holiday
        $holiday = \App\Models\Holiday::getHoliday(Carbon::parse($date));
        if ($holiday) {
            return '-';
        }

        // Check if this day was a working day based on stored office time snapshot
        if (!$record->wasWorkingDay()) {
            return '-';
        }

        // Check if on leave
        if ($this->isUserOnLeave($date, $user)) {
            return '-';
        }

        return number_format($record->total_working_hours, 2) . 'h';
    }

    private function isWorkingDay($date, $user): bool
    {
        if ($user->officeTime) {
            return $user->officeTime->isWorkingDate(Carbon::parse($date));
        } else {
            return !Carbon::parse($date)->isWeekend();
        }
    }

    private function isUserOnLeave($date, $user): ?LeaveApplication
    {
        return LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->first();
    }

    public function getMonthlyWorkingHours(): float
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('total_working_hours') ?? 0;
    }

    public function getMonthlyPresentDays(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->whereIn('status', ['present', 'late_in', 'early_out', 'late_in_early_out', 'late', 'early_leave', 'half_day'])
            ->count();
    }

    public function getMonthlyLateDays(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->whereIn('status', ['late_in', 'late_in_early_out', 'late'])
            ->count();
    }

    public function getMonthlyAbsentDays(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->where('status', 'absent')
            ->count();
    }

    public function getMonthlyLeaveDays(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->where('status', 'leave')
            ->count();
    }

    public function getMonthlyHolidayDays(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->where('status', 'holiday')
            ->count();
    }

    public function getMonthlyLateMinutes(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('total_late_minutes') ?? 0;
    }

    public function getMonthlyEarlyMinutes(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $currentMonth = now()->format('Y-m');
        
        return DailyAttendance::where('user_id', $user->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('total_early_minutes') ?? 0;
    }
}