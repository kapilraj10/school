<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Support\PermissionRegistry;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * @var array<int, string>
     */
    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignedPermissions = $this->record->permissions->pluck('name')->all();
        $groupedPermissions = [];

        foreach (PermissionRegistry::permissionMap() as $prefix => $config) {
            $groupedPermissions[$prefix] = array_values(array_filter(
                $assignedPermissions,
                fn (string $permission): bool => str_starts_with($permission, "{$prefix}.")
            ));
        }

        $data['grouped_permissions'] = $groupedPermissions;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedPermissions = array_values(array_unique(array_filter(
            Arr::flatten($data['grouped_permissions'] ?? [])
        )));

        unset($data['grouped_permissions']);
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! in_array($this->record->name, ['super-admin', 'admin'], true)),
        ];
    }
}
