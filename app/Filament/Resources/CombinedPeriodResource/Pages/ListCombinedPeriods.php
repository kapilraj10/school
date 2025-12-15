<?php

namespace App\Filament\Resources\CombinedPeriodResource\Pages;

use App\Filament\Resources\CombinedPeriodResource;
use Filament\Resources\Pages\ListRecords;

class ListCombinedPeriods extends ListRecords
{
    protected static string $resource = CombinedPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
