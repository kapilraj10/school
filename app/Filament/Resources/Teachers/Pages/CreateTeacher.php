<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        return TeacherForm::mutateFormDataBeforeSave($data);
    }
}
