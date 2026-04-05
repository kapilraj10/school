<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Services\GeneticAlgorithmTimetableService;
use App\Services\TimetableValidationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimetableDesigner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.timetable-designer';

    protected static ?string $navigationLabel = 'Timetable Designer';

    protected static ?string $title = 'Timetable Designer';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 1;

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, $tenant = null): string
    {
        return route('timetable-designer');
    }

    // Page properties
    public ?int $selectedClassRoomId = null;

    public ?int $selectedTermId = null;

    public array $timetableSlots = [];

    public Collection $subjects;

    public Collection $teachers;

    public array $validationErrors = [];

    public array $validationWarnings = [];

    public bool $isLoading = false;

    public array $subjectPlacements = [];

    public array $periodTimes = [];

    public array $constraintStatus = [];

    /** @var array<int, array{weekly_periods: int, min_periods_per_week: int, max_periods_per_week: int}> */
    public array $classSubjectSettingsMap = [];

    public array $days = [];

    public int $periodsPerDay = 8;

    /**
     * Initialize the page
     */
    public function mount(): void
    {
        // Set default term to active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $this->selectedTermId = $activeTerm?->id;

        // Set default class room to first available
        $firstClass = ClassRoom::where('status', 'active')->first();
        $this->selectedClassRoomId = $firstClass?->id;

        // Load initial data with relationships
        $this->subjects = Subject::where('status', 'active')
            ->with('teachers')
            ->get();
        $this->teachers = Teacher::where('status', 'active')
            ->with('subjects')
            ->get();

        // Load period times from settings
        $this->loadPeriodTimes();

        // Load dynamic day/period settings
        $this->days = TimetableSlot::getDays();
        $this->periodsPerDay = TimetableSlot::getPeriodsPerDay();

        // Load timetable if selections are made
        if ($this->selectedClassRoomId && $this->selectedTermId) {
            $this->loadTimetable();
        }
    }

    /**
     * Form schema for class and term selection
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedTermId')
                    ->options(AcademicTerm::pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable()),

                Select::make('selectedClassRoomId')
                    ->options(ClassRoom::where('status', 'active')
                        ->get()
                        ->mapWithKeys(fn ($class) => [$class->id => "{$class->name} - {$class->section}"]))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadTimetable()),
            ])
            ->columns(2);
    }

    /**
     * Load timetable slots for selected class and term
     */
    public function loadTimetable(): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            $this->timetableSlots = [];

            return;
        }

        $this->isLoading = true;

        // Initialize empty grid structure
        $this->timetableSlots = [];
        foreach (array_keys($this->days) as $day) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $this->timetableSlots[$day][$period] = null;
            }
        }

        // Load existing slots from database
        $slots = TimetableSlot::where('class_room_id', $this->selectedClassRoomId)
            ->where('academic_term_id', $this->selectedTermId)
            ->with(['subject', 'teacher', 'originalSubject', 'originalTeacher'])
            ->get();

        foreach ($slots as $slot) {
            $this->timetableSlots[$slot->day][$slot->period] = [
                'id' => $slot->id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
                'subject_name' => $slot->subject?->name,
                'subject_code' => $slot->subject?->code,
                'teacher_name' => $slot->teacher?->name,
                'original_subject_id' => $slot->original_subject_id,
                'original_teacher_id' => $slot->original_teacher_id,
                'original_subject_name' => $slot->originalSubject?->name,
                'original_teacher_name' => $slot->originalTeacher?->name,
                'is_locked' => $slot->is_locked,
                'is_combined' => $slot->is_combined,
                'is_temporary' => $slot->is_temporary,
                'type' => $slot->type,
                'temporary_type' => $slot->temporary_type,
                'temporary_reason' => $slot->temporary_reason,
                'temporary_effective_from' => $slot->temporary_effective_from?->toDateString(),
                'temporary_effective_until' => $slot->temporary_effective_until?->toDateString(),
            ];
        }

        $this->isLoading = false;

        // Load class subject settings for the selected class
        $this->loadClassSubjectSettings();

        // Calculate subject placements
        $this->calculateSubjectPlacements();

        // Calculate constraint status
        $this->calculateConstraintStatus();

        // Validate after loading
        $this->validateTimetable();
    }

    /**
     * Assign a subject and teacher to a time slot
     */
    public function assignSlot(int $day, int $period, int $subjectId, ?int $teacherId = null): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            Notification::make()
                ->warning()
                ->title('Please select a class and term first')
                ->send();

            return;
        }

        // Check if slot is locked
        if (isset($this->timetableSlots[$day][$period]['is_locked']) && $this->timetableSlots[$day][$period]['is_locked']) {
            Notification::make()
                ->warning()
                ->title('This slot is locked')
                ->send();

            return;
        }

        $validationService = app(TimetableValidationService::class);
        $validationResult = $validationService->validateSlotAssignment(
            $this->selectedClassRoomId,
            $this->selectedTermId,
            $subjectId,
            $teacherId,
            $day,
            $period,
        );

        $this->validationErrors = $validationResult['errors'] ?? [];
        $this->validationWarnings = $validationResult['warnings'] ?? [];

        if (! empty($this->validationErrors)) {
            $errorMessage = collect($this->validationErrors)
                ->pluck('message')
                ->filter()
                ->take(2)
                ->implode(' ');

            Notification::make()
                ->danger()
                ->title('Cannot assign this slot')
                ->body($errorMessage !== '' ? $errorMessage : 'Assignment violates timetable constraints.')
                ->send();

            return;
        }

        try {
            // Create or update the slot
            $slot = TimetableSlot::updateOrCreate(
                [
                    'class_room_id' => $this->selectedClassRoomId,
                    'academic_term_id' => $this->selectedTermId,
                    'day' => $day,
                    'period' => $period,
                ],
                [
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacherId,
                    'type' => 'regular',
                    'is_temporary' => false,
                    'original_subject_id' => null,
                    'original_teacher_id' => null,
                    'temporary_type' => null,
                    'temporary_reason' => null,
                    'temporary_effective_from' => null,
                    'temporary_effective_until' => null,
                ]
            );
        } catch (QueryException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot assign this slot')
                ->body('Teacher is already assigned to another class at this time.')
                ->send();

            return;
        }

        // Update local state
        $subject = Subject::find($subjectId);
        $teacher = $teacherId ? Teacher::find($teacherId) : null;

        $this->timetableSlots[$day][$period] = [
            'id' => $slot->id,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'subject_name' => $subject?->name,
            'subject_code' => $subject?->code,
            'teacher_name' => $teacher?->name,
            'is_locked' => false,
            'is_combined' => false,
            'is_temporary' => false,
            'type' => 'regular',
            'temporary_type' => null,
            'temporary_reason' => null,
            'temporary_effective_from' => null,
            'temporary_effective_until' => null,
            'original_subject_id' => null,
            'original_teacher_id' => null,
            'original_subject_name' => null,
            'original_teacher_name' => null,
        ];

        // Recalculate placements and constraint status
        $this->calculateSubjectPlacements();
        $this->calculateConstraintStatus();

        // Validate after assignment
        $this->validateTimetable();

        Notification::make()
            ->success()
            ->title('Slot assigned successfully')
            ->send();

        if (! empty($this->validationWarnings)) {
            Notification::make()
                ->warning()
                ->title('Assignment saved with warnings')
                ->body(collect($this->validationWarnings)
                    ->pluck('message')
                    ->filter()
                    ->take(2)
                    ->implode(' '))
                ->send();
        }
    }

    /**
     * Remove a slot
     */
    public function removeSlot(int $day, int $period): void
    {
        if (! isset($this->timetableSlots[$day][$period]) || ! $this->timetableSlots[$day][$period]) {
            return;
        }

        // Check if slot is locked
        if ($this->timetableSlots[$day][$period]['is_locked']) {
            Notification::make()
                ->warning()
                ->title('Cannot remove locked slot')
                ->send();

            return;
        }

        $slotId = $this->timetableSlots[$day][$period]['id'];

        // Delete from database
        TimetableSlot::where('id', $slotId)->delete();

        // Update local state
        $this->timetableSlots[$day][$period] = null;

        // Recalculate placements and constraint status
        $this->calculateSubjectPlacements();
        $this->calculateConstraintStatus();

        // Validate after removal
        $this->validateTimetable();

        Notification::make()
            ->success()
            ->title('Slot removed successfully')
            ->send();
    }

    /**
     * Assign a substitute teacher for an existing slot.
     */
    public function applySubstituteTeacher(int $day, int $period, int $substituteTeacherId, ?string $reason = null, ?string $effectiveUntil = null): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            Notification::make()
                ->warning()
                ->title('Please select a class and term first')
                ->send();

            return;
        }

        $slotState = $this->timetableSlots[$day][$period] ?? null;

        if (! $slotState) {
            Notification::make()
                ->warning()
                ->title('Cannot substitute an empty slot')
                ->send();

            return;
        }

        if (($slotState['is_locked'] ?? false) === true) {
            Notification::make()
                ->warning()
                ->title('Cannot update a locked slot')
                ->send();

            return;
        }

        $slot = TimetableSlot::query()->find($slotState['id']);

        if (! $slot) {
            Notification::make()
                ->danger()
                ->title('Slot not found')
                ->send();

            return;
        }

        $originalTeacherId = $slot->teacher_id;

        if ($originalTeacherId === $substituteTeacherId) {
            Notification::make()
                ->warning()
                ->title('Substitute teacher is already assigned to this slot')
                ->send();

            return;
        }

        $validationService = app(TimetableValidationService::class);
        $validationResult = $validationService->validateSlotAssignment(
            $this->selectedClassRoomId,
            $this->selectedTermId,
            (int) $slot->subject_id,
            $substituteTeacherId,
            $day,
            $period,
        );

        $this->validationErrors = $validationResult['errors'] ?? [];
        $this->validationWarnings = $validationResult['warnings'] ?? [];

        if (! empty($this->validationErrors)) {
            Notification::make()
                ->danger()
                ->title('Cannot assign substitute teacher')
                ->body(collect($this->validationErrors)->pluck('message')->filter()->take(2)->implode(' '))
                ->send();

            return;
        }

        try {
            $slot->update([
                'teacher_id' => $substituteTeacherId,
                'is_temporary' => true,
                'temporary_type' => 'substitute_teacher',
                'temporary_reason' => $reason,
                'temporary_effective_from' => Carbon::today()->toDateString(),
                'temporary_effective_until' => $effectiveUntil,
                'original_teacher_id' => $slot->original_teacher_id ?? $originalTeacherId,
            ]);
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Cannot assign substitute teacher')
                ->body('Teacher is already assigned to another class at this time.')
                ->send();

            return;
        }

        $this->loadTimetable();

        Notification::make()
            ->success()
            ->title('Substitute teacher assigned successfully')
            ->send();
    }

    /**
     * Apply a temporary subject/teacher change for an existing slot.
     */
    public function applyTemporaryScheduleChange(
        int $day,
        int $period,
        int $subjectId,
        ?int $teacherId = null,
        ?string $reason = null,
        ?string $effectiveUntil = null
    ): void {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            Notification::make()
                ->warning()
                ->title('Please select a class and term first')
                ->send();

            return;
        }

        $slotState = $this->timetableSlots[$day][$period] ?? null;

        if (! $slotState) {
            Notification::make()
                ->warning()
                ->title('Cannot apply temporary change to an empty slot')
                ->send();

            return;
        }

        if (($slotState['is_locked'] ?? false) === true) {
            Notification::make()
                ->warning()
                ->title('Cannot update a locked slot')
                ->send();

            return;
        }

        $slot = TimetableSlot::query()->find($slotState['id']);

        if (! $slot) {
            Notification::make()
                ->danger()
                ->title('Slot not found')
                ->send();

            return;
        }

        $validationService = app(TimetableValidationService::class);
        $validationResult = $validationService->validateSlotAssignment(
            $this->selectedClassRoomId,
            $this->selectedTermId,
            $subjectId,
            $teacherId,
            $day,
            $period,
        );

        $this->validationErrors = $validationResult['errors'] ?? [];
        $this->validationWarnings = $validationResult['warnings'] ?? [];

        if (! empty($this->validationErrors)) {
            Notification::make()
                ->danger()
                ->title('Cannot apply temporary change')
                ->body(collect($this->validationErrors)->pluck('message')->filter()->take(2)->implode(' '))
                ->send();

            return;
        }

        try {
            $slot->update([
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'is_temporary' => true,
                'temporary_type' => 'schedule_adjustment',
                'temporary_reason' => $reason,
                'temporary_effective_from' => Carbon::today()->toDateString(),
                'temporary_effective_until' => $effectiveUntil,
                'original_subject_id' => $slot->original_subject_id ?? $slotState['subject_id'],
                'original_teacher_id' => $slot->original_teacher_id ?? $slotState['teacher_id'],
            ]);
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Cannot apply temporary change')
                ->body('Teacher is already assigned to another class at this time.')
                ->send();

            return;
        }

        $this->loadTimetable();

        Notification::make()
            ->success()
            ->title('Temporary schedule change applied')
            ->send();
    }

    /**
     * Revert a temporary substitution or temporary schedule change.
     */
    public function revertTemporaryChange(int $day, int $period): void
    {
        $slotState = $this->timetableSlots[$day][$period] ?? null;

        if (! $slotState) {
            return;
        }

        if (($slotState['is_locked'] ?? false) === true) {
            Notification::make()
                ->warning()
                ->title('Cannot update a locked slot')
                ->send();

            return;
        }

        if (($slotState['is_temporary'] ?? false) !== true) {
            Notification::make()
                ->warning()
                ->title('This slot has no temporary change to revert')
                ->send();

            return;
        }

        $slot = TimetableSlot::query()->find($slotState['id']);

        if (! $slot) {
            Notification::make()
                ->danger()
                ->title('Slot not found')
                ->send();

            return;
        }

        $restoreSubjectId = $slot->original_subject_id ?? $slot->subject_id;
        $restoreTeacherId = $slot->original_teacher_id ?? $slot->teacher_id;

        $validationService = app(TimetableValidationService::class);
        $validationResult = $validationService->validateSlotAssignment(
            (int) $slot->class_room_id,
            (int) $slot->academic_term_id,
            (int) $restoreSubjectId,
            $restoreTeacherId,
            $day,
            $period,
        );

        $this->validationErrors = $validationResult['errors'] ?? [];
        $this->validationWarnings = $validationResult['warnings'] ?? [];

        if (! empty($this->validationErrors)) {
            Notification::make()
                ->danger()
                ->title('Cannot revert temporary change')
                ->body(collect($this->validationErrors)->pluck('message')->filter()->take(2)->implode(' '))
                ->send();

            return;
        }

        $slot->update([
            'subject_id' => $restoreSubjectId,
            'teacher_id' => $restoreTeacherId,
            'is_temporary' => false,
            'temporary_type' => null,
            'temporary_reason' => null,
            'temporary_effective_from' => null,
            'temporary_effective_until' => null,
            'original_subject_id' => null,
            'original_teacher_id' => null,
        ]);

        $this->loadTimetable();

        Notification::make()
            ->success()
            ->title('Temporary change reverted')
            ->send();
    }

    /**
     * Swap two slots
     */
    public function swapSlots(int $day1, int $period1, int $day2, int $period2): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            return;
        }

        $slot1 = $this->timetableSlots[$day1][$period1] ?? null;
        $slot2 = $this->timetableSlots[$day2][$period2] ?? null;

        // Check if either slot is locked
        if (($slot1 && $slot1['is_locked']) || ($slot2 && $slot2['is_locked'])) {
            Notification::make()
                ->warning()
                ->title('Cannot swap locked slots')
                ->send();

            return;
        }

        // Swap in database using temporary position to avoid unique constraint
        \DB::transaction(function () use ($slot1, $slot2, $day1, $period1, $day2, $period2) {
            if ($slot1) {
                // Move slot1 to temporary position
                TimetableSlot::where('id', $slot1['id'])->update([
                    'day' => 999,
                    'period' => 999,
                ]);
            }

            if ($slot2) {
                // Move slot2 to slot1's position
                TimetableSlot::where('id', $slot2['id'])->update([
                    'day' => $day1,
                    'period' => $period1,
                ]);
            }

            if ($slot1) {
                // Move slot1 from temporary to slot2's position
                TimetableSlot::where('id', $slot1['id'])->update([
                    'day' => $day2,
                    'period' => $period2,
                ]);
            }
        });

        // Swap in local state
        $this->timetableSlots[$day1][$period1] = $slot2;
        $this->timetableSlots[$day2][$period2] = $slot1;

        // Recalculate placements and constraint status
        $this->calculateSubjectPlacements();
        $this->calculateConstraintStatus();

        // Validate after swap
        $this->validateTimetable();

        Notification::make()
            ->success()
            ->title('Slots swapped successfully')
            ->send();
    }

    /**
     * Toggle lock status of a slot
     */
    public function toggleLockSlot(int $day, int $period): void
    {
        if (! isset($this->timetableSlots[$day][$period]) || ! $this->timetableSlots[$day][$period]) {
            return;
        }

        $slotId = $this->timetableSlots[$day][$period]['id'];
        $currentLockStatus = $this->timetableSlots[$day][$period]['is_locked'];

        // Toggle in database
        TimetableSlot::where('id', $slotId)->update([
            'is_locked' => ! $currentLockStatus,
        ]);

        // Toggle in local state
        $this->timetableSlots[$day][$period]['is_locked'] = ! $currentLockStatus;

        Notification::make()
            ->success()
            ->title($currentLockStatus ? 'Slot unlocked' : 'Slot locked')
            ->send();
    }

    /**
     * Validate the entire timetable for constraint violations
     */
    public function validateTimetable(): void
    {
        $this->validationErrors = [];
        $this->validationWarnings = [];

        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            return;
        }

        // Use the comprehensive validation service
        $validator = app(TimetableValidationService::class);
        $result = $validator->validate(
            $this->timetableSlots,
            $this->selectedClassRoomId,
            $this->selectedTermId
        );

        $this->validationErrors = $result['errors'];
        $this->validationWarnings = $result['warnings'];
    }

    /**
     * Save the current timetable state
     */
    public function saveTimetable(): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            Notification::make()
                ->warning()
                ->title('Please select a class and term first')
                ->send();

            return;
        }

        // Validate before saving
        $this->validateTimetable();

        if (count($this->validationErrors) > 0) {
            Notification::make()
                ->danger()
                ->title('Cannot save: Hard constraint violations detected')
                ->body(sprintf('%d constraint error(s) must be fixed before saving', count($this->validationErrors)))
                ->persistent()
                ->send();

            return;
        }

        // Show warnings but allow saving
        if (count($this->validationWarnings) > 0) {
            Notification::make()
                ->warning()
                ->title('Timetable saved with warnings')
                ->body(sprintf('%d optimization suggestion(s) detected. Review warnings for improvements.', count($this->validationWarnings)))
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Timetable saved successfully')
                ->body('All constraints satisfied!')
                ->send();
        }
    }

    /**
     * Reset the timetable (clear all slots)
     */
    public function resetTimetable(): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            return;
        }

        // Delete all non-locked slots
        TimetableSlot::where('class_room_id', $this->selectedClassRoomId)
            ->where('academic_term_id', $this->selectedTermId)
            ->where('is_locked', false)
            ->delete();

        // Reload timetable
        $this->loadTimetable();

        Notification::make()
            ->success()
            ->title('Timetable reset successfully')
            ->send();
    }

    /**
     * Auto-generate timetable using the service
     */
    public function autoGenerate(): void
    {
        if (! $this->selectedClassRoomId || ! $this->selectedTermId) {
            Notification::make()
                ->warning()
                ->title('Please select a class and term first')
                ->send();

            return;
        }

        $this->isLoading = true;

        try {
            $generator = app(GeneticAlgorithmTimetableService::class);
            $classRoom = ClassRoom::find($this->selectedClassRoomId);
            $academicTerm = AcademicTerm::find($this->selectedTermId);

            $result = $generator->generateTimetable(
                $classRoom,
                $academicTerm,
                20,
                150
            );

            if ($result['success']) {
                $this->loadTimetable();

                Notification::make()
                    ->success()
                    ->title('Timetable generated successfully')
                    ->body(sprintf(
                        'Generated %d slots with fitness score: %.2f',
                        $result['slots'] ?? 0,
                        $result['fitness'] ?? 0
                    ))
                    ->send();

                if (! empty($result['warnings'])) {
                    foreach (array_slice($result['warnings'], 0, 2) as $warning) {
                        Notification::make()
                            ->warning()
                            ->title('Warning')
                            ->body($warning)
                            ->send();
                    }
                }
            } else {
                Notification::make()
                    ->danger()
                    ->title('Generation failed')
                    ->body($result['message'] ?? 'Unknown error')
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Generation error')
                ->body($e->getMessage())
                ->send();
        }

        $this->isLoading = false;
    }

    /**
     * Page actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Timetable')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('saveTimetable'),

            Action::make('reset')
                ->label('Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action('resetTimetable'),

            Action::make('autoGenerate')
                ->label('Auto Generate')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->action('autoGenerate'),

            Action::make('substituteTeacher')
                ->label('Substitute Teacher')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->form([
                    Select::make('day')
                        ->label('Day')
                        ->options($this->days)
                        ->required(),
                    Select::make('period')
                        ->label('Period')
                        ->options($this->getPeriodOptions())
                        ->required(),
                    Select::make('substitute_teacher_id')
                        ->label('Substitute Teacher')
                        ->options(
                            Teacher::query()
                                ->where('status', 'active')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required(),
                    DatePicker::make('temporary_effective_until')
                        ->label('Effective Until')
                        ->native(false),
                    Textarea::make('temporary_reason')
                        ->label('Reason')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->applySubstituteTeacher(
                        (int) $data['day'],
                        (int) $data['period'],
                        (int) $data['substitute_teacher_id'],
                        $data['temporary_reason'] ?? null,
                        $data['temporary_effective_until'] ?? null,
                    );
                }),

            Action::make('temporaryScheduleChange')
                ->label('Temporary Change')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('gray')
                ->form([
                    Select::make('day')
                        ->label('Day')
                        ->options($this->days)
                        ->required(),
                    Select::make('period')
                        ->label('Period')
                        ->options($this->getPeriodOptions())
                        ->required(),
                    Select::make('subject_id')
                        ->label('Temporary Subject')
                        ->options(
                            Subject::query()
                                ->where('status', 'active')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required(),
                    Select::make('teacher_id')
                        ->label('Temporary Teacher')
                        ->options(
                            Teacher::query()
                                ->where('status', 'active')
                                ->pluck('name', 'id')
                        )
                        ->searchable(),
                    DatePicker::make('temporary_effective_until')
                        ->label('Effective Until')
                        ->native(false),
                    Textarea::make('temporary_reason')
                        ->label('Reason')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->applyTemporaryScheduleChange(
                        (int) $data['day'],
                        (int) $data['period'],
                        (int) $data['subject_id'],
                        isset($data['teacher_id']) ? (int) $data['teacher_id'] : null,
                        $data['temporary_reason'] ?? null,
                        $data['temporary_effective_until'] ?? null,
                    );
                }),

            Action::make('revertTemporaryChange')
                ->label('Revert Temporary')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->form([
                    Select::make('day')
                        ->label('Day')
                        ->options($this->days)
                        ->required(),
                    Select::make('period')
                        ->label('Period')
                        ->options($this->getPeriodOptions())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->revertTemporaryChange(
                        (int) $data['day'],
                        (int) $data['period'],
                    );
                }),
        ];
    }

    /**
     * Get period options for header actions.
     *
     * @return array<int, string>
     */
    protected function getPeriodOptions(): array
    {
        $periodOptions = [];
        for ($period = 1; $period <= $this->periodsPerDay; $period++) {
            $periodOptions[$period] = "Period {$period}";
        }

        return $periodOptions;
    }

    /**
     * Load period times from TimetableSetting
     */
    protected function loadPeriodTimes(): void
    {
        $this->periodTimes = [];

        // Try to get period times from settings
        $periodsPerDay = TimetableSlot::getPeriodsPerDay();
        for ($period = 1; $period <= $periodsPerDay; $period++) {
            $startTime = \App\Models\TimetableSetting::get("period_{$period}_start");
            $endTime = \App\Models\TimetableSetting::get("period_{$period}_end");

            if ($startTime && $endTime) {
                $this->periodTimes[$period] = [
                    'start' => $startTime,
                    'end' => $endTime,
                ];
            } else {
                // Default times if not set
                $this->periodTimes[$period] = null;
            }
        }
    }

    /**
     * Calculate how many times each subject has been placed
     */
    protected function calculateSubjectPlacements(): void
    {
        $this->subjectPlacements = [];

        foreach ($this->timetableSlots as $day => $periods) {
            foreach ($periods as $period => $slot) {
                if ($slot && isset($slot['subject_id'])) {
                    $subjectId = $slot['subject_id'];
                    if (! isset($this->subjectPlacements[$subjectId])) {
                        $this->subjectPlacements[$subjectId] = 0;
                    }
                    $this->subjectPlacements[$subjectId]++;
                }
            }
        }
    }

    /**
     * Get placement count for a subject
     */
    public function getSubjectPlacementCount(int $subjectId): int
    {
        return $this->subjectPlacements[$subjectId] ?? 0;
    }

    /**
     * Load class subject settings for the selected class room
     */
    protected function loadClassSubjectSettings(): void
    {
        $this->classSubjectSettingsMap = [];

        if (! $this->selectedClassRoomId) {
            return;
        }

        $settings = ClassSubjectSetting::where('class_room_id', $this->selectedClassRoomId)
            ->where('is_active', true)
            ->get();

        foreach ($settings as $setting) {
            $this->classSubjectSettingsMap[$setting->subject_id] = [
                'weekly_periods' => $setting->weekly_periods ?? 0,
                'min_periods_per_week' => $setting->min_periods_per_week ?? 0,
                'max_periods_per_week' => $setting->max_periods_per_week ?? 0,
            ];
        }
    }

    /**
     * Get weekly periods for a subject from class subject settings
     */
    public function getSubjectWeeklyPeriods(int $subjectId): int
    {
        return $this->classSubjectSettingsMap[$subjectId]['weekly_periods'] ?? 0;
    }

    /**
     * Calculate constraint status for each subject
     */
    protected function calculateConstraintStatus(): void
    {
        $this->constraintStatus = [];

        foreach ($this->subjects as $subject) {
            $placed = $this->getSubjectPlacementCount($subject->id);
            $required = $this->getSubjectWeeklyPeriods($subject->id);

            $this->constraintStatus[$subject->id] = [
                'placed' => $placed,
                'required' => $required,
                'percentage' => $required > 0 ? round(($placed / $required) * 100) : 0,
                'satisfied' => $placed >= $required,
                'status' => match (true) {
                    $placed >= $required => 'complete',
                    $placed > 0 => 'partial',
                    default => 'none',
                },
            ];
        }
    }

    /**
     * Get subject type color classes
     */
    public function getSubjectTypeColor(string $type): array
    {
        return match ($type) {
            'core' => [
                'bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'border' => 'border-blue-300 dark:border-blue-700',
                'text' => 'text-blue-700 dark:text-blue-300',
                'badge' => 'bg-blue-500 text-white',
            ],
            'elective' => [
                'bg' => 'bg-green-100 dark:bg-green-900/30',
                'border' => 'border-green-300 dark:border-green-700',
                'text' => 'text-green-700 dark:text-green-300',
                'badge' => 'bg-green-500 text-white',
            ],
            'co_curricular' => [
                'bg' => 'bg-purple-100 dark:bg-purple-900/30',
                'border' => 'border-purple-300 dark:border-purple-700',
                'text' => 'text-purple-700 dark:text-purple-300',
                'badge' => 'bg-purple-500 text-white',
            ],
            default => [
                'bg' => 'bg-gray-100 dark:bg-gray-900/30',
                'border' => 'border-gray-300 dark:border-gray-700',
                'text' => 'text-gray-700 dark:text-gray-300',
                'badge' => 'bg-gray-500 text-white',
            ],
        };
    }

    /**
     * Get validation state for a specific slot
     */
    public function getSlotValidationState(int $day, int $period): string
    {
        // Check if slot has errors
        foreach ($this->validationErrors as $error) {
            // Check for single period errors
            if (isset($error['day']) && isset($error['period'])) {
                if ($error['day'] == $day && $error['period'] == $period) {
                    return 'error';
                }
            }
            // Check for errors with multiple periods (e.g., subject_daily_limit)
            if (isset($error['day']) && isset($error['periods']) && is_array($error['periods'])) {
                if ($error['day'] == $day && in_array($period, $error['periods'])) {
                    return 'error';
                }
            }
        }

        // Check if slot has warnings
        foreach ($this->validationWarnings as $warning) {
            // Check for single period warnings
            if (isset($warning['day']) && isset($warning['period'])) {
                if ($warning['day'] == $day && $warning['period'] == $period) {
                    return 'warning';
                }
            }
            // Check for warnings with multiple periods
            if (isset($warning['day']) && isset($warning['periods']) && is_array($warning['periods'])) {
                if ($warning['day'] == $day && in_array($period, $warning['periods'])) {
                    return 'warning';
                }
            }
        }

        // Check if slot is filled
        if (isset($this->timetableSlots[$day][$period]) && $this->timetableSlots[$day][$period]) {
            return 'valid';
        }

        return 'empty';
    }
}
