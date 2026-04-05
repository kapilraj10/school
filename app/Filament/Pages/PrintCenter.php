<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Room;
use App\Models\Teacher;
use App\Services\TimetablePrintService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PrintCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static string $view = 'filament.pages.print-center';

    protected static ?string $navigationLabel = 'Print Center';

    protected static ?string $title = 'Print Center';

    protected static ?string $navigationGroup = 'Timetable Management';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->hasAnyRole(['super-admin', 'admin', 'teacher', 'student']);
    }

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();

        $selectedPrintType = request('print_type', 'class');
        $selectedTermId = request('term_id', $currentTerm?->id);
        $selectedClassId = request('class_room_id');
        $selectedTeacherId = request('teacher_id');
        $selectedRoomId = request('room_id');

        $this->form->fill([
            'academic_term_id' => $selectedTermId,
            'print_type' => $selectedPrintType,
            'class_room_id' => $selectedClassId,
            'teacher_id' => $selectedTeacherId,
            'room_id' => $selectedRoomId,
        ]);

        $this->enforceRolePrintConstraints();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('academic_term_id')
                    ->label('Academic Term')
                    ->options(AcademicTerm::query()->orderBy('year', 'desc')->orderBy('term', 'desc')->pluck('name', 'id'))
                    ->required()
                    ->native(false)
                    ->searchable(),

                Select::make('print_type')
                    ->label('Print Type')
                    ->options(function (): array {
                        if ($this->isTeacherUser()) {
                            return ['teacher' => 'Teacher Schedule'];
                        }

                        if ($this->isStudentUser()) {
                            return ['class' => 'Class Timetable'];
                        }

                        return [
                            'class' => 'Class Timetable',
                            'teacher' => 'Teacher Schedule',
                            'room' => 'Room Schedule',
                            'all_classes' => 'All Classes (Bulk)',
                            'master' => 'Master Timetable',
                        ];
                    })
                    ->required()
                    ->live()
                    ->disabled(fn (): bool => ! $this->isAdminUser())
                    ->native(false)
                    ->helperText('Select what you want to print'),

                Select::make('class_room_id')
                    ->label('Select Class')
                    ->options(ClassRoom::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->full_name]))
                    ->visible(fn (Get $get) => $get('print_type') === 'class')
                    ->required(fn (Get $get) => $get('print_type') === 'class')
                    ->disabled(fn (): bool => $this->isStudentUser())
                    ->native(false)
                    ->searchable(),

                Select::make('teacher_id')
                    ->label('Select Teacher')
                    ->options(Teacher::active()->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('print_type') === 'teacher')
                    ->required(fn (Get $get) => $get('print_type') === 'teacher')
                    ->disabled(fn (): bool => $this->isTeacherUser())
                    ->native(false)
                    ->searchable(),

                Select::make('room_id')
                    ->label('Select Room / Lab')
                    ->options(Room::active()->orderBy('name')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('print_type') === 'room')
                    ->required(fn (Get $get) => $get('print_type') === 'room')
                    ->native(false)
                    ->searchable(),

            ])
            ->columns(2)
            ->statePath('data');
    }

    public function downloadPdf(): mixed
    {
        return $this->generateOutput('pdf');
    }

    public function downloadExcel(): mixed
    {
        return $this->generateOutput('excel');
    }

    public function previewOutput(): mixed
    {
        return $this->generateOutput('print');
    }

    public function generateOutput(string $format = 'pdf'): mixed
    {
        $this->enforceRolePrintConstraints();

        $data = $this->form->getState();
        $data['format'] = $format;

        // Validate required fields based on print type
        if ($data['print_type'] === 'class' && empty($data['class_room_id'])) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Please select a class to print.')
                ->send();

            return null;
        }

        if ($data['print_type'] === 'teacher' && empty($data['teacher_id'])) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Please select a teacher to print.')
                ->send();

            return null;
        }

        if ($data['print_type'] === 'room' && empty($data['room_id'])) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Please select a room/lab to print.')
                ->send();

            return null;
        }

        try {
            $printService = new TimetablePrintService;
            $term = AcademicTerm::findOrFail($data['academic_term_id']);

            // Handle different print types and formats
            switch ($data['print_type']) {
                case 'class':
                    return $this->handleClassPrint($data, $printService, $term);

                case 'teacher':
                    return $this->handleTeacherPrint($data, $printService, $term);

                case 'room':
                    return $this->handleRoomPrint($data, $printService, $term);

                case 'all_classes':
                    return $this->handleAllClassesPrint($data, $printService, $term);

                case 'master':
                    return $this->handleMasterTimetablePrint($data, $printService, $term);

                default:
                    throw new \Exception('Invalid print type selected.');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Generating Document')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return null;
        }
    }

    protected function handleClassPrint(array $data, TimetablePrintService $printService, AcademicTerm $term)
    {
        $class = ClassRoom::findOrFail($data['class_room_id']);

        if ($data['format'] === 'pdf') {
            $pdf = $printService->generateClassTimetablePdf($class->id, $term->id);
            $filename = $printService->generateFilename('class', $class, $term);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename
            );
        }

        if ($data['format'] === 'print') {
            return redirect()->route('print.class-preview', [
                'class_id' => $class->id,
                'term_id' => $term->id,
            ]);
        }

        if ($data['format'] === 'excel') {
            return $this->exportClassToExcel($class->id, $term->id, $class, $term);
        }
    }

    protected function handleTeacherPrint(array $data, TimetablePrintService $printService, AcademicTerm $term)
    {
        $teacher = Teacher::findOrFail($data['teacher_id']);

        if ($data['format'] === 'pdf') {
            $pdf = $printService->generateTeacherSchedulePdf($teacher->id, $term->id);
            $filename = $printService->generateFilename('teacher', $teacher, $term);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename
            );
        }

        if ($data['format'] === 'print') {
            return redirect()->route('print.teacher-preview', [
                'teacher_id' => $teacher->id,
                'term_id' => $term->id,
            ]);
        }

        if ($data['format'] === 'excel') {
            return $this->exportTeacherToExcel($teacher->id, $term->id, $teacher, $term);
        }

        return null;
    }

    protected function handleRoomPrint(array $data, TimetablePrintService $printService, AcademicTerm $term)
    {
        $room = Room::findOrFail($data['room_id']);

        if ($data['format'] === 'pdf') {
            $pdf = $printService->generateRoomSchedulePdf($room->id, $term->id);
            $filename = $printService->generateFilename('room', $room, $term);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename
            );
        }

        if ($data['format'] === 'print') {
            return redirect()->route('print.room-preview', [
                'room_id' => $room->id,
                'term_id' => $term->id,
            ]);
        }

        if ($data['format'] === 'excel') {
            return $this->exportRoomToExcel($room->id, $term->id, $room, $term);
        }

        return null;
    }

    protected function handleAllClassesPrint(array $data, TimetablePrintService $printService, AcademicTerm $term)
    {
        if ($data['format'] === 'pdf') {
            $pdf = $printService->generateAllClassesPdf($term->id);
            $filename = $printService->generateFilename('all_classes', null, $term);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename
            );
        }

        Notification::make()
            ->title('Format Not Supported')
            ->warning()
            ->body('Only PDF format is supported for bulk class printing.')
            ->send();
    }

    protected function handleMasterTimetablePrint(array $data, TimetablePrintService $printService, AcademicTerm $term)
    {
        if ($data['format'] === 'pdf') {
            $pdf = $printService->generateMasterTimetablePdf($term->id);
            $filename = $printService->generateFilename('master', null, $term);

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename
            );
        }

        Notification::make()
            ->title('Format Not Supported')
            ->warning()
            ->body('Only PDF format is supported for master timetable.')
            ->send();
    }

    protected function exportClassToExcel(int $classId, int $termId, ClassRoom $class, AcademicTerm $term)
    {
        $printService = new TimetablePrintService;
        $filename = "class-timetable-{$class->full_name}-{$term->name}.xlsx";

        return $printService->exportClassTimetableToExcel($classId, $termId, $filename);
    }

    protected function exportTeacherToExcel(int $teacherId, int $termId, Teacher $teacher, AcademicTerm $term)
    {
        $printService = new TimetablePrintService;
        $filename = "teacher-schedule-{$teacher->name}-{$term->name}.xlsx";

        return $printService->exportTeacherScheduleToExcel($teacherId, $termId, $filename);
    }

    protected function exportRoomToExcel(int $roomId, int $termId, Room $room, AcademicTerm $term)
    {
        $printService = new TimetablePrintService;
        $filename = "room-schedule-{$room->name}-{$term->name}.xlsx";

        return $printService->exportRoomScheduleToExcel($roomId, $termId, $filename);
    }

    private function enforceRolePrintConstraints(): void
    {
        if ($this->isTeacherUser()) {
            $teacher = $this->currentUserTeacher();

            $this->data['print_type'] = 'teacher';
            $this->data['teacher_id'] = $teacher?->id;
            $this->data['class_room_id'] = null;
            $this->data['room_id'] = null;

            return;
        }

        if ($this->isStudentUser()) {
            $this->data['print_type'] = 'class';
            $this->data['class_room_id'] = Auth::user()?->class_room_id;
            $this->data['teacher_id'] = null;
            $this->data['room_id'] = null;
        }
    }

    private function currentUserTeacher(): ?Teacher
    {
        $email = Auth::user()?->email;

        if (! $email) {
            return null;
        }

        return Teacher::active()->where('email', $email)->first();
    }

    private function isAdminUser(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['super-admin', 'admin']);
    }

    private function isTeacherUser(): bool
    {
        return (bool) Auth::user()?->hasRole('teacher');
    }

    private function isStudentUser(): bool
    {
        return (bool) Auth::user()?->hasRole('student');
    }
}
