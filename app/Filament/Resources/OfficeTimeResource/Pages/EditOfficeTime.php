<?php

namespace App\Filament\Resources\OfficeTimeResource\Pages;

use App\Filament\Resources\OfficeTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficeTime extends EditRecord
{
    protected static string $resource = OfficeTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
