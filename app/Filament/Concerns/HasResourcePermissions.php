<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasResourcePermissions
{
    abstract protected static function permissionPrefix(): string;

    protected static function canDo(string $action): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->can(static::permissionPrefix().'.'.$action);
    }

    public static function canViewAny(): bool
    {
        return static::canDo('list');
    }

    public static function canView($record): bool
    {
        return static::canDo('view');
    }

    public static function canCreate(): bool
    {
        return static::canDo('create');
    }

    public static function canEdit($record): bool
    {
        return static::canDo('edit');
    }

    public static function canDelete($record): bool
    {
        return static::canDo('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::canDo('delete');
    }
}
