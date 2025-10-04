<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Attendance';
    
    protected static ?int $navigationSort = 1;

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
        return $user && $user->isAdmin();
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
            // Admin can see all attendance records
            return parent::getEloquentQuery();
        } elseif ($user->isSupervisor()) {
            // Supervisor can only see their team's attendance records
            return parent::getEloquentQuery()
                ->whereIn('user_id', $user->subordinates->pluck('id'));
        }
        
        // Employees cannot access this resource (they use MyAttendance page)
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TimePicker::make('clock_in')
                            ->label('Clock In Time'),
                        Forms\Components\TimePicker::make('clock_out')
                            ->label('Clock Out Time'),
                        Forms\Components\Select::make('source')
                            ->options([
                                'office' => 'Office',
                                'remote' => 'Remote',
                                'mobile' => 'Mobile',
                            ])
                            ->default('office')
                            ->required(),
                        Forms\Components\Select::make('status')
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
                            ])
                            ->default('full_present')
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Location Information')
                    ->schema([
                        Forms\Components\Select::make('clock_in_location_id')
                            ->relationship('clockInLocation', 'name')
                            ->label('Clock In Location')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('clock_out_location_id')
                            ->relationship('clockOutLocation', 'name')
                            ->label('Clock Out Location')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('clock_in_latitude')
                            ->label('Clock In Latitude')
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('clock_in_longitude')
                            ->label('Clock In Longitude')
                            ->numeric()
                            ->step(0.00000001),
                    ])->columns(2),
                
                Forms\Components\Section::make('Adjustment Information')
                    ->schema([
                        Forms\Components\TextInput::make('late_minutes')
                            ->numeric()
                            ->label('Late Minutes')
                            ->default(0),
                        Forms\Components\TextInput::make('early_minutes')
                            ->numeric()
                            ->label('Early Minutes')
                            ->default(0),
                        Forms\Components\TextInput::make('deduction_amount')
                            ->numeric()
                            ->label('Deduction Amount')
                            ->default(0)
                            ->step(0.01),
                        Forms\Components\Select::make('adjusted_by')
                            ->relationship('adjustedBy', 'name')
                            ->label('Adjusted By')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('adjustment_reason')
                            ->label('Adjustment Reason')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_type')
                    ->label('Day Type')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->date);
                        
                        if (\App\Models\Holiday::isHoliday($date)) {
                            $holiday = \App\Models\Holiday::getHoliday($date);
                            return $holiday ? $holiday->name : 'Holiday';
                        }
                        
                        if ($date->isWeekend()) {
                            return $date->format('l'); // Saturday, Sunday
                        }
                        
                        return 'Working Day';
                    })
                    ->color(fn ($state): string => match (true) {
                        str_contains($state, 'Holiday') => 'danger',
                        in_array($state, ['Saturday', 'Sunday']) => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('clock_in')
                    ->time()
                    ->sortable()
                    ->label('Clock In'),
                Tables\Columns\TextColumn::make('clock_out')
                    ->time()
                    ->sortable()
                    ->label('Clock Out'),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'office' => 'success',
                        'remote' => 'info',
                        'mobile' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_present' => 'success',
                        'late_in' => 'warning',
                        'early_out' => 'warning',
                        'late_in_early_out' => 'danger',
                        'present' => 'success', // Legacy
                        'late' => 'danger', // Legacy
                        'early_leave' => 'danger', // Legacy
                        'absent' => 'danger',
                        'half_day' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full_present' => 'Full Present',
                        'late_in' => 'Late In',
                        'early_out' => 'Early Out',
                        'late_in_early_out' => 'Late In + Early Out',
                        'present' => 'Present',
                        'late' => 'Late',
                        'early_leave' => 'Early Leave',
                        'absent' => 'Absent',
                        'half_day' => 'Half Day',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('late_minutes')
                    ->numeric()
                    ->label('Late (min)')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state): string => $state > 0 ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('early_minutes')
                    ->numeric()
                    ->label('Early (min)')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state): string => $state > 0 ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('working_hours')
                    ->label('Working Hours')
                    ->getStateUsing(function ($record): string {
                        if ($record->clock_in && $record->clock_out) {
                            $totalMinutes = \Carbon\Carbon::parse($record->clock_in)->diffInMinutes(\Carbon\Carbon::parse($record->clock_out));
                            $totalHours = round($totalMinutes / 60, 2);
                            return number_format($totalHours, 2) . 'h';
                        }
                        return '-';
                    }),
                Tables\Columns\TextColumn::make('deduction_amount')
                    ->money('USD')
                    ->label('Deduction')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clockInLocation.name')
                    ->label('Location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                        'early_leave' => 'Early Leave',
                        'half_day' => 'Half Day',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'office' => 'Office',
                        'remote' => 'Remote',
                        'mobile' => 'Mobile',
                    ]),
                Tables\Filters\Filter::make('date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
