<?php

namespace App\Filament\Resources\ClassSubjectSettingResource\Pages;

use App\Filament\Resources\ClassSubjectSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassSubjectSettings extends ListRecords
{
    protected static string $resource = ClassSubjectSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Subject'),
        ];
    }
}
