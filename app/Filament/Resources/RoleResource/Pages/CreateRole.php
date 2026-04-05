<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * @var array<int, string>
     */
    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPermissions = array_values(array_unique(array_filter(
            Arr::flatten($data['grouped_permissions'] ?? [])
        )));

        unset($data['grouped_permissions']);
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }
}
