<?php

namespace App\Livewire;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Services\TimetableValidationService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.designer')]
class TimetableDesigner extends Component
{
    public $selectedClassId;

    public $selectedTermId;

    public $classes;

    public $academicTerms;

    public $subjects;

    public $teachers;

    public $timetableSlots = [];

    public $weekStart;

    public $weekDates = [];

    public $editingSlot = null;

    public $slotDay;

    public $slotPeriod;

    public $slotSubjectId;

    public $slotTeacherId;

    public $slotStatus = 'draft';

    public $validationErrors = [];

    public $validationWarnings = [];

    public $showValidationModal = false;

    public $editMode = true;

    protected $rules = [
        'slotSubjectId' => 'required|exists:subjects,id',
        'slotTeacherId' => 'required|exists:teachers,id',
        'slotStatus' => 'required|in:draft,published',
    ];

    public function mount(): void
    {
        $this->classes = ClassRoom::query()
            ->active()
            ->orderByRaw('CAST(name AS UNSIGNED), name, section')
            ->get();
        $this->academicTerms = AcademicTerm::query()->orderBy('year', 'desc')->orderBy('term', 'desc')->get();

        $this->teachers = Teacher::query()->orderBy('name')->get();

        $this->selectedTermId = AcademicTerm::query()->active()->first()?->id;
        $this->selectedClassId = $this->classes->first()?->id;

        $this->weekStart = Carbon::now()->startOfWeek();
        $this->calculateWeekDates();

        $this->loadSubjects();
        $this->loadTimetableSlots();
    }

    public function loadSubjects(): void
    {
        if (! $this->selectedClassId) {
            $this->subjects = collect();

            return;
        }

        // Get subjects assigned to the selected class from ClassSubjectSetting
        $subjectIds = \App\Models\ClassSubjectSetting::query()
            ->where('class_room_id', $this->selectedClassId)
            ->pluck('subject_id')
            ->unique();

        $this->subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->with('teachers')
            ->orderBy('name')
            ->get()
            ->groupBy('name')
            ->map(fn ($group) => $group->first());
    }

    public function calculateWeekDates(): void
    {
        $this->weekDates = [];
        // Start from Sunday (dayOfWeek = 0)
        $sunday = Carbon::parse($this->weekStart)->startOfWeek(Carbon::SUNDAY);
        for ($i = 0; $i < 7; $i++) {
            $date = $sunday->copy()->addDays($i);
            $this->weekDates[] = [
                'date' => $date,
                'dayName' => $date->format('D'),
                'dayNum' => $date->day,
                'monthShort' => $date->format('M'),
                'dayOfWeek' => $date->dayOfWeek,
            ];
        }
    }

    public function updatedSelectedClassId(): void
    {
        $this->loadSubjects();
        $this->loadTimetableSlots();
    }

    public function updatedSelectedTermId(): void
    {
        $this->loadTimetableSlots();
    }

    public function loadTimetableSlots(): void
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            $this->timetableSlots = [];

