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
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('leave_type_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('year')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('consumed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('accrued')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('carry_forward')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leave_type_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('consumed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('accrued')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carry_forward')
                    ->numeric()
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
