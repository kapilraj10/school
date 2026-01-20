<?php
/**
 * CLI Test Script for Genetic Algorithm Timetable Scheduler
 * 
 * Usage: php test_genetic_algorithm.php
 */

require_once 'GeneticAlgorithm.php';
require_once 'SubjectData.php';
require_once 'TeacherData.php';

use Testing_algo\SubjectData;
use Testing_algo\TeacherData;

echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║         GENETIC ALGORITHM TIMETABLE SCHEDULER - TEST                           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

/**
 * Helper function to create a subject ID from subject name
 */
function createSubjectId($subjectName) {
    return strtolower(str_replace([' ', '&', '.', '/'], ['_', 'and', '', '_'], $subjectName));
}

/**
 * Helper function to check if a class matches a class range
 */
function matchesClassRange($class, $classRange) {
    $class = intval($class);
    
    // Exact match (e.g., "8")
    if (is_numeric($classRange) && intval($classRange) === $class) {
        return true;
    }
    
    // Range match (e.g., "5 - 7")
    if (strpos($classRange, '-') !== false) {
        $parts = array_map('trim', explode('-', $classRange));
        $start = intval($parts[0]);
        $end = intval($parts[1]);
        return $class >= $start && $class <= $end;
    }
    
    return false;
}

/**
 * Load subjects for a specific class
 */
function loadSubjectsForClass($targetClass) {
    $subjectData = SubjectData::getSubjects();
    $subjects = [];
    
    foreach ($subjectData as $subjectInfo) {
        if (matchesClassRange($targetClass, $subjectInfo['class_range'])) {
            $id = createSubjectId($subjectInfo['subject']);
            $name = $subjectInfo['subject'];
            $type = $subjectInfo['type'] === 'eca' ? 'co_curricular' : $subjectInfo['type'];
            $minPeriods = $subjectInfo['min_period_per_week'];
            $maxPeriods = $subjectInfo['max_period_per_week'];
            $isCombined = $subjectInfo['single_combined'] === 'combined';
            
            $subjects[$id] = new Subject($id, $name, $type, $minPeriods, $maxPeriods, $isCombined);
        }
    }
    
    return $subjects;
}

/**
 * Load teachers for a specific class
 */
function loadTeachersForClass($targetClass, $subjects) {
    $teacherData = TeacherData::getTeachers();
    $teachers = [];
    
    foreach ($teacherData as $teacherInfo) {
        if (matchesClassRange($targetClass, $teacherInfo['class_range'])) {
            $id = strtolower($teacherInfo['name']);
            $name = $teacherInfo['name'];
            
            // Convert subject names to IDs
            $subjectIds = [];
            $teacherSubjects = is_array($teacherInfo['subject']) 
                ? $teacherInfo['subject'] 
                : [$teacherInfo['subject']];
            
            foreach ($teacherSubjects as $subjectName) {
                $subjectId = createSubjectId($subjectName);
                // Only add if subject exists in our subjects array
                if (isset($subjects[$subjectId])) {
                    $subjectIds[] = $subjectId;
                }
            }
            
            if (!empty($subjectIds)) {
                $maxPeriodsPerDay = 7; // Default
                $teachers[$id] = new Teacher($id, $name, $subjectIds, $maxPeriodsPerDay);
            }
        }
    }
    
    return $teachers;
}

/**
 * Get all unique class identifiers from the data
 */
function getAllClassRanges() {
    $subjectData = SubjectData::getSubjects();
    $classRanges = [];
    
    foreach ($subjectData as $subject) {
        $range = $subject['class_range'];
        if (!in_array($range, $classRanges)) {
            $classRanges[] = $range;
        }
    }
    
    return $classRanges;
}

/**
 * Create sections for a given class
 */
function createSectionsForClass($classNum, $subjects) {
    $compulsorySubjects = [];
    $optionalSubjects = [];
    $coCurricularSubjects = [];

    foreach ($subjects as $id => $subject) {
        if ($subject->type === 'compulsory') {
            $compulsorySubjects[] = $id;
        } elseif ($subject->type === 'optional') {
            $optionalSubjects[] = $id;
        } elseif ($subject->type === 'co_curricular') {
            $coCurricularSubjects[] = $id;
        }
    }
    
    // Create two sections (A and B) for each class
    $sections = [];
    
    $sectionA = new Section($classNum . 'A', (string)$classNum, 'A');
    $sectionA->compulsorySubjects = $compulsorySubjects;
    $sectionA->optionalSubjects = $optionalSubjects;
    $sectionA->coCurricularSubjects = array_slice($coCurricularSubjects, 0, max(1, intval(count($coCurricularSubjects) / 2)));
    $sections[] = $sectionA;

    $sectionB = new Section($classNum . 'B', (string)$classNum, 'B');
    $sectionB->compulsorySubjects = $compulsorySubjects;
    $sectionB->optionalSubjects = $optionalSubjects;
    $sectionB->coCurricularSubjects = array_slice($coCurricularSubjects, max(1, intval(count($coCurricularSubjects) / 2)));
    $sections[] = $sectionB;
    
    return $sections;
}

