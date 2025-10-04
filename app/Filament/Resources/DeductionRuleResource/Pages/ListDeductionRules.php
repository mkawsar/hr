<?php

namespace App\Filament\Resources\DeductionRuleResource\Pages;

use App\Filament\Resources\DeductionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeductionRules extends ListRecords
{
    protected static string $resource = DeductionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
