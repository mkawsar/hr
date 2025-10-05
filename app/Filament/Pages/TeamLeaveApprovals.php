<?php

namespace App\Filament\Pages;

use App\Models\LeaveApplication;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class TeamLeaveApprovals extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $navigationLabel = 'Team Leave Approvals';
    protected static ?string $title = 'Team Leave Approvals';
    protected static string $view = 'filament.pages.team-leave-approvals';

    protected static ?string $navigationGroup = 'Leave Management';
    protected static ?int $navigationSort = 3;

    public function getTableQuery(): Builder
    {
        $user = auth()->user();
        
        if ($user->isSupervisor()) {
            // Supervisor can only see their team's pending leave applications
            return LeaveApplication::query()
                ->whereIn('user_id', $user->subordinates->pluck('id'))
                ->where('status', 'pending')
                ->with(['user', 'leaveType']);
        }
        
        return LeaveApplication::query()->whereRaw('1 = 0'); // Empty query for non-supervisors
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.employee_id')
                    ->label('Employee ID')
                    ->searchable(),
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
                TextColumn::make('reason')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('applied_at')
                    ->dateTime()
                    ->label('Applied At')
                    ->sortable(),
            ])
            ->actions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->placeholder('Optional notes for approval...')
                    ])
                    ->action(function (LeaveApplication $record, array $data) {
                        $user = auth()->user();
                        
                        if (!$user->canApproveLeave($record)) {
                            Notification::make()
                                ->title('Access Denied')
                                ->body('You are not authorized to approve this leave application.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        // Deduct leave days from user's leave balance
                        $record->deductFromLeaveBalance();

                        Notification::make()
                            ->title('Leave Approved')
                            ->body("Leave application for {$record->user->name} has been approved and deducted from leave balance.")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('approval_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide a reason for rejection...')
                    ])
                    ->action(function (LeaveApplication $record, array $data) {
                        $user = auth()->user();
                        
                        if (!$user->canApproveLeave($record)) {
                            Notification::make()
                                ->title('Access Denied')
                                ->body('You are not authorized to reject this leave application.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'],
                        ]);

                        Notification::make()
                            ->title('Leave Rejected')
                            ->body("Leave application for {$record->user->name} has been rejected.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('applied_at', 'desc');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isSupervisor();
    }
}