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

    public $unsavedChanges = [];

    protected $rules = [
        'slotSubjectId' => 'required|exists:subjects,id',
        'slotTeacherId' => 'required|exists:teachers,id',
        'slotStatus' => 'required|in:draft,published',
    ];

    public function mount(): void
    {
        $this->initializeClasses();
        $this->initializeAcademicTerms();
        $this->initializeTeachers();
        $this->setDefaultSelections();
        $this->initializeWeek();
        $this->loadSubjects();
        $this->loadTimetableSlots();
    }

    protected function initializeClasses(): void
    {
        $this->classes = ClassRoom::query()
            ->active()
            ->get()
            ->sortBy(fn ($class) => $class->name.$class->section, SORT_NATURAL | SORT_FLAG_CASE);
    }

    protected function initializeAcademicTerms(): void
    {
        $this->academicTerms = AcademicTerm::query()
            ->orderBy('year', 'desc')
            ->orderBy('term', 'desc')
            ->get();
    }

    protected function initializeTeachers(): void
    {
        $this->teachers = Teacher::query()
            ->where('status', 'active')
            ->with(['subjects' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    protected function setDefaultSelections(): void
    {
        $this->selectedTermId = AcademicTerm::query()->active()->first()?->id;
        $this->selectedClassId = $this->classes->first()?->id;
    }

    protected function initializeWeek(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek();
        $this->calculateWeekDates();
    }

    public function loadSubjects(): void
    {
        if (! $this->selectedClassId) {
            $this->subjects = collect();

            return;
        }

        $subjectIds = $this->getClassSubjectIds();
        $subjects = $this->fetchSubjectsWithTeachers($subjectIds);
        $teacherAssignments = $this->getTeacherAssignments($subjectIds);

        $this->assignTeachersToSubjects($subjects, $teacherAssignments);
        $this->subjects = $subjects->groupBy('name')->map(fn ($group) => $group->first());
    }

    protected function getClassSubjectIds(): array
    {
        return \App\Models\ClassSubjectSetting::query()
            ->where('class_room_id', $this->selectedClassId)
            ->pluck('subject_id')
            ->unique()
            ->toArray();
    }

    protected function fetchSubjectsWithTeachers(array $subjectIds)
    {
        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->with(['teachers' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    protected function getTeacherAssignments(array $subjectIds)
    {
        return TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->whereIn('subject_id', $subjectIds)
            ->select('subject_id', 'teacher_id')
            ->distinct()
            ->get()
            ->groupBy('subject_id');
    }

    protected function assignTeachersToSubjects($subjects, $teacherAssignments): void
    {
        foreach ($subjects as $subject) {
            if (isset($teacherAssignments[$subject->id])) {
                $this->setTeachersFromSlots($subject, $teacherAssignments[$subject->id]);
            } elseif ($subject->teachers->isEmpty()) {
                $this->setFallbackTeachers($subject);
            }
        }
    }

    protected function setTeachersFromSlots($subject, $assignments): void
    {
        $teacherIds = $assignments->pluck('teacher_id')->unique();
        $teachers = Teacher::query()->whereIn('id', $teacherIds)->get();
        $subject->setRelation('teachers', $teachers);
    }

    protected function setFallbackTeachers($subject): void
    {
        $teachers = Teacher::query()
            ->where('status', 'active')
            ->whereJsonContains('subject_ids', $subject->id)
            ->orderBy('name')
            ->get();

        if ($teachers->isNotEmpty()) {
            $subject->setRelation('teachers', $teachers);
        }
    }

    public function calculateWeekDates(): void
    {
        $this->weekDates = [];
        $sunday = Carbon::parse($this->weekStart)->startOfWeek(Carbon::SUNDAY);

        for ($i = 0; $i < 7; $i++) {
            $date = $sunday->copy()->addDays($i);
            $this->weekDates[] = $this->formatWeekDate($date);
        }
    }

    protected function formatWeekDate(Carbon $date): array
    {
        return [
            'date' => $date,
            'dayName' => $date->format('D'),
            'dayNum' => $date->day,
            'monthShort' => $date->format('M'),
            'dayOfWeek' => $date->dayOfWeek,
        ];
    }

    public function updatedSelectedClassId(): void
    {
        $this->unsavedChanges = [];
        $this->loadSubjects();
        $this->loadTimetableSlots();
    }

    public function updatedSelectedTermId(): void
    {
        $this->unsavedChanges = [];
        $this->loadTimetableSlots();
    }

    public function loadTimetableSlots(): void
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            $this->timetableSlots = [];

            return;
        }

        $slots = $this->fetchTimetableSlots();
        $this->timetableSlots = $this->mapSlotsToWeek($slots);
        $this->mergeTimetableSlots();
    }

    protected function fetchTimetableSlots()
    {
        return TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->with(['subject', 'teacher'])
            ->get();
    }

    protected function mapSlotsToWeek($slots): array
    {
        $mappedSlots = [];

        foreach ($slots as $slot) {
            foreach ($this->weekDates as $weekDate) {
                if ($weekDate['dayOfWeek'] == $slot->day) {
                    $dateKey = $weekDate['date']->format('Y-m-d');
                    $key = "{$dateKey}_{$slot->period}";
                    $mappedSlots[$key] = $slot;
                }
            }
        }

        return $mappedSlots;
    }

    public function mergeTimetableSlots(): void
    {
        foreach ($this->unsavedChanges as $key => $change) {
            $this->timetableSlots[$key] = $this->createMockSlot($change);
        }
    }

    protected function createMockSlot(array $change): TimetableSlot
    {
        $mockSlot = new TimetableSlot($change);
        $mockSlot->subject = Subject::find($change['subject_id']);
        $mockSlot->teacher = Teacher::find($change['teacher_id']);
        $mockSlot->is_unsaved = true;

        return $mockSlot;
    }

    public function assignPeriod($subjectId, $teacherId, $date, $period): void
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return;
        }

        if (! $subjectId || ! $teacherId) {
            $this->showError('Invalid Assignment', 'Please select both a subject and a teacher before assigning to the timetable.');

            return;
        }

        $subject = Subject::find($subjectId);

        if (! $subject) {
            $this->showError('Subject Not Found', 'The selected subject could not be found. Please refresh and try again.');

            return;
        }

        if ($this->isMaxPeriodsExceeded($subject, $subjectId)) {
            $this->showError('Max Periods Exceeded', "Cannot assign {$subject->name}. Maximum periods per week ({$subject->max_periods_per_week}) already reached.");

            return;
        }

        $this->processAssignment($subjectId, $teacherId, $date, $period, $subject);
    }

    protected function showError(string $type, string $message): void
    {
        $this->validationErrors = [['type' => $type, 'message' => $message]];
        $this->showValidationModal = true;
    }

    protected function isMaxPeriodsExceeded(Subject $subject, int $subjectId): bool
    {
        if (! $subject->max_periods_per_week) {
            return false;
        }

        $currentCount = TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('subject_id', $subjectId)
            ->count();

        return $currentCount >= $subject->max_periods_per_week;
    }

    protected function processAssignment(int $subjectId, int $teacherId, $date, int $period, Subject $subject): void
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $dateKey = Carbon::parse($date)->format('Y-m-d');

        $validation = $this->validateAssignment($subjectId, $teacherId, $dayOfWeek, $period);

        if (! empty($validation['errors'])) {
            $this->validationErrors = $validation['errors'];
            $this->validationWarnings = $validation['warnings'] ?? [];
            $this->showValidationModal = true;

            return;
        }

        $this->createUnsavedChange($dateKey, $period, $subjectId, $teacherId, $dayOfWeek, $subject);

        if (! empty($validation['warnings'])) {
            $this->validationWarnings = $validation['warnings'];
            $this->showValidationModal = true;
        }

        $this->mergeTimetableSlots();
        session()->flash('message', 'Period assigned (unsaved)!');
    }

    protected function validateAssignment(int $subjectId, int $teacherId, int $dayOfWeek, int $period): array
    {
        $validationService = new TimetableValidationService;

        return $validationService->validateSlotAssignment(
            $this->selectedClassId,
            $this->selectedTermId,
            $subjectId,
            $teacherId,
            $dayOfWeek,
            $period
        );
    }

    protected function createUnsavedChange(string $dateKey, int $period, int $subjectId, int $teacherId, int $dayOfWeek, Subject $subject): void
    {
        $key = "{$dateKey}_{$period}";
        $this->unsavedChanges[$key] = [
            'class_room_id' => $this->selectedClassId,
            'academic_term_id' => $this->selectedTermId,
            'day' => $dayOfWeek,
            'period' => $period,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'date' => $dateKey,
            'type' => 'regular',
            'status' => 'draft',
            'subject_name' => $subject->name,
            'teacher_name' => Teacher::find($teacherId)?->name,
        ];
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

        $this->loadSlotData($key);
    }

    protected function loadSlotData(string $key): void
    {
        if (isset($this->timetableSlots[$key])) {
            $slot = $this->timetableSlots[$key];
            $this->slotSubjectId = $slot->subject_id;
            $this->slotTeacherId = $slot->teacher_id;
            $this->slotStatus = $slot->status ?? 'draft';
        } else {
            $this->resetSlotData();
        }
    }

    protected function resetSlotData(): void
    {
        $this->slotSubjectId = null;
        $this->slotTeacherId = null;
        $this->slotStatus = 'draft';
    }

    public function saveSlot(): void
    {
        $this->validate();

        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return;
        }

        $dateKey = $this->extractDateFromSlotKey();

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

    protected function extractDateFromSlotKey(): string
    {
        $parts = explode('_', $this->editingSlot);

        return $parts[0];
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
        $this->resetSlotData();
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

    public function saveAllSlots(): void
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            $this->showError('Save Error', 'Please select a class and term before saving.');

            return;
        }

        if (empty($this->unsavedChanges)) {
            session()->flash('message', 'No unsaved changes to save.');

            return;
        }

        $savedCount = $this->persistUnsavedChanges();
        $this->unsavedChanges = [];
        $this->loadTimetableSlots();

        session()->flash('message', "Successfully saved {$savedCount} change(s)!");
    }

    protected function persistUnsavedChanges(): int
    {
        $savedCount = 0;

        foreach ($this->unsavedChanges as $change) {
            if (isset($change['deleted']) && $change['deleted']) {
                $this->deleteSlotByChange($change);
            } else {
                $this->upsertSlotByChange($change);
            }
            $savedCount++;
        }

        return $savedCount;
    }

    protected function deleteSlotByChange(array $change): void
    {
        TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('day', $change['day'])
            ->where('period', $change['period'])
            ->delete();
    }

    protected function upsertSlotByChange(array $change): void
    {
        TimetableSlot::updateOrCreate(
            [
                'class_room_id' => $change['class_room_id'],
                'academic_term_id' => $change['academic_term_id'],
                'day' => $change['day'],
                'period' => $change['period'],
            ],
            [
                'subject_id' => $change['subject_id'],
                'teacher_id' => $change['teacher_id'],
                'date' => $change['date'],
                'type' => $change['type'],
                'status' => $change['status'],
            ]
        );
    }

    public function removeSlot($date, $period): void
    {
        $dateKey = Carbon::parse($date)->format('Y-m-d');
        $key = "{$dateKey}_{$period}";

        if (isset($this->unsavedChanges[$key])) {
            unset($this->unsavedChanges[$key]);
        } else {
            $this->markSlotForDeletion($dateKey, $key, $date, $period);
        }

        $this->loadTimetableSlots();
        session()->flash('message', 'Subject removed (unsaved)!');
    }

    protected function markSlotForDeletion(string $dateKey, string $key, $date, int $period): void
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $this->unsavedChanges[$key] = [
            'deleted' => true,
            'day' => $dayOfWeek,
            'period' => $period,
        ];
    }

    public function getSubjectWorkload(): array
    {
        if (! $this->selectedClassId || ! $this->selectedTermId) {
            return [];
        }

        $workload = [];

        foreach ($this->subjects as $subject) {
            $dbCount = $this->getSubjectDbCount($subject->id);
            $unsavedCount = $this->getSubjectUnsavedCount($subject->id);
            $workload[$subject->id] = $dbCount + $unsavedCount;
        }

        return $workload;
    }

    protected function getSubjectDbCount(int $subjectId): int
    {
        return TimetableSlot::query()
            ->where('class_room_id', $this->selectedClassId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('subject_id', $subjectId)
            ->count();
    }

    protected function getSubjectUnsavedCount(int $subjectId): int
    {
        return collect($this->unsavedChanges)
            ->filter(fn ($change) => isset($change['subject_id'])
                && $change['subject_id'] == $subjectId
                && ! isset($change['deleted']))
            ->count();
    }

    public function render()
    {
        return view('livewire.timetable-designer', [
            'subjectWorkload' => $this->getSubjectWorkload(),
        ]);
    }
}
