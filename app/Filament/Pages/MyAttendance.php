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

    public function getAvailableMonths(): array
    {
        $months = [];
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // If viewing current year, only show months up to current month
        // If viewing previous years, show all 12 months
        if ($this->selectedYear == $currentYear) {
            // Current year: only allow current and past months
            for ($month = 1; $month <= $currentMonth; $month++) {
                $months[$month] = now()->month($month)->format('F');
            }
        } else {
            // Previous years: show all 12 months
            for ($month = 1; $month <= 12; $month++) {
                $months[$month] = now()->month($month)->format('F');
            }
        }
        
        return $months;
    }

    public function getAvailableYears(): array
    {
        $years = [];
        $currentYear = now()->year;
        
        // Only allow current and past years (going back 3 years)
        for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
            $years[$year] = $year;
        }
        
        return $years;
    }

    public function getTableQuery(): Builder
    {
        $query = DailyAttendance::query()
            ->where('user_id', auth()->id())
            ->with(['entries' => function ($query) {
                $query->orderBy('clock_in')->orderBy('created_at');
            }]);

        // If viewing current month, show from first day to today
        if ($this->selectedMonth == now()->month && $this->selectedYear == now()->year) {
            $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
            $endDate = now();
            
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            // For other months, show the full month
            $query->whereMonth('date', $this->selectedMonth)
                  ->whereYear('date', $this->selectedYear);
        }

        return $query->orderBy('date', 'desc');
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
                        $html .= '<div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">';
                        $html .= '<h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 Day Summary</h4>';
                        $html .= '<div class="grid grid-cols-2 gap-2 text-sm">';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">Date:</strong> ' . $record->date->format('M d, Y') . '</div>';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">Total Entries:</strong> ' . $record->total_entries . '</div>';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">First Clock In:</strong> ' . ($record->first_clock_in ? \Carbon\Carbon::parse($record->first_clock_in)->format('H:i:s') : '-') . '</div>';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">Last Clock Out:</strong> ' . ($record->last_clock_out ? \Carbon\Carbon::parse($record->last_clock_out)->format('H:i:s') : '-') . '</div>';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">Total Hours:</strong> ' . number_format($record->total_working_hours, 2) . 'h</div>';
                        $html .= '<div class="text-blue-800 dark:text-blue-200"><strong class="text-blue-900 dark:text-blue-100">Status:</strong> ' . ucwords(str_replace('_', ' ', $record->status)) . '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // All Entries
                        if ($entries->count() > 0) {
                            $html .= '<h4 class="font-semibold text-gray-900 dark:text-white mb-3">📋 All Entries</h4>';
                            foreach ($entries as $index => $entry) {
                                $html .= '<div class="border border-gray-200 dark:border-gray-600 p-4 rounded-lg mb-3 bg-white dark:bg-gray-800">';
                                $html .= '<h5 class="font-medium text-gray-900 dark:text-white mb-3">Entry ' . ($index + 1) . '</h5>';
                                
                                // Basic Info Grid
                                $html .= '<div class="grid grid-cols-2 gap-3 text-sm mb-3">';
                                $html .= '<div class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Clock In:</strong> ' . ($entry->clock_in ? \Carbon\Carbon::parse($entry->clock_in)->format('H:i:s') : '-') . '</div>';
                                $html .= '<div class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Clock Out:</strong> ' . ($entry->clock_out ? \Carbon\Carbon::parse($entry->clock_out)->format('H:i:s') : '-') . '</div>';
                                $html .= '<div class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Working Hours:</strong> ' . ($entry->working_hours ? number_format($entry->working_hours, 2) . 'h' : '-') . '</div>';
                                $html .= '<div class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Status:</strong> ' . ucwords(str_replace('_', ' ', $entry->entry_status)) . '</div>';
                                $html .= '<div class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Source:</strong> ' . ucwords($entry->source) . '</div>';
                                $html .= '</div>';
                                
                                // Address Information Section
                                if ($entry->clock_in_address || $entry->clock_out_address) {
                                    $html .= '<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">';
                                    $html .= '<h6 class="font-medium text-gray-800 dark:text-gray-200 mb-2 text-sm">📍 Location Details</h6>';
                                    $html .= '<div class="space-y-2">';
                                    if ($entry->clock_in_address) {
                                        $html .= '<div class="flex items-start space-x-2">';
                                        $html .= '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">IN</span>';
                                        $html .= '<div class="flex-1">';
                                        $html .= '<div class="text-xs text-gray-600 dark:text-gray-400">Clock In Location</div>';
                                        $html .= '<div class="text-sm text-gray-800 dark:text-gray-200 break-words">' . htmlspecialchars($entry->clock_in_address) . '</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }
                                    if ($entry->clock_out_address) {
                                        $html .= '<div class="flex items-start space-x-2">';
                                        $html .= '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">OUT</span>';
                                        $html .= '<div class="flex-1">';
                                        $html .= '<div class="text-xs text-gray-600 dark:text-gray-400">Clock Out Location</div>';
                                        $html .= '<div class="text-sm text-gray-800 dark:text-gray-200 break-words">' . htmlspecialchars($entry->clock_out_address) . '</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                
                                // Notes Section
                                if ($entry->notes) {
                                    $html .= '<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">';
                                    $html .= '<h6 class="font-medium text-gray-800 dark:text-gray-200 mb-1 text-sm">📝 Notes</h6>';
                                    $html .= '<div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-2 rounded break-words">' . htmlspecialchars($entry->notes) . '</div>';
                                    $html .= '</div>';
                                }
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
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Year')
                        ->options($this->getAvailableYears())
                        ->default($this->selectedYear)
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            // Reset month to 1 when year changes to ensure valid selection
                            if ($state != now()->year) {
                                $set('month', 1);
                            }
                        }),
                    \Filament\Forms\Components\Select::make('month')
                        ->label('Month')
                        ->options(function ($get) {
                            // Get the selected year from the form
                            $selectedYear = $get('year') ?? $this->selectedYear;
                            
                            // Temporarily set the selected year to get correct months
                            $originalYear = $this->selectedYear;
                            $this->selectedYear = $selectedYear;
                            $months = $this->getAvailableMonths();
                            $this->selectedYear = $originalYear;
                            
                            return $months;
                        })
                        ->default($this->selectedMonth)
                        ->live(),
                ])
                ->action(function (array $data): void {
                    // Only update when modal is submitted
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

        // Check if on leave (approved or pending)
        $leaveApplication = LeaveApplication::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->with('leaveType')
            ->first();

        if ($leaveApplication) {
            if ($leaveApplication->status === 'approved') {
                return $leaveApplication->leaveType->name . ' Leave';
            } else {
                return $leaveApplication->leaveType->name . ' Pending';
            }
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

        // Check if on leave (approved or pending)
        $leaveApplication = LeaveApplication::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->first();

        if ($leaveApplication) {
            if ($leaveApplication->status === 'approved') {
                return 'info'; // Blue for approved leave
            } else {
                return 'warning'; // Yellow for pending leave
            }
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
            ->whereIn('status', ['approved', 'pending'])
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