<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class TimetablePrintService
{
    /**
     * Generate a PDF for a class timetable
     */
    public function generateClassTimetablePdf(int $classRoomId, int $academicTermId): \Barryvdh\DomPDF\PDF
    {
        $data = $this->getClassTimetableData($classRoomId, $academicTermId);

        $pdf = Pdf::loadView('print.class-timetable', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Generate a PDF for a teacher schedule
     */
    public function generateTeacherSchedulePdf(int $teacherId, int $academicTermId): \Barryvdh\DomPDF\PDF
    {
        $data = $this->getTeacherScheduleData($teacherId, $academicTermId);

        $pdf = Pdf::loadView('print.teacher-schedule', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Generate a PDF for a room/lab schedule
     */
    public function generateRoomSchedulePdf(int $roomId, int $academicTermId): \Barryvdh\DomPDF\PDF
    {
        $data = $this->getRoomScheduleData($roomId, $academicTermId);

        $pdf = Pdf::loadView('print.room-schedule', $data);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Generate PDFs for all classes in a term
     */
    public function generateAllClassesPdf(int $academicTermId): \Barryvdh\DomPDF\PDF
    {
        $term = AcademicTerm::findOrFail($academicTermId);
        $classes = ClassRoom::active()->orderBy('name')->orderBy('section')->get();

        $html = '';
        foreach ($classes as $index => $class) {
            $data = $this->getClassTimetableData($class->id, $academicTermId);
            $html .= View::make('print.class-timetable', $data)->render();

            // Add page break between classes except for the last one
            if ($index < $classes->count() - 1) {
                $html .= '<div class="page-break"></div>';
            }
        }

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Generate master timetable (all classes overview)
     */
    public function generateMasterTimetablePdf(int $academicTermId): \Barryvdh\DomPDF\PDF
    {
        $term = AcademicTerm::findOrFail($academicTermId);
        $classes = ClassRoom::active()->orderBy('name')->orderBy('section')->get();

        $masterData = [
            'term' => $term,
            'classes' => $classes,
            'days' => TimetableSlot::getDays(),
            'periods' => TimetableSlot::getPeriods(),
            'schedules' => [],
        ];

        // Collect timetable data for each class
        foreach ($classes as $class) {
            $slots = $this->getOrganizedSlots($class->id, $academicTermId);
            $masterData['schedules'][$class->id] = [
                'class' => $class,
                'slots' => $slots,
            ];
        }

        $pdf = Pdf::loadView('print.master-timetable', $masterData);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    public function exportClassTimetableToExcel(int $classRoomId, int $academicTermId, string $filename)
    {
        $data = $this->getClassTimetableData($classRoomId, $academicTermId);

        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $tempFile = tempnam(sys_get_temp_dir(), 'timetable_');
        $writer->openToFile($tempFile);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Timetable');

        $titleRow = [
            \OpenSpout\Common\Entity\Row::fromValues([
                "Class Timetable: {$data['class']->full_name} - {$data['term']->name}",
            ]),
        ];
        $writer->addRows($titleRow);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));

        $header = ['Day / Period'];
        foreach ($data['periods'] as $period => $label) {
            $header[] = $label;
        }
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($header));

        foreach ($data['days'] as $dayNum => $dayName) {
            $row = [$dayName];
            foreach (array_keys($data['periods']) as $period) {
                $slot = $data['slots'][$dayNum][$period] ?? null;
                if ($slot && $slot->subject) {
                    $cellData = $slot->subject->name;
                    if ($slot->teacher) {
                        $cellData .= "\n".$slot->teacher->name;
                    }
                    $row[] = $cellData;
                } else {
                    $row[] = 'Free';
                }
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));
        }

        $writer->close();

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function exportTeacherScheduleToExcel(int $teacherId, int $academicTermId, string $filename)
    {
        $data = $this->getTeacherScheduleData($teacherId, $academicTermId);

        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $tempFile = tempnam(sys_get_temp_dir(), 'schedule_');
        $writer->openToFile($tempFile);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Schedule');

        $titleRow = [
            \OpenSpout\Common\Entity\Row::fromValues([
                "Teacher Schedule: {$data['teacher']->name} - {$data['term']->name}",
            ]),
        ];
        $writer->addRows($titleRow);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));

        $header = ['Day / Period'];
        foreach ($data['periods'] as $period => $label) {
            $header[] = $label;
        }
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($header));

        foreach ($data['days'] as $dayNum => $dayName) {
            $row = [$dayName];
            foreach (array_keys($data['periods']) as $period) {
                $slot = $data['slots'][$dayNum][$period] ?? null;
                if ($slot && $slot->subject) {
                    $cellData = $slot->classRoom->full_name."\n".$slot->subject->name;
                    $row[] = $cellData;
                } else {
                    $row[] = 'Free';
                }
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));
        }

        $writer->close();

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function exportRoomScheduleToExcel(int $roomId, int $academicTermId, string $filename)
    {
        $data = $this->getRoomScheduleData($roomId, $academicTermId);

        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $tempFile = tempnam(sys_get_temp_dir(), 'room_schedule_');
        $writer->openToFile($tempFile);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Room Schedule');

        $titleRow = [
            \OpenSpout\Common\Entity\Row::fromValues([
                "Room Schedule: {$data['room']->name} - {$data['term']->name}",
            ]),
        ];
        $writer->addRows($titleRow);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['']));

        $header = ['Day / Period'];
        foreach ($data['periods'] as $period => $label) {
            $header[] = $label;
        }
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($header));

        foreach ($data['days'] as $dayNum => $dayName) {
            $row = [$dayName];
            foreach (array_keys($data['periods']) as $period) {
                $slot = $data['slots'][$dayNum][$period] ?? null;
                if ($slot && $slot->subject) {
                    $cellData = ($slot->classRoom?->full_name ?? 'No Class')."\n".$slot->subject->name;
                    if ($slot->teacher) {
                        $cellData .= "\n".$slot->teacher->name;
                    }
                    $row[] = $cellData;
                } else {
                    $row[] = 'Free';
                }
            }
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($row));
        }

        $writer->close();

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get organized timetable data for a class
     */
    protected function getClassTimetableData(int $classRoomId, int $academicTermId): array
    {
        $class = ClassRoom::findOrFail($classRoomId);
        $term = AcademicTerm::findOrFail($academicTermId);

        $slots = $this->getOrganizedSlots($classRoomId, $academicTermId);

        $totalSlots = collect($slots)->flatten(1)->filter()->count();
        $filledSlots = collect($slots)->flatten(1)->filter(fn ($slot) => $slot && $slot->subject_id)->count();

        return [
            'class' => $class,
            'term' => $term,
            'slots' => $slots,
            'days' => TimetableSlot::getDays(),
            'periods' => TimetableSlot::getPeriods(),
            'totalSlots' => $totalSlots,
            'filledSlots' => $filledSlots,
        ];
    }

    /**
     * Get organized schedule data for a teacher
     */
    protected function getTeacherScheduleData(int $teacherId, int $academicTermId): array
    {
        $teacher = Teacher::findOrFail($teacherId);
        $term = AcademicTerm::findOrFail($academicTermId);

        $slotsQuery = TimetableSlot::where('academic_term_id', $academicTermId)
            ->where('teacher_id', $teacherId)
            ->with(['subject', 'classRoom', 'combinedPeriod'])
            ->orderBy('day')
            ->orderBy('period')
            ->get();

        $organized = [];
        $days = TimetableSlot::getDays();
        $periods = TimetableSlot::getPeriods();
        foreach (array_keys($days) as $day) {
            $organized[$day] = [];
            foreach (array_keys($periods) as $period) {
                $slot = $slotsQuery->where('day', $day)->where('period', $period)->first();
                $organized[$day][$period] = $slot;
            }
        }

        $totalSlots = collect($organized)->flatten(1)->filter()->count();
        $filledSlots = collect($organized)->flatten(1)->filter(fn ($slot) => $slot && $slot->subject_id)->count();

        return [
            'teacher' => $teacher,
            'term' => $term,
            'slots' => $organized,
            'days' => $days,
            'periods' => $periods,
            'totalSlots' => $totalSlots,
            'filledSlots' => $filledSlots,
        ];
    }

    /**
     * Get organized schedule data for a room/lab
     */
    protected function getRoomScheduleData(int $roomId, int $academicTermId): array
    {
        $room = Room::findOrFail($roomId);
        $term = AcademicTerm::findOrFail($academicTermId);

        $slotsQuery = TimetableSlot::query()
            ->where('academic_term_id', $academicTermId)
            ->whereExists(function ($query) use ($roomId): void {
                $query->selectRaw('1')
                    ->from('class_subject_settings')
                    ->whereColumn('class_subject_settings.class_room_id', 'timetable_slots.class_room_id')
                    ->whereColumn('class_subject_settings.subject_id', 'timetable_slots.subject_id')
                    ->where('class_subject_settings.room_id', $roomId)
                    ->where('class_subject_settings.is_active', true);
            })
            ->with(['subject', 'teacher', 'classRoom', 'combinedPeriod'])
            ->orderBy('day')
            ->orderBy('period')
            ->get();

        $organized = [];
        $days = TimetableSlot::getDays();
        $periods = TimetableSlot::getPeriods();
        foreach (array_keys($days) as $day) {
            $organized[$day] = [];
            foreach (array_keys($periods) as $period) {
                $slot = $slotsQuery->where('day', $day)->where('period', $period)->first();
                $organized[$day][$period] = $slot;
            }
        }

        $totalSlots = collect($organized)->flatten(1)->filter()->count();
        $filledSlots = collect($organized)->flatten(1)->filter(fn ($slot) => $slot && $slot->subject_id)->count();

        return [
            'room' => $room,
            'term' => $term,
            'slots' => $organized,
            'days' => $days,
            'periods' => $periods,
            'totalSlots' => $totalSlots,
            'filledSlots' => $filledSlots,
        ];
    }

    /**
     * Get organized slots for a class
     */
    protected function getOrganizedSlots(int $classRoomId, int $academicTermId): array
    {
        $slotsQuery = TimetableSlot::where('academic_term_id', $academicTermId)
            ->where('class_room_id', $classRoomId)
            ->with(['subject', 'teacher', 'combinedPeriod'])
            ->orderBy('day')
            ->orderBy('period')
            ->get();

        $organized = [];
        $days = TimetableSlot::getDays();
        $periods = TimetableSlot::getPeriods();
        foreach (array_keys($days) as $day) {
            $organized[$day] = [];
            foreach (array_keys($periods) as $period) {
                $slot = $slotsQuery->where('day', $day)->where('period', $period)->first();
                $organized[$day][$period] = $slot;
            }
        }

        return $organized;
    }

    /**
     * Format timetable data for Excel export
     */
    protected function formatTimetableForExcel(array $slots, array $days, array $periods): array
    {
        $formatted = [];

        // Header row
        $header = ['Day/Period'];
        foreach ($periods as $period => $label) {
            $header[] = $label;
        }
        $formatted[] = $header;

        // Data rows
        foreach ($days as $dayNum => $dayName) {
            $row = [$dayName];
            foreach ($periods as $period => $label) {
                $slot = $slots[$dayNum][$period] ?? null;
                if ($slot) {
                    $cellData = $slot->subject?->name ?? 'N/A';
                    $cellData .= "\n".($slot->teacher?->name ?? 'No Teacher');
                    if ($slot->subject?->code) {
                        $cellData .= "\n[{$slot->subject->code}]";
                    }
                    if ($slot->is_combined) {
                        $cellData .= "\n(Combined)";
                    }
                    $row[] = $cellData;
                } else {
                    $row[] = 'Free';
                }
            }
            $formatted[] = $row;
        }

        return $formatted;
    }

    /**
     * Generate filename for the export
     */
    public function generateFilename(string $type, $entity, AcademicTerm $term): string
    {
        $timestamp = now()->format('Y-m-d');

        switch ($type) {
            case 'class':
                return "timetable_{$entity->full_name}_{$term->name}_{$timestamp}.pdf";
            case 'teacher':
                return "schedule_{$entity->name}_{$term->name}_{$timestamp}.pdf";
            case 'room':
                return "room_schedule_{$entity->name}_{$term->name}_{$timestamp}.pdf";
            case 'all_classes':
                return "timetables_all_classes_{$term->name}_{$timestamp}.pdf";
            case 'master':
                return "master_timetable_{$term->name}_{$timestamp}.pdf";
            default:
                return "timetable_{$timestamp}.pdf";
        }
    }
}
