<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Filament\Resources\LeaveTypeResource\RelationManagers;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->helperText('Short code for this leave type'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Leave Policy')
                    ->schema([
                        Forms\Components\Toggle::make('encashable')
                            ->label('Can be encashed')
                            ->helperText('Allow employees to convert unused leave to cash'),
                        Forms\Components\Toggle::make('carry_forward_allowed')
                            ->label('Allow carry forward')
                            ->helperText('Allow unused leave to be carried to next year'),
                        Forms\Components\TextInput::make('max_carry_forward_days')
                            ->numeric()
                            ->label('Max carry forward days')
                            ->visible(fn (Forms\Get $get) => $get('carry_forward_allowed')),
                        Forms\Components\Toggle::make('requires_approval')
                            ->label('Requires approval')
                            ->helperText('Leave applications need manager/HR approval'),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Make this leave type available for use'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Accrual Settings')
                    ->schema([
                        Forms\Components\TextInput::make('accrual_days_per_year')
                            ->numeric()
                            ->label('Days per year')
                            ->helperText('Number of days accrued per year'),
                        Forms\Components\Select::make('accrual_frequency')
                            ->options([
                                'yearly' => 'Yearly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                            ])
                            ->required()
                            ->default('yearly')
                            ->label('Accrual frequency'),
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('accrual_days_per_year')
                    ->numeric()
                    ->sortable()
                    ->label('Days/Year'),
                Tables\Columns\TextColumn::make('accrual_frequency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yearly' => 'success',
                        'monthly' => 'info',
                        'quarterly' => 'warning',
                    }),
                Tables\Columns\IconColumn::make('encashable')
                    ->boolean()
                    ->label('Encashable'),
                Tables\Columns\IconColumn::make('carry_forward_allowed')
                    ->boolean()
                    ->label('Carry Forward'),
                Tables\Columns\IconColumn::make('requires_approval')
                    ->boolean()
                    ->label('Approval Required'),
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
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('encashable')
                    ->label('Encashable'),
                Tables\Filters\TernaryFilter::make('requires_approval')
                    ->label('Requires Approval'),
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
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
