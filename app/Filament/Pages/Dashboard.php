<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
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
