<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\TimetableSlot;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $classes = ClassRoom::query()
            ->active()
            ->get()
            ->sortBy(function ($class) {
                return (int) filter_var($class->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($class->section);
            })
            ->values();

        $currentTerm = AcademicTerm::query()
            ->active()
            ->first();

        $academicTerms = AcademicTerm::query()
            ->orderBy('year', 'desc')
            ->orderBy('term', 'desc')
            ->get();

        $timetableSlots = collect();

        if ($currentTerm) {
            $timetableSlots = TimetableSlot::query()
                ->where('academic_term_id', $currentTerm->id)
                ->with(['subject', 'teacher', 'classRoom'])
                ->get()
                ->groupBy('class_room_id');
        }

        return view('home', [
            'classes' => $classes,
            'currentTerm' => $currentTerm,
            'academicTerms' => $academicTerms,
            'timetableSlots' => $timetableSlots,
        ]);
    }
}
