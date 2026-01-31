<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TimetableGrid extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public Collection $slots,
        public ?string $className = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.timetable-grid');
    }
}
