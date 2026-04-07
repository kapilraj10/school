<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ContactSubmissionResource\Pages;
use App\Models\ContactSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactSubmissionResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = ContactSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Contact Submissions';

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?int $navigationSort = 20;

    protected static function permissionPrefix(): string
    {
        return 'contact_submission';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->disabled(),
                        Forms\Components\Textarea::make('message')
                            ->rows(6)
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Submitted At')
                            ->disabled()
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn (ContactSubmission $record): string => $record->message),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('created_at', now()->toDateString())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListContactSubmissions::route('/'),
        ];
    }
}
