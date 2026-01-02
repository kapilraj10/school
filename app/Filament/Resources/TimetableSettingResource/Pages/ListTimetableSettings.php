<?php

namespace App\Filament\Resources\TimetableSettingResource\Pages;

use App\Filament\Resources\TimetableSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimetableSettings extends ListRecords
{
    protected static string $resource = TimetableSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
