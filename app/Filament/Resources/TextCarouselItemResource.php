<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\TextCarouselItemResource\Pages;
use App\Models\TextCarouselItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TextCarouselItemResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = TextCarouselItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Text Carousel';

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?int $navigationSort = 6;

    protected static function permissionPrefix(): string
    {
        return 'text_carousel_item';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Carousel Text Item')
                    ->schema([
                        Forms\Components\Textarea::make('quote')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('author_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('author_role')
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('author_image')
                            ->image()
                            ->disk('public')
                            ->directory('text-carousel-authors')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(5)
                            ->required(),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('author_image')
                    ->label('Image')
                    ->getStateUsing(fn (TextCarouselItem $record): ?string => $record->author_image_url)
                    ->square()
                    ->defaultImageUrl(asset('images/logo.png')),

                Tables\Columns\TextColumn::make('quote')
                    ->limit(70),

                Tables\Columns\TextColumn::make('author_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('author_role')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTextCarouselItems::route('/'),
            'create' => Pages\CreateTextCarouselItem::route('/create'),
            'edit' => Pages\EditTextCarouselItem::route('/{record}/edit'),
        ];
    }
}