            return;
        }

        $slots = TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->with(['subject', 'teacher'])
            ->get();

        $this->timetableSlots = [];
        foreach ($slots as $slot) {
            // Map recurring weekly slots to current week dates
            foreach ($this->weekDates as $weekDate) {
                if ($weekDate['dayOfWeek'] == $slot->day) {
                    $dateKey = $weekDate['date']->format('Y-m-d');
                    $key = "{$dateKey}_{$slot->period}";
                    $this->timetableSlots[$key] = $slot;
                }
            }
        }
    }

    public function assignPeriod($subjectId, $teacherId, $date, $period): void
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return;
        }

        $subject = Subject::find($subjectId);
        $currentCount = TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('subject_id', $subjectId)
            ->count();

        if ($subject && $subject->max_periods_per_week && $currentCount >= $subject->max_periods_per_week) {
            $this->validationErrors = [[
                'type' => 'Max Periods Exceeded',
                'message' => "Cannot assign {$subject->name}. Maximum periods per week ({$subject->max_periods_per_week}) already reached.",
            ]];
            $this->showValidationModal = true;

            return;
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $dateKey = Carbon::parse($date)->format('Y-m-d');

        $validationService = new TimetableValidationService;
        $validation = $validationService->validateSlotAssignment(
            $this->selectedClassId,
            $this->selectedTermId,
            $subjectId,
            $teacherId,
            $dayOfWeek,
            $period
        );

        if (! empty($validation['errors'])) {
            $this->validationErrors = $validation['errors'];
            $this->validationWarnings = $validation['warnings'] ?? [];
            $this->showValidationModal = true;

            return;
        }

        TimetableSlot::updateOrCreate(
            [
                'class_room_id' => $this->selectedClassId,
                'academic_term_id' => $this->selectedTermId,
                'day' => $dayOfWeek,
                'period' => $period,
            ],
            [
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'date' => $dateKey,
                'type' => 'regular',
                'status' => 'draft',
            ]
        );

        if (! empty($validation['warnings'])) {
            $this->validationWarnings = $validation['warnings'];
            $this->showValidationModal = true;
        }

        $this->loadTimetableSlots();
        session()->flash('message', 'Period assigned successfully!');
    }

    public function toggleStatus($date, $period): void
    {
        $dateKey = Carbon::parse($date)->format('Y-m-d');
        $key = "{$dateKey}_{$period}";

        if (! isset($this->timetableSlots[$key])) {
            return;
        }

        $slot = $this->timetableSlots[$key];
        $newStatus = $slot->status === 'draft' ? 'published' : 'draft';

        TimetableSlot::where('id', $slot->id)->update(['status' => $newStatus]);

        $this->loadTimetableSlots();
        session()->flash('message', "Status changed to {$newStatus}!");
    }

    public function editSlot($date, $period): void
    {
        $dateKey = Carbon::parse($date)->format('Y-m-d');
        $key = "{$dateKey}_{$period}";
        $this->editingSlot = $key;
        $this->slotDay = Carbon::parse($date)->dayOfWeek;
        $this->slotPeriod = $period;

        if (isset($this->timetableSlots[$key])) {
            $slot = $this->timetableSlots[$key];
            $this->slotSubjectId = $slot->subject_id;
            $this->slotTeacherId = $slot->teacher_id;
            $this->slotStatus = $slot->status ?? 'draft';
        } else {
            $this->slotSubjectId = null;
            $this->slotTeacherId = null;
            $this->slotStatus = 'draft';
        }
    }

    public function saveSlot(): void
    {
        $this->validate();

        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return;
        }

        // Extract date from editing slot key
        $parts = explode('_', $this->editingSlot);
        $dateKey = $parts[0];

        TimetableSlot::updateOrCreate(
            [
                'class_room_id' => $this->selectedClassId,
                'academic_term_id' => $this->selectedTermId,
                'day' => $this->slotDay,
                'period' => $this->slotPeriod,
            ],
            [
                'subject_id' => $this->slotSubjectId,
                'teacher_id' => $this->slotTeacherId,
                'date' => $dateKey,
                'type' => 'regular',
                'status' => $this->slotStatus,
            ]
        );

        $this->loadTimetableSlots();
        $this->cancelEdit();

        session()->flash('message', 'Timetable slot saved successfully!');
    }

    public function deleteSlot($date, $period): void
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('day', $dayOfWeek)
            ->where('period', $period)
            ->delete();

        $this->loadTimetableSlots();

        session()->flash('message', 'Timetable slot deleted successfully!');
    }

    public function cancelEdit(): void
    {
        $this->editingSlot = null;
        $this->slotDay = null;
        $this->slotPeriod = null;
        $this->slotSubjectId = null;
        $this->slotTeacherId = null;
        $this->slotStatus = 'draft';
        $this->resetErrorBag();
    }

    public function closeValidationModal(): void
    {
        $this->showValidationModal = false;
        $this->validationErrors = [];
        $this->validationWarnings = [];
    }

    public function toggleEditMode(): void
    {
        $this->editMode = ! $this->editMode;
    }

    public function removeSlot($date, $period): void
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('day', $dayOfWeek)
            ->where('period', $period)
            ->delete();

        $this->loadTimetableSlots();

        session()->flash('message', 'Subject removed from timetable successfully!');
    }

    public function getSubjectWorkload(): array
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return [];
        }

        $workload = [];
        foreach ($this->subjects as $subject) {
            $count = TimetableSlot::query()
                ->where('class_room_id', $this->selectedClassId)
                ->where('academic_term_id', $this->selectedTermId)
                ->where('subject_id', $subject->id)
                ->count();

            $workload[$subject->id] = $count;
        }

        return $workload;
    }

    public function render()
    {
        $subjectWorkload = $this->getSubjectWorkload();

        return view('livewire.timetable-designer', [
            'subjectWorkload' => $subjectWorkload,
        ]);
    }
}
