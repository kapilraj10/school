<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Master your school schedule with our automated timetable generator designed for Nepal. Conflict-free scheduling, teacher workload management, and mobile-friendly views.">
        <meta name="keywords" content="Automated School Timetable Generator Nepal, School Management Software Kathmandu, Conflict-free Teacher Scheduling">
        
        <title>School Timetable Management System - Automated Generator Nepal</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        <style>
            [x-cloak] { display: none !important; }
        </style>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    darkMode: 'media', 
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Instrument Sans', 'sans-serif'],
                            },
                            colors: {
                                primary: {
                                    50: '#eff6ff',
                                    100: '#dbeafe',
                                    200: '#bfdbfe',
                                    300: '#93c5fd',
                                    400: '#60a5fa',
                                    500: '#3b82f6',
                                    600: '#2563eb',
                                    700: '#1d4ed8',
                                    800: '#1e40af',
                                    900: '#1e3a8a',
                                    950: '#172554',
                                }
                            }
                        }
                    }
                }
            </script>
        @endif
    </head>
    <body class="antialiased bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 font-sans selection:bg-primary-500 selection:text-white">

        <!-- Navigation -->
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 p-1.5 flex items-center gap-2">
                        <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-indigo-600 bg-clip-text text-transparent">TimetableNepal</span>
                    </a>
                </div>
                <div class="hidden lg:flex lg:gap-x-12">
                    <a href="#features" class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400">Features</a>
                    <a href="#local" class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400">For Nepal</a>
                    <a href="#faq" class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400">FAQ</a>
                </div>
                <div class="flex flex-1 justify-end gap-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-primary-600">Dashboard <span aria-hidden="true">&rarr;</span></a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-primary-600 hidden md:block">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </nav>
        </header>

        <main class="isolate">
            <!-- Hero Section -->
            <div class="relative pt-14">
                <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                    <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>
                <div class="py-24 sm:py-32 lg:pb-40">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        <div class="mx-auto max-w-2xl text-center">
                            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">Master Your School Schedule in Minutes, Not Days</h1>
                            <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                Built specifically for the Nepali education system with full Sunday-Friday support. Say goodbye to manual conflict checking and hello to automated efficiency.
                            </p>
                            <div class="mt-10 flex items-center justify-center gap-x-6">
                                <a href="#" class="rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">Book a Live Demo</a>
                                <a href="#features" class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100">View Sample Timetable <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                        <div class="mt-16 flow-root sm:mt-24">
                            <div class="-m-2 rounded-xl bg-gray-900/5 p-2 ring-1 ring-inset ring-gray-900/10 dark:bg-white/5 dark:ring-white/10 lg:-m-4 lg:rounded-2xl lg:p-4">
                                <div class="rounded-md bg-white dark:bg-gray-800 p-8 shadow-2xl ring-1 ring-gray-900/10 dark:ring-white/10 min-h-[300px] flex items-center justify-center text-gray-400">
                                    <!-- Placeholder for Screenshot -->
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-100">Application Dashboard Screenshot</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                    <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>
            </div>

            <!-- Features Section -->
            <div id="features" class="mx-auto max-w-7xl px-6 lg:px-8 py-24 sm:py-32">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2 class="text-base font-semibold leading-7 text-primary-600">Powerful Features</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Everything you need to manage school time</p>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400">
                        Designed to handle the complexity of Nepali schools, from simple class schedules to complex elective management.
                    </p>
                </div>
                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </div>
                                Automatic Conflict Detection
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Instantly identifies double-booked teachers or rooms. Our algorithm ensures every class has a teacher and a room.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                                Teacher Workload Management
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Balance classes fairly across your staff. Track total hours and ensure no teacher is overloaded.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                    </svg>
                                </div>
                                PDF Export for Classrooms
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Generate print-ready PDFs for each classroom noticeboard and individual teacher schedules.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </div>
                                Mobile-Friendly Views
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Teachers can check their upcoming classes on their phones. Real-time updates mean everyone stays informed.</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Local Relevance Section -->
            <div id="local" class="bg-gray-100 dark:bg-gray-900 py-24 sm:py-32">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:mx-0">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Designed for Nepal 🇳🇵</h2>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                            We understand the unique requirements of Nepali schools. Our system is built to handle:
                        </p>
                    </div>
                    <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 text-base leading-7 sm:grid-cols-2 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <div>
                            <h3 class="border-l-4 border-primary-600 pl-4 font-semibold text-gray-900 dark:text-white">Sunday-Friday Work Week</h3>
                            <p class="mt-2 pl-4 text-gray-600 dark:text-gray-400">Default settings optimized for the Sunday to Friday school week, with Friday often being a half-day.</p>
                        </div>
                        <div>
                            <h3 class="border-l-4 border-primary-600 pl-4 font-semibold text-gray-900 dark:text-white">Multi-Shift Support</h3>
                            <p class="mt-2 pl-4 text-gray-600 dark:text-gray-400">Easily manage Morning (College/plus2) and Day (School) shifts within the same system.</p>
                        </div>
                        <div>
                            <h3 class="border-l-4 border-primary-600 pl-4 font-semibold text-gray-900 dark:text-white">Local Calendar & Holidays</h3>
                            <p class="mt-2 pl-4 text-gray-600 dark:text-gray-400">Integrated Nepali holiday calendar to automatically adjust schedules for festivals and public holidays.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Proof -->
            <div class="py-24 sm:py-32">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <h2 class="text-center text-lg font-semibold leading-8 text-gray-900 dark:text-white">Trusted by Schools across Nepal</h2>
                    <div class="mx-auto mt-10 grid max-w-lg grid-cols-4 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-5">
                       <div class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 text-center font-bold text-gray-400 text-xl grayscale opacity-70">Sagarmatha School</div>
                       <div class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 text-center font-bold text-gray-400 text-xl grayscale opacity-70">Kathmandu Academy</div>
                       <div class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 text-center font-bold text-gray-400 text-xl grayscale opacity-70">Pokhara Model</div>
                       <div class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 text-center font-bold text-gray-400 text-xl grayscale opacity-70">Butwal Secondary</div>
                       <div class="col-span-2 max-h-12 w-full object-contain lg:col-span-1 text-center font-bold text-gray-400 text-xl grayscale opacity-70">Lalitpur Int'l</div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div id="faq" class="mx-auto max-w-7xl px-6 lg:px-8 py-16 sm:py-24" x-data="{ openFaq: null }">
                <div class="mx-auto max-w-4xl divide-y divide-gray-900/10 dark:divide-white/10">
                    <h2 class="text-2xl font-bold leading-10 tracking-tight text-gray-900 dark:text-white">Frequently Asked Questions</h2>
                    <dl class="mt-10 space-y-6 divide-y divide-gray-900/10 dark:divide-white/10">
                        <div class="pt-6">
                            <dt>
                                <button type="button" @click="openFaq === 0 ? openFaq = null : openFaq = 0" class="flex w-full items-start justify-between text-left text-gray-900 dark:text-white" aria-controls="faq-0" :aria-expanded="openFaq === 0">
                                    <span class="text-base font-semibold leading-7">How long does it take to set up?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        <svg class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': openFaq === 0 }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </span>
                                </button>
                            </dt>
                            <dd class="mt-2 pr-12" id="faq-0" x-show="openFaq === 0" x-cloak>
                                <p class="text-base leading-7 text-gray-600 dark:text-gray-400">You can have your first draft timetable in less than 30 minutes. Simply import your teachers, classes, and subjects, and let our algorithm do the rest.</p>
                            </dd>
                        </div>
                        <div class="pt-6">
                            <dt>
                                <button type="button" @click="openFaq === 1 ? openFaq = null : openFaq = 1" class="flex w-full items-start justify-between text-left text-gray-900 dark:text-white" aria-controls="faq-1" :aria-expanded="openFaq === 1">
                                    <span class="text-base font-semibold leading-7">Does it support elective subjects?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        <svg class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </span>
                                </button>
                            </dt>
                            <dd class="mt-2 pr-12" id="faq-1" x-show="openFaq === 1" x-cloak>
                                <p class="text-base leading-7 text-gray-600 dark:text-gray-400">Yes! We support complex groupings for optional/elective subjects where students from different sections merge into different classes.</p>
                            </dd>
                        </div>
                        <div class="pt-6">
                            <dt>
                                <button type="button" @click="openFaq === 2 ? openFaq = null : openFaq = 2" class="flex w-full items-start justify-between text-left text-gray-900 dark:text-white" aria-controls="faq-2" :aria-expanded="openFaq === 2">
                                    <span class="text-base font-semibold leading-7">Can I print the timetables?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        <svg class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </span>
                                </button>
                            </dt>
                            <dd class="mt-2 pr-12" id="faq-2" x-show="openFaq === 2" x-cloak>
                                <p class="text-base leading-7 text-gray-600 dark:text-gray-400">Absolutely. We offer one-click PDF exports formatted specifically for sending to print, posting on notice boards, or emailing to staff.</p>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800" aria-labelledby="footer-heading">
            <h2 id="footer-heading" class="sr-only">Footer</h2>
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:space-x-6 md:order-2">
                        <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">Contact Sales</a>
                        <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">Support</a>
                        <a href="#" class="text-sm leading-6 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">Privacy Policy</a>
                    </div>
                    <div class="mt-8 md:order-1 md:mt-0">
                        <p class="text-center text-xs leading-5 text-gray-500 dark:text-gray-400 relative pl-8">
                            &copy; {{ date('Y') }} TimetableNepal. All rights reserved. 
                            <span class="inline-flex items-center gap-1 ml-2 px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-medium border border-red-200">
                                🇳🇵 Made in Nepal
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Mobile Menu Handling with AlpineJS -->
        <div x-data x-show="false" class="hidden">
           <!-- Placeholder for eventual real mobile menu implementation -->
        </div>
    </body>
</html>
