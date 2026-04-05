<?php

namespace App\Filament\Resources\ClassRooms\Pages;

use App\Filament\Resources\ClassRooms\ClassRoomResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditClassRoom extends EditRecord
{
    protected static string $resource = ClassRoomResource::class;

    /**
     * @var array<int, int>
     */
    protected array $selectedStudentIds = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['student_ids'] = $this->record->students()->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedStudentIds = collect($data['student_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (count($this->selectedStudentIds) > (int) ($data['capacity'] ?? $this->record->capacity)) {
            throw ValidationException::withMessages([
                'student_ids' => 'Assigned students exceed class capacity.',
            ]);
        }

        unset($data['student_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        DB::transaction(function (): void {
            User::query()
                ->where('class_room_id', $this->record->id)
                ->whereNotIn('id', $this->selectedStudentIds)
                ->update(['class_room_id' => null]);

            if ($this->selectedStudentIds !== []) {
                User::query()
                    ->whereIn('id', $this->selectedStudentIds)
                    ->update(['class_room_id' => $this->record->id]);
            }
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
