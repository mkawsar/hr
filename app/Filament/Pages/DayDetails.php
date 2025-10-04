<?php

namespace App\Filament\Pages;

use App\Models\DailyAttendance;
use App\Models\AttendanceEntry;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class DayDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Day Details';
    protected static ?string $title = 'Day Details';
    protected static ?string $slug = 'day-details/{date}';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.day-details';

    public ?string $date = null;
    public ?DailyAttendance $dailyAttendance = null;

    public function mount(string $date): void
    {
        $this->date = $date;
        $this->dailyAttendance = DailyAttendance::where('user_id', auth()->id())
            ->where('date', $date)
            ->with('entries')
            ->first();
    }

    public function getTitle(): string
    {
        return 'Day Details - ' . \Carbon\Carbon::parse($this->date)->format('M d, Y');
    }

    public function table(Table $table): Table
    {
        if (!$this->dailyAttendance) {
            return $table->query(AttendanceEntry::query()->whereRaw('1 = 0')); // Empty query
        }

        return $table
            ->query(
                AttendanceEntry::query()
                    ->where('daily_attendance_id', $this->dailyAttendance->id)
                    ->orderBy('clock_in')
            )
            ->columns([
                TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->dateTime('H:i:s')
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->dateTime('H:i:s')
                    ->sortable(),
                TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->getStateUsing(fn (AttendanceEntry $record) => 
                        $record->working_hours ? number_format($record->working_hours, 2) . 'h' : '-'
                    ),
                TextColumn::make('entry_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_present', 'present' => 'success',
                        'late_in', 'early_out', 'late_in_early_out', 'late', 'early_leave' => 'warning',
                        'absent' => 'danger',
                        'half_day' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => 
                        str_replace('_', ' ', ucwords($state))
                    ),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'gray',
                        'biometric' => 'success',
                        'mobile' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->heading('Time Entries for ' . \Carbon\Carbon::parse($this->date)->format('M d, Y'))
            ->description($this->getDaySummary());
    }

    private function getDaySummary(): string
    {
        if (!$this->dailyAttendance) {
            return 'No attendance record found for this date.';
        }

        $summary = [];
        
        $summary[] = "Total Entries: {$this->dailyAttendance->total_entries}";
        
        if ($this->dailyAttendance->first_clock_in) {
            $summary[] = "First Clock In: " . \Carbon\Carbon::parse($this->dailyAttendance->first_clock_in)->format('H:i:s');
        }
        
        if ($this->dailyAttendance->last_clock_out) {
            $summary[] = "Last Clock Out: " . \Carbon\Carbon::parse($this->dailyAttendance->last_clock_out)->format('H:i:s');
        }
        
        if ($this->dailyAttendance->total_working_hours > 0) {
            $summary[] = "Total Working Hours: " . number_format($this->dailyAttendance->total_working_hours, 2) . 'h';
        }

        return implode(' • ', $summary);
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
