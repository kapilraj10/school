<x-filament-panels::page>
    <div class="space-y-6" x-data="timetableDesigner()">
        {{-- Class and Term Selection Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit.prevent="loadTimetable">
                {{ $this->form }}
            </form>
        </div>

        @if($isLoading)
            <div class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>
        @elseif($selectedClassRoomId && $selectedTermId)
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Main Timetable Grid --}}
                <div class="lg:col-span-3 space-y-4">
                    {{-- Days and Periods Grid --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Timetable Grid</h3>
                            
                            {{-- Timetable Grid --}}
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100 dark:bg-gray-700">
                                            <th class="border border-gray-300 dark:border-gray-600 p-2 text-sm font-semibold">Day / Period</th>
                                            @for($period = 1; $period <= 8; $period++)
                                                <th class="border border-gray-300 dark:border-gray-600 p-2 text-sm font-semibold">
                                                    <div>P{{ $period }}</div>
                                                    @if(isset($periodTimes[$period]) && $periodTimes[$period])
                                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                            {{ $periodTimes[$period]['start'] }} - {{ $periodTimes[$period]['end'] }}
                                                        </div>
                                                    @endif
                                                </th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $day => $dayName)
                                            <tr>
                                                <td class="border border-gray-300 dark:border-gray-600 p-2 bg-gray-50 dark:bg-gray-700 font-medium text-sm">
                                                    {{ $dayName }}
                                                </td>
                                                @for($period = 1; $period <= 8; $period++)
                                                    <td class="border border-gray-300 dark:border-gray-600 p-1 relative">
                                                        @php
                                                            $slot = $timetableSlots[$day][$period] ?? null;
                                                            $isLocked = $slot && $slot['is_locked'];
                                                            $validationState = $this->getSlotValidationState($day, $period);
                                                            
                                                            // Get subject type colors if slot exists
                                                            $subjectTypeColors = null;
                                                            if ($slot && $slot['subject_id']) {
                                                                $subject = $subjects->firstWhere('id', $slot['subject_id']);
                                                                if ($subject) {
                                                                    $subjectTypeColors = $this->getSubjectTypeColor($subject->type ?? 'core');
                                                                }
                                                            }
                                                        @endphp
                                                        
                                                        <div 
                                                            class="min-h-24 p-2 rounded transition-all duration-200
                                                                {{ $slot && $subjectTypeColors ? $subjectTypeColors['bg'] . ' ' . $subjectTypeColors['border'] . ' border-2' : 'bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900' }}
                                                                {{ $isLocked ? 'border-2 border-yellow-500 opacity-75' : 'cursor-pointer' }}
                                                                {{ $validationState === 'error' ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-900/30' : '' }}
                                                                {{ $validationState === 'warning' ? 'ring-2 ring-orange-400' : '' }}"
                                                            wire:key="slot-{{ $day }}-{{ $period }}"
                                                            data-day="{{ $day }}"
                                                            data-period="{{ $period }}"
                                                            title="{{ $slot ? ($slot['subject_name'] . ' - ' . ($slot['teacher_name'] ?? 'No teacher')) : 'Empty slot' }}"
                                                            @if(!$isLocked)
                                                                @dragover.prevent="handleDragOver($event, {{ $day }}, {{ $period }})"
                                                                @dragleave="handleDragLeave($event)"
                                                                @drop.prevent="handleDrop($event, {{ $day }}, {{ $period }})"
                                                            @endif
                                                            @if($slot && !$isLocked)
                                                                draggable="true"
                                                                @dragstart="handleDragStart($event, {{ $day }}, {{ $period }}, {{ $slot['subject_id'] }}, {{ $slot['teacher_id'] ?? 'null' }})"
                                                                @dragend="handleDragEnd($event)"
                                                            @endif
                                                            :class="{ 
                                                                'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30': isDragOver({{ $day }}, {{ $period }}),
                                                                'opacity-50': isDraggingFrom({{ $day }}, {{ $period }}),
                                                                'ring-4 ring-red-500 animate-pulse bg-red-100 dark:bg-red-900/50': isSlotHighlighted({{ $day }}, {{ $period }}),
                                                                'animate-[pulse_0.5s_ease-in-out]': wasJustUpdated({{ $day }}, {{ $period }})
                                                            }"
                                                        >
                                                            @if($slot)
                                                                <div class="space-y-1">
                                                                    {{-- Subject Info with Type Badge --}}
                                                                    <div class="flex items-start justify-between gap-1">
                                                                        <div class="font-semibold text-sm {{ $subjectTypeColors ? $subjectTypeColors['text'] : 'text-gray-900 dark:text-gray-100' }}">
                                                                            {{ $slot['subject_code'] ?? $slot['subject_name'] }}
                                                                        </div>
                                                                        {{-- Validation indicator --}}
                                                                        @if($validationState === 'valid')
                                                                            <span class="text-green-500 text-xs" title="Valid slot">✓</span>
                                                                        @elseif($validationState === 'error')
                                                                            <span class="text-red-500 text-xs" title="Has errors">⚠</span>
                                                                        @elseif($validationState === 'warning')
                                                                            <span class="text-orange-500 text-xs" title="Has warnings">⚡</span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    {{-- Teacher Info --}}
                                                                    @if($slot['teacher_name'])
                                                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                            👤 {{ $slot['teacher_name'] }}
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    {{-- Action Buttons --}}
                                                                    <div class="flex gap-1 mt-2">
                                                                        <button 
                                                                            type="button"
                                                                            wire:click="toggleLockSlot({{ $day }}, {{ $period }})"
                                                                            class="text-xs px-2 py-1 rounded bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                                            title="{{ $slot['is_locked'] ? 'Unlock' : 'Lock' }}"
                                                                        >
                                                                            @if($slot['is_locked'])
                                                                                🔒
                                                                            @else
                                                                                🔓
                                                                            @endif
                                                                        </button>
                                                                        
                                                                        @if(!$slot['is_locked'])
                                                                            <button 
                                                                                type="button"
                                                                                wire:click="removeSlot({{ $day }}, {{ $period }})"
                                                                                class="text-xs px-2 py-1 rounded bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400"
                                                                                title="Remove"
                                                                            >
                                                                                ✕
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="text-center text-gray-400 dark:text-gray-600 text-xs py-8">
                                                                    Empty
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Hard Constraint Violations (Errors) --}}
                    @if(count($validationErrors) > 0)
                        <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg shadow p-6" x-data="{ expanded: true }">
                            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="expanded = !expanded">
                                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Hard Constraints Violated ({{ count($validationErrors) }})
                                </h3>
                                <svg class="w-5 h-5 text-red-600 transition-transform" :class="{ 'rotate-180': !expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            
                            <div x-show="expanded" x-transition class="space-y-3">
                                <div class="text-sm text-red-600 dark:text-red-400 font-medium mb-3 px-3 py-2 bg-red-100 dark:bg-red-900/30 rounded">
                                    ⛔ These errors must be fixed before saving the timetable
                                </div>
                                
                                @foreach($validationErrors as $index => $error)
                                    <div class="flex items-start gap-3 text-sm p-3 bg-white dark:bg-gray-800 rounded border border-red-200 dark:border-red-800 hover:shadow-md transition-shadow
                                        @if(isset($error['day']) && isset($error['period']))
                                            cursor-pointer
                                        @endif"
                                        @if(isset($error['day']) && isset($error['period']))
                                            @click="highlightSlot({{ $error['day'] }}, {{ $error['period'] }})"
                                        @elseif(isset($error['periods']) && is_array($error['periods']) && isset($error['day']))
                                            @click="highlightSlots({{ $error['day'] }}, {{ json_encode($error['periods']) }})"
                                        @endif
                                    >
                                        <span class="text-red-600 dark:text-red-400 text-lg shrink-0">🚫</span>
                                        <div class="flex-1">
                                            <div class="font-medium text-red-900 dark:text-red-200 mb-1">
                                                @if(isset($error['type']))
                                                    <span class="text-xs px-2 py-1 bg-red-200 dark:bg-red-800 rounded">{{ strtoupper(str_replace('_', ' ', $error['type'])) }}</span>
                                                @endif
                                            </div>
                                            @if(isset($error['day_name']))
                                                <span class="font-semibold text-red-700 dark:text-red-300">{{ $error['day_name'] }}</span>
                                                @if(isset($error['period']))
                                                    <span class="text-red-600 dark:text-red-400"> - Period {{ $error['period'] }}</span>
                                                @endif
                                                <span class="text-red-600 dark:text-red-400">: </span>
                                            @endif
                                            <span class="text-red-800 dark:text-red-300">{{ $error['message'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Soft Constraint Warnings (Optimization Suggestions) --}}
                    @if(count($validationWarnings) > 0)
                        <div class="bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-lg shadow p-6" x-data="{ expanded: true }">
                            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="expanded = !expanded">
                                <h3 class="text-lg font-semibold text-orange-700 dark:text-orange-400 flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Optimization Suggestions ({{ count($validationWarnings) }})
                                </h3>
                                <svg class="w-5 h-5 text-orange-600 transition-transform" :class="{ 'rotate-180': !expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            
                            <div x-show="expanded" x-transition class="space-y-3">
                                <div class="text-sm text-orange-600 dark:text-orange-400 font-medium mb-3 px-3 py-2 bg-orange-100 dark:bg-orange-900/30 rounded">
                                    💡 These warnings suggest improvements but don't prevent saving
                                </div>
                                
                                @php
                                    $severityColors = [
                                        'low' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/10', 'border' => 'border-yellow-200 dark:border-yellow-800', 'icon' => '⚡', 'text' => 'text-yellow-800 dark:text-yellow-300'],
                                        'medium' => ['bg' => 'bg-orange-50 dark:bg-orange-900/10', 'border' => 'border-orange-200 dark:border-orange-800', 'icon' => '⚠️', 'text' => 'text-orange-800 dark:text-orange-300'],
                                        'info' => ['bg' => 'bg-blue-50 dark:bg-blue-900/10', 'border' => 'border-blue-200 dark:border-blue-800', 'icon' => 'ℹ️', 'text' => 'text-blue-800 dark:text-blue-300'],
                                    ];
                                @endphp
                                
                                @foreach($validationWarnings as $index => $warning)
                                    @php
                                        $severity = $warning['severity'] ?? 'medium';
                                        $colors = $severityColors[$severity] ?? $severityColors['medium'];
                                    @endphp
                                    <div class="flex items-start gap-3 text-sm p-3 {{ $colors['bg'] }} rounded border {{ $colors['border'] }} hover:shadow-md transition-shadow
                                        @if(isset($warning['day']) && isset($warning['period']))
                                            cursor-pointer
                                        @endif"
                                        @if(isset($warning['day']) && isset($warning['period']))
                                            @click="highlightSlot({{ $warning['day'] }}, {{ $warning['period'] }})"
                                        @endif
                                    >
                                        <span class="text-lg shrink-0">{{ $colors['icon'] }}</span>
                                        <div class="flex-1">
                                            <div class="font-medium mb-1">
                                                @if(isset($warning['type']))
                                                    <span class="text-xs px-2 py-1 bg-orange-200 dark:bg-orange-800 rounded">{{ strtoupper(str_replace('_', ' ', $warning['type'])) }}</span>
                                                @endif
                                                @if(isset($warning['severity']))
                                                    <span class="text-xs px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded ml-1">{{ strtoupper($severity) }}</span>
                                                @endif
                                            </div>
                                            <span class="{{ $colors['text'] }}">{{ $warning['message'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Subject Palette Sidebar --}}
                <div class="lg:col-span-1 space-y-4">
                    {{-- Subjects Palette --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Subjects</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($subjects as $subject)
                                @php
                                    $colors = $this->getSubjectTypeColor($subject->type ?? 'core');
                                    $placementCount = $this->getSubjectPlacementCount($subject->id);
                                    $constraintStatus = $constraintStatus[$subject->id] ?? null;
                                @endphp
                                <div 
                                    class="p-3 {{ $colors['bg'] }} {{ $colors['border'] }} border-2 rounded-lg cursor-move hover:shadow-md transition-all duration-200 relative"
                                    draggable="true"
                                    wire:key="subject-{{ $subject->id }}"
                                    @dragstart="handleSubjectDragStart($event, {{ $subject->id }})"
                                    @dragend="handleDragEnd($event)"
                                    :class="{ 'opacity-50 scale-95': isDraggingSubject({{ $subject->id }}) }"
                                >
                                    {{-- Subject Type Badge --}}
                                    <div class="absolute top-2 right-2">
                                        <span class="text-xs px-2 py-1 rounded {{ $colors['badge'] }}">
                                            {{ strtoupper(str_replace('_', ' ', $subject->type ?? 'CORE')) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-start gap-2 pr-16">
                                        <span class="text-gray-400 text-lg">⋮⋮</span>
                                        <div class="flex-1">
                                            <div class="font-semibold text-sm {{ $colors['text'] }}">
                                                {{ $subject->code }}
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $subject->name }}
                                            </div>
                                            
                                            {{-- Weekly requirements --}}
                                            @if($subject->weekly_periods)
                                                <div class="flex items-center gap-2 mt-2">
                                                    <div class="text-xs {{ $colors['text'] }}">
                                                        📅 {{ $subject->weekly_periods }}/week
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            {{-- Placement count --}}
                                            <div class="flex items-center gap-2 mt-1">
                                                <div class="text-xs font-medium">
                                                    @if($constraintStatus && $constraintStatus['satisfied'])
                                                        <span class="text-green-600 dark:text-green-400">
                                                            ✓ {{ $placementCount }}/{{ $subject->weekly_periods }} placed
                                                        </span>
                                                    @elseif($placementCount > 0)
                                                        <span class="text-orange-600 dark:text-orange-400">
                                                            ⚡ {{ $placementCount }}/{{ $subject->weekly_periods }} placed
                                                        </span>
                                                    @else
                                                        <span class="text-gray-500 dark:text-gray-400">
                                                            0/{{ $subject->weekly_periods }} placed
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            {{-- Progress bar --}}
                                            @if($constraintStatus && $subject->weekly_periods > 0)
                                                <div class="mt-2">
                                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                        <div class="h-1.5 rounded-full transition-all duration-300
                                                            {{ $constraintStatus['satisfied'] ? 'bg-green-500' : 'bg-orange-500' }}"
                                                            style="width: {{ min($constraintStatus['percentage'], 100) }}%">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No subjects available</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Constraint Status Panel --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" x-data="{ expanded: true }">
                        <div class="flex items-center justify-between mb-4 cursor-pointer" @click="expanded = !expanded">
                            <h3 class="text-lg font-semibold flex items-center gap-2">
                                <span>📊</span> Constraint Status
                            </h3>
                            <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': !expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        
                        <div x-show="expanded" x-transition class="space-y-3 max-h-80 overflow-y-auto">
                            @forelse($subjects as $subject)
                                @php
                                    $status = $constraintStatus[$subject->id] ?? null;
                                    $colors = $this->getSubjectTypeColor($subject->type ?? 'core');
                                @endphp
                                @if($status)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border {{ $colors['border'] }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="font-medium text-sm {{ $colors['text'] }}">
                                                {{ $subject->code }}
                                            </div>
                                            @if($status['satisfied'])
                                                <span class="text-green-500 text-lg" title="Requirement satisfied">✓</span>
                                            @elseif($status['status'] === 'partial')
                                                <span class="text-orange-500 text-lg" title="Partially satisfied">⚡</span>
                                            @else
                                                <span class="text-red-500 text-lg" title="Not satisfied">✗</span>
                                            @endif
                                        </div>
                                        
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            {{ $status['placed'] }}/{{ $status['required'] }} periods assigned
                                        </div>
                                        
                                        {{-- Progress bar --}}
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300
                                                {{ $status['satisfied'] ? 'bg-green-500' : ($status['status'] === 'partial' ? 'bg-orange-500' : 'bg-red-500') }}"
                                                style="width: {{ min($status['percentage'], 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No constraint data</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Teachers List --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Teachers</h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($teachers as $teacher)
                                <div 
                                    class="p-2 bg-gray-50 dark:bg-gray-900 rounded text-sm"
                                    wire:key="teacher-{{ $teacher->id }}"
                                >
                                    <div class="font-medium">{{ $teacher->name }}</div>
                                    @if($teacher->subjects->count() > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $teacher->subjects->pluck('code')->join(', ') }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No teachers available</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Statistics</h3>
                        <div class="space-y-3">
                            @php
                                $filledSlots = 0;
                                $lockedSlots = 0;
                                foreach($timetableSlots as $day => $periods) {
                                    foreach($periods as $period => $slot) {
                                        if($slot) {
                                            $filledSlots++;
                                            if($slot['is_locked']) $lockedSlots++;
                                        }
                                    }
                                }
                                $totalSlots = 48; // 6 days × 8 periods
                                $fillPercentage = round(($filledSlots / $totalSlots) * 100);
                            @endphp
                            
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Filled Slots</div>
                                <div class="text-2xl font-bold">{{ $filledSlots }} / {{ $totalSlots }}</div>
                                <div class="text-xs text-gray-500">{{ $fillPercentage }}% complete</div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Locked Slots</div>
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $lockedSlots }}</div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Errors</div>
                                <div class="text-2xl font-bold {{ count($validationErrors) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ count($validationErrors) }}
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Warnings</div>
                                <div class="text-2xl font-bold {{ count($validationWarnings ?? []) > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ count($validationWarnings ?? []) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <div class="text-gray-400 dark:text-gray-600 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Select a Class and Term
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Choose a class room and academic term from the form above to start designing the timetable.
                </p>
            </div>
        @endif

        {{-- Teacher Selection Modal --}}
        <div 
            x-show="showTeacherModal" 
            x-cloak
            @click.self="closeTeacherModal()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            style="display: none;"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div 
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
                @click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <span>👤</span> Select Teacher
                    </h3>
                    <button 
                        @click="closeTeacherModal()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                {{-- Search input --}}
                <div class="mb-4">
                    <input 
                        type="text" 
                        x-model="teacherSearch"
                        placeholder="Search teachers..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                </div>
                
                <div class="space-y-2 max-h-96 overflow-y-auto mb-4">
                    <template x-for="teacher in filteredTeachers" :key="teacher.id">
                        <button
                            type="button"
                            @click="selectTeacher(teacher.id)"
                            class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-gray-900 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all duration-200 border border-transparent hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-md"
                        >
                            <div class="font-medium text-gray-900 dark:text-gray-100" x-text="teacher.name"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="teacher.subjects"></div>
                        </button>
                    </template>
                    
                    <template x-if="filteredTeachers.length === 0">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <div class="text-4xl mb-2">🔍</div>
                            <div>No teachers found</div>
                        </div>
                    </template>
                </div>
                
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="closeTeacherModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function timetableDesigner() {
            return {
                draggedItem: null,
                draggedFrom: null,
                dragOverCell: null,
                showTeacherModal: false,
                pendingAssignment: null,
                availableTeachers: @js($teachers->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subjects' => $t->subjects->pluck('code')->join(', ')
                ])->values()),
                highlightedSlots: [],
                recentlyUpdatedSlots: [],
                teacherSearch: '',
                
                // Computed property for filtered teachers
                get filteredTeachers() {
                    if (!this.teacherSearch) {
                        return this.availableTeachers;
                    }
                    const search = this.teacherSearch.toLowerCase();
                    return this.availableTeachers.filter(teacher => 
                        teacher.name.toLowerCase().includes(search) || 
                        teacher.subjects.toLowerCase().includes(search)
                    );
                },
                
                // Mark slot as recently updated with pulse animation
                markSlotUpdated(day, period) {
                    const key = `${day}-${period}`;
                    this.recentlyUpdatedSlots.push(key);
                    
                    // Remove after animation completes
                    setTimeout(() => {
                        const index = this.recentlyUpdatedSlots.indexOf(key);
                        if (index > -1) {
                            this.recentlyUpdatedSlots.splice(index, 1);
                        }
                    }, 500);
                },
                
                // Check if slot was just updated
                wasJustUpdated(day, period) {
                    return this.recentlyUpdatedSlots.includes(`${day}-${period}`);
                },
                
                // Highlight a single slot when error is clicked
                highlightSlot(day, period) {
                    this.highlightedSlots = [{ day, period }];
                    this.scrollToSlot(day, period);
                    // Clear highlight after 3 seconds
                    setTimeout(() => {
                        this.highlightedSlots = [];
                    }, 3000);
                },
                
                // Highlight multiple slots when error is clicked
                highlightSlots(day, periods) {
                    this.highlightedSlots = periods.map(p => ({ day, period: p }));
                    if (periods.length > 0) {
                        this.scrollToSlot(day, periods[0]);
                    }
                    // Clear highlight after 3 seconds
                    setTimeout(() => {
                        this.highlightedSlots = [];
                    }, 3000);
                },
                
                // Scroll to a specific slot
                scrollToSlot(day, period) {
                    const element = document.querySelector(`[data-day="${day}"][data-period="${period}"]`);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                },
                
                // Check if a slot is highlighted
                isSlotHighlighted(day, period) {
                    return this.highlightedSlots.some(s => s.day === day && s.period === period);
                },
                
                // Handle dragging a subject from the sidebar
                handleSubjectDragStart(event, subjectId) {
                    this.draggedItem = {
                        type: 'subject',
                        subjectId: subjectId
                    };
                    event.dataTransfer.effectAllowed = 'copy';
                    event.target.classList.add('opacity-50', 'scale-95');
                },
                
                // Handle dragging an existing slot
                handleDragStart(event, day, period, subjectId, teacherId) {
                    this.draggedItem = {
                        type: 'slot',
                        subjectId: subjectId,
                        teacherId: teacherId
                    };
                    this.draggedFrom = { day, period };
                    event.dataTransfer.effectAllowed = 'move';
                    event.target.classList.add('opacity-50', 'scale-95');
                },
                
                handleDragEnd(event) {
                    event.target.classList.remove('opacity-50', 'scale-95');
                    this.draggedItem = null;
                    this.draggedFrom = null;
                    this.dragOverCell = null;
                },
                
                handleDragOver(event, day, period) {
                    if (!this.draggedItem) return;
                    event.preventDefault();
                    this.dragOverCell = { day, period };
                    event.dataTransfer.dropEffect = this.draggedItem.type === 'slot' ? 'move' : 'copy';
                },
                
                handleDragLeave(event) {
                    this.dragOverCell = null;
                },
                
                handleDrop(event, day, period) {
                    event.preventDefault();
                    this.dragOverCell = null;
                    
                    if (!this.draggedItem) return;
                    
                    if (this.draggedItem.type === 'subject') {
                        // Dropping a new subject - need to select teacher
                        this.pendingAssignment = {
                            day: day,
                            period: period,
                            subjectId: this.draggedItem.subjectId
                        };
                        this.openTeacherModal(this.draggedItem.subjectId);
                    } else if (this.draggedItem.type === 'slot') {
                        // Swapping or moving an existing slot
                        if (this.draggedFrom.day === day && this.draggedFrom.period === period) {
                            // Dropped on same cell, do nothing
                            return;
                        }
                        
                        // Mark both slots as updated
                        this.markSlotUpdated(this.draggedFrom.day, this.draggedFrom.period);
                        this.markSlotUpdated(day, period);
                        
                        @this.swapSlots(
                            this.draggedFrom.day,
                            this.draggedFrom.period,
                            day,
                            period
                        );
                    }
                    
                    this.draggedItem = null;
                    this.draggedFrom = null;
                },
                
                openTeacherModal(subjectId) {
                    // Reset search
                    this.teacherSearch = '';
                    
                    // Filter teachers who can teach this subject
                    const subject = @js($subjects->values());
                    const subjectData = subject.find(s => s.id === subjectId);
                    
                    if (subjectData && subjectData.teachers && subjectData.teachers.length > 0) {
                        this.availableTeachers = subjectData.teachers.map(t => ({
                            id: t.id,
                            name: t.name,
                            subjects: t.subjects ? t.subjects.map(s => s.code).join(', ') : ''
                        }));
                    } else {
                        // If no specific teachers, show all teachers
                        this.availableTeachers = @js($teachers->map(fn($t) => [
                            'id' => $t->id,
                            'name' => $t->name,
                            'subjects' => $t->subjects->pluck('code')->join(', ')
                        ])->values());
                    }
                    
                    this.showTeacherModal = true;
                },
                
                selectTeacher(teacherId) {
                    if (!this.pendingAssignment) return;
                    
                    // Mark slot as updated
                    this.markSlotUpdated(this.pendingAssignment.day, this.pendingAssignment.period);
                    
                    @this.assignSlot(
                        this.pendingAssignment.day,
                        this.pendingAssignment.period,
                        this.pendingAssignment.subjectId,
                        teacherId
                    );
                    
                    this.closeTeacherModal();
                },
                
                closeTeacherModal() {
                    this.showTeacherModal = false;
                    this.pendingAssignment = null;
                    this.teacherSearch = '';
                },
                
                isDragOver(day, period) {
                    return this.dragOverCell && 
                           this.dragOverCell.day === day && 
                           this.dragOverCell.period === period;
                },
                
                isDraggingFrom(day, period) {
                    return this.draggedFrom && 
                           this.draggedFrom.day === day && 
                           this.draggedFrom.period === period;
                },
                
                isDraggingSubject(subjectId) {
                    return this.draggedItem && 
                           this.draggedItem.type === 'subject' && 
                           this.draggedItem.subjectId === subjectId;
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
        
        /* Custom animations */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        /* Smooth transitions for all interactive elements */
        [draggable="true"] {
            transition: all 0.2s ease;
        }
        
        [draggable="true"]:hover {
            transform: scale(1.02);
        }
        
        /* Pulse animation for updated slots */
        @keyframes pulse-success {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.8;
                transform: scale(1.05);
            }
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar for subject list */
        .space-y-2::-webkit-scrollbar,
        .max-h-96::-webkit-scrollbar,
        .max-h-80::-webkit-scrollbar {
            width: 8px;
        }
        
        .space-y-2::-webkit-scrollbar-track,
        .max-h-96::-webkit-scrollbar-track,
        .max-h-80::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .space-y-2::-webkit-scrollbar-thumb,
        .max-h-96::-webkit-scrollbar-thumb,
        .max-h-80::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 4px;
        }
        
        .space-y-2::-webkit-scrollbar-thumb:hover,
        .max-h-96::-webkit-scrollbar-thumb:hover,
        .max-h-80::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.7);
        }
        
        /* Dark mode scrollbar */
        .dark .space-y-2::-webkit-scrollbar-thumb,
        .dark .max-h-96::-webkit-scrollbar-thumb,
        .dark .max-h-80::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.5);
        }
        
        .dark .space-y-2::-webkit-scrollbar-thumb:hover,
        .dark .max-h-96::-webkit-scrollbar-thumb:hover,
        .dark .max-h-80::-webkit-scrollbar-thumb:hover {
            background: rgba(75, 85, 99, 0.7);
        }
    </style>
</x-filament-panels::page>
