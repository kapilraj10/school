<?php

namespace App\Filament\Resources\AcademicTermResource\Pages;

use App\Filament\Resources\AcademicTermResource;
use App\Models\AcademicTerm;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicTerm extends CreateRecord
{
    protected static string $resource = AcademicTermResource::class;

    protected function afterCreate(): void
    {
        // If this term is set as active, deactivate all other terms
        if ($this->record->is_active) {
            AcademicTerm::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}
