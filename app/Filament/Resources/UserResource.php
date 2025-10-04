<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Employees';
    
    protected static ?string $modelLabel = 'Employee';
    
    protected static ?string $pluralModelLabel = 'Employees';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isSupervisor()) {
            // Supervisor can only edit their team members
            return $user->subordinates->contains('id', $record->id);
        }
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            // Admin can see all users
            return parent::getEloquentQuery();
        } elseif ($user->isSupervisor()) {
            // Supervisor can only see their team members
            return parent::getEloquentQuery()
                ->whereIn('id', $user->subordinates->pluck('id'));
        }
        
        // Employees cannot access this resource
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('employee_id')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Employee ID'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state)),
                    ])->columns(2),
                
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone_1')
                            ->tel()
                            ->label('Primary Phone'),
                        Forms\Components\TextInput::make('phone_2')
                            ->tel()
                            ->label('Secondary Phone'),
                        Forms\Components\Textarea::make('address')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
                
                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->relationship('role', 'name')
                            ->required()
                            ->preload(),
                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->preload(),
                        Forms\Components\Select::make('office_time_id')
                            ->label('Office Time Schedule')
                            ->searchable()
                            ->options(function () {
                                return \App\Models\OfficeTime::where('active', true)
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
                            ->helperText('Assign office time schedule to this employee')
                            ->placeholder('Select an office time schedule')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Custom Schedule'),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('e.g., CUSTOM'),
                                Forms\Components\TimePicker::make('start_time')
                                    ->required()
                                    ->default('09:00'),
                                Forms\Components\TimePicker::make('end_time')
                                    ->required()
                                    ->default('17:00'),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $officeTime = \App\Models\OfficeTime::create([
                                    'name' => $data['name'],
                                    'code' => $data['code'],
                                    'start_time' => $data['start_time'],
                                    'end_time' => $data['end_time'],
                                    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                                    'working_hours_per_day' => 8,
                                    'late_grace_minutes' => 15,
                                    'early_grace_minutes' => 15,
                                    'active' => true,
                                ]);
                                
                                return $officeTime->id;
                            }),
                        Forms\Components\Select::make('manager_id')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('designation')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date_of_joining')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Profile')
                    ->schema([
                        Forms\Components\FileUpload::make('profile_photo')
                            ->image()
                            ->directory('profile-photos')
                            ->visibility('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),
                Tables\Columns\TextColumn::make('employee_id')
                    ->searchable()
                    ->sortable()
                    ->label('Employee ID'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('designation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable()
                    ->label('Department'),
                Tables\Columns\TextColumn::make('officeTime.name')
                    ->searchable()
                    ->sortable()
                    ->label('Office Time')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($record) {
                        if (!$record->officeTime) {
                            return 'Not Assigned';
                        }
                        return $record->officeTime->name . ' (' . $record->officeTime->code . ')';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->officeTime) {
                            return 'No office time assigned';
                        }
                        
                        $startTime = $record->officeTime->start_time ? $record->officeTime->start_time->format('H:i') : 'N/A';
                        $endTime = $record->officeTime->end_time ? $record->officeTime->end_time->format('H:i') : 'N/A';
                        $workingDays = $record->officeTime->working_days && is_array($record->officeTime->working_days) 
                            ? implode(', ', array_map('ucfirst', $record->officeTime->working_days))
                            : 'Not Set';
                            
                        return $startTime . ' - ' . $endTime . ' | ' . $workingDays;
                    }),
                Tables\Columns\TextColumn::make('role.name')
                    ->searchable()
                    ->sortable()
                    ->label('Role'),
                Tables\Columns\TextColumn::make('manager.name')
                    ->searchable()
                    ->sortable()
                    ->label('Manager'),
                Tables\Columns\TextColumn::make('date_of_joining')
                    ->date()
                    ->sortable()
                    ->label('Joining Date'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('role_id')
                    ->relationship('role', 'name'),
                Tables\Filters\SelectFilter::make('office_time_id')
                    ->relationship('officeTime', 'name')
                    ->label('Office Time')
                    ->placeholder('All Office Times'),
                Tables\Filters\TernaryFilter::make('has_office_time')
                    ->label('Office Time Assignment')
                    ->placeholder('All Employees')
                    ->trueLabel('Has Office Time')
                    ->falseLabel('No Office Time')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('office_time_id'),
                        false: fn (Builder $query) => $query->whereNull('office_time_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_office_time')
                    ->label('Assign Office Time')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('office_time_id')
                            ->label('Office Time Schedule')
                            ->options(function () {
                                return \App\Models\OfficeTime::where('active', true)
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
                    ])
                    ->action(function (array $data, $record) {
                        $record->update(['office_time_id' => $data['office_time_id']]);
                    })
                    ->successNotificationTitle('Office time assigned successfully'),
                Tables\Actions\Action::make('remove_office_time')
                    ->label('Remove Office Time')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['office_time_id' => null]);
                    })
                    ->successNotificationTitle('Office time removed successfully')
                    ->visible(fn ($record) => $record->office_time_id !== null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('assign_office_time')
                        ->label('Assign Office Time')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('office_time_id')
                                ->label('Office Time Schedule')
                                ->options(function () {
                                    return \App\Models\OfficeTime::where('active', true)
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
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['office_time_id' => $data['office_time_id']]);
                                
                                // Create daily attendance records for current month
                                $this->createDailyAttendanceForUser($record, $data['office_time_id']);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Office time assigned successfully'),
                    Tables\Actions\BulkAction::make('remove_office_time')
                        ->label('Remove Office Time')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['office_time_id' => null]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Office time removed successfully'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function createDailyAttendanceForUser($user, $officeTimeId): void
    {
        $officeTime = \App\Models\OfficeTime::find($officeTimeId);
        if (!$officeTime) {
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

        // Create records for current month
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayName = strtolower($currentDate->format('l'));
            $isWorkingDay = in_array($dayName, $officeTimeSnapshot['working_days'] ?? []);

            $existingRecord = \App\Models\DailyAttendance::where('user_id', $user->id)
                ->where('date', $currentDate->format('Y-m-d'))
                ->first();

            if (!$existingRecord) {
                \App\Models\DailyAttendance::create([
                    'user_id' => $user->id,
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
            } else {
                $existingRecord->update([
                    'office_time_id' => $officeTimeId,
                    'office_time_snapshot' => $officeTimeSnapshot,
                    'adjusted_by' => auth()->id(),
                    'adjustment_reason' => 'Office time assignment - Updated office time information',
                ]);
            }

            $currentDate->addDay();
        }
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
