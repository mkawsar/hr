<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Filament\Resources\LeaveBalanceResource\RelationManagers;
use App\Models\LeaveBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationLabel = 'Leave Balances';
    
    protected static ?string $modelLabel = 'Leave Balance';
    
    protected static ?string $pluralModelLabel = 'Leave Balances';
    
    protected static ?string $navigationGroup = 'Leave Management';

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
            // Admin can see all leave balances
            return parent::getEloquentQuery();
        } elseif ($user->isSupervisor()) {
            // Supervisor can only see their team's leave balances
            return parent::getEloquentQuery()
                ->whereIn('user_id', $user->subordinates->pluck('id'));
        }
        
        // Employees cannot access this resource
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->numeric()
                    ->default(date('Y')),
                Forms\Components\TextInput::make('balance')
                    ->label('Current Balance')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.1),
                Forms\Components\TextInput::make('consumed')
                    ->label('Consumed Days')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.1),
                Forms\Components\TextInput::make('accrued')
                    ->label('Accrued Days')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.1),
                Forms\Components\TextInput::make('carry_forward')
                    ->label('Carry Forward Days')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->numeric(decimalPlaces: 1)
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 5 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('consumed')
                    ->label('Used')
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\TextColumn::make('accrued')
                    ->label('Earned')
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = LeaveBalance::select('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                        return $years;
                    })
                    ->default(date('Y')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLeaveBalances::route('/'),
            'create' => Pages\CreateLeaveBalance::route('/create'),
            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }
}
