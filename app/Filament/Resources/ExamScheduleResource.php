<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ExamScheduleResource\Pages;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\ExamSchedule;
use App\Models\TimetableSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamScheduleResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = ExamSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Exam Schedules';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 6;

    protected static function permissionPrefix(): string
    {
        return 'exam_schedule';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Exam Information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('academic_term_id')
                                ->label('Academic Term')
                                ->options(AcademicTerm::query()->orderByDesc('year')->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Select::make('class_room_id')
                                ->label('Class (Optional)')
                                ->options(ClassRoom::query()->where('status', 'active')->pluck('name', 'id'))
                                ->searchable(),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Select::make('exam_type')
                                ->options([
                                    'unit' => 'Unit Test',
                                    'midterm' => 'Midterm',
                                    'final' => 'Final Exam',
                                    'practical' => 'Practical',
                                    'other' => 'Other',
                                ])
                                ->required()
                                ->native(false),
                            DatePicker::make('date')
                                ->required()
                                ->native(false),
                            Select::make('day_of_week')
                                ->label('Day of Week')
                                ->options(TimetableSlot::getDays())
                                ->helperText('Optional weekly mapping used by timetable validation for recurring templates.'),
                            TimePicker::make('start_time')
                                ->seconds(false),
                            TimePicker::make('end_time')
                                ->seconds(false),
                            Toggle::make('is_school_wide')
                                ->inline(false)
                                ->default(true),
                            Textarea::make('notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->placeholder('School-wide'),
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_school_wide')
                    ->boolean()
                    ->label('School-wide'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type')
                    ->options([
                        'unit' => 'Unit Test',
                        'midterm' => 'Midterm',
                        'final' => 'Final Exam',
                        'practical' => 'Practical',
                        'other' => 'Other',
                    ]),
            ])
            ->defaultSort('date')
            ->actions([
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
            'index' => Pages\ListExamSchedules::route('/'),
            'create' => Pages\CreateExamSchedule::route('/create'),
            'edit' => Pages\EditExamSchedule::route('/{record}/edit'),
        ];
    }
}
