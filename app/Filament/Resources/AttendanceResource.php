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
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'early_leave' => 'Early Leave',
                                'half_day' => 'Half Day',
                            ])
                            ->default('present')
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
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'early_leave' => 'warning',
                        'half_day' => 'info',
                    }),
                Tables\Columns\TextColumn::make('late_minutes')
                    ->numeric()
                    ->label('Late (min)')
                    ->sortable(),
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
