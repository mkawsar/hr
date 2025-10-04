<?php

namespace App\Filament\Pages;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LeaveApplication;
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

class MyLeaveBalance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'My Leave Balance';
    protected static ?string $title = 'My Leave Balance';
    protected static string $view = 'filament.pages.my-leave-balance';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 5;

    public $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = now()->year;
    }

    public function getTableQuery(): Builder
    {
        return LeaveBalance::query()
            ->where('user_id', auth()->id())
            ->where('year', $this->selectedYear)
            ->with(['leaveType']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leaveType.code')
                    ->label('Code')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('accrued')
                    ->label('Accrued Days')
                    ->numeric()
                    ->description('Days earned this year'),
                TextColumn::make('carry_forward')
                    ->label('Carry Forward')
                    ->numeric()
                    ->color('info')
                    ->description('Days from previous year'),
                TextColumn::make('consumed')
                    ->label('Availed Days')
                    ->numeric()
                    ->color('warning')
                    ->description('Days taken this year'),
                TextColumn::make('balance')
                    ->label('Remaining Balance')
                    ->numeric()
                    ->color('success')
                    ->description('Available days'),
                TextColumn::make('deducted_days')
                    ->label('Deducted Amount')
                    ->getStateUsing(function (LeaveBalance $record): string {
                        // Calculate deducted days (this could be from penalties, etc.)
                        $deducted = 0; // You can implement deduction logic here
                        return number_format($deducted, 2);
                    })
                    ->color('danger')
                    ->description('Penalty deductions'),
                TextColumn::make('leaveType.encashable')
                    ->label('Encashable')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                TextColumn::make('leaveType.carry_forward_allowed')
                    ->label('Carry Forward')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
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
            ->defaultSort('leaveType.name');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('change_year')
                ->label('Change Year')
                ->icon('heroicon-o-calendar')
                ->form([
                    Select::make('year')
                        ->label('Year')
                        ->options(array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))
                        ->default($this->selectedYear),
                ])
                ->action(function (array $data) {
                    $this->selectedYear = $data['year'];
                }),
        ];
    }
}