<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ContactSettingResource\Pages;
use App\Models\ContactSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactSettingResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = ContactSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Contact Settings';

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?int $navigationSort = 21;

    protected static function permissionPrefix(): string
    {
        return 'contact_setting';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Map Link')
                    ->description('Set the Google Maps embed URL displayed on the contact page.')
                    ->schema([
                        Forms\Components\Textarea::make('map_embed_url')
                            ->label('Map Embed URL')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Paste a Google Maps embed URL (https://www.google.com/maps/embed?...).'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('map_embed_url')
                    ->label('Map URL')
                    ->limit(80)
                    ->tooltip(fn (ContactSetting $record): string => $record->map_embed_url),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactSettings::route('/'),
            'create' => Pages\CreateContactSetting::route('/create'),
            'edit' => Pages\EditContactSetting::route('/{record}/edit'),
        ];
    }
}
