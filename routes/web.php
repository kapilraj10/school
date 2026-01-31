<?php

use App\Http\Controllers\HomeController;
use App\Livewire\TimetableDesigner;
use App\Models\PageClick;
use App\Services\TimetablePrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/timetable-designer', TimetableDesigner::class)->name('timetable-designer');

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

        if (! $classId || ! $termId) {
            abort(404, 'Missing parameters');
        }

        $printService = new TimetablePrintService;
        $data = $printService->generateClassTimetablePdf($classId, $termId);

        return $data->stream();
    })->name('print.class-preview');

    Route::get('/print/teacher-preview', function () {
        $teacherId = request('teacher_id');
        $termId = request('term_id');

        if (! $teacherId || ! $termId) {
            abort(404, 'Missing parameters');
        }

        $printService = new TimetablePrintService;
        $data = $printService->generateTeacherSchedulePdf($teacherId, $termId);

        return $data->stream();
    })->name('print.teacher-preview');
});
