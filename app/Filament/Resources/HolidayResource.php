<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Holidays';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Holiday Information')
                ->description('Enter the holiday details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Christmas Day'),
                        DatePicker::make('date')
                            ->required()
                            ->native(false),
                        Select::make('type')
                            ->options([
                                'fixed' => 'Fixed (One-time)',
                                'annually' => 'Annually (Repeats yearly)',
                                'recurring' => 'Recurring',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Fixed: specific date only. Annually: repeats every year.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Optional description about this holiday')
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
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'info',
                        'annually' => 'success',
                        'recurring' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'fixed' => 'Fixed',
                        'annually' => 'Annually',
                        'recurring' => 'Recurring',
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
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
