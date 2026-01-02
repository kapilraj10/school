<?php

namespace App\Filament\Resources\TimetableSettingResource\Pages;

use App\Filament\Resources\TimetableSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimetableSetting extends EditRecord
{
    protected static string $resource = TimetableSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
