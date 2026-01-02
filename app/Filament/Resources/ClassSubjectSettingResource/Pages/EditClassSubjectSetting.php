<?php

namespace App\Filament\Resources\ClassSubjectSettingResource\Pages;

use App\Filament\Resources\ClassSubjectSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassSubjectSetting extends EditRecord
{
    protected static string $resource = ClassSubjectSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
