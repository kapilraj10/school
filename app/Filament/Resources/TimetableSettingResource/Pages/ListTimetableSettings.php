<?php

namespace App\Filament\Resources\TimetableSettingResource\Pages;

use App\Filament\Resources\TimetableSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimetableSettings extends ListRecords
{
    protected static string $resource = TimetableSettingResource::class;

    protected static string $view = 'filament.resources.timetable-setting-resource.pages.list-timetable-settings';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function paginateTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Contracts\Pagination\Paginator
    {
        return $query->simplePaginate(500);
    }

    public function getRecords(): \Illuminate\Support\Collection
    {
        return static::getResource()::getModel()::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function deleteRecord(int $recordId): void
    {
        $record = static::getResource()::getModel()::findOrFail($recordId);
        $record->delete();

        $this->dispatch('recordDeleted');

        \Filament\Notifications\Notification::make()
            ->title('Setting deleted')
            ->success()
            ->send();
    }
}
