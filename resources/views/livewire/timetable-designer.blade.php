<div class="h-[calc(100vh-3.5rem)] gap-0 flex flex-col"
     x-data="{
         draggedSubject: null,
         draggedTeacher: null,
         draggedSlot: null,
         editMode: @entangle('editMode'),
         zoom: 1,
         subjectSearch: '',
         init() {
            this.$watch('zoom', val => {
                if(val < 0.5) this.zoom = 0.5;
                if(val > 1.5) this.zoom = 1.5;
            });
            window.addEventListener('resize', () => {
                this.fitToScreen();
            });
         },
         fitToScreen() {
            // Rough estimation to fit 8 periods + headers vertically
            const availableHeight = window.innerHeight - 120; // Header + controls
            const contentHeight = 700; // Approx height of grid
            this.zoom = Math.min(1, Math.max(0.6, availableHeight / contentHeight));
         },
         startDrag(subjectId, teacherId) {
             if (!this.editMode) return;
             this.draggedSubject = subjectId;
             this.draggedTeacher = teacherId;
         },
         endDrag() {
             this.draggedSubject = null;
             this.draggedTeacher = null;
         },
         startDragSlot(date, period) {
             if (!this.editMode) return;
             this.draggedSlot = { date, period };
         },
         handleSidebarDrop() {
             if (this.draggedSlot) {
                 $wire.removeSlot(this.draggedSlot.date, this.draggedSlot.period);
                 this.draggedSlot = null;
             }
         }
     }">

    <!-- Left Sidebar - Subject/Teacher List -->
     <div class="fixed left-0 top-14 bottom-0 w-64 bg-white dark:bg-gray-950 shadow-md border-r border-gray-200 dark:border-gray-700 z-40 flex flex-col transition-transform duration-300 transform"
          @dragover.prevent
          @drop="handleSidebarDrop()">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <input
                    type="text"
                    placeholder="Search Subjects"
                    x-model="subjectSearch"
                    class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button
                    x-show="subjectSearch !== ''"
                    @click="subjectSearch = ''"
                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    title="Clear search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-2">
            @foreach ($subjects as $subject)
                @php
                    $primaryTeacher = $subject->teachers->first();
                    $workload = isset($subjectWorkload[$subject->id]) ? $subjectWorkload[$subject->id] : 0;
                    $setting = isset($classSubjectSettings[$subject->id]) ? $classSubjectSettings[$subject->id] : null;
                    $maxPeriods = $setting['max_periods_per_week'] ?? 0;
                    $minPeriods = $setting['min_periods_per_week'] ?? 0;
                    $remaining = $maxPeriods > 0 ? ($maxPeriods - $workload) : null;
                    $subjectType = strtolower(str_replace('-', '_', $subject->type ?? ''));

                    if ($subjectType === 'core') {
                        $bgColor = 'bg-blue-50 dark:bg-blue-950 border-blue-300 dark:border-blue-600';
                    } elseif ($subjectType === 'co_curricular') {
                        $bgColor = 'bg-green-50 dark:bg-green-950 border-green-300 dark:border-green-600';
                    } else {
                        $bgColor = 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700';
                    }

                    // Badge color based on remaining slots
                    if ($remaining === null) {
                        $badgeColor = 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200';
                    } elseif ($remaining <= 1) {
                        $badgeColor = 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300';
                    } elseif ($remaining <= 2) {
                        $badgeColor = 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300';
                    } else {
                        $badgeColor = 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300';
                    }
                @endphp

                @if($primaryTeacher && ($remaining === null || $remaining > 0))
                    <div class="p-3 rounded-lg border transition-colors {{ $bgColor }}"
                         x-show="subjectSearch === '' || @js(strtolower(($subject->code ?? $subject->name) . ' ' . $subject->name . ' ' . ($primaryTeacher->employee_id ?? '') . ' ' . $primaryTeacher->name)).includes(subjectSearch.toLowerCase())"
                         x-bind:class="{ 'cursor-move hover:border-blue-400 dark:hover:border-blue-600': editMode, 'cursor-not-allowed opacity-60': !editMode }"
                         x-bind:draggable="editMode"
                         @dragstart="startDrag({{ $subject->id }}, {{ $primaryTeacher->id }})"
                         @dragend="endDrag()">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm {{ $subjectType === 'core' ? 'text-blue-700 dark:text-blue-300' : ($subjectType === 'co_curricular' ? 'text-green-700 dark:text-green-300' : 'text-gray-900 dark:text-gray-100') }} truncate" title="{{ $subject->name }}">
                                    {{ $subject->code ?? $subject->name }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5 truncate" title="{{ $primaryTeacher->name }}">
                                    {{ $primaryTeacher->employee_id ?? $primaryTeacher->name }}
                                </p>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $badgeColor }}">
                                    @if($remaining !== null)
                                        {{ $remaining }}/{{ $maxPeriods }}
                                    @else
                                        {{ $workload }}
                                    @endif
                                </span>
                                @if($minPeriods > 0)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Min: {{ $minPeriods }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif(!$primaryTeacher)
                    <div class="p-3 rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 opacity-60">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <h3 class="font-semibold text-sm text-red-900 dark:text-red-100">
                                    {{ $subject->name }}
                                </h3>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                    No teacher assigned
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="p-2 border-t border-gray-200 dark:border-gray-700">
            <div class="bg-white dark:bg-gray-950 p-2 rounded-lg border border-gray-200 dark:border-gray-700 grid grid-cols-1 gap-2 text-xs">
                <div class="font-semibold text-center mb-1">Color Legend</div>
                <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-600 rounded px-2 py-1">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="text-gray-700 dark:text-gray-300">Core Subject</span>
                </div>
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-600 rounded px-2 py-1">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <span class="text-gray-700 dark:text-gray-300">Co-Curricular</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area - Weekly Calendar -->
    <div class="ml-64 flex-1 bg-white dark:bg-gray-950 rounded-none shadow-none border-none overflow-hidden flex flex-col h-full">

        <!-- Top Controls -->
        <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4 h-14 bg-white dark:bg-gray-950 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <select
                    wire:model.live="selectedTermId"
                    class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Select Term</option>
                    @foreach ($academicTerms as $term)
                        <option value="{{ $term->id }}">{{ $term->full_name }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="selectedClassId"
                    class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Select Class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <!-- Edit Mode Toggle -->
                <button
                    wire:click="toggleEditMode"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="editMode ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    <span x-text="editMode ? 'Edit Mode: ON' : 'Edit Mode: OFF'"></span>
                </button>

                <!-- Refresh Button -->
                <button
                    onclick="window.location.reload()"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    title="Refresh page"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <!-- Save Button -->
                <button
                    wire:click="saveAllSlots"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="Object.keys($wire.unsavedChanges || {}).length > 0 ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                    wire:loading.attr="disabled"
                    wire:target="saveAllSlots"
                    x-bind:disabled="Object.keys($wire.unsavedChanges || {}).length === 0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="saveAllSlots">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" wire:loading wire:target="saveAllSlots">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="saveAllSlots">Save</span>
                    <span wire:loading wire:target="saveAllSlots">Saving...</span>
                </button>
            </div>

             <!-- Zoom Controls -->
             <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                 <button @click="fitToScreen()" class="px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 border-r border-gray-300 dark:border-gray-600 mr-1" title="Adust zoom to fit screen">
                     Fit
                 </button>
                 <button @click="zoom = Math.max(0.5, zoom - 0.1)" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded" title="Zoom Out">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                 </button>
                 <span class="text-xs w-8 text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                 <button @click="zoom = Math.min(1.5, zoom + 0.1)" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded" title="Zoom In">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                 </button>
             </div>
        </div>

        @if (session()->has('message'))
            <div class="mx-4 mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm text-green-900 dark:text-green-300">{{ session('message') }}</p>
            </div>
        @endif

        <div class="flex-1 overflow-auto p-4 transition-transform origin-top-left" :style="'zoom: ' + zoom">
            @if ($selectedClassId && $selectedTermId)
                <div class="min-w-[1100px] space-y-3">
                    <div class="grid items-stretch gap-2 sticky top-0 z-20 bg-white/90 dark:bg-gray-950/90 backdrop-blur"
                         style="grid-template-columns: 100px repeat({{ $periodsPerDay }}, minmax(140px, 1fr));">
                        <div class="h-full p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 sticky left-0 z-30 flex items-center justify-center">
                            <span class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Day</span>
                        </div>
                        @for ($period = 1; $period <= $periodsPerDay; $period++)
                            <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-center">
                                <div class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-200">Period</div>
                                <div class="text-xl font-bold text-blue-900 dark:text-blue-100">{{ $period }}</div>
                            </div>
                        @endfor
                    </div>

                    @foreach ($weekDates as $index => $dateInfo)
                        @php
                            $dateKey = $dateInfo['date']->format('Y-m-d');
                            $isToday = $dateInfo['date']->isToday();
                            $isSchoolDay = $dateInfo['isSchoolDay'] ?? true;
                        @endphp

                        @if ($isSchoolDay)
                            <div class="grid items-stretch gap-2"
                                 style="grid-template-columns: 100px repeat({{ $periodsPerDay }}, minmax(140px, 1fr));">
                                <div class="p-3 rounded-lg border bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 sticky left-0 z-10 flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        @if ($isToday)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-semibold">&bull;</span>
                                        @endif
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $dateInfo['dayName'] }}
                                        </span>
                                    </div>
                                </div>

                                @for ($period = 1; $period <= $periodsPerDay; $period++)
                                    @php
                                        $key = "{$dateKey}_{$period}";
                                        $slot = $timetableSlots[$key] ?? null;
                                        
                                        // Determine color classes based on subject type and period type
                                        $colorClasses = '';
                                        if ($slot && $slot->subject) {
                                            $subjectType = strtolower(str_replace('-', '_', $slot->subject->type ?? ''));
                                            
                                            if ($subjectType === 'core') {
                                                // Core subjects - Blue
                                                $colorClasses = 'bg-blue-50 dark:bg-blue-950 border-blue-300 dark:border-blue-600';
                                            } elseif ($subjectType === 'co_curricular') {
                                                // Co-curricular subjects - Green
                                                $colorClasses = 'bg-green-50 dark:bg-green-950 border-green-300 dark:border-green-600';
                                            } else {
                                                // Others - Gray
                                                $colorClasses = 'bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600';
                                            }
                                        }
                                    @endphp

                                    <div
                                        class="min-h-[100px] p-3 rounded-lg border-2 transition-all {{ $slot ? $colorClasses : 'border-dashed bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-700' }} {{ $slot && isset($slot->is_unsaved) && $slot->is_unsaved ? 'ring-2 ring-yellow-400 dark:ring-yellow-500' : '' }}"
                                        x-bind:class="{
                                            'hover:border-blue-400 dark:hover:border-blue-600': !draggedSubject && editMode,
                                            'border-blue-500 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20': draggedSubject && editMode,
                                            'cursor-move': editMode && {{ $slot && ! ($slot->is_locked ?? false) ? 'true' : 'false' }},
                                            'cursor-not-allowed opacity-60': !editMode
                                        }"
                                        x-bind:draggable="editMode && {{ $slot && ! ($slot->is_locked ?? false) ? 'true' : 'false' }}"
                                        @dragstart="startDragSlot('{{ $dateKey }}', {{ $period }})"
                                        @dragover.prevent="editMode && $event.preventDefault()"
                                        @drop.prevent="editMode && $wire.assignPeriod(draggedSubject, draggedTeacher, '{{ $dateKey }}', {{ $period }})">

                                        @if ($slot)
                                            <div class="h-full flex flex-col justify-between">
                                                <div class="space-y-1">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h4 class="font-semibold text-sm {{ $slot->subject?->type === 'core' ? 'text-blue-700 dark:text-blue-300' : ($slot->subject?->type === 'co_curricular' ? 'text-green-700 dark:text-green-300' : 'text-gray-900 dark:text-gray-100') }} line-clamp-2">
                                                            {{ $slot->subject?->code ?? 'N/A' }}
                                                            @if($slot->is_locked ?? false)
                                                                <span class="inline-block ml-1 px-1 py-0.5 text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded">LOCKED</span>
                                                            @endif
                                                        </h4>
                                                        <div class="flex gap-1" x-show="editMode">
                                                            <button
                                                                wire:click="toggleLockSlot('{{ $dateKey }}', {{ $period }})"
                                                                class="{{ ($slot->is_locked ?? false) ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300' : 'text-gray-400 hover:text-amber-600 dark:hover:text-amber-400' }}"
                                                                title="{{ ($slot->is_locked ?? false) ? 'Unlock slot' : 'Lock slot' }}">
                                                                @if($slot->is_locked ?? false)
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3 1.343 3 3v2a3 3 0 11-6 0v-2c0-1.657 1.343-3 3-3zm0 0V8a4 4 0 10-8 0v3"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V8a4 4 0 118 0v3m-9 0h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                                                                    </svg>
                                                                @endif
                                                            </button>
                                                            <button
                                                                wire:click="removeSlot('{{ $dateKey }}', {{ $period }})"
                                                                @disabled($slot->is_locked ?? false)
                                                                class="text-red-500 hover:text-red-700 dark:hover:text-red-400 disabled:opacity-40 disabled:cursor-not-allowed"
                                                                title="Remove subject">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                            <button
                                                                wire:click="editSlot('{{ $dateKey }}', {{ $period }})"
                                                                @disabled($slot->is_locked ?? false)
                                                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 disabled:opacity-40 disabled:cursor-not-allowed"
                                                                title="Edit slot">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $slot->teacher?->employee_id ?? 'No Teacher' }}</p>
                                                </div>

                                            <div class="flex items-center justify-between pt-2">
                                                @php
                                                    $isUnsaved = isset($slot->is_unsaved) && $slot->is_unsaved;
                                                    $isPublished = !$isUnsaved && ($slot->status ?? 'draft') === 'published';
                                                    $markerLabel = $isUnsaved ? 'UNSAVED' : ($isPublished ? 'PUBLISHED' : 'SAVED');
                                                    $markerClass = $isUnsaved
                                                        ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                        : ($isPublished
                                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                                            : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400');
                                                @endphp
                                                <button
                                                    wire:click="toggleStatus('{{ $dateKey }}', {{ $period }})"
                                                    x-bind:disabled="!editMode || {{ ($slot->is_locked ?? false) ? 'true' : 'false' }}"
                                                    class="px-2 py-1 text-xs font-medium rounded {{ $markerClass }}"
                                                    x-bind:class="{ 'opacity-50 cursor-not-allowed': !editMode }">
                                                    {{ $markerLabel }}
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="h-full flex flex-col items-center justify-center text-center text-gray-400 dark:text-gray-500 gap-2" x-show="editMode">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <button
                                                wire:click="editSlot('{{ $dateKey }}', {{ $period }})"
                                                class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                Add slot
                                            </button>
                                        </div>
                                        <div class="h-full flex items-center justify-center" x-show="!editMode">
                                            <span class="text-xs text-gray-400 dark:text-gray-600">Empty</span>
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="h-full flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 text-lg">Please select a class and term to begin</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($editingSlot)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="cancelEdit">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Edit Period</h3>
                    <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Subject
                        </label>
                        <select
                            wire:model="slotSubjectId"
                            class="block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @error('slotSubjectId')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Teacher
                        </label>
                        <select
                            wire:model="slotTeacherId"
                            class="block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        @error('slotTeacherId')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select
                            wire:model="slotStatus"
                            class="block w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    @if ($slotSubjectId)
                        <button
                            wire:click="deleteSlot('{{ explode('_', $editingSlot)[0] }}', {{ $slotPeriod }})"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Delete
                        </button>
                    @endif

                    <button
                        wire:click="cancelEdit"
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>

                    <button
                        wire:click="saveSlot"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Validation Modal -->
    @if ($showValidationModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeValidationModal">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        @if (count($validationErrors) > 0)
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">Validation Errors</h3>
                        @else
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100">Validation Warnings</h3>
                        @endif
                    </div>
                    <button wire:click="closeValidationModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    @if (count($validationErrors) > 0)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-100 mb-3">
                                {{ count($validationErrors) }} Error{{ count($validationErrors) > 1 ? 's' : '' }} Found
                            </h4>
                            <ul class="space-y-2">
                                @foreach ($validationErrors as $error)
                                    <li class="flex items-start gap-2 text-sm text-red-800 dark:text-red-200">
                                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>
                                            <strong class="font-medium">{{ $error['type'] ?? 'Error' }}:</strong>
                                            {{ $error['message'] }}
                                            @if (isset($error['context']))
                                                <span class="block text-xs mt-1 text-red-700 dark:text-red-300">{{ $error['context'] }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (count($validationWarnings) > 0)
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-3">
                                {{ count($validationWarnings) }} Warning{{ count($validationWarnings) > 1 ? 's' : '' }}
                            </h4>
                            <ul class="space-y-2">
                                @foreach ($validationWarnings as $warning)
                                    <li class="flex items-start gap-2 text-sm text-yellow-800 dark:text-yellow-200">
                                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>
                                            <strong class="font-medium">{{ $warning['type'] ?? 'Warning' }}:</strong>
                                            {{ $warning['message'] }}
                                            @if (isset($warning['context']))
                                                <span class="block text-xs mt-1 text-yellow-700 dark:text-yellow-300">{{ $warning['context'] }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (count($validationErrors) === 0 && count($validationWarnings) === 0)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <p class="text-sm text-green-800 dark:text-green-200">
                                All validation checks passed successfully!
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 mt-6">
                    <button
                        wire:click="closeValidationModal"
                        class="flex-1 px-4 py-2 {{ count($validationErrors) > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white text-sm font-medium rounded-lg transition-colors">
                        {{ count($validationErrors) > 0 ? 'Understand & Close' : 'Got it!' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
