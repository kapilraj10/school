<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use App\Models\ClassSubjectSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

    /**
     * @var array<string, int|string|bool>
     */
    protected array $periodSettings = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->periodSettings = [
            'min_periods_per_week' => (int) ($data['min_periods_per_week'] ?? 1),
            'weekly_periods' => (int) ($data['weekly_periods'] ?? 4),
            'max_periods_per_week' => (int) ($data['max_periods_per_week'] ?? 6),
            'single_combined' => (string) ($data['single_combined'] ?? 'single'),
            'is_active' => ((string) ($data['setting_is_active'] ?? '1')) === '1',
            'priority' => (int) ($data['priority'] ?? 5),
        ];

        unset(
            $data['min_periods_per_week'],
            $data['weekly_periods'],
            $data['max_periods_per_week'],
            $data['single_combined'],
            $data['setting_is_active'],
            $data['priority'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        ClassSubjectSetting::updateOrCreate(
            [
                'class_room_id' => $this->record->class_room_id,
                'subject_id' => $this->record->id,
            ],
            $this->periodSettings,
        );
    }
}
