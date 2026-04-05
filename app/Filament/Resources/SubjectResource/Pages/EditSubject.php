<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use App\Models\ClassSubjectSetting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    /**
     * @var array<string, int|string|bool>
     */
    protected array $periodSettings = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $setting = ClassSubjectSetting::query()
            ->where('class_room_id', $data['class_room_id'] ?? $this->record->class_room_id)
            ->where('subject_id', $this->record->id)
            ->first();

        $data['min_periods_per_week'] = $setting?->min_periods_per_week ?? 1;
        $data['weekly_periods'] = $setting?->weekly_periods ?? 4;
        $data['max_periods_per_week'] = $setting?->max_periods_per_week ?? 6;
        $data['single_combined'] = $setting?->single_combined ?? 'single';
        $data['room_id'] = $setting?->room_id;
        $data['setting_is_active'] = $setting?->is_active ? '1' : '0';
        $data['priority'] = $setting?->priority ?? 5;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->periodSettings = [
            'min_periods_per_week' => (int) ($data['min_periods_per_week'] ?? 1),
            'weekly_periods' => (int) ($data['weekly_periods'] ?? 4),
            'max_periods_per_week' => (int) ($data['max_periods_per_week'] ?? 6),
            'single_combined' => (string) ($data['single_combined'] ?? 'single'),
            'room_id' => isset($data['room_id']) ? (int) $data['room_id'] : null,
            'is_active' => ((string) ($data['setting_is_active'] ?? '1')) === '1',
            'priority' => (int) ($data['priority'] ?? 5),
        ];

        unset(
            $data['min_periods_per_week'],
            $data['weekly_periods'],
            $data['max_periods_per_week'],
            $data['single_combined'],
            $data['room_id'],
            $data['setting_is_active'],
            $data['priority'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        ClassSubjectSetting::query()
            ->where('subject_id', $this->record->id)
            ->where('class_room_id', '!=', $this->record->class_room_id)
            ->delete();

        ClassSubjectSetting::updateOrCreate(
            [
                'class_room_id' => $this->record->class_room_id,
                'subject_id' => $this->record->id,
            ],
            $this->periodSettings,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
