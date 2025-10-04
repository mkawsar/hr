<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeductionRuleResource\Pages;
use App\Filament\Resources\DeductionRuleResource\RelationManagers;
use App\Models\DeductionRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeductionRuleResource extends Resource
{
    protected static ?string $model = DeductionRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 2;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Deduction Rule Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('threshold_minutes')
                            ->required()
                            ->numeric()
                            ->label('Threshold (minutes)')
                            ->helperText('Minutes late/early to trigger this rule'),
                        Forms\Components\TextInput::make('deduction_value')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->label('Deduction Value')
                            ->helperText('Amount to deduct'),
                        Forms\Components\Select::make('deduction_unit')
                            ->options([
                                'hours' => 'Hours',
                                'days' => 'Days',
                                'payroll_units' => 'Payroll Units',
                            ])
                            ->required()
                            ->label('Deduction Unit'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Description')
                            ->helperText('Explain this deduction rule'),
                        Forms\Components\Toggle::make('active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Enable this deduction rule'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('threshold_minutes')
                    ->numeric()
                    ->sortable()
                    ->label('Threshold (min)'),
                Tables\Columns\TextColumn::make('deduction_value')
                    ->numeric()
                    ->sortable()
                    ->label('Deduction Value'),
                Tables\Columns\TextColumn::make('deduction_unit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hours' => 'info',
                        'days' => 'warning',
                        'payroll_units' => 'danger',
                    })
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Active'),
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
                Tables\Filters\SelectFilter::make('deduction_unit')
                    ->options([
                        'hours' => 'Hours',
                        'days' => 'Days',
                        'payroll_units' => 'Payroll Units',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
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
            ->defaultSort('threshold_minutes');
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
            'index' => Pages\ListDeductionRules::route('/'),
            'create' => Pages\CreateDeductionRule::route('/create'),
            'edit' => Pages\EditDeductionRule::route('/{record}/edit'),
        ];
    }
}
