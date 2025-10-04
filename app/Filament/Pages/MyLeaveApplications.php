<?php

namespace App\Filament\Pages;

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

class MyLeaveApplications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'My Leave Applications';
    protected static ?string $title = 'My Leave Applications';
    protected static string $view = 'filament.pages.my-leave-applications';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 4;

    public function getTableQuery(): Builder
    {
        return LeaveApplication::query()
            ->where('user_id', auth()->id())
            ->with(['leaveType', 'approvedBy']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('days_count')
                    ->label('Days')
                    ->numeric(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                    }),
                TextColumn::make('applied_at')
                    ->dateTime()
                    ->label('Applied At')
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('Not approved'),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->label('Approved At')
                    ->placeholder('Not approved'),
            ])
            ->actions([
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (LeaveApplication $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LeaveApplication $record) {
                        $record->update(['status' => 'cancelled']);
                        
                        Notification::make()
                            ->title('Leave Cancelled')
                            ->body('Your leave application has been cancelled.')
                            ->success()
                            ->send();
                    }),
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
                                    $start = \Carbon\Carbon::parse($state);
                                    $end = \Carbon\Carbon::parse($endDate);
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
                                    $start = \Carbon\Carbon::parse($startDate);
                                    $end = \Carbon\Carbon::parse($state);
                                    $days = $start->diffInDays($end) + 1;
                                    $set('days_count', $days);
                                }
                            }),
                        TextColumn::make('days_count')
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
            ->defaultSort('applied_at', 'desc');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isEmployee();
    }
}