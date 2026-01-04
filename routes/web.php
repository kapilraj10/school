<?php

use App\Services\TimetablePrintService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
