<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\SpecialEventResource\Pages;
use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\SpecialEvent;
use App\Models\TimetableSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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

class SpecialEventResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = SpecialEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Special Events';

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?int $navigationSort = 7;

    protected static function permissionPrefix(): string
    {
        return 'special_event';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Event Information')
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
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('event_type')
                                ->options([
                                    'event' => 'Event',
                                    'assembly' => 'Assembly',
                                    'activity' => 'Activity',
                                    'program' => 'Program',
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
                            Toggle::make('blocks_timetable')
                                ->inline(false)
                                ->label('Block timetable assignments')
                                ->default(false),
                            Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
            Section::make('Website Display')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('venue')
                                ->maxLength(255)
                                ->placeholder('Main Auditorium'),

                            Toggle::make('show_on_home')
                                ->label('Show in homepage events section')
                                ->default(true)
                                ->inline(false),

                            TextInput::make('notice_url')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://...'),

                            TextInput::make('notice_link_text')
                                ->maxLength(100)
                                ->default('View Notice'),

                            Toggle::make('show_popup')
                                ->label('Show popup when website opens')
                                ->inline(false)
                                ->default(false),

                            FileUpload::make('popup_image')
                                ->label('Popup Image')
                                ->image()
                                ->disk('public')
                                ->directory('event-popup-temp')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Uploaded to Cloudinary when configured.'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('academicTerm.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->placeholder('School-wide'),
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('venue')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('show_on_home')
                    ->boolean()
                    ->label('Home'),
                Tables\Columns\IconColumn::make('show_popup')
                    ->boolean()
                    ->label('Popup'),
                Tables\Columns\IconColumn::make('blocks_timetable')
                    ->boolean()
                    ->label('Blocks timetable'),
            ])
            ->defaultSort('date')
            ->filters([
                Tables\Filters\TernaryFilter::make('blocks_timetable')
                    ->label('Blocking events only'),
                Tables\Filters\TernaryFilter::make('show_on_home')
                    ->label('Shown on homepage'),
            ])
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
            'index' => Pages\ListSpecialEvents::route('/'),
            'create' => Pages\CreateSpecialEvent::route('/create'),
            'edit' => Pages\EditSpecialEvent::route('/{record}/edit'),
        ];
    }
}
