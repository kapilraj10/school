<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - School Timetable</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/home.js'])

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-linear-to-br from-gray-50 to-blue-50 dark:from-gray-950 dark:to-gray-950 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-12 mt-8">
            <img src="/timetable_logo.png" alt="Timetable Logo" class="mx-auto mb-4 w-24 h-24 object-contain" />
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                {{ config('app.name', 'School Timetable') }}
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">Creating timetable made easy</p>
            
            <div class="flex justify-center items-center gap-4 mt-6">
                @php
                    $adminLabel = auth()->check() ? 'Admin Panel' : 'Go to Admin Panel';
                @endphp

                <a href="/admin" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ $adminLabel }}
                </a>
                
                <button onclick="toggleTheme()" class="inline-flex items-center px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors border border-gray-300 dark:border-gray-600 shadow-md">
                    <svg class="w-5 h-5 mr-2 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-5 h-5 mr-2 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span class="hidden dark:block">Light</span>
                    <span class="block dark:hidden">Dark</span>
                </button>
            </div>
        </div>

        {{-- @if ($academicTerms->count() > 1)
            <div class="mb-6 flex justify-center">
                <div class="w-full max-w-md">
                    <label for="term-selector" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-center">
                        Academic Term
                    </label>
                    <select 
                        id="term-selector" 
                        onchange="switchTerm()"
                        class="block w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                    >
                        @foreach ($academicTerms as $term)
                            <option value="{{ $term->id }}" {{ $currentTerm && $currentTerm->id === $term->id ? 'selected' : '' }}>
                                {{ $term->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        @if ($currentTerm)
            <div class="mb-8 max-w-2xl mx-auto">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-center">
                    <p class="text-sm text-blue-900 dark:text-blue-300">
                        <span class="font-semibold">Current Term:</span> {{ $currentTerm->full_name }}
                    </p>
                </div>
            </div>
        @else
            <div class="mb-8 max-w-2xl mx-auto">
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-center">
                    <p class="text-sm text-amber-900 dark:text-amber-300">
                        No active academic term found. Please contact your administrator.
                    </p>
                </div>
            </div>
        @endif --}}

        @if (blank($classes))
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-md max-w-2xl mx-auto">
                <svg class="mx-auto h-20 w-20 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-gray-100">No classes found</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Please contact your administrator to add classes.</p>
            </div>
        @else
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-4 text-center">Select a Class</h2>
                
                <div class="flex flex-wrap gap-3 justify-center max-w-4xl mx-auto">
                    @foreach ($classes as $index => $class)
                        <button
                            type="button"
                            data-class-button="{{ $class->id }}"
                            onclick="switchClass('{{ $class->id }}')"
                            class="px-6 py-3 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 transition-all shadow-sm hover:shadow-md {{ $index === 0 ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        >
                            {{ $class->full_name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                @foreach ($classes as $index => $class)
                    <div 
                        data-class-card="{{ $class->id }}"
                        class="{{ $index === 0 ? '' : 'hidden' }}"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-5 bg-linear-to-r from-blue-600 to-blue-700 text-white">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                                    <div>
                                        <h3 class="text-2xl font-bold">
                                            {{ $class->full_name }} Timetable
                                        </h3>
                                        @if ($class->classTeacher)
                                            <p class="text-sm text-blue-100 mt-1">
                                                Class Teacher: {{ $class->classTeacher->name }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 self-start">
                                        <button id="toggle-teacher-btn" onclick="toggleTeacherNames()" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-3 text-xs font-medium text-white shadow-sm ring-1 ring-white/20 transition-colors hover:bg-white/15" title="Toggle Teacher Names">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Teacher Names</span>
                                        </button>

                                        <div class="flex items-center gap-1.5 rounded-lg bg-white/10 p-1.5 shadow-sm ring-1 ring-white/20">
                                            <button onclick="decreaseFontSize()" class="p-1.5 hover:bg-white/10 rounded transition-colors" title="Decrease Font Size">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>
                                            <span class="text-xs font-medium text-white w-11 text-center font-size-display">100%</span>
                                            <button onclick="increaseFontSize()" class="p-1.5 hover:bg-white/10 rounded transition-colors" title="Increase Font Size">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 timetable-container">
                                <x-timetable-grid 
                                    :slots="$timetableSlots->get($class->id) ?? collect([])"
                                    :className="$class->full_name"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <footer class="mt-16 py-8 border-t border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'School Timetable Management System - Animesh Shakya') }}. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
