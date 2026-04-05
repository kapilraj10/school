<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\Tables\TeachersTable;
use App\Models\Teacher;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class TeacherResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Teachers';

    protected static ?string $modelLabel = 'Teacher';

    protected static ?string $pluralModelLabel = 'Teachers';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 3;

    protected static function permissionPrefix(): string
    {
        return 'teacher';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'employee_id', 'email', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return TeacherForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'view' => Pages\ViewTeacher::route('/{record}'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}
