<?php

namespace App\Filament\Resources\ClassRooms\Pages;

use App\Filament\Resources\ClassRooms\ClassRoomResource;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateClassRoom extends CreateRecord
{
    protected static string $resource = ClassRoomResource::class;

    protected ?int $copyFromClassRoomId = null;

    protected bool $copySubjectsFromSource = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->copyFromClassRoomId = isset($data['copy_from_class_room_id'])
            ? (int) $data['copy_from_class_room_id']
            : null;

        $this->copySubjectsFromSource = (bool) ($data['copy_subjects_from_source'] ?? false);

        unset($data['copy_from_class_room_id'], $data['copy_subjects_from_source'], $data['copy_from_class_message']);

        // Set default status to active
        $data['status'] = $data['status'] ?? 'active';

        // Set weekly_periods and total_subjects from general settings or defaults
        $data['weekly_periods'] = $data['weekly_periods'] ?? config('timetable.default_weekly_periods', 48);
        $data['total_subjects'] = $data['total_subjects'] ?? config('timetable.default_total_subjects', 8);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->copyFromClassRoomId) {
            return;
        }

        if (! $this->copySubjectsFromSource) {
            return;
        }

        $sourceClassRoom = ClassRoom::find($this->copyFromClassRoomId);

        if (! $sourceClassRoom) {
            return;
        }

        $sourceSubjects = Subject::query()
            ->where('class_room_id', $sourceClassRoom->id)
            ->get();

        foreach ($sourceSubjects as $sourceSubject) {
            $base = Str::upper(Str::before((string) $sourceSubject->code, '-'));

            if ($base === '') {
                $base = Str::upper(Str::of($sourceSubject->name)
                    ->replaceMatches('/[^A-Za-z]/', '')
                    ->substr(0, 6)
                    ->toString());
            }

            $classNumber = preg_replace('/\D+/', '', (string) $this->record->name) ?: (string) $this->record->id;
            $suffix = Str::upper($classNumber.(string) $this->record->section);

            $code = Str::upper(Str::substr($base, 0, 10)).'-'.$suffix;
            $counter = 2;

            while (Subject::query()->where('code', $code)->exists()) {
                $shortBase = Str::upper(Str::substr($base, 0, max(1, 10 - strlen((string) $counter))));
                $code = $shortBase.$counter.'-'.$suffix;
                $counter++;

                if ($counter > 99) {
                    $code = Str::upper(Str::substr($base, 0, 6)).'-'.$this->record->id.'-'.$sourceSubject->id;
                    break;
                }
            }

            $newSubject = Subject::create([
                'name' => $sourceSubject->name,
                'code' => $code,
                'class_room_id' => $this->record->id,
                'type' => $sourceSubject->type,
                'level' => $sourceSubject->level,
                'status' => $sourceSubject->status,
            ]);

            $sourceSetting = ClassSubjectSetting::where('class_room_id', $sourceClassRoom->id)
                ->where('subject_id', $sourceSubject->id)
                ->first();

            ClassSubjectSetting::create([
                'class_room_id' => $this->record->id,
                'subject_id' => $newSubject->id,
                'weekly_periods' => $sourceSetting?->weekly_periods ?? 4,
                'min_periods_per_week' => $sourceSetting?->min_periods_per_week ?? 1,
                'max_periods_per_week' => $sourceSetting?->max_periods_per_week ?? 6,
                'single_combined' => $sourceSetting?->single_combined ?? 'single',
                'is_active' => true,
                'priority' => $sourceSetting?->priority ?? 5,
            ]);
        }
    }
}
