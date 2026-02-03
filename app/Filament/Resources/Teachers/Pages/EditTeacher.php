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
        // Derive days and periods from availability_matrix
        $matrix = $data['availability_matrix'] ?? [];
        $days = array_keys($matrix);
        $periods = [];

        if (! empty($matrix)) {
            foreach ($matrix as $dayPeriods) {
                $periods = array_merge($periods, array_keys($dayPeriods));
            }
            $periods = array_unique($periods);
            sort($periods);
        }

        $data['availability'] = [
            'days' => $days,
            'periods' => $periods,
            'matrix' => $matrix,
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return TeacherForm::mutateFormDataBeforeSave($data);
    }
}
