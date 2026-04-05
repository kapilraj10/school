<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\RoleResource\Pages;
use App\Support\PermissionRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static function permissionPrefix(): string
    {
        return 'role';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Role Details')
                ->schema([
                    Forms\Components\Hidden::make('guard_name')
                        ->default('web'),

                    Forms\Components\TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                ]),
            ...static::permissionSections(),
        ]);
    }

    /**
     * @return array<int, Forms\Components\Section>
     */
    protected static function permissionSections(): array
    {
        $sections = [];
        $optionsByPrefix = PermissionRegistry::optionsByPrefix();

        foreach (PermissionRegistry::permissionMap() as $prefix => $config) {
            $sections[] = Forms\Components\Section::make($config['label'])
                ->schema([
                    Forms\Components\CheckboxList::make("grouped_permissions.{$prefix}")
                        ->label('')
                        ->options($optionsByPrefix[$prefix] ?? [])
                        ->columns(5)
                        ->bulkToggleable(),
                ]);
        }

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record) => ! in_array($record->name, ['super-admin', 'admin'], true)),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