/**
 * Convert class range to array of class numbers
 */
function expandClassRange($classRange) {
    $classRange = trim($classRange);
    
    // Check if it's a range (e.g., "1 - 4")
    if (strpos($classRange, '-') !== false) {
        $parts = array_map('trim', explode('-', $classRange));
        $start = intval($parts[0]);
        $end = intval($parts[1]);
        return range($start, $end);
    }
    
    // Single class (e.g., "8")
    return [intval($classRange)];
}

echo "Loading real data from SubjectData.php and TeacherData.php...\n";
echo str_repeat("-", 80) . "\n";

// Get all class ranges from the data
$classRanges = getAllClassRanges();
echo "Found class ranges: " . implode(', ', $classRanges) . "\n\n";

// Initialize statistics
$totalClasses = 0;
$totalSections = 0;
$totalTime = 0;
$allResults = [];

// Process each class range
foreach ($classRanges as $classRange) {
    $classes = expandClassRange($classRange);
    
    foreach ($classes as $targetClass) {
        echo "\n" . str_repeat("═", 80) . "\n";
        echo "  PROCESSING CLASS $targetClass\n";
        echo str_repeat("═", 80) . "\n\n";
        
        // Load subjects and teachers for this class
        $subjects = loadSubjectsForClass($targetClass);
        $teachers = loadTeachersForClass($targetClass, $subjects);
        
        // Check if we have data for this class
        if (empty($subjects)) {
            echo "⚠ No subjects found for Class $targetClass - skipping...\n";
            continue;
        }
        
        if (empty($teachers)) {
            echo "⚠ No teachers found for Class $targetClass - skipping...\n";
            continue;
        }
        
        echo "✓ Loaded " . count($subjects) . " subjects for Class $targetClass\n";
        echo "✓ Loaded " . count($teachers) . " teachers for Class $targetClass\n\n";
        
        // Create sections for this class
        $sections = createSectionsForClass($targetClass, $subjects);
        
        echo "Creating timetables for " . count($sections) . " sections...\n";
        
        // Create scheduler
        $scheduler = new GeneticAlgorithmScheduler(
            $subjects,
            $teachers,
            $sections,
            100,  // population size
            1000  // max generations
        );
        
        // Generate timetable
        echo "Running genetic algorithm...\n";
        
        $startTime = microtime(true);
        $bestTimetable = $scheduler->generateTimetable();
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        echo "✓ Completed in " . round($executionTime, 2) . " seconds\n";
        echo "✓ Best Fitness: " . round($bestTimetable->fitness, 4) . "\n";
        
        // Store results for summary
        $allResults[] = [
            'class' => $targetClass,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'timetable' => $bestTimetable,
            'time' => $executionTime,
            'fitness' => $bestTimetable->fitness
        ];
        
        $totalClasses++;
        $totalSections += count($sections);
        $totalTime += $executionTime;
    }
}

echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║  DISPLAYING GENERATED TIMETABLES                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Display all timetables
foreach ($allResults as $result) {
    $targetClass = $result['class'];
    $sections = $result['sections'];
    $subjects = $result['subjects'];
    $teachers = $result['teachers'];
    $bestTimetable = $result['timetable'];
    
    echo "\n" . str_repeat("▓", 80) . "\n";
    echo "  CLASS $targetClass TIMETABLES\n";
    echo str_repeat("▓", 80) . "\n";
    
    foreach ($sections as $section) {
        echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  TIMETABLE FOR SECTION: Class " . $targetClass . " - Section " . $section->name . str_repeat(" ", max(0, 35 - strlen((string)$targetClass))) . "║\n";
        echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";
        
        echo str_pad("Period", 12) . "| ";
        for ($p = 0; $p < 8; $p++) {
            echo str_pad("P" . ($p + 1), 10) . " | ";
        }
        echo "\n" . str_repeat("-", 100) . "\n";
        
        for ($day = 0; $day < 6; $day++) {
            echo str_pad($days[$day], 12) . "| ";
            
            for ($period = 0; $period < 8; $period++) {
                $slot = $bestTimetable->slots[$section->id][$day][$period];
                $subjectName = $slot->subjectId ? $subjects[$slot->subjectId]->name : 'Empty';
                $teacherName = $slot->teacherId ? substr($teachers[$slot->teacherId]->name, 0, 7) : '';
                
                $display = substr($subjectName, 0, 10);
                echo str_pad($display, 10) . " | ";
            }
            echo "\n";
        }
        echo "\n";
        
        // Display subject allocation
        echo "Subject Allocation:\n";
        echo str_repeat("-", 50) . "\n";
        
        $subjectCounts = [];
        for ($day = 0; $day < 6; $day++) {
            for ($period = 0; $period < 8; $period++) {
                $subjectId = $bestTimetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId) {
                    $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                }
            }
        }
        
        foreach ($subjectCounts as $subjectId => $count) {
            $subject = $subjects[$subjectId];
            $status = ($count >= $subject->minPeriodsPerWeek && $count <= $subject->maxPeriodsPerWeek) 
                ? '✓' : '✗';
            echo sprintf(
                "%s %-15s : %2d periods (min: %d, max: %d)\n",
                $status,
                $subject->name,
                $count,
                $subject->minPeriodsPerWeek,
                $subject->maxPeriodsPerWeek
            );
        }
        echo "\n";
    }
    
    // Display teacher workload for this class
    echo "\n┌─────────────────────────────────────────────────────────┐\n";
    echo "│  TEACHER WORKLOAD - CLASS $targetClass" . str_repeat(" ", max(0, 30 - strlen((string)$targetClass))) . "│\n";
    echo "└─────────────────────────────────────────────────────────┘\n\n";
    
    foreach ($teachers as $teacher) {
        echo "Teacher: " . $teacher->name . "\n";
        echo str_repeat("-", 50) . "\n";
        
        $dailyLoad = [];
        $totalLoad = 0;
        
        for ($day = 0; $day < 6; $day++) {
            $dayCount = 0;
            foreach ($sections as $section) {
                for ($period = 0; $period < 8; $period++) {
                    if ($bestTimetable->slots[$section->id][$day][$period]->teacherId === $teacher->id) {
                        $dayCount++;
                        $totalLoad++;
                    }
                }
            }
            $dailyLoad[$days[$day]] = $dayCount;
        }
        
        foreach ($dailyLoad as $day => $count) {
            $status = $count <= $teacher->maxPeriodsPerDay ? '✓' : '✗';
            echo sprintf("  %s %-10s : %d periods (max: %d)\n", $status, $day, $count, $teacher->maxPeriodsPerDay);
        }
        echo "  Total weekly periods: " . $totalLoad . "\n\n";
    }
}

// Display summary statistics
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║  SUMMARY STATISTICS                                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "Total Classes Processed:        " . $totalClasses . "\n";
echo "Total Sections Generated:       " . $totalSections . "\n";
echo "Total Execution Time:           " . round($totalTime, 2) . " seconds\n";
echo "Average Time per Class:         " . ($totalClasses > 0 ? round($totalTime / $totalClasses, 2) : 0) . " seconds\n\n";

echo "Class-wise Performance:\n";
echo str_repeat("-", 80) . "\n";
echo str_pad("Class", 10) . " | " . str_pad("Sections", 10) . " | " . str_pad("Time (s)", 12) . " | " . str_pad("Fitness", 10) . "\n";
echo str_repeat("-", 80) . "\n";

foreach ($allResults as $result) {
    echo str_pad($result['class'], 10) . " | ";
    echo str_pad(count($result['sections']), 10) . " | ";
    echo str_pad(round($result['time'], 2), 12) . " | ";
    echo str_pad(round($result['fitness'], 4), 10) . "\n";
}

echo "\n";
echo "Status: ";
if ($totalClasses > 0) {
    $avgFitness = array_sum(array_column($allResults, 'fitness')) / count($allResults);
    if ($avgFitness >= 0.9) {
        echo "✓ EXCELLENT - All timetables generated successfully!\n";
    } elseif ($avgFitness >= 0.7) {
        echo "✓ GOOD - Timetables generated with acceptable quality.\n";
    } else {
        echo "⚠ WARNING - Some timetables may have constraint violations.\n";
    }
} else {
    echo "✗ ERROR - No classes were processed.\n";
}

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║  Test Completed Successfully!                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
