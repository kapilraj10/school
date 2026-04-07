<?php

namespace App\Filament\Resources\TextCarouselItemResource\Pages;

use App\Filament\Resources\TextCarouselItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTextCarouselItems extends ListRecords
{
    protected static string $resource = TextCarouselItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
