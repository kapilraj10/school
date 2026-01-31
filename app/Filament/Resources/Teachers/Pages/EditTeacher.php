<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['availability'] = [
            'days' => $data['available_days'] ?? [],
            'periods' => $data['available_periods'] ?? [],
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return TeacherForm::mutateFormDataBeforeSave($data);
    }
}
