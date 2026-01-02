<?php

namespace App\Filament\Resources\ClassRangeResource\Pages;

use App\Filament\Resources\ClassRangeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassRange extends EditRecord
{
    protected static string $resource = ClassRangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
