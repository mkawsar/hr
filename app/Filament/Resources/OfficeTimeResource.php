<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeTimeResource\Pages;
use App\Models\OfficeTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficeTimeResource extends Resource
{
    protected static ?string $model = OfficeTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Office Times';
    protected static ?string $modelLabel = 'Office Time';
    protected static ?string $pluralModelLabel = 'Office Times';
    protected static ?string $navigationGroup = 'Time Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Standard Office Hours'),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., STD'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Description of this office time schedule'),
                    ])->columns(2),

                Forms\Components\Section::make('Working Hours')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->default('09:00')
                            ->label('Start Time'),
                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->default('17:00')
                            ->label('End Time'),
                        Forms\Components\TimePicker::make('break_start_time')
                            ->label('Break Start Time')
                            ->default('12:00'),
                        Forms\Components\TimePicker::make('break_end_time')
                            ->label('Break End Time')
                            ->default('13:00'),
                        Forms\Components\TextInput::make('break_duration_minutes')
                            ->numeric()
                            ->default(60)
                            ->label('Break Duration (minutes)')
                            ->suffix('minutes'),
                        Forms\Components\TextInput::make('working_hours_per_day')
                            ->numeric()
                            ->default(8)
                            ->label('Working Hours per Day')
                            ->suffix('hours'),
                    ])->columns(3),

                Forms\Components\Section::make('Working Days')
                    ->schema([
                        Forms\Components\CheckboxList::make('working_days')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->default(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                            ->columns(4)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Grace Periods')
                    ->schema([
                        Forms\Components\TextInput::make('late_grace_minutes')
                            ->numeric()
                            ->default(15)
                            ->label('Late Grace Period (minutes)')
                            ->suffix('minutes')
                            ->helperText('Grace period for late arrival'),
                        Forms\Components\TextInput::make('early_grace_minutes')
                            ->numeric()
                            ->default(15)
                            ->label('Early Grace Period (minutes)')
                            ->suffix('minutes')
                            ->helperText('Grace period for early departure'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Whether this office time is currently active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Start Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('End Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('working_days_formatted')
                    ->label('Working Days')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_working_hours')
                    ->label('Total Hours')
                    ->suffix(' hrs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Assigned Employees')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListOfficeTimes::route('/'),
            'create' => Pages\CreateOfficeTime::route('/create'),
            'edit' => Pages\EditOfficeTime::route('/{record}/edit'),
        ];
    }

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
}