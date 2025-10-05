<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Holidays';
    protected static ?string $modelLabel = 'Holiday';
    protected static ?string $pluralModelLabel = 'Holidays';
    protected static ?string $navigationGroup = 'Time Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Holiday Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., New Year Day'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Description of the holiday'),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->label('Holiday Date'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'national' => 'National Holiday',
                                'regional' => 'Regional Holiday',
                                'company' => 'Company Holiday',
                            ])
                            ->default('national')
                            ->required(),
                        Forms\Components\Toggle::make('recurring')
                            ->label('Recurring Holiday')
                            ->helperText('Check if this holiday occurs every year (like Christmas)'),
                        Forms\Components\Toggle::make('active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Whether this holiday is currently active'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_name')
                    ->label('Day')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'national' => 'success',
                        'regional' => 'info',
                        'company' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'national' => 'National',
                        'regional' => 'Regional',
                        'company' => 'Company',
                        default => ucfirst($state),
                    }),
                Tables\Columns\IconColumn::make('recurring')
                    ->boolean()
                    ->label('Recurring'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'national' => 'National Holiday',
                        'regional' => 'Regional Holiday',
                        'company' => 'Company Holiday',
                    ]),
                Tables\Filters\TernaryFilter::make('recurring')
                    ->label('Recurring'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->options(array_combine(range(now()->year - 2, now()->year + 2), range(now()->year - 2, now()->year + 2)))
                            ->default(now()->year),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->whereYear('date', $year),
                            );
                    }),
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
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
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