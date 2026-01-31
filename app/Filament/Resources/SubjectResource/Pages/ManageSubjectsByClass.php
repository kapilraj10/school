<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use App\Models\ClassRoom;
use App\Models\Subject;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ManageSubjectsByClass extends Page
{
    protected static string $resource = SubjectResource::class;

    protected static string $view = 'filament.resources.subject-resource.pages.manage-subjects-by-class';

    protected static ?string $title = 'Manage Subjects by Class';

    public function getHeading(): string|Htmlable
    {
        return 'Manage Subjects by Class';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Select a class to view and manage its subjects';
    }

    public function getClasses()
    {
        return ClassRoom::query()
            ->with(['classTeacher'])
            ->withCount('timetableSlots')
            ->get()
            ->map(function ($class) {
                $subjectsCount = Subject::where('class_room_id', $class->id)->count();
                $activeSubjectsCount = Subject::where('class_room_id', $class->id)
                    ->where('status', 'active')
                    ->count();

                // Extract numeric part from class name
                preg_match('/\d+/', $class->name, $matches);
                $classNumber = isset($matches[0]) ? (int) $matches[0] : 0;

                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'section' => $class->section,
                    'full_name' => $class->full_name,
                    'status' => $class->status,
                    'class_teacher' => $class->classTeacher?->name ?? 'Not assigned',
                    'subjects_count' => $subjectsCount,
                    'active_subjects_count' => $activeSubjectsCount,
                    'weekly_periods' => $class->weekly_periods,
                    'total_subjects' => $class->total_subjects,
                    'class_number' => $classNumber,
                ];
            })
            ->sortBy([
                ['class_number', 'asc'],
                ['section', 'asc'],
            ])
            ->values();
    }

    public function getClassesGrouped()
    {
        $classes = $this->getClasses();
        $grouped = [];

        foreach ($classes as $class) {
            $key = $class['name']; // Group by class name (e.g., "Class 1", "Class 2")
            if (! isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $class;
        }

        return $grouped;
    }
}
