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
    private int $totalPeriodsPerWeek = 48; // 8 periods * 6 days
    private int $maxTeacherPeriodsPerDay = 6; // Hard constraint: max 6 periods per teacher per day

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

    // Mentally heavy subjects (for soft constraint: avoid consecutive)
    private array $heavySubjects = ['Maths', 'Science', 'Eng', 'Nep', 'Social'];

    // Core subjects that should have positional consistency
    private array $coreSubjects = ['Eng', 'Maths', 'Science', 'Nep'];

    // Preferred periods for ECA (middle and last periods)
    private array $preferredEcaPeriods = [4, 5, 6, 7, 8];

    private const int SCHOOL_START_CLASS = 1;
    private const int SCHOOL_END_CLASS = 10;

    // Track global teacher assignments across all classes/sections
    private array $globalTeacherOccupancy = [];

    // Track teacher workload per day
    private array $teacherDailyWorkload = [];

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

    /**
     * Get subjects for a specific class range, categorized by type
     */
    private function getSubjectsForClassRange(string $classRange): array
    {
        $subjects = [
            'compulsory' => [],
            'optional' => [],
            'eca' => []  // co-curricular
        ];

        foreach ($this->subjectData as $subject) {
            if ($subject['class_range'] === $classRange) {
                $type = $subject['type'];
                if ($type === 'compulsory') {
                    $subjects['compulsory'][] = $subject;
                } elseif ($type === 'optional') {
                    $subjects['optional'][] = $subject;
                } else {
                    $subjects['eca'][] = $subject;
                }
            }
        }

        return $subjects;
    }

    /**
     * Get all subjects as flat array for a class range
     */
    private function getAllSubjectsForClassRange(string $classRange): array
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
        $periodStr = (string) $period;

        $dayAvailable = in_array($shortDay, $teacher['available_days'], true);
        $periodAvailable = in_array($periodStr, $teacher['available_periods'], true);

        return $dayAvailable && $periodAvailable;
    }

    /**
     * Check if teacher is already assigned to another class at this slot
     * HARD CONSTRAINT: Teacher cannot be in two places at once
     */
    private function isTeacherOccupied(string $teacherName, string $day, int $period): bool
    {
        $key = "{$teacherName}_{$day}_{$period}";
        return isset($this->globalTeacherOccupancy[$key]);
    }

    /**
     * Mark teacher as occupied at a slot
     */
    private function markTeacherOccupied(string $teacherName, string $day, int $period, string $classSection): void
    {
        $key = "{$teacherName}_{$day}_{$period}";
        $this->globalTeacherOccupancy[$key] = $classSection;

        // Track daily workload
        $workloadKey = "{$teacherName}_{$day}";
        if (!isset($this->teacherDailyWorkload[$workloadKey])) {
            $this->teacherDailyWorkload[$workloadKey] = 0;
        }
        $this->teacherDailyWorkload[$workloadKey]++;
    }

    /**
     * Check if teacher has exceeded daily workload limit
     * HARD CONSTRAINT: Max 6 periods per day per teacher
     */
    private function hasExceededDailyWorkload(string $teacherName, string $day): bool
    {
        $workloadKey = "{$teacherName}_{$day}";
        $currentWorkload = $this->teacherDailyWorkload[$workloadKey] ?? 0;
        return $currentWorkload >= $this->maxTeacherPeriodsPerDay;
    }

    /**
     * Check if subject is ECA/co-curricular
     */
    private function isEcaSubject(array $subject): bool
    {
        return $subject['type'] === 'eca';
    }

    /**
     * Count ECA subjects assigned on a specific day
     * HARD CONSTRAINT: No two co-curricular subjects in a single day
     */
    private function countEcaSubjectsOnDay(array $timetable, string $day): array
    {
        $ecaSubjects = [];
        if (!isset($timetable[$day])) {
            return $ecaSubjects;
        }

        foreach ($timetable[$day] as $slot) {
            if ($slot && $slot['type'] === 'eca') {
                $ecaSubjects[$slot['subject']] = true;
            }
        }

        return array_keys($ecaSubjects);
    }

    /**
     * Check if ANY ECA subject is already placed on a day
     * HARD CONSTRAINT: Only one ECA subject type per day (but can have 2 consecutive periods)
     */
    private function hasAnyEcaOnDay(array $timetable, string $day): bool
    {
        $ecaSubjects = $this->countEcaSubjectsOnDay($timetable, $day);
        return count($ecaSubjects) > 0;
    }
    
    /**
     * Get the ECA subject name on a day (if any)
     */
    private function getEcaSubjectOnDay(array $timetable, string $day): ?string
    {
        $ecaSubjects = $this->countEcaSubjectsOnDay($timetable, $day);
        return count($ecaSubjects) > 0 ? $ecaSubjects[0] : null;
    }
    
    /**
     * Check if ECA placement would be consecutive with existing ECA of same subject
     * HARD CONSTRAINT: If 2 ECA periods on same day, they must be consecutive and same subject
     */
    private function isConsecutiveEcaPlacement(array $timetable, string $day, int $period, string $subjectName): bool
    {
        // Check period before
        if ($period > 1) {
            $prevSlot = $timetable[$day][$period - 1] ?? null;
            if ($prevSlot && $prevSlot['type'] === 'eca' && $prevSlot['subject'] === $subjectName) {
                return true;
            }
        }
        
        // Check period after
        if ($period < $this->periodsPerDay) {
            $nextSlot = $timetable[$day][$period + 1] ?? null;
            if ($nextSlot && $nextSlot['type'] === 'eca' && $nextSlot['subject'] === $subjectName) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Count how many periods of a specific ECA subject are on a day
     */
    private function countSpecificEcaOnDay(array $timetable, string $day, string $subjectName): int
    {
        $count = 0;
        if (!isset($timetable[$day])) {
            return $count;
        }
        
        foreach ($timetable[$day] as $slot) {
            if ($slot && $slot['type'] === 'eca' && $slot['subject'] === $subjectName) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Check if an ECA subject is already placed on a day
     * HARD CONSTRAINT: Each ECA subject can appear at most once per day
     */
    private function isEcaSubjectOnDay(array $timetable, string $day, string $subjectName): bool
    {
        $ecaSubjects = $this->countEcaSubjectsOnDay($timetable, $day);
        return in_array($subjectName, $ecaSubjects);
    }

    /**
     * Count occurrences of a subject on a specific day
     * HARD CONSTRAINT: Not more than 2 periods of 1 subject per day
     */
    private function countSubjectOnDay(array $timetable, string $day, string $subjectName): int
    {
        $count = 0;
        if (!isset($timetable[$day])) {
            return $count;
        }

        foreach ($timetable[$day] as $slot) {
            if ($slot && $slot['subject'] === $subjectName) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get total periods assigned to a subject in a week
     */
    private function getTotalWeeklyPeriods(array $timetable, string $subjectName): int
    {
        $count = 0;
        foreach ($this->days as $day) {
            if (isset($timetable[$day])) {
                foreach ($timetable[$day] as $slot) {
                    if ($slot && $slot['subject'] === $subjectName) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Calculate penalty score for soft constraints
     */
    private function calculateSoftConstraintPenalty(array $timetable, string $day, int $period, string $subjectName): int
    {
        $penalty = 0;

        // Soft constraint: Not more than 1 period of same subject per day (prefer)
        $dayCount = $this->countSubjectOnDay($timetable, $day, $subjectName);
        if ($dayCount >= 1) {
            $penalty += 10; // Penalize second occurrence on same day
        }

        // Soft constraint: Avoid consecutive heavy subjects (check both before and after)
        if (in_array($subjectName, $this->heavySubjects)) {
            // Check previous period
            if ($period > 1) {
                $prevSlot = $timetable[$day][$period - 1] ?? null;
                if ($prevSlot && in_array($prevSlot['subject'], $this->heavySubjects)) {
                    $penalty += 5;
                }
            }
            // Check next period
            if ($period < $this->periodsPerDay) {
                $nextSlot = $timetable[$day][$period + 1] ?? null;
                if ($nextSlot && in_array($nextSlot['subject'], $this->heavySubjects)) {
                    $penalty += 5;
                }
            }
        }

        // Soft constraint: ECA should be in middle or last periods
        if ($this->isEcaSubjectByName($subjectName)) {
            if (!in_array($period, $this->preferredEcaPeriods)) {
                $penalty += 3;
            }
        }

        return $penalty;
    }

    /**
     * Check if subject name is ECA
     */
    private function isEcaSubjectByName(string $subjectName): bool
    {
        foreach ($this->subjectData as $subject) {
            if ($subject['subject'] === $subjectName && $subject['type'] === 'eca') {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a base template for Sunday (first day)
     * This will be used to maintain positional consistency
     */
    private function generateSundayTemplate(array $subjects, string $classRange): array
    {
        $template = [];
        $allSubjects = [];

        // Flatten and prioritize subjects
        foreach ($subjects['compulsory'] as $s) {
            $allSubjects[] = array_merge($s, ['priority' => 0]);
        }
        foreach ($subjects['optional'] as $s) {
            $allSubjects[] = array_merge($s, ['priority' => 1]);
        }
        foreach ($subjects['eca'] as $s) {
            $allSubjects[] = array_merge($s, ['priority' => 2]);
        }

        // Sort by priority
        usort($allSubjects, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // Create period slots assignment based on subject needs
        $periodAssignments = [];

        // Assign core subjects to early periods (positional consistency)
        $corePosition = 1;
        foreach ($allSubjects as $subject) {
            if (in_array($subject['subject'], $this->coreSubjects)) {
                $periodAssignments[$subject['subject']] = $corePosition++;
                if ($corePosition > 4) break;
            }
        }

        return $periodAssignments;
    }

    /**
     * Generate timetable for a specific class with all constraints
     */
    public function generateTimetable(string $classRange = '1 - 4', int $classNumber = 1, string $section = 'A'): array
    {
        $subjectsByType = $this->getSubjectsForClassRange($classRange);
        $allSubjects = $this->getAllSubjectsForClassRange($classRange);
        $classSection = "Class{$classNumber}_{$section}";

        // Initialize empty timetable
        $timetable = [];
        foreach ($this->days as $day) {
            $timetable[$day] = array_fill(1, $this->periodsPerDay, null);
        }

        // Track periods assigned per subject
        $assignedPeriods = [];
        foreach ($allSubjects as $subject) {
            $assignedPeriods[$subject['subject']] = 0;
        }

        // Categorize subjects
        $compulsorySubjects = $subjectsByType['compulsory'];
        $optionalSubjects = $subjectsByType['optional'];
        $ecaSubjects = $subjectsByType['eca'];

        // Sort compulsory by min period requirement (higher first)
        usort($compulsorySubjects, function ($a, $b) {
            return $b['min_period_per_week'] - $a['min_period_per_week'];
        });

        // Generate Sunday template for positional consistency
        $sundayOrder = $this->createDailyTemplate($compulsorySubjects, $ecaSubjects, $classRange);

        // Phase 1: Assign compulsory subjects maintaining positional consistency
        $this->assignCompulsorySubjects(
            $timetable,
            $compulsorySubjects,
            $assignedPeriods,
            $classRange,
            $classSection,
            $sundayOrder
        );

        // Phase 2: Assign ECA subjects (one per day max, middle/last periods preferred)
        $this->assignEcaSubjects(
            $timetable,
            $ecaSubjects,
            $assignedPeriods,
            $classRange,
            $classSection
        );

        // Phase 3: Assign optional subjects
        $this->assignOptionalSubjects(
            $timetable,
            $optionalSubjects,
            $assignedPeriods,
            $classRange,
            $classSection
        );

        // Phase 4: Fill remaining slots (balance across subjects)
        $this->fillRemainingSlots(
            $timetable,
            $allSubjects,
            $assignedPeriods,
            $classRange,
            $classSection
        );

        // Phase 5: Resolve constraint violations using backtracking
        $violations = $this->validateTimetable($timetable, $allSubjects, $assignedPeriods);
        
        if (!empty($violations)) {
            $this->resolveConstraintViolationsWithBacktracking(
                $timetable,
                $allSubjects,
                $assignedPeriods,
                $classRange,
                $classSection
            );
        }

        return $timetable;
    }

    /**
     * Create a daily template maintaining subject order
     * SOFT CONSTRAINT: Positional consistency across days
     * Core subjects (English, Math, Science, Nepali) in same slots daily
     */
    private function createDailyTemplate(array $compulsorySubjects, array $ecaSubjects, string $classRange): array
    {
        $template = [];
        $position = 1;

        // Core subjects in early periods (positions 1-4)
        foreach ($this->coreSubjects as $coreSubject) {
            foreach ($compulsorySubjects as $subject) {
                if ($subject['subject'] === $coreSubject) {
                    $template[$subject['subject']] = $position++;
                    break;
                }
            }
        }

        // Other compulsory subjects in next positions
        foreach ($compulsorySubjects as $subject) {
            if (!in_array($subject['subject'], $this->coreSubjects) && !isset($template[$subject['subject']])) {
                $template[$subject['subject']] = $position++;
            }
        }

        return $template;
    }

    /**
     * Assign compulsory subjects with positional consistency
     * SOFT CONSTRAINT: Timetable should maintain positional consistency across days
     */
    private function assignCompulsorySubjects(
        array &$timetable,
        array $subjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection,
        array $sundayOrder
    ): void {
        // First, establish Sunday (first day) as the template
        $sunday = $this->days[0]; // Sunday
        
        // Assign subjects to Sunday first to establish the template
        foreach ($subjects as $subject) {
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            $teacherName = $teacher ? $teacher['name'] : null;
            
            $preferredPeriod = $sundayOrder[$subject['subject']] ?? null;
            
            // Ensure preferred period is within valid range (1 to periodsPerDay)
            if ($preferredPeriod && $preferredPeriod >= 1 && $preferredPeriod <= $this->periodsPerDay && $timetable[$sunday][$preferredPeriod] === null) {
                if ($this->canAssignSlot($timetable, $sunday, $preferredPeriod, $subject, $teacher, $classSection)) {
                    $timetable[$sunday][$preferredPeriod] = [
                        'subject' => $subject['subject'],
                        'teacher' => $teacherName,
                        'type' => $subject['type']
                    ];
                    $assignedPeriods[$subject['subject']]++;
                    if ($teacherName) {
                        $this->markTeacherOccupied($teacherName, $sunday, $preferredPeriod, $classSection);
                    }
                }
            }
        }

        // Build actual Sunday template from what was assigned
        $actualSundayTemplate = [];
        for ($period = 1; $period <= $this->periodsPerDay; $period++) {
            if ($timetable[$sunday][$period] !== null) {
                $actualSundayTemplate[$period] = $timetable[$sunday][$period]['subject'];
            }
        }

        // Now assign to other days following the Sunday template
        foreach ($subjects as $subject) {
            $targetMin = $subject['min_period_per_week'];
            $targetMax = $subject['max_period_per_week'];
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            $teacherName = $teacher ? $teacher['name'] : null;
            $maxPerDay = 2; // Hard constraint

            // Find which period this subject is at on Sunday (for positional consistency)
            $sundayPeriod = array_search($subject['subject'], $actualSundayTemplate);

            foreach ($this->days as $dayIndex => $day) {
                if ($dayIndex === 0) continue; // Skip Sunday, already done
                
                if ($assignedPeriods[$subject['subject']] >= $targetMin) {
                    break;
                }

                // Check daily limit
                $todayCount = $this->countSubjectOnDay($timetable, $day, $subject['subject']);
                if ($todayCount >= $maxPerDay) {
                    continue;
                }

                // Try Sunday's period first (positional consistency)
                $periodsToTry = [];
                if ($sundayPeriod !== false) {
                    $periodsToTry[] = $sundayPeriod;
                }
                
                // Then try other periods
                for ($p = 1; $p <= $this->periodsPerDay; $p++) {
                    if (!in_array($p, $periodsToTry)) {
                        $periodsToTry[] = $p;
                    }
                }

                foreach ($periodsToTry as $period) {
                    if ($todayCount >= $maxPerDay) {
                        break;
                    }

                    if ($timetable[$day][$period] !== null) {
                        continue;
                    }

                    // Check all hard constraints
                    if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                        continue;
                    }

                    // Assign the slot
                    $timetable[$day][$period] = [
                        'subject' => $subject['subject'],
                        'teacher' => $teacherName,
                        'type' => $subject['type']
                    ];
                    $assignedPeriods[$subject['subject']]++;
                    $todayCount++;

                    if ($teacherName) {
                        $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                    }
                    
                    // Only assign once per day initially (soft constraint: prefer 1 per day)
                    break;
                }
            }

            // Second pass: fill up to minimum if not achieved
            while ($assignedPeriods[$subject['subject']] < $targetMin) {
                $assigned = false;

                foreach ($this->days as $day) {
                    if ($assignedPeriods[$subject['subject']] >= $targetMin) {
                        break;
                    }

                    $todayCount = $this->countSubjectOnDay($timetable, $day, $subject['subject']);
                    if ($todayCount >= $maxPerDay) {
                        continue;
                    }

                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                        if ($timetable[$day][$period] !== null) {
                            continue;
                        }

                        if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                            continue;
                        }

                        $timetable[$day][$period] = [
                            'subject' => $subject['subject'],
                            'teacher' => $teacherName,
                            'type' => $subject['type']
                        ];
                        $assignedPeriods[$subject['subject']]++;
                        $assigned = true;

                        if ($teacherName) {
                            $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                        }
                        break;
                    }
                }

                if (!$assigned) {
                    break; // Cannot assign more
                }
            }
        }
    }

    /**
     * Assign ECA subjects with constraints
     * HARD CONSTRAINT: 
     *   - Only one ECA subject type per day
     *   - Max 2 periods of same ECA per day
     *   - If 2 periods, they must be consecutive (e.g., dance-dance allowed, dance-math-dance not allowed)
     * - Prefer middle/last periods
     */
    private function assignEcaSubjects(
        array &$timetable,
        array $subjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection
    ): void {
        // Track which days already have an ECA assigned and which subject
        $ecaOnDay = [];
        foreach ($this->days as $day) {
            $existingEca = $this->getEcaSubjectOnDay($timetable, $day);
            if ($existingEca !== null) {
                $ecaOnDay[$day] = $existingEca;
            }
        }

        // For each ECA subject, try to assign its minimum periods
        foreach ($subjects as $subject) {
            $targetMin = $subject['min_period_per_week'];
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            $teacherName = $teacher ? $teacher['name'] : null;
            $subjectName = $subject['subject'];

            while ($assignedPeriods[$subjectName] < $targetMin) {
                $foundSlot = false;

                // First, try to add a second consecutive period on a day that already has this ECA
                foreach ($this->days as $day) {
                    if (isset($ecaOnDay[$day]) && $ecaOnDay[$day] === $subjectName) {
                        // This day has this ECA - try to add consecutive period
                        $ecaCount = $this->countSpecificEcaOnDay($timetable, $day, $subjectName);
                        if ($ecaCount >= 2) {
                            continue; // Already has 2 periods
                        }
                        
                        // Find where the existing ECA is and try to place consecutive
                        for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                            $slot = $timetable[$day][$period] ?? null;
                            if ($slot && $slot['subject'] === $subjectName) {
                                // Try period before
                                if ($period > 1 && $timetable[$day][$period - 1] === null) {
                                    if ($this->canAssignSlot($timetable, $day, $period - 1, $subject, $teacher, $classSection)) {
                                        $timetable[$day][$period - 1] = [
                                            'subject' => $subjectName,
                                            'teacher' => $teacherName,
                                            'type' => 'eca'
                                        ];
                                        $assignedPeriods[$subjectName]++;
                                        if ($teacherName) {
                                            $this->markTeacherOccupied($teacherName, $day, $period - 1, $classSection);
                                        }
                                        $foundSlot = true;
                                        break;
                                    }
                                }
                                // Try period after
                                if ($period < $this->periodsPerDay && $timetable[$day][$period + 1] === null) {
                                    if ($this->canAssignSlot($timetable, $day, $period + 1, $subject, $teacher, $classSection)) {
                                        $timetable[$day][$period + 1] = [
                                            'subject' => $subjectName,
                                            'teacher' => $teacherName,
                                            'type' => 'eca'
                                        ];
                                        $assignedPeriods[$subjectName]++;
                                        if ($teacherName) {
                                            $this->markTeacherOccupied($teacherName, $day, $period + 1, $classSection);
                                        }
                                        $foundSlot = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if ($foundSlot) break;
                    }
                }

                // If couldn't add consecutive, try a new day without any ECA
                if (!$foundSlot) {
                    foreach ($this->days as $day) {
                        if (isset($ecaOnDay[$day])) {
                            continue; // Day already has a different ECA
                        }

                        // Try preferred periods (middle and last)
                        $periodsToTry = array_merge($this->preferredEcaPeriods, range(1, 3));

                        foreach ($periodsToTry as $period) {
                            if ($timetable[$day][$period] !== null) {
                                continue;
                            }

                            if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                                continue;
                            }

                            $timetable[$day][$period] = [
                                'subject' => $subjectName,
                                'teacher' => $teacherName,
                                'type' => 'eca'
                            ];
                            $assignedPeriods[$subjectName]++;
                            $ecaOnDay[$day] = $subjectName; // Mark this day as having this ECA

                            if ($teacherName) {
                                $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                            }

                            $foundSlot = true;
                            break;
                        }

                        if ($foundSlot) break;
                    }
                }

                if (!$foundSlot) {
                    break; // Cannot find suitable slot
                }
            }
        }
    }

    /**
     * Assign optional subjects (per section/student group)
     */
    private function assignOptionalSubjects(
        array &$timetable,
        array $subjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection
    ): void {
        foreach ($subjects as $subject) {
            $targetMin = $subject['min_period_per_week'];
            $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
            $teacherName = $teacher ? $teacher['name'] : null;

            while ($assignedPeriods[$subject['subject']] < $targetMin) {
                $assigned = false;

                foreach ($this->days as $day) {
                    if ($assignedPeriods[$subject['subject']] >= $targetMin) {
                        break;
                    }

                    // Check daily limit (max 2 per day for optional too)
                    $todayCount = $this->countSubjectOnDay($timetable, $day, $subject['subject']);
                    if ($todayCount >= 2) {
                        continue;
                    }

                    for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                        if ($timetable[$day][$period] !== null) {
                            continue;
                        }

                        if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                            continue;
                        }

                        $timetable[$day][$period] = [
                            'subject' => $subject['subject'],
                            'teacher' => $teacherName,
                            'type' => $subject['type']
                        ];
                        $assignedPeriods[$subject['subject']]++;
                        $assigned = true;

                        if ($teacherName) {
                            $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                        }
                        break;
                    }
                }

                if (!$assigned) {
                    break;
                }
            }
        }
    }

    /**
     * Fill remaining slots while respecting constraints
     * HARD CONSTRAINT: All 48 periods must be filled
     */
    private function fillRemainingSlots(
        array &$timetable,
        array $allSubjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection
    ): void {
        // Calculate how many empty slots remain
        $emptySlots = $this->countEmptySlots($timetable);
        
        // Sort subjects by how far they are from max allocation (prioritize those needing more)
        usort($allSubjects, function ($a, $b) use ($assignedPeriods) {
            $remainingA = $a['max_period_per_week'] - ($assignedPeriods[$a['subject']] ?? 0);
            $remainingB = $b['max_period_per_week'] - ($assignedPeriods[$b['subject']] ?? 0);
            return $remainingB - $remainingA;
        });

        // First pass: try to fill with best matches considering all constraints
        foreach ($this->days as $day) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if ($timetable[$day][$period] !== null) {
                    continue;
                }

                // Find best subject to fill this slot
                $bestSubject = null;
                $bestPenalty = PHP_INT_MAX;

                foreach ($allSubjects as $subject) {
                    // Skip if at max allocation
                    if ($assignedPeriods[$subject['subject']] >= $subject['max_period_per_week']) {
                        continue;
                    }

                    // Check daily limit
                    $todayCount = $this->countSubjectOnDay($timetable, $day, $subject['subject']);
                    $maxPerDay = ($subject['type'] === 'eca') ? 1 : 2;
                    if ($todayCount >= $maxPerDay) {
                        continue;
                    }

                    // ECA constraint: No two ECA in a single day
                    if ($subject['type'] === 'eca' && $this->hasAnyEcaOnDay($timetable, $day)) {
                        continue;
                    }

                    $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
                    if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                        continue;
                    }

                    $penalty = $this->calculateSoftConstraintPenalty($timetable, $day, $period, $subject['subject']);

                    // Prioritize subjects that haven't reached min yet
                    if ($assignedPeriods[$subject['subject']] < $subject['min_period_per_week']) {
                        $penalty -= 20;
                    }

                    if ($penalty < $bestPenalty) {
                        $bestPenalty = $penalty;
                        $bestSubject = $subject;
                    }
                }

                if ($bestSubject) {
                    $teacher = $this->getTeacherForSubject($bestSubject['subject'], $classRange);
                    $teacherName = $teacher ? $teacher['name'] : null;

                    $timetable[$day][$period] = [
                        'subject' => $bestSubject['subject'],
                        'teacher' => $teacherName,
                        'type' => $bestSubject['type']
                    ];
                    $assignedPeriods[$bestSubject['subject']]++;

                    if ($teacherName) {
                        $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                    }
                }
            }
        }

        // Second pass: Force fill any remaining empty slots (relaxing soft constraints)
        $this->forceFillingRemainingSlots($timetable, $allSubjects, $assignedPeriods, $classRange, $classSection);
    }

    /**
     * Count empty slots in timetable
     */
    private function countEmptySlots(array $timetable): int
    {
        $count = 0;
        foreach ($this->days as $day) {
            foreach ($timetable[$day] as $slot) {
                if ($slot === null) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Force fill remaining slots - relax soft constraints to ensure 48 periods are filled
     * HARD CONSTRAINT: All 48 periods must be taught in a week
     */
    private function forceFillingRemainingSlots(
        array &$timetable,
        array $allSubjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection
    ): void {
        foreach ($this->days as $day) {
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                if ($timetable[$day][$period] !== null) {
                    continue;
                }

                // Find any valid subject (relaxing soft constraints but keeping hard constraints)
                foreach ($allSubjects as $subject) {
                    // Skip ECA if any ECA already on this day (hard constraint)
                    if ($subject['type'] === 'eca' && $this->hasAnyEcaOnDay($timetable, $day)) {
                        continue;
                    }

                    // Check daily limit (hard constraint)
                    $todayCount = $this->countSubjectOnDay($timetable, $day, $subject['subject']);
                    $maxPerDay = ($subject['type'] === 'eca') ? 1 : 2;
                    if ($todayCount >= $maxPerDay) {
                        continue;
                    }

                    // Skip if at max allocation (hard constraint)
                    if ($assignedPeriods[$subject['subject']] >= $subject['max_period_per_week']) {
                        continue;
                    }

                    $teacher = $this->getTeacherForSubject($subject['subject'], $classRange);
                    $teacherName = $teacher ? $teacher['name'] : null;

                    // Check teacher constraints (hard constraints)
                    if ($teacher) {
                        if (!$this->isTeacherAvailable($teacher, $day, $period)) {
                            continue;
                        }
                        if ($this->isTeacherOccupied($teacherName, $day, $period)) {
                            continue;
                        }
                        if ($this->hasExceededDailyWorkload($teacherName, $day)) {
                            continue;
                        }
                    }

                    // Assign it
                    $timetable[$day][$period] = [
                        'subject' => $subject['subject'],
                        'teacher' => $teacherName,
                        'type' => $subject['type']
                    ];
                    $assignedPeriods[$subject['subject']]++;

                    if ($teacherName) {
                        $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                    }
                    break;
                }
            }
        }
    }

    /**
     * Check if a slot can be assigned (all hard constraints)
     */
    private function canAssignSlot(
        array $timetable,
        string $day,
        int $period,
        array $subject,
        ?array $teacher,
        string $classSection
    ): bool {
        $teacherName = $teacher ? $teacher['name'] : null;

        // 1. Teacher availability check
        if ($teacher && !$this->isTeacherAvailable($teacher, $day, $period)) {
            return false;
        }

        // 2. Teacher not double-booked
        if ($teacherName && $this->isTeacherOccupied($teacherName, $day, $period)) {
            return false;
        }

        // 3. Teacher daily workload limit
        if ($teacherName && $this->hasExceededDailyWorkload($teacherName, $day)) {
            return false;
        }

        // 4. Subject daily limit (max 2 per day for all subjects)
        $maxPerDay = 2;
        if ($this->countSubjectOnDay($timetable, $day, $subject['subject']) >= $maxPerDay) {
            if ($debug) echo "      [canAssignSlot $subjectName] FAIL: Subject daily limit reached\n";
            return false;
        }

        // 5. ECA HARD CONSTRAINTS:
        //    - Only one ECA subject TYPE per day
        //    - Max 2 periods of same ECA per day
        //    - If 2 periods, they must be consecutive
        if ($subject['type'] === 'eca') {
            $existingEca = $this->getEcaSubjectOnDay($timetable, $day);
            
            if ($existingEca !== null) {
                // There's already an ECA on this day
                if ($existingEca !== $subject['subject']) {
                    // Different ECA subject - not allowed
                    return false;
                }
                
                // Same ECA subject - check if we already have 2 periods
                $ecaCount = $this->countSpecificEcaOnDay($timetable, $day, $subject['subject']);
                if ($ecaCount >= 2) {
                    return false;
                }
                
                // Check if this placement would be consecutive
                if (!$this->isConsecutiveEcaPlacement($timetable, $day, $period, $subject['subject'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate timetable and report any constraint violations
     * Checks all HARD constraints
     */
    private function validateTimetable(array $timetable, array $allSubjects, array $assignedPeriods): array
    {
        $violations = [];

        // 1. Check total periods (HARD: All 48 periods must be filled)
        $totalPeriods = 0;
        foreach ($this->days as $day) {
            foreach ($timetable[$day] as $slot) {
                if ($slot !== null) {
                    $totalPeriods++;
                }
            }
        }
        if ($totalPeriods < $this->totalPeriodsPerWeek) {
            $violations[] = "Only $totalPeriods periods filled (required: {$this->totalPeriodsPerWeek})";
        }

        // 2. Check subject min/max allocations
        foreach ($allSubjects as $subject) {
            $name = $subject['subject'];
            $assigned = $assignedPeriods[$name] ?? 0;
            $min = $subject['min_period_per_week'];
            $max = $subject['max_period_per_week'];

            if ($assigned < $min) {
                $violations[] = "Subject '$name' has $assigned periods (min required: $min)";
            }
            if ($assigned > $max) {
                $violations[] = "Subject '$name' has $assigned periods (max allowed: $max)";
            }
        }

        // 3. Check daily constraints
        foreach ($this->days as $day) {
            $subjectCounts = [];
            $ecaSubjects = [];
            $ecaPeriods = []; // Track which periods have ECA

            foreach ($timetable[$day] as $period => $slot) {
                if ($slot) {
                    $name = $slot['subject'];
                    $subjectCounts[$name] = ($subjectCounts[$name] ?? 0) + 1;

                    if ($slot['type'] === 'eca') {
                        $ecaSubjects[] = $name;
                        $ecaPeriods[$name][] = $period;
                    }
                }
            }

            // HARD: Max 2 periods per subject per day
            foreach ($subjectCounts as $name => $count) {
                if ($count > 2) {
                    $violations[] = "Subject '$name' has $count periods on $day (max 2)";
                }
            }

            // HARD: Only one ECA subject type per day
            $uniqueEca = array_unique($ecaSubjects);
            if (count($uniqueEca) > 1) {
                $violations[] = "Multiple ECA subjects on $day: " . implode(', ', $uniqueEca) . " (only 1 ECA type per day)";
            }
            
            // HARD: If 2 ECA periods on same day, they must be consecutive
            foreach ($ecaPeriods as $ecaName => $periods) {
                if (count($periods) == 2) {
                    sort($periods);
                    if ($periods[1] - $periods[0] != 1) {
                        $violations[] = "ECA '$ecaName' on $day has non-consecutive periods (P{$periods[0]} and P{$periods[1]})";
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Resolve constraint violations using backtracking
     * Reduces compulsory subjects at max allocation to free slots for under-allocated subjects
     * 
     * @param array $timetable Reference to timetable array
     * @param array $allSubjects All subjects with their constraints
     * @param array $assignedPeriods Reference to assigned periods tracking
     * @param string $classRange Class range for teacher lookup
     * @param string $classSection Class section identifier
     * @return bool True if violations resolved, false otherwise
     */
    public function resolveConstraintViolationsWithBacktracking(
        array &$timetable,
        array $allSubjects,
        array &$assignedPeriods,
        string $classRange,
        string $classSection
    ): bool {
        // Backtracking summary output removed for brevity
        
        // Identify violations
        $underAllocated = [];
        $overAllocated = [];
        $canReduce = [];
        
        foreach ($allSubjects as $subject) {
            $name = $subject['subject'];
            $assigned = $assignedPeriods[$name] ?? 0;
            $min = $subject['min_period_per_week'];
            $max = $subject['max_period_per_week'];
            
            if ($assigned < $min) {
                $deficit = $min - $assigned;
                $underAllocated[$name] = [
                    'subject' => $subject,
                    'deficit' => $deficit,
                    'current' => $assigned,
                    'min' => $min
                ];
            } elseif ($subject['type'] === 'compulsory' && $assigned === $max && $max > $min) {
                // Compulsory subjects at max can be reduced to min
                $surplus = $max - $min;
                $canReduce[$name] = [
                    'subject' => $subject,
                    'surplus' => $surplus,
                    'current' => $assigned,
                    'min' => $min
                ];
            }
        }
        
        if (empty($underAllocated)) {
            echo $this->colorText("  ✓ No constraint violations found\n", 'green');
            return true;
        }
        
        $totalDeficit = array_sum(array_column($underAllocated, 'deficit'));
        $totalSurplus = array_sum(array_column($canReduce, 'surplus'));
        
        // Suppress detailed deficit/surplus logs
        
        if ($totalDeficit > $totalSurplus) {
            //echo $this->colorText("  ✗ Insufficient surplus to resolve all violations\n", 'red');
            return false;
        }
        
        // Use backtracking to find best reallocation
        // Searching message suppressed
        
        $success = $this->backtrackReallocation(
            $timetable,
            $assignedPeriods,
            $underAllocated,
            $canReduce,
            $classRange,
            $classSection,
            0
        );
        
        // Outcome summary suppressed (handled elsewhere)
        
        return $success;
    }
    
    /**
     * Backtracking algorithm to reallocate periods
     * 
     * @param array $timetable Reference to timetable
     * @param array $assignedPeriods Reference to period tracking
     * @param array $underAllocated Subjects needing more periods
     * @param array $canReduce Subjects that can be reduced
     * @param string $classRange Class range
     * @param string $classSection Class section
     * @param int $depth Recursion depth
     * @return bool True if successful reallocation found
     */
    private function backtrackReallocation(
        array &$timetable,
        array &$assignedPeriods,
        array &$underAllocated,
        array &$canReduce,
        string $classRange,
        string $classSection,
        int $depth
    ): bool {
        // Base case: all deficits resolved
        $remainingDeficit = 0;
        foreach ($underAllocated as $name => $info) {
            $current = $assignedPeriods[$name] ?? 0;
            $deficit = $info['min'] - $current;
            if ($deficit > 0) {
                $remainingDeficit += $deficit;
            }
        }
        
        if ($remainingDeficit === 0) {
            return true; // Success!
        }
        
        // Prevent infinite recursion
        if ($depth > 200) {
            return false;
        }
        
        // Find subject with highest deficit (prioritize ECA subjects as they have stricter constraints)
        $targetSubject = null;
        $targetInfo = null;
        $maxDeficit = 0;
        $isEcaTarget = false;
        
        foreach ($underAllocated as $name => $info) {
            $current = $assignedPeriods[$name] ?? 0;
            $deficit = $info['min'] - $current;
            if ($deficit <= 0) continue;
            
            $isEca = ($info['subject']['type'] === 'eca');
            
            // Prioritize ECA subjects (they have stricter per-day constraints)
            if ($isEca && !$isEcaTarget) {
                $targetSubject = $name;
                $targetInfo = $info;
                $maxDeficit = $deficit;
                $isEcaTarget = true;
            } elseif ($deficit > $maxDeficit && ($isEca || !$isEcaTarget)) {
                $maxDeficit = $deficit;
                $targetSubject = $name;
                $targetInfo = $info;
                $isEcaTarget = $isEca;
            }
        }
        
        if ($targetSubject === null) {
            return false;
        }
        
        $isTargetEca = ($targetInfo['subject']['type'] === 'eca');
        
        // For ECA subjects, we need special handling
        if ($isTargetEca) {
            return $this->backtrackEcaReallocation(
                $timetable,
                $assignedPeriods,
                $underAllocated,
                $canReduce,
                $classRange,
                $classSection,
                $targetSubject,
                $targetInfo,
                $depth
            );
        }
        
        // For non-ECA subjects, try to remove one period from reducible subjects
        
        // Get the target subject's teacher's available days
        $targetTeacher = $this->getTeacherForSubject($targetSubject, $classRange);
        $preferredDays = null;
        if ($targetTeacher && isset($targetTeacher['available_days'])) {
            $preferredDays = $targetTeacher['available_days'];
            $preferredDays = array_reverse($preferredDays);
        }
        
        foreach ($canReduce as $sourceName => $sourceInfo) {
            $currentSource = $assignedPeriods[$sourceName] ?? 0;
            
            // Check if we can still reduce this subject
            if ($currentSource <= $sourceInfo['min']) {
                continue;
            }
            
            // Find and remove one period of source subject (prefer days where target teacher is available)
            $removed = $this->removeOnePeriod($timetable, $assignedPeriods, $sourceName, $classSection, $preferredDays);
            
            if (!$removed) {
                continue;
            }
            
            // Removed slot logged internally
            
            // First, try to add target subject to the exact slot that was just freed
            $teacher = $this->getTeacherForSubject($targetSubject, $classRange);
            $teacherName = $teacher ? $teacher['name'] : null;
            $added = false;
            
            // Try the just-freed slot first
            if ($this->canAssignSlot($timetable, $removed['day'], $removed['period'], $underAllocated[$targetSubject]['subject'], $teacher, $classSection)) {
                $timetable[$removed['day']][$removed['period']] = [
                    'subject' => $targetSubject,
                    'teacher' => $teacherName,
                    'type' => $underAllocated[$targetSubject]['subject']['type']
                ];
                $assignedPeriods[$targetSubject]++;
                if ($teacherName) {
                    $this->markTeacherOccupied($teacherName, $removed['day'], $removed['period'], $classSection);
                }
                $added = true;
                // Direct slot swap successful
            }
            
            // If direct swap didn't work, try other slots
            if (!$added) {
                $added = $this->addOnePeriod(
                    $timetable,
                    $assignedPeriods,
                    $targetSubject,
                    $underAllocated[$targetSubject]['subject'],
                    $classRange,
                    $classSection
                );
            }
            
            if ($added) {
                // Update canReduce if source is now at minimum
                if ($assignedPeriods[$sourceName] <= $sourceInfo['min']) {
                    unset($canReduce[$sourceName]);
                }
                
                // Recurse
                if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                    return true;
                }
                
                // Backtrack: remove added period and restore removed period
                $this->removeOnePeriod($timetable, $assignedPeriods, $targetSubject, $classSection);
                $this->restorePeriod($timetable, $assignedPeriods, $sourceName, $removed, $classSection);
                
                // Restore canReduce
                $canReduce[$sourceName] = $sourceInfo;
            } else {
                // Couldn't add target, restore removed period
                $this->restorePeriod($timetable, $assignedPeriods, $sourceName, $removed, $classSection);
            }
        }
        
        return false;
    }
    
    /**
     * Special backtracking for ECA subjects
     * HARD CONSTRAINTS:
     *   - Only one ECA subject type per day
     *   - Max 2 periods of same ECA per day, must be consecutive
     */
    private function backtrackEcaReallocation(
        array &$timetable,
        array &$assignedPeriods,
        array &$underAllocated,
        array &$canReduce,
        string $classRange,
        string $classSection,
        string $targetSubject,
        array $targetInfo,
        int $depth
    ): bool {
        // Categorize days by their ECA status
        $daysWithoutEca = [];
        $daysWithSameEca = [];
        $daysWithOtherEca = [];
        
        foreach ($this->days as $day) {
            $existingEca = $this->getEcaSubjectOnDay($timetable, $day);
            if ($existingEca === null) {
                $daysWithoutEca[] = $day;
            } elseif ($existingEca === $targetSubject) {
                // Same ECA - might be able to add consecutive
                $ecaCount = $this->countSpecificEcaOnDay($timetable, $day, $targetSubject);
                if ($ecaCount < 2) {
                    $daysWithSameEca[] = $day;
                }
            } else {
                $daysWithOtherEca[] = $day;
            }
        }
        
        // ECA placement logging suppressed
        
        // First try: add consecutive period on a day that already has this ECA
        foreach ($daysWithSameEca as $day) {
            // Find where the existing ECA is and try to place consecutive
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $slot = $timetable[$day][$period] ?? null;
                if ($slot && $slot['subject'] === $targetSubject) {
                    $teacher = $this->getTeacherForSubject($targetSubject, $classRange);
                    $teacherName = $teacher ? $teacher['name'] : null;
                    
                    // Try period before
                    if ($period > 1 && $timetable[$day][$period - 1] === null) {
                        if ($this->canAssignSlot($timetable, $day, $period - 1, $targetInfo['subject'], $teacher, $classSection)) {
                            $timetable[$day][$period - 1] = [
                                'subject' => $targetSubject,
                                'teacher' => $teacherName,
                                'type' => 'eca'
                            ];
                            $assignedPeriods[$targetSubject]++;
                            if ($teacherName) {
                                $this->markTeacherOccupied($teacherName, $day, $period - 1, $classSection);
                            }
                            
                            // Consecutive addition logged elsewhere
                            
                            if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                                return true;
                            }
                            
                            // Backtrack
                            $this->removeOnePeriodAt($timetable, $assignedPeriods, $targetSubject, $day, $period - 1, $classSection);
                        }
                    }
                    
                    // Try period after
                    if ($period < $this->periodsPerDay && $timetable[$day][$period + 1] === null) {
                        if ($this->canAssignSlot($timetable, $day, $period + 1, $targetInfo['subject'], $teacher, $classSection)) {
                            $timetable[$day][$period + 1] = [
                                'subject' => $targetSubject,
                                'teacher' => $teacherName,
                                'type' => 'eca'
                            ];
                            $assignedPeriods[$targetSubject]++;
                            if ($teacherName) {
                                $this->markTeacherOccupied($teacherName, $day, $period + 1, $classSection);
                            }
                            
                            // Consecutive addition logged elsewhere
                            
                            if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                                return true;
                            }
                            
                            // Backtrack
                            $this->removeOnePeriodAt($timetable, $assignedPeriods, $targetSubject, $day, $period + 1, $classSection);
                        }
                    }
                }
            }
        }
        
        // Second try: find an empty slot on a day without ECA
        foreach ($daysWithoutEca as $day) {
            for ($period = $this->periodsPerDay; $period >= 1; $period--) {
                if ($timetable[$day][$period] === null) {
                    $teacher = $this->getTeacherForSubject($targetSubject, $classRange);
                    $teacherName = $teacher ? $teacher['name'] : null;
                    
                    if ($this->canAssignSlot($timetable, $day, $period, $targetInfo['subject'], $teacher, $classSection)) {
                        $timetable[$day][$period] = [
                            'subject' => $targetSubject,
                            'teacher' => $teacherName,
                            'type' => $targetInfo['subject']['type']
                        ];
                        $assignedPeriods[$targetSubject]++;
                        
                        if ($teacherName) {
                            $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                        }
                        
                        // Empty slot addition logged elsewhere
                        
                        if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                            return true;
                        }
                        
                        // Backtrack
                        $this->removeOnePeriodAt($timetable, $assignedPeriods, $targetSubject, $day, $period, $classSection);
                    }
                }
            }
        }
        
        // Third try: swap compulsory subject on a day without ECA
        // Swapping attempts logging suppressed
        
        foreach ($daysWithoutEca as $day) {
            foreach ($canReduce as $sourceName => $sourceInfo) {
                $currentSource = $assignedPeriods[$sourceName] ?? 0;
                
                if ($currentSource <= $sourceInfo['min']) {
                    continue;
                }
                
                $removed = $this->removeOnePeriodFromDay($timetable, $assignedPeriods, $sourceName, $day, $classSection);
                
                if (!$removed) {
                    continue;
                }
                
                // Removed slot logged internally
                
                $teacher = $this->getTeacherForSubject($targetSubject, $classRange);
                $teacherName = $teacher ? $teacher['name'] : null;
                
                $added = false;
                $addedPeriod = 0;
                
                $periodsToTry = array_unique(array_merge($this->preferredEcaPeriods, range(1, $this->periodsPerDay)));
                
                foreach ($periodsToTry as $tryPeriod) {
                    if ($timetable[$day][$tryPeriod] !== null) {
                        continue;
                    }
                    
                    if ($this->canAssignSlot($timetable, $day, $tryPeriod, $targetInfo['subject'], $teacher, $classSection)) {
                        $timetable[$day][$tryPeriod] = [
                            'subject' => $targetSubject,
                            'teacher' => $teacherName,
                            'type' => $targetInfo['subject']['type']
                        ];
                        $assignedPeriods[$targetSubject]++;
                        
                        if ($teacherName) {
                            $this->markTeacherOccupied($teacherName, $day, $tryPeriod, $classSection);
                        }
                        $added = true;
                        $addedPeriod = $tryPeriod;
                        // Successful swap logged elsewhere
                        break;
                    }
                }
                
                if ($added) {
                    if ($assignedPeriods[$sourceName] <= $sourceInfo['min']) {
                        unset($canReduce[$sourceName]);
                    }
                    
                    if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                        return true;
                    }
                    
                    // Backtrack
                    $this->removeOnePeriodAt($timetable, $assignedPeriods, $targetSubject, $day, $addedPeriod, $classSection);
                    $this->restorePeriod($timetable, $assignedPeriods, $sourceName, $removed, $classSection);
                    $canReduce[$sourceName] = $sourceInfo;
                } else {
                    $this->restorePeriod($timetable, $assignedPeriods, $sourceName, $removed, $classSection);
                }
            }
        }
        
        // Fourth try: swap compulsory subject on a day that has this ECA to create consecutive slot
        foreach ($daysWithSameEca as $day) {
            // Find where existing ECA is
            for ($period = 1; $period <= $this->periodsPerDay; $period++) {
                $slot = $timetable[$day][$period] ?? null;
                if ($slot && $slot['subject'] === $targetSubject) {
                    // Try to free up adjacent slot
                    $adjacentPeriods = [];
                    if ($period > 1) $adjacentPeriods[] = $period - 1;
                    if ($period < $this->periodsPerDay) $adjacentPeriods[] = $period + 1;
                    
                    foreach ($adjacentPeriods as $adjPeriod) {
                        $adjSlot = $timetable[$day][$adjPeriod] ?? null;
                        if ($adjSlot && $adjSlot['type'] !== 'eca') {
                            $adjSubject = $adjSlot['subject'];
                            
                            // Check if we can reduce this subject
                            if (!isset($canReduce[$adjSubject])) continue;
                            if ($assignedPeriods[$adjSubject] <= $canReduce[$adjSubject]['min']) continue;
                            
                            $removed = $this->removeOnePeriodAt($timetable, $assignedPeriods, $adjSubject, $day, $adjPeriod, $classSection);
                            $removedInfo = ['day' => $day, 'period' => $adjPeriod, 'slot' => $adjSlot];
                            
                            $teacher = $this->getTeacherForSubject($targetSubject, $classRange);
                            $teacherName = $teacher ? $teacher['name'] : null;
                            
                            if ($this->canAssignSlot($timetable, $day, $adjPeriod, $targetInfo['subject'], $teacher, $classSection)) {
                                $timetable[$day][$adjPeriod] = [
                                    'subject' => $targetSubject,
                                    'teacher' => $teacherName,
                                    'type' => 'eca'
                                ];
                                $assignedPeriods[$targetSubject]++;
                                
                                if ($teacherName) {
                                    $this->markTeacherOccupied($teacherName, $day, $adjPeriod, $classSection);
                                }
                                
                                // Consecutive addition logged elsewhere
                                
                                if ($assignedPeriods[$adjSubject] <= $canReduce[$adjSubject]['min']) {
                                    unset($canReduce[$adjSubject]);
                                }
                                
                                if ($this->backtrackReallocation($timetable, $assignedPeriods, $underAllocated, $canReduce, $classRange, $classSection, $depth + 1)) {
                                    return true;
                                }
                                
                                // Backtrack
                                $this->removeOnePeriodAt($timetable, $assignedPeriods, $targetSubject, $day, $adjPeriod, $classSection);
                                $this->restorePeriod($timetable, $assignedPeriods, $adjSubject, $removedInfo, $classSection);
                                $canReduce[$adjSubject] = $canReduce[$adjSubject] ?? ['subject' => $adjSlot, 'surplus' => 1, 'current' => $assignedPeriods[$adjSubject] + 1, 'min' => $canReduce[$adjSubject]['min'] ?? 5];
                            } else {
                                $this->restorePeriod($timetable, $assignedPeriods, $adjSubject, $removedInfo, $classSection);
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Remove one period of a subject from a specific day
     */
    private function removeOnePeriodFromDay(array &$timetable, array &$assignedPeriods, string $subjectName, string $day, string $classSection): array|false
    {
        for ($period = $this->periodsPerDay; $period >= 1; $period--) {
            $slot = $timetable[$day][$period] ?? null;
            if ($slot && $slot['subject'] === $subjectName) {
                $removedSlot = [
                    'day' => $day,
                    'period' => $period,
                    'slot' => $slot
                ];
                
                $timetable[$day][$period] = null;
                $assignedPeriods[$subjectName]--;
                
                if ($slot['teacher']) {
                    $key = "{$slot['teacher']}_{$day}_{$period}";
                    unset($this->globalTeacherOccupancy[$key]);
                    
                    $workloadKey = "{$slot['teacher']}_{$day}";
                    if (isset($this->teacherDailyWorkload[$workloadKey])) {
                        $this->teacherDailyWorkload[$workloadKey]--;
                    }
                }
                
                return $removedSlot;
            }
        }
        
        return false;
    }
    
    /**
     * Remove a period at a specific day and period
     */
    private function removeOnePeriodAt(array &$timetable, array &$assignedPeriods, string $subjectName, string $day, int $period, string $classSection): void
    {
        $slot = $timetable[$day][$period] ?? null;
        if ($slot && $slot['subject'] === $subjectName) {
            $timetable[$day][$period] = null;
            $assignedPeriods[$subjectName]--;
            
            if ($slot['teacher']) {
                $key = "{$slot['teacher']}_{$day}_{$period}";
                unset($this->globalTeacherOccupancy[$key]);
                
                $workloadKey = "{$slot['teacher']}_{$day}";
                if (isset($this->teacherDailyWorkload[$workloadKey])) {
                    $this->teacherDailyWorkload[$workloadKey]--;
                }
            }
        }
    }
    
    /**
     * Remove one period of a subject from timetable
     * 
     * @param array $timetable Reference to timetable
     * @param array $assignedPeriods Reference to period tracking
     * @param string $subjectName Subject to remove
     * @param string $classSection Class section
     * @param array|null $preferredDays Days to prefer for removal (if null, use default order)
     * @return array|false Removed slot info or false if none removed
     */
    private function removeOnePeriod(array &$timetable, array &$assignedPeriods, string $subjectName, string $classSection, ?array $preferredDays = null): array|false
    {
        // If preferred days are specified, try those first
        $daysToTry = $preferredDays ?? array_reverse($this->days);
        
        // Then add remaining days
        if ($preferredDays !== null) {
            foreach (array_reverse($this->days) as $day) {
                if (!in_array($day, $daysToTry)) {
                    $daysToTry[] = $day;
                }
            }
        }
        
        foreach ($daysToTry as $day) {
            for ($period = $this->periodsPerDay; $period >= 1; $period--) {
                $slot = $timetable[$day][$period] ?? null;
                if ($slot && $slot['subject'] === $subjectName) {
                    $removedSlot = [
                        'day' => $day,
                        'period' => $period,
                        'slot' => $slot
                    ];
                    
                    // Remove from timetable
                    $timetable[$day][$period] = null;
                    $assignedPeriods[$subjectName]--;
                    
                    // Remove teacher occupancy
                    if ($slot['teacher']) {
                        $key = "{$slot['teacher']}_{$day}_{$period}";
                        unset($this->globalTeacherOccupancy[$key]);
                        
                        $workloadKey = "{$slot['teacher']}_{$day}";
                        if (isset($this->teacherDailyWorkload[$workloadKey])) {
                            $this->teacherDailyWorkload[$workloadKey]--;
                        }
                    }
                    
                    return $removedSlot;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Add one period of a subject to timetable
     * 
     * @param array $timetable Reference to timetable
     * @param array $assignedPeriods Reference to period tracking
     * @param string $subjectName Subject to add
     * @param array $subject Subject data
     * @param string $classRange Class range
     * @param string $classSection Class section
     * @return bool True if successfully added
     */
    private function addOnePeriod(
        array &$timetable,
        array &$assignedPeriods,
        string $subjectName,
        array $subject,
        string $classRange,
        string $classSection
    ): bool {
        $teacher = $this->getTeacherForSubject($subjectName, $classRange);
        $teacherName = $teacher ? $teacher['name'] : null;
        
        // Try to find suitable empty slot
        foreach ($this->days as $day) {
            // Check daily limit for this subject
            $todayCount = $this->countSubjectOnDay($timetable, $day, $subjectName);
            $maxPerDay = ($subject['type'] === 'eca') ? 1 : 2;
            
            if ($todayCount >= $maxPerDay) {
                continue;
            }
            
            // For ECA, check if any ECA already exists on this day
            if ($subject['type'] === 'eca' && $this->hasAnyEcaOnDay($timetable, $day)) {
                continue;
            }
            
            // Try each period
            $periodsToTry = ($subject['type'] === 'eca') ? $this->preferredEcaPeriods : range(1, $this->periodsPerDay);
            
            foreach ($periodsToTry as $period) {
                if ($timetable[$day][$period] !== null) {
                    continue;
                }
                
                // Found empty slot - check if we can assign
                if (!$this->canAssignSlot($timetable, $day, $period, $subject, $teacher, $classSection)) {
                    continue;
                }
                
                // Assign the period
                $timetable[$day][$period] = [
                    'subject' => $subjectName,
                    'teacher' => $teacherName,
                    'type' => $subject['type']
                ];
                $assignedPeriods[$subjectName]++;
                
                // Added slot acknowledged in summary output if needed
                
                if ($teacherName) {
                    $this->markTeacherOccupied($teacherName, $day, $period, $classSection);
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Restore a previously removed period
     * 
     * @param array $timetable Reference to timetable
     * @param array $assignedPeriods Reference to period tracking
     * @param string $subjectName Subject to restore
     * @param array $removedSlot Slot information to restore
     * @param string $classSection Class section
     */
    private function restorePeriod(
        array &$timetable,
        array &$assignedPeriods,
        string $subjectName,
        array $removedSlot,
        string $classSection
    ): void {
        $day = $removedSlot['day'];
        $period = $removedSlot['period'];
        $slot = $removedSlot['slot'];
        
        $timetable[$day][$period] = $slot;
        $assignedPeriods[$subjectName]++;
        
        if ($slot['teacher']) {
            $this->markTeacherOccupied($slot['teacher'], $day, $period, $classSection);
        }
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
            echo "│" . $this->centerText($this->colorText($day, 'green'), $colWidth + 9);
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
                    $color = match ($slot['type']) {
                        'compulsory' => 'yellow',
                        'eca' => 'magenta',
                        'optional' => 'cyan',
                        default => 'white'
                    };
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
        echo "├" . str_repeat("─", $periodColWidth) . "┴" . str_repeat("─", $timeColWidth);
        foreach ($this->days as $day) {
            echo "┴" . str_repeat("─", $colWidth);
        }
        echo "┤\n";

        $totalWidth = $periodColWidth + $timeColWidth + ($colWidth * count($this->days)) + count($this->days) + 1;
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
        echo $this->colorText("■", 'magenta') . " ECA/Co-curricular    ";
        echo $this->colorText("■", 'cyan') . " Optional Subjects\n";
        echo "\n";
    }

    /**
     * Center text within given width
     */
    private function centerText(string $text, int $width): string
    {
        $visibleLength = $this->visibleLength($text);
        $padding = $width - $visibleLength;
        $leftPad = (int) floor($padding / 2);
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
    public function displayStatistics(array $timetable, string $classRange = '1 - 4'): void
    {
        $stats = [];
        $totalPeriods = 0;
        $violations = [];

        // Count periods per subject
        foreach ($this->days as $day) {
            foreach ($timetable[$day] as $slot) {
                if ($slot) {
                    $subject = $slot['subject'];
                    $stats[$subject] = ($stats[$subject] ?? 0) + 1;
                    $totalPeriods++;
                }
            }
        }

        // Get subject requirements
        $allSubjects = $this->getAllSubjectsForClassRange($classRange);
        $requirements = [];
        foreach ($allSubjects as $s) {
            $requirements[$s['subject']] = [
                'min' => $s['min_period_per_week'],
                'max' => $s['max_period_per_week'],
                'type' => $s['type']
            ];
        }

        echo $this->colorText("📊 Timetable Statistics:", 'cyan') . "\n";
        echo str_repeat("─", 60) . "\n";

        arsort($stats);
        foreach ($stats as $subject => $count) {
            $bar = str_repeat("█", min($count, 20));
            $req = $requirements[$subject] ?? ['min' => 0, 'max' => 0, 'type' => 'unknown'];
            $status = '';

            if ($count < $req['min']) {
                $status = $this->colorText(" ⚠ UNDER", 'red');
                $violations[] = "$subject: $count < {$req['min']} min";
            } elseif ($count > $req['max']) {
                $status = $this->colorText(" ⚠ OVER", 'red');
                $violations[] = "$subject: $count > {$req['max']} max";
            } else {
                $status = $this->colorText(" ✓", 'green');
            }

            $typeIndicator = match ($req['type']) {
                'compulsory' => $this->colorText('[C]', 'yellow'),
                'eca' => $this->colorText('[E]', 'magenta'),
                'optional' => $this->colorText('[O]', 'cyan'),
                default => '   '
            };

            echo sprintf(
                "  %s %-12s %s %2d periods (min:%d, max:%d)%s\n",
                $typeIndicator,
                $subject,
                $this->colorText($bar, 'green'),
                $count,
                $req['min'],
                $req['max'],
                $status
            );
        }

        echo str_repeat("─", 60) . "\n";
        echo "  Total: $totalPeriods periods/week ({$this->periodsPerDay} periods × " . count($this->days) . " days = " . ($this->periodsPerDay * count($this->days)) . " slots)\n";
        echo "  Empty: " . ($this->periodsPerDay * count($this->days) - $totalPeriods) . " slots\n";

        if (!empty($violations)) {
            echo "\n" . $this->colorText("⚠️  Constraint Violations:", 'red') . "\n";
            foreach ($violations as $v) {
                echo "   - $v\n";
            }
        }

        echo "\n";
    }

    /**
     * Main function to run the timetable display for a single section
     */
    public function timetable(string $classRange = '1 - 4', int $classNumber = 1, string $section = 'A'): void
    {
        $className = "Class $classNumber-$section (Range: $classRange)";
        $timetable = $this->generateTimetable($classRange, $classNumber, $section);
        $this->displayTimetable($timetable, $className);
        $this->displayStatistics($timetable, $classRange);
    }

    /**
     * Generate and display timetables for both Section A and Section B of a class
     */
    public function timetableWithSections(string $classRange = '1 - 4', int $classNumber = 1): void
    {
        $sections = ['A', 'B'];
        
        foreach ($sections as $section) {
            $className = "Class $classNumber-$section (Range: $classRange)";
            $timetable = $this->generateTimetable($classRange, $classNumber, $section);
            $this->displayTimetable($timetable, $className);
            $this->displayStatistics($timetable, $classRange);
        }
    }

    /**
     * Display timetables for multiple classes
     */
    public function displayAllTimetables(string $classRange = '1 - 4'): void
    {
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $classRange, $matches)) {
            $startClass = (int) ($matches[1] ?? 1);
            $endClass = (int) ($matches[2] ?? $startClass);

            for ($class = $startClass; $class <= $endClass; $class++) {
                $this->timetable($classRange, $class);
            }

            return;
        }

        $single = trim($classRange);
        if (ctype_digit($single)) {
            $class = (int) $single;
            $this->timetable($classRange, $class);
        }
    }

    /**
     * Reset global state (call before generating timetables for multiple classes)
     */
    public function resetGlobalState(): void
    {
        $this->globalTeacherOccupancy = [];
        $this->teacherDailyWorkload = [];
    }

    public function displaySchoolTimetables(int $startClass = self::SCHOOL_START_CLASS, int $endClass = self::SCHOOL_END_CLASS, bool $bothSections = true): void
    {
        $this->resetGlobalState();

        $startClass = max(self::SCHOOL_START_CLASS, $startClass);
        $endClass = min(self::SCHOOL_END_CLASS, $endClass);

        for ($classNumber = $startClass; $classNumber <= $endClass; $classNumber++) {
            $classRange = $this->getClassRangeForClassNumber($classNumber);
            
            if ($bothSections) {
                $this->timetableWithSections($classRange, $classNumber);
            } else {
                $this->timetable($classRange, $classNumber);
            }
        }
    }

    /**
     * Display summary of constraints
     */
    public function displayConstraintsSummary(): void
    {
        echo "\n";
        echo $this->colorText("╔════════════════════════════════════════════════════════════════════════════════╗", 'cyan') . "\n";
        echo $this->colorText("║                    TIMETABLE CONSTRAINT SUMMARY                                ║", 'cyan') . "\n";
        echo $this->colorText("╚════════════════════════════════════════════════════════════════════════════════╝", 'cyan') . "\n";
        echo "\n";

        echo $this->colorText("HARD CONSTRAINTS (Must be satisfied):", 'yellow') . "\n";
        echo "  • 8 periods per day, 6 days (Sunday-Friday) = 48 periods/week\n";
        echo "  • All 48 periods must be filled in a week\n";
        echo "  • 3 types of subjects: Compulsory, Optional, Co-curricular (ECA)\n";
        echo "  • Only one ECA subject type per day\n";
        echo "  • ECA: max 2 periods per day, must be consecutive if 2 (e.g., dance-dance OK, dance-sport NOT OK)\n";
        echo "  • Max 2 periods of any subject per day\n";
        echo "  • Optional subjects assigned per section/student group\n";
        echo "  • Each subject must satisfy min/max weekly period allocation\n";
        echo "  • Subject allocations must be balanced across the week\n";
        echo "  • Teacher cannot be assigned to multiple classes in same period\n";
        echo "  • Teacher max workload: 6 periods per day\n";
        echo "\n";

        echo $this->colorText("SOFT CONSTRAINTS (Best effort):", 'green') . "\n";
        echo "  • Positional consistency across days (same subject order daily)\n";
        echo "  • Prefer max 1 period of same subject per day\n";
        echo "  • Core subjects (Eng, Math, Science, Nep) in same slots daily\n";
        echo "  • Avoid consecutive mentally heavy subjects (Math, Science, etc.)\n";
        echo "  • ECA/Co-curricular subjects in middle or last periods (4-8)\n";
        echo "\n";
    }
}

// Main execution
$algorithm = new Algorithm();

// Display constraints summary
$algorithm->displayConstraintsSummary();

// Display menu
echo "\n";
echo $algorithm->colorText("╔═══════════════════════════════════════╗", 'cyan') . "\n";
echo $algorithm->colorText("║    SCHOOL TIMETABLE GENERATOR CLI     ║", 'cyan') . "\n";
echo $algorithm->colorText("╚═══════════════════════════════════════╝", 'cyan') . "\n";
echo "\n";

// Generate and display timetables for all classes (1-10) with both sections A and B
$algorithm->displaySchoolTimetables(1, 10, true);

?>
