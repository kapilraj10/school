<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Rooms & Labs';

    protected static ?string $modelLabel = 'Room / Lab';

    protected static ?string $pluralModelLabel = 'Rooms / Labs';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 2;

    protected static function permissionPrefix(): string
    {
        return 'room';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Room Information')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Computer Lab A'),

                            Forms\Components\TextInput::make('code')
                                ->label('Room Code')
                                ->required()
                                ->maxLength(50)
                                ->unique(ignoreRecord: true)
                                ->placeholder('e.g., LAB-COMP-A'),

                            Forms\Components\Select::make('type')
                                ->options([
                                    'classroom' => 'Classroom',
                                    'computer_lab' => 'Computer Lab',
                                    'science_lab' => 'Science Lab',
                                    'language_lab' => 'Language Lab',
                                    'library' => 'Library',
                                    'other' => 'Other',
                                ])
                                ->required()
                                ->default('classroom')
                                ->native(false),

                            Forms\Components\TextInput::make('capacity')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(500)
                                ->nullable(),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->required()
                                ->default('active')
                                ->native(false),
                        ]),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'classroom' => 'Classroom',
                        'computer_lab' => 'Computer Lab',
                        'science_lab' => 'Science Lab',
                        'language_lab' => 'Language Lab',
                        'library' => 'Library',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'classroom' => 'gray',
                        'computer_lab' => 'info',
                        'science_lab' => 'success',
                        'language_lab' => 'warning',
                        'library' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->state(fn (Room $record): bool => $record->status === 'active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'classroom' => 'Classroom',
                        'computer_lab' => 'Computer Lab',
                        'science_lab' => 'Science Lab',
                        'language_lab' => 'Language Lab',
                        'library' => 'Library',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
