<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\HeroSlide;
use App\Models\TextCarouselItem;
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

        $heroSlides = HeroSlide::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($heroSlides->isEmpty()) {
            $heroSlides = collect([
                [
                    'subtitle' => 'Quality School Education',
                    'title' => 'Welcome to\nYumak Bauddha Mandal School',
                    'description' => 'Admissions open for students from Nursery to Class 10.',
                    'button_text' => 'Read More',
                    'button_link' => '#',
                    'background_image_url' => asset('images/slide1.png'),
                ],
                [
                    'subtitle' => 'Learning with Values',
                    'title' => 'Strong Foundation\nfor Every Child',
                    'description' => 'We nurture young minds with modern teaching from Nursery to Class 10.',
                    'button_text' => 'Read More',
                    'button_link' => '#',
                    'background_image_url' => asset('images/slide-2.png'),
                ],
                [
                    'subtitle' => 'A Better Future Starts Here',
                    'title' => 'Grow, Learn,\nand Succeed',
                    'description' => 'Join Yumak Bauddha Mandal School and build a bright future from the early years.',
                    'button_text' => 'Read More',
                    'button_link' => '#',
                    'background_image_url' => asset('images/slide-3.png'),
                ],
            ]);
        }

        $textCarouselItems = TextCarouselItem::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($textCarouselItems->isEmpty()) {
            $textCarouselItems = collect([
                [
                    'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
                    'author_name' => 'Ram Prasad Sharma',
                    'author_role' => 'Engineer',
                    'author_image_url' => asset('images/slide1.png'),
                    'rating' => 5,
                ],
                [
                    'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
                    'author_name' => 'Sunita Thapa',
                    'author_role' => 'Doctor',
                    'author_image_url' => asset('images/slide-2.png'),
                    'rating' => 5,
                ],
                [
                    'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
                    'author_name' => 'David Wilson',
                    'author_role' => 'Banker',
                    'author_image_url' => asset('images/slide-3.png'),
                    'rating' => 4,
                ],
            ]);
        }

        $textCarouselGroups = $textCarouselItems->chunk(3)->values();

        return view('home', [
            'classes' => $classes,
            'currentTerm' => $currentTerm,
            'academicTerms' => $academicTerms,
            'timetableSlots' => $timetableSlots,
            'heroSlides' => $heroSlides,
            'textCarouselGroups' => $textCarouselGroups,
        ]);
    }
}
