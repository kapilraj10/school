<?php

namespace App\Filament\Forms\Components;

use App\Models\TimetableSetting;
use Filament\Forms\Components\Field;

class AvailabilityGrid extends Field
{
    protected string $view = 'filament.forms.components.availability-grid';

    protected array $schoolDays = [];

    protected int $periodsPerDay = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolDays = TimetableSetting::get('school_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        $this->periodsPerDay = TimetableSetting::get('periods_per_day', 8);

        $this->afterStateHydrated(function (AvailabilityGrid $component, $state) {
            if (is_null($state)) {
                $component->state([
                    'days' => [],
                    'periods' => [],
                    'matrix' => [],
                ]);

                return;
            }

            if (is_array($state)) {
                $component->state([
                    'days' => $state['days'] ?? [],
                    'periods' => $state['periods'] ?? [],
                    'matrix' => $state['matrix'] ?? [],
                ]);

                return;
            }

            $component->state([
                'days' => [],
                'periods' => [],
                'matrix' => [],
            ]);
        });

        $this->dehydrateStateUsing(function ($state) {
            return $state;
        });
    }

    public function getSchoolDays(): array
    {
        return $this->schoolDays;
    }

    public function getPeriodsPerDay(): int
    {
        return $this->periodsPerDay;
    }
}
