<?php

namespace App\Filament\Resources\LeaveBalanceResource\Pages;

use App\Filament\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveBalances extends ListRecords
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Leave Balance'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Balances'),
            'current_year' => Tab::make('Current Year')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('year', date('Y')))
                ->badge(LeaveBalance::where('year', date('Y'))->count()),
            'low_balance' => Tab::make('Low Balance')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('balance', '<', 5))
                ->badge(LeaveBalance::where('balance', '<', 5)->count())
                ->badgeColor('warning'),
        ];
    }
}
