<?php

namespace App\Filament\Resources\CombinedPeriodResource\Pages;

use App\Filament\Resources\CombinedPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCombinedPeriod extends EditRecord
{
    protected static string $resource = CombinedPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
