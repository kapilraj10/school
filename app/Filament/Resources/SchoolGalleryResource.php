<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\SchoolGalleryResource\Pages;
use App\Models\SchoolGallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolGalleryResource extends Resource
{
    use HasResourcePermissions;

    private const CATEGORY_OPTIONS = [
        'activities' => 'Activities',
        'finance' => 'Finance',
        'administration' => 'Administration',
        'academic' => 'Academic',
    ];

    protected static ?string $model = SchoolGallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'School Gallery';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 8;

    protected static function permissionPrefix(): string
    {
        return 'school_gallery';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Upload Photo')
                ->description('Upload school gallery photos only.')
                ->schema([
                    Forms\Components\Select::make('category')
                        ->options(self::CATEGORY_OPTIONS)
                        ->default('academic')
                        ->required()
                        ->native(false),

                    Forms\Components\FileUpload::make('photo')
                        ->label('Photo')
                        ->image()
                        ->required()
                        ->disk('public')
                        ->directory('school-gallery-temp')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->helperText('Allowed: JPG, PNG, WEBP'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->height(80)
                    ->square(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->formatStateUsing(fn (?string $state): string => self::CATEGORY_OPTIONS[$state ?? ''] ?? ucfirst((string) $state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
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
            'index' => Pages\ListSchoolGalleries::route('/'),
            'create' => Pages\CreateSchoolGallery::route('/create'),
        ];
    }
}
