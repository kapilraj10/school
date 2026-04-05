<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('dashboard.view');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToHome')
                ->label('Back to Home')
                ->icon('heroicon-o-home')
                ->url(route('home'))
                ->color('gray'),
        ];
    }
}
