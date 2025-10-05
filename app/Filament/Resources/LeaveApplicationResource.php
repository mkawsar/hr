<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveApplicationResource\Pages;
use App\Filament\Resources\LeaveApplicationResource\RelationManagers;
use App\Models\LeaveApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        // Admins cannot access leave applications resource
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        // Admins cannot create leave applications for themselves
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        // Only employees and supervisors can edit their own applications
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        // Only employees and supervisors can delete their own applications
        return $user && ($user->isEmployee() || $user->isSupervisor());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Leave Application Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Employee'),
                        Forms\Components\Select::make('leave_type_id')
                            ->relationship('leaveType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Leave Type'),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $endDate = $get('end_date');
                                if ($state && $endDate) {
                                    $start = \Carbon\Carbon::parse($state);
                                    $end = \Carbon\Carbon::parse($endDate);
                                    $days = $start->diffInDays($end) + 1;
                                    $set('days_count', $days);
                                }
                            }),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $startDate = $get('start_date');
                                if ($state && $startDate) {
                                    $start = \Carbon\Carbon::parse($startDate);
                                    $end = \Carbon\Carbon::parse($state);
                                    $days = $start->diffInDays($end) + 1;
                                    $set('days_count', $days);
                                }
                            }),
                        Forms\Components\TextInput::make('days_count')
                            ->numeric()
                            ->label('Number of Days')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Reason for Leave'),
                        Forms\Components\FileUpload::make('attachment')
                            ->label('Supporting Document')
                            ->directory('leave-attachments')
                            ->visibility('private'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Approval Information')
                    ->schema([
                        Forms\Components\Select::make('approved_by')
                            ->relationship('approvedBy', 'name')
                            ->label('Approved By')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At'),
                        Forms\Components\Textarea::make('approval_notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Approval Notes'),
                        Forms\Components\DateTimePicker::make('applied_at')
                            ->default(now())
                            ->required()
                            ->label('Applied At'),
                    ])->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if ($user->isEmployee() || $user->isSupervisor()) {
            // Employees and supervisors can only see their own applications
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }
        
        // Admins cannot access this resource
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->searchable()
                    ->sortable()
                    ->label('Leave Type')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_count')
                    ->numeric()
                    ->sortable()
                    ->label('Days'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('applied_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Applied At'),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->searchable()
                    ->sortable()
                    ->label('Approved By')
                    ->placeholder('Not approved'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Approved At')
                    ->placeholder('Not approved'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->label('Leave Type'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveApplication $record): bool => 
                        $record->status === 'pending' && auth()->user()->canApproveLeave($record)
                    )
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->placeholder('Optional notes for approval...')
                    ])
                    ->action(function (LeaveApplication $record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        // Deduct leave days from user's leave balance
                        $record->deductFromLeaveBalance();
                    }),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LeaveApplication $record): bool => 
                        $record->status === 'pending' && auth()->user()->canApproveLeave($record)
                    )
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide a reason for rejection...')
                    ])
                    ->action(function (LeaveApplication $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('applied_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'edit' => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }
}
