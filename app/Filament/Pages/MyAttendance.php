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
                TextColumn::make('clock_in')
                    ->time()
                    ->label('Clock In'),
                TextColumn::make('clock_out')
                    ->time()
                    ->label('Clock Out'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        'early_leave' => 'warning',
                        'half_day' => 'info',
                    }),
                TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->getStateUsing(function (Attendance $record): string {
                        if ($record->clock_in && $record->clock_out) {
                            $hours = Carbon::parse($record->clock_in)->diffInHours(Carbon::parse($record->clock_out));
                            $minutes = Carbon::parse($record->clock_in)->diffInMinutes(Carbon::parse($record->clock_out)) % 60;
                            return sprintf('%d:%02d', $hours, $minutes);
                        }
                        return '-';
                    }),
                TextColumn::make('late_minutes')
                    ->label('Late (min)')
                    ->placeholder('0'),
                TextColumn::make('early_minutes')
                    ->label('Early (min)')
                    ->placeholder('0'),
                TextColumn::make('clockInLocation.name')
                    ->label('Location')
                    ->placeholder('N/A'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                        'early_leave' => 'Early Leave',
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