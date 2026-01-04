<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Services\TimetablePrintService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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

    public function mount(): void
    {
        $currentTerm = AcademicTerm::where('is_active', true)->first();

        $this->form->fill([
            'academic_term_id' => $currentTerm?->id,
            'print_type' => 'class',
        ]);
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
                    ->options([
                        'class' => 'Class Timetable',
                        'teacher' => 'Teacher Schedule',
                        'all_classes' => 'All Classes (Bulk)',
                        'master' => 'Master Timetable',
                    ])
                    ->required()
                    ->live()
                    ->native(false)
                    ->helperText('Select what you want to print'),

                Select::make('class_room_id')
                    ->label('Select Class')
                    ->options(ClassRoom::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->full_name]))
                    ->visible(fn (Get $get) => $get('print_type') === 'class')
                    ->required(fn (Get $get) => $get('print_type') === 'class')
                    ->native(false)
                    ->searchable(),

                Select::make('teacher_id')
                    ->label('Select Teacher')
                    ->options(Teacher::active()->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('print_type') === 'teacher')
                    ->required(fn (Get $get) => $get('print_type') === 'teacher')
                    ->native(false)
                    ->searchable(),

                Select::make('format')
                    ->label('Output Format')
                    ->options([
                        'pdf' => 'PDF Document',
                        'print' => 'Print Preview',
                        'excel' => 'Excel Spreadsheet',
                    ])
                    ->default('pdf')
                    ->required()
                    ->native(false),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function generateOutput()
    {
        $data = $this->form->getState();

        // Validate required fields based on print type
        if ($data['print_type'] === 'class' && empty($data['class_room_id'])) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Please select a class to print.')
                ->send();

            return;
        }

        if ($data['print_type'] === 'teacher' && empty($data['teacher_id'])) {
            Notification::make()
                ->title('Validation Error')
                ->danger()
                ->body('Please select a teacher to print.')
                ->send();

            return;
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

            return;
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
            // Redirect to a print preview page
            return redirect()->route('print.class-preview', [
                'class_id' => $class->id,
                'term_id' => $term->id,
            ]);
        }

        if ($data['format'] === 'excel') {
            Notification::make()
                ->title('Excel Export')
                ->info()
                ->body('Excel export feature coming soon!')
                ->send();

            return;
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
            Notification::make()
                ->title('Excel Export')
                ->info()
                ->body('Excel export feature coming soon!')
                ->send();

            return;
        }
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

    protected function getFormActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->action(function () {
                    $data = $this->form->getState();

                    if ($data['print_type'] === 'class' && ! empty($data['class_room_id'])) {
                        return redirect()->route('filament.admin.pages.timetable-viewer');
                    } elseif ($data['print_type'] === 'teacher' && ! empty($data['teacher_id'])) {
                        return redirect()->route('filament.admin.pages.teacher-schedule');
                    } else {
                        Notification::make()
                            ->title('Selection Required')
                            ->warning()
                            ->body('Please complete all required selections.')
                            ->send();
                    }
                }),

            Action::make('generate')
                ->label('Generate & Download')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->action('generateOutput'),
        ];
    }
}
