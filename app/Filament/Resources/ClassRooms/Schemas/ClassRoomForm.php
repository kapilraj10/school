<?php

namespace App\Filament\Resources\ClassRooms\Schemas;

use App\Filament\Resources\ClassRooms\Pages\CreateClassRoom;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;

class ClassRoomForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('copy_from_class_room_id')
                    ->default(null),

                Hidden::make('copy_subjects_from_source')
                    ->default(false),

                Hidden::make('copy_from_class_message')
                    ->default(null),

                Section::make('Class Information')
                    ->description('Enter the basic information for the class')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Class Name')
                                    ->required()
                                    ->placeholder('e.g., Class 1, Class 2')
                                    ->maxLength(100)
                                    ->helperText('Enter the class name without section')
                                    ->columnSpan(1),

                                TextInput::make('section')
                                    ->label('Section')
                                    ->required()
                                    ->placeholder('e.g., A, B, C')
                                    ->maxLength(10)
                                    ->helperText('You can define any section label')
                                    ->columnSpan(1),

                                TextInput::make('capacity')
                                    ->label('Class Capacity')
                                    ->numeric()
                                    ->required()
                                    ->default(40)
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->suffix('students')
                                    ->helperText('Maximum number of students that can be assigned')
                                    ->columnSpan(1),

                                Select::make('class_teacher_id')
                                    ->label('Class Teacher')
                                    ->options(fn () => Teacher::active()->pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('The class teacher will be assigned Period 1 each day')
                                    ->columnSpanFull(),

                                Select::make('status')
                                    ->label('Active Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Select::make('student_ids')
                                    ->label('Assigned Students')
                                    ->multiple()
                                    ->options(fn () => User::query()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(function (User $user): array {
                                            $classSuffix = $user->classRoom
                                                ? " (Currently: {$user->classRoom->full_name})"
                                                : '';

                                            return [$user->id => "{$user->name} ({$user->email}){$classSuffix}"];
                                        })
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->helperText('Select users to assign as students of this class')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Subject Configuration')
                    ->description('Configure the subjects for this class. If you prefer to manage them manually, you may do so from the Subjects page after the class has been created.')
                    ->headerActions([
                        Action::make('copy_from_class')
                            ->label('Copy Subjects from another class')
                            ->icon('heroicon-o-clipboard-document')
                            ->visible(fn ($livewire): bool => $livewire instanceof CreateClassRoom)
                            ->form([
                                Select::make('source_class_room_id')
                                    ->label('Copy From')
                                    ->options(fn (): array => ClassRoom::query()
                                        ->orderBy('name')
                                        ->orderBy('section')
                                        ->get()
                                        ->mapWithKeys(fn (ClassRoom $classRoom) => [
                                            $classRoom->id => $classRoom->full_name,
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                                Toggle::make('copy_subjects')
                                    ->label('Also copy subjects after creating this class')
                                    ->default(true),
                            ])
                            ->action(function (array $data, $livewire): void {
                                $sourceClassRoom = ClassRoom::find($data['source_class_room_id']);
                                if (! $sourceClassRoom) {
                                    return;
                                }

                                $state = $livewire->form->getState();

                                $livewire->form->fill([
                                    ...$state,
                                    'class_teacher_id' => $sourceClassRoom->class_teacher_id,
                                    'copy_from_class_room_id' => $sourceClassRoom->id,
                                    'copy_subjects_from_source' => (bool) ($data['copy_subjects'] ?? true),
                                    'copy_from_class_message' => sprintf(
                                        '✓ %d subjects from "%s" will be copied when you create this class.',
                                        Subject::where('class_room_id', $sourceClassRoom->id)->where('status', 'active')->count(),
                                        $sourceClassRoom->full_name
                                    ),
                                ]);
                            }),
                    ])
                    ->schema([
                        Placeholder::make('copy_from_class_notice')
                            ->label('Configuration Copied')
                            ->visible(fn (Get $get): bool => filled($get('copy_from_class_message')))
                            ->content(fn (Get $get): ?string => $get('copy_from_class_message'))
                            ->extraAttributes([
                                'class' => 'fi-badge fi-badge-color-success',
                            ]),
                    ]),
            ]);
    }
}
