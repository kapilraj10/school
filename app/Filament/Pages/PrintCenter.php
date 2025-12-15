<?php

namespace App\Filament\Pages;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Teacher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PrintCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected string $view = 'filament.pages.print-center';

    protected static ?string $navigationLabel = 'Print Center';

    protected static ?string $title = 'Print Center';

    protected static UnitEnum|string|null $navigationGroup = 'Timetable Management';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->options(ClassRoom::active()->get()->mapWithKeys(fn($c) => [$c->id => $c->full_name]))
                    ->visible(fn ($get) => $get('print_type') === 'class')
                    ->required(fn ($get) => $get('print_type') === 'class')
                    ->native(false)
                    ->searchable(),

                Select::make('teacher_id')
                    ->label('Select Teacher')
                    ->options(Teacher::active()->pluck('name', 'id'))
                    ->visible(fn ($get) => $get('print_type') === 'teacher')
                    ->required(fn ($get) => $get('print_type') === 'teacher')
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

        // Show notification that feature is being processed
        Notification::make()
            ->title('Generating Document')
            ->info()
            ->body('Your document is being generated. This feature will be fully implemented soon.')
            ->send();

        // TODO: Implement actual PDF/Excel generation
        // For now, redirect to appropriate view page
        if ($data['print_type'] === 'class' && isset($data['class_room_id'])) {
            return redirect()->route('filament.admin.pages.timetable-viewer', [
                'academic_term_id' => $data['academic_term_id'],
                'class_room_id' => $data['class_room_id'],
            ]);
        }

        if ($data['print_type'] === 'teacher' && isset($data['teacher_id'])) {
            return redirect()->route('filament.admin.pages.teacher-schedule', [
                'academic_term_id' => $data['academic_term_id'],
                'teacher_id' => $data['teacher_id'],
            ]);
        }
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
                    
                    if ($data['print_type'] === 'class' && !empty($data['class_room_id'])) {
                        return redirect()->route('filament.admin.pages.timetable-viewer');
                    } elseif ($data['print_type'] === 'teacher' && !empty($data['teacher_id'])) {
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
