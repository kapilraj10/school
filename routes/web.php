<?php

use App\Http\Controllers\HomeController;
use App\Livewire\TimetableDesigner;
use App\Models\PageClick;
use App\Services\TimetablePrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/about', 'about')->name('about');

Route::redirect('/login', '/admin/login')->name('login');

Route::middleware(['auth'])->group(function () {
    Route::get('/timetable-designer', TimetableDesigner::class)
        ->middleware('permission:timetable_designer.view')
        ->name('timetable-designer');

    Route::post('/track-click', function (Request $request) {
        $url = $request->input('url');

        // Normalize URL to relative path
        $parsedUrl = parse_url($url);
        $normalizedUrl = $parsedUrl['path'] ?? $url;

        PageClick::recordClick($request->input('page_name'), $normalizedUrl);

        return response()->json(['success' => true]);
    })->name('track-click');
});

Route::get('/testing', function () {

    return 'This is a testing route.';
});

// Print preview routes
Route::middleware(['auth'])->group(function () {
    Route::get('/print/class-preview', function () {
        $classId = request('class_id');
        $termId = request('term_id');
        $user = auth()->user();

        if (! $classId || ! $termId) {
            abort(404, 'Missing parameters');
        }

        if ($user?->hasRole('student') && (int) $user->class_room_id !== (int) $classId) {
            abort(403, 'You can only view your own class timetable.');
        }

        if ($user?->hasRole('teacher')) {
            abort(403, 'Teachers cannot preview class timetables.');
        }

        $printService = new TimetablePrintService;
        $data = $printService->generateClassTimetablePdf($classId, $termId);

        return $data->stream();
    })->name('print.class-preview');

    Route::get('/print/teacher-preview', function () {
        $teacherId = request('teacher_id');
        $termId = request('term_id');
        $user = auth()->user();

        if (! $teacherId || ! $termId) {
            abort(404, 'Missing parameters');
        }

        if ($user?->hasRole('student')) {
            abort(403, 'Students cannot preview teacher timetables.');
        }

        if ($user?->hasRole('teacher')) {
            $teacher = \App\Models\Teacher::query()
                ->where('email', $user->email)
                ->first();

            if (! $teacher || (int) $teacher->id !== (int) $teacherId) {
                abort(403, 'You can only view your own timetable.');
            }
        }

        $printService = new TimetablePrintService;
        $data = $printService->generateTeacherSchedulePdf($teacherId, $termId);

        return $data->stream();
    })->name('print.teacher-preview');

    Route::get('/print/room-preview', function () {
        $roomId = request('room_id');
        $termId = request('term_id');
        $user = auth()->user();

        if (! $roomId || ! $termId) {
            abort(404, 'Missing parameters');
        }

        if ($user && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            abort(403, 'Only admins can preview room schedules.');
        }

        $printService = new TimetablePrintService;
        $data = $printService->generateRoomSchedulePdf($roomId, $termId);

        return $data->stream();
    })->name('print.room-preview');
});
