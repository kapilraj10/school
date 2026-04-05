<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\AcademicTermResource\Pages;
use App\Models\AcademicTerm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AcademicTermResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = AcademicTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Academic Terms';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 4;

    protected static function permissionPrefix(): string
    {
        return 'academic_term';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'year', 'term'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Term Information')
                ->description('Enter the basic information for the academic term')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2024-2025 Term 1'),
                        TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->default(date('Y')),
                        Select::make('term')
                            ->options([
                                '1' => 'First Term',
                                '2' => 'Second Term',
                                '3' => 'Third Term',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->options([
                                'upcoming' => 'Upcoming',
                                'active' => 'Active',
                                'completed' => 'Completed',
                            ])
                            ->default('upcoming')
                            ->required()
                            ->native(false),
                        DatePicker::make('start_date')
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->required()
                            ->after('start_date')
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Active Term')
                            ->helperText('Only one term can be active at a time')
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '1' => 'First Term',
                        '2' => 'Second Term',
                        '3' => 'Third Term',
                        default => "Term {$state}",
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upcoming' => 'info',
                        'active' => 'success',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'completed' => 'Completed',
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
            'index' => Pages\ListAcademicTerms::route('/'),
            'create' => Pages\CreateAcademicTerm::route('/create'),
            'edit' => Pages\EditAcademicTerm::route('/{record}/edit'),
        ];
    }
}
