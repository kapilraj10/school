<?php

namespace App\Filament\Resources\ExamScheduleResource\Pages;

use App\Filament\Resources\ExamScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamSchedule extends EditRecord
{
    protected static string $resource = ExamScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
