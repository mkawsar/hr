<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\DailyAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = DailyAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Attendance';
    
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
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
        }
        
        // Only admins can access this resource
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
                        Forms\Components\TimePicker::make('first_clock_in')
                            ->label('First Clock In Time'),
                        Forms\Components\TimePicker::make('last_clock_out')
                            ->label('Last Clock Out Time'),
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
                                'present' => 'Present',
                                'late' => 'Late',
                                'absent' => 'Absent',
                                'half_day' => 'Half Day',
                            ])
                            ->default('present')
                            ->required(),
                    ])->columns(2),
                
                
                Forms\Components\Section::make('Adjustment Information')
                    ->schema([
                        Forms\Components\TextInput::make('total_late_minutes')
                            ->numeric()
                            ->label('Total Late Minutes')
                            ->default(0),
                        Forms\Components\TextInput::make('total_early_minutes')
                            ->numeric()
                            ->label('Total Early Minutes')
                            ->default(0),
                        Forms\Components\TextInput::make('total_working_hours')
                            ->numeric()
                            ->label('Total Working Hours')
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
                Tables\Columns\TextColumn::make('first_clock_in')
                    ->time()
                    ->sortable()
                    ->label('First Clock In'),
                Tables\Columns\TextColumn::make('last_clock_out')
                    ->time()
                    ->sortable()
                    ->label('Last Clock Out'),
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
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        'half_day' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                        'half_day' => 'Half Day',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('total_late_minutes')
                    ->numeric()
                    ->label('Late (min)')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state): string => $state > 0 ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('total_early_minutes')
                    ->numeric()
                    ->label('Early (min)')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state): string => $state > 0 ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('total_working_hours')
                    ->label('Working Hours')
                    ->numeric()
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state, 2) . 'h' : '-'),
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
