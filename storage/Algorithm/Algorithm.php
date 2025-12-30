<?php

namespace Testing_algo;
require_once 'SubjectData.php';
require_once 'TeacherData.php';
use Testing_algo\SubjectData;
use Testing_algo\TeacherData;

class Algorithm
{
    public array $subjectData = [];
    public array $teacherData = [];
    
    // Timetable configuration
    private array $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private int $periodsPerDay = 8;
    private array $periodTimes = [
        1 => '8:00-8:45',
        2 => '8:45-9:30',
        3 => '9:30-10:15',
        4 => '10:30-11:15',  // After break
        5 => '11:15-12:00',
        6 => '12:45-1:30',   // After lunch
        7 => '1:30-2:15',
        8 => '2:15-3:00'
    ];

    // Day name mapping for availability check
    private array $dayShortMap = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue', 
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri'
    ];

    private const int SCHOOL_START_CLASS = 1;
    private const int SCHOOL_END_CLASS = 10;

    public function __construct()
    {
        $subjectDataObj = new SubjectData();
        $this->subjectData = $subjectDataObj->getSubjects();
        $teacherDataObj = new TeacherData();
        $this->teacherData = $teacherDataObj->getTeachers();
    }

    private function getClassRangeForClassNumber(int $classNumber): string
    {
        if ($classNumber >= 1 && $classNumber <= 4) {
            return '1 - 4';
        }

        if ($classNumber >= 5 && $classNumber <= 7) {
            return '5 - 7';
        }

        if ($classNumber === 8) {
            return '8';
        }

        if ($classNumber >= 9 && $classNumber <= 10) {
            return '9 - 10';
        }

        return '1 - 4';
    }

    public function processData()
    {
        $datas = $this->subjectData;

        foreach ($datas as &$data) {
            if ($data['class_range'] === '1 - 4') {
                if ($data['type'] === 'compulsory') {
                    $data['priority'] = 0;
                } else {
                    $data['priority'] = 1;
                }
            }
        }

        return $datas;
    }

    /**
     * Get subjects for a specific class range
     */
    private function getSubjectsForClassRange(string $classRange): array
    {
        $subjects = [];
        foreach ($this->subjectData as $subject) {
            if ($subject['class_range'] === $classRange) {
                $subjects[] = $subject;
            }
        }
        return $subjects;
    }

    /**
     * Get teacher for a subject
     */
    private function getTeacherForSubject(string $subjectName, string $classRange): ?array
    {
        foreach ($this->teacherData as $teacher) {
            if ($teacher['subject'] === $subjectName && $teacher['class_range'] === $classRange) {
                return $teacher;
            }
        }
        return null;
    }

    /**
     * Check if teacher is available on a specific day and period
     */
    private function isTeacherAvailable(array $teacher, string $day, int $period): bool
    {
        $shortDay = $this->dayShortMap[$day] ?? '';
        $periodStr = (string)$period;
        
        $dayAvailable = in_array($shortDay, $teacher['available_days'], true);
        $periodAvailable = in_array($periodStr, $teacher['available_periods'], true);
        
        return $dayAvailable && $periodAvailable;
    }

    /**
     * Generate timetable for a specific class
     */
    public function generateTimetable(string $classRange = '1 - 4', int $classNumber = 1): array
    {
        $subjects = $this->getSubjectsForClassRange($classRange);
        $timetable = [];
        
        // Initialize empty timetable
        foreach ($this->days as $day) {
            $timetable[$day] = array_fill(1, $this->periodsPerDay, null);
        }

        // Sort subjects by priority (compulsory first)
        usort($subjects, function($a, $b) {
            $priorityA = $a['type'] === 'compulsory' ? 0 : 1;
            $priorityB = $b['type'] === 'compulsory' ? 0 : 1;
            return $priorityA - $priorityB;
        });

        // Track periods assigned per subject
        $assignedPeriods = [];
        foreach ($subjects as $subject) {
            $assignedPeriods[$subject['subject']] = 0;
        }

        // First pass: Assign minimum periods to all subjects
        foreach ($subjects as $subject) {
            $targetPeriods = $subject['min_period_per_week'];
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            
            while ($assignedPeriods[$subject['subject']] < $targetPeriods) {
                $assigned = false;
                
                foreach ($this->days as $day) {
                    if ($assignedPeriods[$subject['subject']] >= $targetPeriods) {
                        break;
                    }
                    
                    // Check if subject already assigned today (limit 2 per day for main subjects)
                    $todayCount = 0;
                    foreach ($timetable[$day] as $slot) {
                        if ($slot && $slot['subject'] === $subject['subject']) {
                            $todayCount++;
                        }
                    }
                    
                    if ($todayCount >= 2) {
                        continue;
                    }
                    
                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                        if ($timetable[$day][$period] === null) {
                            // Check teacher availability
                            if ($teacher && !$this->isTeacherAvailable($teacher, $day, $period)) {
                                continue; // Teacher not available, try next slot
                            }
                            
                            $timetable[$day][$period] = [
                                'subject' => $subject['subject'],
                                'teacher' => $teacher ? $teacher['name'] : null,
                                'type' => $subject['type']
                            ];
                            $assignedPeriods[$subject['subject']]++;
                            $assigned = true;
                            break;
                        }
                    }
                }
                
                if (!$assigned) {
                    break; // No more slots available
                }
            }
        }

        // Second pass: Fill remaining empty slots up to max_period_per_week
        foreach ($subjects as $subject) {
            $maxPeriods = $subject['max_period_per_week'];
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            
            while ($assignedPeriods[$subject['subject']] < $maxPeriods) {
                $assigned = false;
                
                foreach ($this->days as $day) {
                    if ($assignedPeriods[$subject['subject']] >= $maxPeriods) {
                        break;
                    }
                    
                    // Check if subject already assigned today (limit 2 per day)
                    $todayCount = 0;
                    foreach ($timetable[$day] as $slot) {
                        if ($slot && $slot['subject'] === $subject['subject']) {
                            $todayCount++;
                        }
                    }
                    
                    if ($todayCount >= 2) {
                        continue;
                    }
                    
                    // Find empty slot where teacher is available
                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                        if ($timetable[$day][$period] === null) {
                            // Check teacher availability
                            if ($teacher && !$this->isTeacherAvailable($teacher, $day, $period)) {
                                continue; // Teacher not available, try next slot
                            }
                            
                            $timetable[$day][$period] = [
                                'subject' => $subject['subject'],
                                'teacher' => $teacher ? $teacher['name'] : null,
                                'type' => $subject['type']
                            ];
                            $assignedPeriods[$subject['subject']]++;
                            $assigned = true;
                            break;
                        }
                    }
                }
                
                if (!$assigned) {
                    break; // No more slots available for this subject
                }
            }
        }

        // Third pass: Fill any remaining empty slots with any available subjects (remove 2 per day limit)
        $subjectIndex = 0;
        foreach ($this->days as $day) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if ($timetable[$day][$period] === null) {
                    // Try to find an available subject/teacher
                    $attempts = 0;
                    while ($attempts < count($subjects)) {
                        $subject = $subjects[$subjectIndex % count($subjects)];
                        $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
                        
                        // Check teacher availability
                        if (!$teacher || $this->isTeacherAvailable($teacher, $day, $period)) {
                            $timetable[$day][$period] = [
                                'subject' => $subject['subject'],
                                'teacher' => $teacher ? $teacher['name'] : null,
                                'type' => $subject['type']
                            ];
                            $assignedPeriods[$subject['subject']]++;
                            $subjectIndex++;
                            break;
                        }
                        
                        $subjectIndex++;
                        $attempts++;
                    }
                }
            }
        }

        return $timetable;
    }

    /**
     * Display timetable in CLI with formatted table
     */
    public function displayTimetable(array $timetable, string $className = 'Class 1'): void
    {
        $this->printHeader($className);
        $this->printTableHeader();
        $this->printTableRows($timetable);
        $this->printFooter();
    }

    /**
     * Print the header
     */
    private function printHeader(string $className): void
    {
        echo "\n";
        echo $this->colorText("╔════════════════════════════════════════════════════════════════════════════════════════════════════╗", 'cyan') . "\n";
        echo $this->colorText("║", 'cyan') . str_pad($this->colorText("  📅 WEEKLY TIMETABLE - $className  ", 'yellow'), 111, ' ', STR_PAD_BOTH) . $this->colorText("║", 'cyan') . "\n";
        echo $this->colorText("╚════════════════════════════════════════════════════════════════════════════════════════════════════╝", 'cyan') . "\n";
        echo "\n";
    }

    /**
     * Print table header with days
     */
    private function printTableHeader(): void
    {
        $colWidth = 14;
        $periodColWidth = 12;
        $timeColWidth = 12;
        
        // Top border
        echo "┌" . str_repeat("─", $periodColWidth) . "┬" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┬" . str_repeat("─", $colWidth);
        }
        echo "┐\n";
        
        // Header row
        echo "│" . $this->centerText("Period", $periodColWidth);
        echo "│" . $this->centerText("Time", $timeColWidth);
        foreach ($this->days as $day) {
            echo "│" . $this->centerText($this->colorText($day, 'green'), $colWidth + 9); // +9 for color codes
        }
        echo "│\n";
        
        // Header separator
        echo "├" . str_repeat("─", $periodColWidth) . "┼" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┼" . str_repeat("─", $colWidth);
        }
        echo "┤\n";
    }

    /**
     * Print table rows
     */
    private function printTableRows(array $timetable): void
    {
        $colWidth = 14;
        $periodColWidth = 12;
        $timeColWidth = 12;
        
        for ($period = 1; $period <= $this->periodsPerDay; $period++) {
            // Add break indicators
            if ($period === 4) {
                $this->printBreakRow("☕ BREAK", $colWidth, $periodColWidth, $timeColWidth);
            }
            if ($period === 6) {
                $this->printBreakRow("🍽️ LUNCH", $colWidth, $periodColWidth, $timeColWidth);
            }
            
            echo "│" . $this->centerText($this->colorText("P$period", 'white'), $periodColWidth + 9);
            echo "│" . $this->centerText($this->periodTimes[$period], $timeColWidth);
            
            foreach ($this->days as $day) {
                $slot = $timetable[$day][$period] ?? null;
                if ($slot) {
                    $subject = $slot['subject'];
                    $color = $slot['type'] === 'compulsory' ? 'yellow' : 'magenta';
                    echo "│" . $this->centerText($this->colorText($subject, $color), $colWidth + 9);
                } else {
                    echo "│" . $this->centerText($this->colorText("-", 'gray'), $colWidth + 9);
                }
            }
            echo "│\n";
        }
    }

    /**
     * Print break row
     */
    private function printBreakRow(string $label, int $colWidth, int $periodColWidth, int $timeColWidth): void
    {
        $totalWidth = $periodColWidth + $timeColWidth + ($colWidth * count($this->days)) + count($this->days) + 1;
        echo "├" . str_repeat("─", $periodColWidth) . "┴" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┴" . str_repeat("─", $colWidth);
        }
        echo "┤\n";
        
        echo "│" . $this->centerText($this->colorText($label, 'cyan'), $totalWidth + (9 * (count($this->days) + 2)) - 2) . "│\n";
        
        echo "├" . str_repeat("─", $periodColWidth) . "┬" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┬" . str_repeat("─", $colWidth);
        }
        echo "┤\n";
    }

    /**
     * Print footer with legend
     */
    private function printFooter(): void
    {
        $colWidth = 14;
        $periodColWidth = 12;
        $timeColWidth = 12;
        
        // Bottom border
        echo "└" . str_repeat("─", $periodColWidth) . "┴" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┴" . str_repeat("─", $colWidth);
        }
        echo "┘\n";
        
        // Legend
        echo "\n";
        echo $this->colorText("📌 Legend:", 'white') . "\n";
        echo "   " . $this->colorText("■", 'yellow') . " Compulsory Subjects    ";
        echo $this->colorText("■", 'magenta') . " ECA Activities\n";
        echo "\n";
    }

    /**
     * Center text within given width
     */
    private function centerText(string $text, int $width): string
    {
        $visibleLength = $this->visibleLength($text);
        $padding = $width - $visibleLength;
        $leftPad = (int)floor($padding / 2);
        $rightPad = $padding - $leftPad;
        return str_repeat(' ', max(0, $leftPad)) . $text . str_repeat(' ', max(0, $rightPad));
    }

    /**
     * Get visible length of string (excluding ANSI codes)
     */
    private function visibleLength(string $text): int
    {
        return mb_strlen(preg_replace('/\033\[[0-9;]*m/', '', $text));
    }

    /**
     * Apply color to text for CLI
     */
    public function colorText(string $text, string $color): string
    {
        $colors = [
            'black' => "\033[30m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'magenta' => "\033[35m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'gray' => "\033[90m",
            'reset' => "\033[0m"
        ];
        
        return ($colors[$color] ?? '') . $text . $colors['reset'];
    }

    /**
     * Display timetable summary statistics
     */
    public function displayStatistics(array $timetable): void
    {
        $stats = [];
        $totalPeriods = 0;
        
        foreach ($this->days as $day) {
            foreach ($timetable[$day] as $slot) {
                if ($slot) {
                    $subject = $slot['subject'];
                    $stats[$subject] = ($stats[$subject] ?? 0) + 1;
                    $totalPeriods++;
                }
            }
        }
        
        echo $this->colorText("📊 Timetable Statistics:", 'cyan') . "\n";
        echo str_repeat("─", 40) . "\n";
        
        arsort($stats);
        foreach ($stats as $subject => $count) {
            $bar = str_repeat("█", $count);
            echo sprintf("  %-12s %s %d periods\n", $subject, $this->colorText($bar, 'green'), $count);
        }
        
        echo str_repeat("─", 40) . "\n";
        echo "  Total: $totalPeriods periods/week\n";
        echo "  Empty: " . ($this->periodsPerDay * count($this->days) - $totalPeriods) . " slots\n\n";
    }

    /**
     * Main function to run the timetable display
     */
    public function timetable(string $classRange = '1 - 4', int $classNumber = 1): void
    {
        $className = "Class $classNumber ($classRange)";
        $timetable = $this->generateTimetable($classRange, $classNumber);
        $this->displayTimetable($timetable, $className);
        $this->displayStatistics($timetable);
    }

    /**
     * Display timetables for multiple classes
     */
    public function displayAllTimetables(string $classRange = '1 - 4'): void
    {
        // Parse class range to get start and end class
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $classRange, $matches)) {
            $startClass = (int)($matches[1] ?? 1);
            $endClass = (int)($matches[2] ?? $startClass);

            for ($class = $startClass; $class <= $endClass; $class++) {
                $this->timetable($classRange, $class);
            }

            return;
        }

        $single = trim($classRange);
        if (ctype_digit($single)) {
            $class = (int)$single;
            $this->timetable($classRange, $class);
        }
    }

    public function displaySchoolTimetables(int $startClass = self::SCHOOL_START_CLASS, int $endClass = self::SCHOOL_END_CLASS): void
    {
        $startClass = max(self::SCHOOL_START_CLASS, $startClass);
        $endClass = min(self::SCHOOL_END_CLASS, $endClass);

        for ($classNumber = $startClass; $classNumber <= $endClass; $classNumber++) {
            $classRange = $this->getClassRangeForClassNumber($classNumber);
            $this->timetable($classRange, $classNumber);
        }
    }
}


// Main execution
$algorithm = new Algorithm();

// Display menu
echo "\n";
echo $algorithm->colorText("╔═══════════════════════════════════════╗", 'cyan') . "\n";
echo $algorithm->colorText("║    SCHOOL TIMETABLE GENERATOR CLI     ║", 'cyan') . "\n";
echo $algorithm->colorText("╚═══════════════════════════════════════╝", 'cyan') . "\n";
echo "\n";

// Generate and display timetables for all classes (1-10)
$algorithm->displaySchoolTimetables(1, 10);

?>