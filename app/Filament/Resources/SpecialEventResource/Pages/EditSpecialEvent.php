<?php

namespace App\Filament\Resources\SpecialEventResource\Pages;

use App\Filament\Resources\SpecialEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpecialEvent extends EditRecord
{
    protected static string $resource = SpecialEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
