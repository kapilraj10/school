<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\SubjectResource\Pages;
use App\Models\ClassRoom;
use App\Models\ClassSubjectSetting;
use App\Models\Room;
use App\Models\Subject;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubjectResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Subjects';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 3;

    protected static function permissionPrefix(): string
    {
        return 'subject';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'classRoom.name', 'classRoom.section'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Subject Information')
                ->description('Enter the subject details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Mathematics'),
                        TextInput::make('code')
                            ->label('Subject Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g., MATH-1A')
                            ->helperText('Unique code for this subject'),
                        Select::make('class_room_id')
                            ->label('Class')
                            ->relationship('classRoom', 'name')
                            ->getOptionLabelFromRecordUsing(fn (ClassRoom $record) => "{$record->name} - {$record->section}")
                            ->searchable(['name', 'section'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Select the class for this subject'),
                        Select::make('type')
                            ->label('Subject Type')
                            ->options([
                                'core' => 'Core Subject',
                                'elective' => 'Elective',
                                'co_curricular' => 'Co-Curricular',
                            ])
                            ->default('core')
                            ->required()
                            ->native(false)
                            ->helperText('Choose the subject category for scheduling behavior.'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ]),
                ]),

            Section::make('Period Configuration')
                ->description('Define weekly periods for this subject in the selected class')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('min_periods_per_week')
                            ->label('Minimum Periods/Week')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(0)
                            ->maxValue(10),

                        TextInput::make('weekly_periods')
                            ->label('Target Periods/Week')
                            ->numeric()
                            ->required()
                            ->default(4)
                            ->minValue(1)
                            ->maxValue(12),

                        TextInput::make('max_periods_per_week')
                            ->label('Maximum Periods/Week')
                            ->numeric()
                            ->required()
                            ->default(6)
                            ->minValue(1)
                            ->maxValue(15),

                        Select::make('single_combined')
                            ->label('Period Mode')
                            ->options([
                                'single' => 'Single',
                                'combined' => 'Combined',
                            ])
                            ->required()
                            ->default('single')
                            ->native(false),

                        Select::make('setting_is_active')
                            ->label('Setting Status')
                            ->options([
                                '1' => 'Active',
                                '0' => 'Inactive',
                            ])
                            ->required()
                            ->default('1')
                            ->native(false),

                        TextInput::make('priority')
                            ->label('Priority')
                            ->numeric()
                            ->required()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(10),

                        Select::make('room_id')
                            ->label('Special Room / Lab')
                            ->options(Room::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->helperText('Optional room for this subject, like Computer Lab or Science Lab.'),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'core' => 'success',
                        'elective' => 'info',
                        'co_curricular' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'core' => 'Core',
                        'elective' => 'Elective',
                        'co_curricular' => 'Co-Curricular',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('periods_per_week')
                    ->label('Periods/Week')
                    ->state(function (Subject $record): int {
                        return (int) ClassSubjectSetting::query()
                            ->where('class_room_id', $record->class_room_id)
                            ->where('subject_id', $record->id)
                            ->value('weekly_periods');
                    })
                    ->sortable(false)
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('assignedRoom.name')
                    ->label('Special Room')
                    ->placeholder('-')
                    ->sortable(false),
            ])
            ->groups([
                Tables\Grouping\Group::make('classRoom.name')
                    ->label('Class')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Subject $record) => "{$record->classRoom->name} - {$record->classRoom->section}"),
            ])
            ->defaultGroup('classRoom.name')
            ->filters([
                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->relationship('classRoom', 'name')
                    ->getOptionLabelFromRecordUsing(fn (ClassRoom $record) => "{$record->name} - {$record->section}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'core' => 'Core',
                        'elective' => 'Elective',
                        'co_curricular' => 'Co-Curricular',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-clipboard-document')
                    ->visible(fn () => (bool) Auth::user()?->can('subject.create'))
                    ->form([
                        Select::make('target_class_ids')
                            ->label('Copy to Classes')
                            ->multiple()
                            ->options(fn (Subject $record) => ClassRoom::where('id', '!=', $record->class_room_id)
                                ->get()
                                ->mapWithKeys(fn ($class) => [
                                    $class->id => "{$class->name} - {$class->section}",
                                ])
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Select classes to copy this subject to'),
                    ])
                    ->action(function (Subject $record, array $data) {
                        foreach ($data['target_class_ids'] as $classId) {
                            $classRoom = ClassRoom::find($classId);
                            $classNumber = preg_replace('/[^0-9]/', '', $classRoom->name);
                            $section = $classRoom->section;
                            $baseCode = preg_replace('/-[\d-]+[A-Z]?$/', '', $record->code);
                            $newCode = "{$baseCode}-{$classNumber}{$section}";

                            $counter = 1;
                            $originalCode = $newCode;
                            while (Subject::where('code', $newCode)->exists()) {
                                $newCode = "{$originalCode}-{$counter}";
                                $counter++;
                            }

                            Subject::create([
                                'name' => $record->name,
                                'code' => $newCode,
                                'class_room_id' => $classId,
                                'type' => $record->type,
                                'level' => $record->level,
                                'status' => $record->status,
                            ]);
                        }
                    })
                    ->successNotificationTitle('Subject copied successfully')
                    ->color('success'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubjectsByClass::route('/'),
            'list' => Pages\ListSubjects::route('/list'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
