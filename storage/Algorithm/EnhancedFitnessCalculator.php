<?php
/**
 * Enhanced FitnessCalculator with Hierarchical Exponential Penalties and Quality Bonuses
 * 
 * This fitness calculator provides:
 * - Hierarchical exponential penalties for different violation severities
 * - Quality bonuses for optimization goals
 * - Normalized fitness scores (0-1 scale)
 * - Detailed breakdown for debugging and logging
 * 
 * @author Timetable System
 * @version 3.0.0
 * @package TimetableScheduler
 */

/**
 * Detailed fitness breakdown for analysis and debugging
 */
class FitnessBreakdown {
    // Critical violations (teacher conflicts, empty slots)
    public $teacherConflictCount = 0;
    public $teacherConflictPenalty = 0;
    public $emptySlotCount = 0;
    public $emptySlotPenalty = 0;
    
    // High violations (weekly allocation, combined subjects)
    public $weeklyAllocationCount = 0;
    public $weeklyAllocationPenalty = 0;
    public $combinedSubjectCount = 0;
    public $combinedSubjectPenalty = 0;
    public $coCurricularConsecutiveCount = 0;
    public $coCurricularConsecutivePenalty = 0;
    
    // Medium violations (workload)
    public $teacherWorkloadCount = 0;
    public $teacherWorkloadPenalty = 0;
    public $maxTwoPerDayCount = 0;
    public $maxTwoPerDayPenalty = 0;
    public $coCurricularSameDayCount = 0;
    public $coCurricularSameDayPenalty = 0;
    
    // Soft violations
    public $positionalConsistencyCount = 0;
    public $positionalConsistencyPenalty = 0;
    public $maxOnePerDayCount = 0;
    public $maxOnePerDayPenalty = 0;
    public $coreSubjectConsistencyCount = 0;
    public $coreSubjectConsistencyPenalty = 0;
    public $heavySubjectSpacingCount = 0;
    public $heavySubjectSpacingPenalty = 0;
    public $coCurricularPlacementCount = 0;
    public $coCurricularPlacementPenalty = 0;
    
    // Quality bonuses
    public $perfectWeeklyAllocationBonus = 0;
    public $positionalConsistencyBonus = 0;
    public $coreSubjectConsistencyBonus = 0;
    public $coCurricularPlacementBonus = 0;
    
    // Totals
    public $totalCriticalPenalty = 0;
    public $totalHighPenalty = 0;
    public $totalMediumPenalty = 0;
    public $totalSoftPenalty = 0;
    public $totalPenalty = 0;
    public $totalQualityBonus = 0;
    public $rawScore = 0;
    public $normalizedFitness = 0;
    
    /**
     * Calculate all totals
     */
    public function calculateTotals() {
        // Critical penalties
        $this->totalCriticalPenalty = $this->teacherConflictPenalty + $this->emptySlotPenalty;
        
        // High penalties
        $this->totalHighPenalty = $this->weeklyAllocationPenalty + 
                                  $this->combinedSubjectPenalty + 
                                  $this->coCurricularConsecutivePenalty;
        
        // Medium penalties
        $this->totalMediumPenalty = $this->teacherWorkloadPenalty + 
                                    $this->maxTwoPerDayPenalty + 
                                    $this->coCurricularSameDayPenalty;
        
        // Soft penalties
        $this->totalSoftPenalty = $this->positionalConsistencyPenalty + 
                                  $this->maxOnePerDayPenalty + 
                                  $this->coreSubjectConsistencyPenalty + 
                                  $this->heavySubjectSpacingPenalty + 
                                  $this->coCurricularPlacementPenalty;
        
        // Total penalty
        $this->totalPenalty = $this->totalCriticalPenalty + 
                              $this->totalHighPenalty + 
                              $this->totalMediumPenalty + 
                              $this->totalSoftPenalty;
        
        // Total quality bonus
        $this->totalQualityBonus = $this->perfectWeeklyAllocationBonus + 
                                   $this->positionalConsistencyBonus + 
                                   $this->coreSubjectConsistencyBonus + 
                                   $this->coCurricularPlacementBonus;
        
        // Raw score (before normalization)
        $this->rawScore = $this->totalPenalty - $this->totalQualityBonus;
        
        // Normalized fitness (0-1 scale)
        $this->normalizedFitness = 1 / (1 + $this->rawScore);
    }
    
    /**
     * Get a formatted string representation of the breakdown
     * 
     * @param bool $detailed Whether to include detailed counts
     * @return string Formatted breakdown
     */
    public function toString($detailed = false) {
        $output = "=== FITNESS BREAKDOWN ===\n";
        $output .= sprintf("Normalized Fitness: %.6f\n", $this->normalizedFitness);
        $output .= sprintf("Total Penalty: %.2f\n", $this->totalPenalty);
        $output .= sprintf("Total Quality Bonus: %.2f\n\n", $this->totalQualityBonus);
        
        if ($detailed) {
            // Critical violations
            $output .= "CRITICAL VIOLATIONS (penalty = violations^2 * 100):\n";
            $output .= sprintf("  Teacher Conflicts: %d (penalty: %.2f)\n", 
                             $this->teacherConflictCount, $this->teacherConflictPenalty);
            $output .= sprintf("  Empty Slots: %d (penalty: %.2f)\n", 
                             $this->emptySlotCount, $this->emptySlotPenalty);
            $output .= sprintf("  Subtotal: %.2f\n\n", $this->totalCriticalPenalty);
            
            // High violations
            $output .= "HIGH VIOLATIONS (penalty = violations^1.5 * 50):\n";
            $output .= sprintf("  Weekly Allocation: %d (penalty: %.2f)\n", 
                             $this->weeklyAllocationCount, $this->weeklyAllocationPenalty);
            $output .= sprintf("  Combined Subjects: %d (penalty: %.2f)\n", 
                             $this->combinedSubjectCount, $this->combinedSubjectPenalty);
            $output .= sprintf("  Co-curricular Consecutive: %d (penalty: %.2f)\n", 
                             $this->coCurricularConsecutiveCount, $this->coCurricularConsecutivePenalty);
            $output .= sprintf("  Subtotal: %.2f\n\n", $this->totalHighPenalty);
            
            // Medium violations
            $output .= "MEDIUM VIOLATIONS (penalty = violations * 20):\n";
            $output .= sprintf("  Teacher Workload: %d (penalty: %.2f)\n", 
                             $this->teacherWorkloadCount, $this->teacherWorkloadPenalty);
            $output .= sprintf("  Max Two Per Day: %d (penalty: %.2f)\n", 
                             $this->maxTwoPerDayCount, $this->maxTwoPerDayPenalty);
            $output .= sprintf("  Co-curricular Same Day: %d (penalty: %.2f)\n", 
                             $this->coCurricularSameDayCount, $this->coCurricularSameDayPenalty);
            $output .= sprintf("  Subtotal: %.2f\n\n", $this->totalMediumPenalty);
            
            // Soft violations
            $output .= "SOFT VIOLATIONS (penalty = violations * 1-5):\n";
            $output .= sprintf("  Positional Consistency: %d (penalty: %.2f)\n", 
                             $this->positionalConsistencyCount, $this->positionalConsistencyPenalty);
            $output .= sprintf("  Max One Per Day: %d (penalty: %.2f)\n", 
                             $this->maxOnePerDayCount, $this->maxOnePerDayPenalty);
            $output .= sprintf("  Core Subject Consistency: %d (penalty: %.2f)\n", 
                             $this->coreSubjectConsistencyCount, $this->coreSubjectConsistencyPenalty);
            $output .= sprintf("  Heavy Subject Spacing: %d (penalty: %.2f)\n", 
                             $this->heavySubjectSpacingCount, $this->heavySubjectSpacingPenalty);
            $output .= sprintf("  Co-curricular Placement: %d (penalty: %.2f)\n", 
                             $this->coCurricularPlacementCount, $this->coCurricularPlacementPenalty);
            $output .= sprintf("  Subtotal: %.2f\n\n", $this->totalSoftPenalty);
            
            // Quality bonuses
            $output .= "QUALITY BONUSES:\n";
            $output .= sprintf("  Perfect Weekly Allocation: %.2f\n", $this->perfectWeeklyAllocationBonus);
            $output .= sprintf("  Positional Consistency: %.2f\n", $this->positionalConsistencyBonus);
            $output .= sprintf("  Core Subject Consistency: %.2f\n", $this->coreSubjectConsistencyBonus);
            $output .= sprintf("  Co-curricular Placement: %.2f\n", $this->coCurricularPlacementBonus);
            $output .= sprintf("  Subtotal: %.2f\n", $this->totalQualityBonus);
        }
        
        return $output;
    }
    
    /**
     * Get total violation count (sum of all violation counts)
     * 
     * @return int Total number of violations
     */
    public function getTotalViolationCount() {
        return $this->teacherConflictCount + $this->emptySlotCount + 
               $this->weeklyAllocationCount + $this->combinedSubjectCount + 
               $this->coCurricularConsecutiveCount + $this->teacherWorkloadCount + 
               $this->maxTwoPerDayCount + $this->coCurricularSameDayCount + 
               $this->positionalConsistencyCount + $this->maxOnePerDayCount + 
               $this->coreSubjectConsistencyCount + $this->heavySubjectSpacingCount + 
               $this->coCurricularPlacementCount;
    }
}

/**
 * Enhanced Fitness Calculator with Hierarchical Exponential Penalties
 */
class EnhancedFitnessCalculator {
    /** @var ConstraintChecker Constraint checker instance */
    private $checker;
    
    /** @var FitnessBreakdown|null Last breakdown for debugging */
    private $lastBreakdown = null;
    
    /**
     * Create a new EnhancedFitnessCalculator
     * 
     * @param ConstraintChecker $checker Constraint checker instance
     */
    public function __construct($checker) {
        $this->checker = $checker;
    }
    
    /**
     * Calculate fitness score for a timetable with detailed breakdown
     * 
     * @param Timetable $timetable Timetable to evaluate
     * @param bool $trackBreakdown Whether to store detailed breakdown (default: true)
     * @return float Normalized fitness score (0-1, higher is better)
     */
    public function calculate($timetable, $trackBreakdown = true) {
        $breakdown = new FitnessBreakdown();
        
        // Calculate all penalties and bonuses
        $this->calculateCriticalPenalties($timetable, $breakdown);
        $this->calculateHighPenalties($timetable, $breakdown);
        $this->calculateMediumPenalties($timetable, $breakdown);
        $this->calculateSoftPenalties($timetable, $breakdown);
        $this->calculateQualityBonuses($timetable, $breakdown);
        
        // Calculate totals and normalized fitness
        $breakdown->calculateTotals();
        
        // Store breakdown for later retrieval
        if ($trackBreakdown) {
            $this->lastBreakdown = $breakdown;
        }
        
        return $breakdown->normalizedFitness;
    }
    
    /**
     * Calculate critical violation penalties (teacher conflicts, empty slots)
     * Formula: penalty = violations^2 * 100
     * 
     * @param Timetable $timetable Timetable to check
     * @param FitnessBreakdown $breakdown Breakdown to populate
     */
    private function calculateCriticalPenalties($timetable, $breakdown) {
        // Teacher conflicts - CRITICAL
        $breakdown->teacherConflictCount = $this->checker->checkTeacherConflicts($timetable);
        $breakdown->teacherConflictPenalty = pow($breakdown->teacherConflictCount, 2) * 100;
        
        // Empty slots - CRITICAL
        $breakdown->emptySlotCount = $this->checker->checkNoEmptySlots($timetable);
        $breakdown->emptySlotPenalty = pow($breakdown->emptySlotCount, 2) * 100;
    }
    
    /**
     * Calculate high violation penalties (weekly allocation, combined subjects)
     * Formula: penalty = violations^1.5 * 50
     * 
     * @param Timetable $timetable Timetable to check
     * @param FitnessBreakdown $breakdown Breakdown to populate
     */
    private function calculateHighPenalties($timetable, $breakdown) {
        // Weekly allocation violations - HIGH
        $breakdown->weeklyAllocationCount = $this->checker->checkSubjectWeeklyAllocation($timetable);
        $breakdown->weeklyAllocationPenalty = pow($breakdown->weeklyAllocationCount, 1.5) * 50;
        
        // Combined subjects violations - HIGH
        $breakdown->combinedSubjectCount = $this->checker->checkCombinedSubjects($timetable);
        $breakdown->combinedSubjectPenalty = pow($breakdown->combinedSubjectCount, 1.5) * 50;
        
        // Co-curricular consecutive violations - HIGH
        $breakdown->coCurricularConsecutiveCount = $this->checker->checkCoCurricularConsecutive($timetable);
        $breakdown->coCurricularConsecutivePenalty = pow($breakdown->coCurricularConsecutiveCount, 1.5) * 50;
    }
    
    /**
     * Calculate medium violation penalties (workload constraints)
     * Formula: penalty = violations * 20
     * 
     * @param Timetable $timetable Timetable to check
     * @param FitnessBreakdown $breakdown Breakdown to populate
     */
    private function calculateMediumPenalties($timetable, $breakdown) {
        // Teacher workload violations - MEDIUM
        $breakdown->teacherWorkloadCount = $this->checker->checkTeacherWorkload($timetable);
        $breakdown->teacherWorkloadPenalty = $breakdown->teacherWorkloadCount * 20;
        
        // Max two periods per subject per day - MEDIUM
        $breakdown->maxTwoPerDayCount = $this->checker->checkMaxTwoPeriodsPerSubjectPerDay($timetable);
        $breakdown->maxTwoPerDayPenalty = $breakdown->maxTwoPerDayCount * 20;
        
        // Co-curricular same day violations - MEDIUM
        $breakdown->coCurricularSameDayCount = $this->checker->checkNoTwoCoCurricularSameDay($timetable);
        $breakdown->coCurricularSameDayPenalty = $breakdown->coCurricularSameDayCount * 20;
    }
    
    /**
     * Calculate soft violation penalties (optimization preferences)
     * Formula: penalty = violations * weight (1-5)
     * 
     * @param Timetable $timetable Timetable to check
     * @param FitnessBreakdown $breakdown Breakdown to populate
     */
    private function calculateSoftPenalties($timetable, $breakdown) {
        // Positional consistency violations - SOFT (weight: 5)
        $breakdown->positionalConsistencyCount = $this->checker->checkPositionalConsistency($timetable);
        $breakdown->positionalConsistencyPenalty = $breakdown->positionalConsistencyCount * 5;
        
        // Max one per subject per day - SOFT (weight: 3)
        $breakdown->maxOnePerDayCount = $this->checker->checkMaxOnePerSubjectPerDay($timetable);
        $breakdown->maxOnePerDayPenalty = $breakdown->maxOnePerDayCount * 3;
        
        // Core subject consistency - SOFT (weight: 4)
        $breakdown->coreSubjectConsistencyCount = $this->checker->checkCoreSubjectConsistency($timetable);
        $breakdown->coreSubjectConsistencyPenalty = $breakdown->coreSubjectConsistencyCount * 4;
        
        // Heavy subject spacing - SOFT (weight: 2)
        $breakdown->heavySubjectSpacingCount = $this->checker->checkHeavySubjectSpacing($timetable);
        $breakdown->heavySubjectSpacingPenalty = $breakdown->heavySubjectSpacingCount * 2;
        
        // Co-curricular placement - SOFT (weight: 1)
        $breakdown->coCurricularPlacementCount = $this->checker->checkCoCurricularPlacement($timetable);
        $breakdown->coCurricularPlacementPenalty = $breakdown->coCurricularPlacementCount * 1;
    }
    
    /**
     * Calculate quality bonuses for optimization goals
     * 
     * @param Timetable $timetable Timetable to check
     * @param FitnessBreakdown $breakdown Breakdown to populate
     */
    private function calculateQualityBonuses($timetable, $breakdown) {
        // Bonus for perfect weekly allocation (all subjects within min/max)
        $breakdown->perfectWeeklyAllocationBonus = $this->calculatePerfectAllocationBonus($timetable);
        
        // Bonus for positional consistency across days (same subjects at similar times)
        $breakdown->positionalConsistencyBonus = $this->calculatePositionalConsistencyBonus($timetable);
        
        // Bonus for core subjects at consistent times
        $breakdown->coreSubjectConsistencyBonus = $this->calculateCoreSubjectConsistencyBonus($timetable);
        
        // Bonus for ideal co-curricular placement (late periods)
        $breakdown->coCurricularPlacementBonus = $this->calculateCoCurricularPlacementBonus($timetable);
    }
    
    /**
     * Calculate bonus for perfect weekly allocation
     * Rewards when ALL subjects meet their min/max requirements exactly
     * 
     * @param Timetable $timetable Timetable to check
     * @return float Bonus value
     */
    private function calculatePerfectAllocationBonus($timetable) {
        $sections = $timetable->getSections();
        $totalBonus = 0;
        
        foreach ($sections as $section) {
            $subjectCounts = $this->getSubjectCounts($timetable, $section);
            $allPerfect = true;
            
            $allSubjects = array_merge(
                $section->compulsorySubjects ?? [],
                $section->optionalSubjects ?? [],
                $section->coCurricularSubjects ?? []
            );
            
            foreach ($allSubjects as $subjectId) {
                $subject = $this->checker->getSubject($subjectId);
                if (!$subject) continue;
                
                $count = $subjectCounts[$subjectId] ?? 0;
                
                // Check if subject is within min/max (perfect allocation)
                if ($count < $subject->minPeriodsPerWeek || $count > $subject->maxPeriodsPerWeek) {
                    $allPerfect = false;
                    break;
                }
            }
            
            // Award bonus if all subjects in this section are perfectly allocated
            if ($allPerfect && count($allSubjects) > 0) {
                $totalBonus += 50; // Significant bonus for perfect allocation
            }
        }
        
        return $totalBonus;
    }
    
    /**
     * Calculate bonus for positional consistency
     * Rewards when subjects appear at similar time slots across days
     * 
     * @param Timetable $timetable Timetable to check
     * @return float Bonus value
     */
    private function calculatePositionalConsistencyBonus($timetable) {
        $sections = $timetable->getSections();
        $totalBonus = 0;
        
        foreach ($sections as $section) {
            $subjectPositions = [];
            
            // Collect all positions for each subject
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $slot = $timetable->slots[$section->id][$day][$period];
                    if ($slot->subjectId) {
                        if (!isset($subjectPositions[$slot->subjectId])) {
                            $subjectPositions[$slot->subjectId] = [];
                        }
                        $subjectPositions[$slot->subjectId][] = $period;
                    }
                }
            }
            
            // Calculate consistency for each subject
            foreach ($subjectPositions as $subjectId => $positions) {
                if (count($positions) < 2) continue;
                
                // Calculate standard deviation of positions
                $mean = array_sum($positions) / count($positions);
                $variance = 0;
                foreach ($positions as $pos) {
                    $variance += pow($pos - $mean, 2);
                }
                $variance /= count($positions);
                $stdDev = sqrt($variance);
                
                // Award bonus inversely proportional to standard deviation
                // Lower std dev = higher consistency = higher bonus
                if ($stdDev < 1.0) {
                    $totalBonus += 10; // Excellent consistency
                } elseif ($stdDev < 2.0) {
                    $totalBonus += 5; // Good consistency
                }
            }
        }
        
        return $totalBonus;
    }
    
    /**
     * Calculate bonus for core subject consistency
     * Rewards when core subjects (compulsory) appear at consistent times
     * 
     * @param Timetable $timetable Timetable to check
     * @return float Bonus value
     */
    private function calculateCoreSubjectConsistencyBonus($timetable) {
        $sections = $timetable->getSections();
        $totalBonus = 0;
        
        foreach ($sections as $section) {
            $coreSubjects = $section->compulsorySubjects ?? [];
            $corePositions = [];
            
            // Collect positions for core subjects only
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $slot = $timetable->slots[$section->id][$day][$period];
                    if ($slot->subjectId && in_array($slot->subjectId, $coreSubjects)) {
                        if (!isset($corePositions[$slot->subjectId])) {
                            $corePositions[$slot->subjectId] = [];
                        }
                        $corePositions[$slot->subjectId][] = $period;
                    }
                }
            }
            
            // Reward if core subjects have low variance in their positions
            foreach ($corePositions as $subjectId => $positions) {
                if (count($positions) < 2) continue;
                
                $mean = array_sum($positions) / count($positions);
                $variance = 0;
                foreach ($positions as $pos) {
                    $variance += pow($pos - $mean, 2);
                }
                $variance /= count($positions);
                $stdDev = sqrt($variance);
                
                // Higher bonus for core subjects with consistent timing
                if ($stdDev < 1.0) {
                    $totalBonus += 15; // Excellent core consistency
                } elseif ($stdDev < 2.0) {
                    $totalBonus += 8; // Good core consistency
                }
            }
        }
        
        return $totalBonus;
    }
    
    /**
     * Calculate bonus for ideal co-curricular placement
     * Rewards when co-curricular activities are placed in later periods
     * 
     * @param Timetable $timetable Timetable to check
     * @return float Bonus value
     */
    private function calculateCoCurricularPlacementBonus($timetable) {
        $sections = $timetable->getSections();
        $totalBonus = 0;
        $idealStartPeriod = SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD ?? 4;
        
        foreach ($sections as $section) {
            $coCurricularSubjects = $section->coCurricularSubjects ?? [];
            $wellPlacedCount = 0;
            $totalCoCurricular = 0;
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $slot = $timetable->slots[$section->id][$day][$period];
                    if ($slot->subjectId && in_array($slot->subjectId, $coCurricularSubjects)) {
                        $totalCoCurricular++;
                        
                        // Award points for placement in preferred periods
                        if ($period >= $idealStartPeriod) {
                            $wellPlacedCount++;
                        }
                    }
                }
            }
            
            // Calculate bonus based on percentage of well-placed co-curricular periods
            if ($totalCoCurricular > 0) {
                $placementRatio = $wellPlacedCount / $totalCoCurricular;
                $totalBonus += $placementRatio * 20; // Up to 20 points per section
            }
        }
        
        return $totalBonus;
    }
    
    /**
     * Helper: Get subject counts for a section
     * 
     * @param Timetable $timetable Timetable to check
     * @param Section $section Section to analyze
     * @return array Subject ID => count mapping
     */
    private function getSubjectCounts($timetable, $section) {
        $counts = [];
        
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $slot = $timetable->slots[$section->id][$day][$period];
                if ($slot->subjectId) {
                    $counts[$slot->subjectId] = ($counts[$slot->subjectId] ?? 0) + 1;
                }
            }
        }
        
        return $counts;
    }
    
    /**
     * Get the last fitness breakdown for analysis
     * 
     * @return FitnessBreakdown|null Last breakdown or null
     */
    public function getLastBreakdown() {
        return $this->lastBreakdown;
    }
    
    /**
     * Get a formatted breakdown string
     * 
     * @param bool $detailed Whether to include detailed counts
     * @return string Formatted breakdown
     */
    public function getBreakdownString($detailed = false) {
        if (!$this->lastBreakdown) {
            return "No breakdown available. Run calculate() first.";
        }
        return $this->lastBreakdown->toString($detailed);
    }
    
    /**
     * Compare two timetables and return improvement metrics
     * 
     * @param Timetable $oldTimetable Previous timetable
     * @param Timetable $newTimetable New timetable
     * @return array Improvement metrics
     */
    public function compareImprovements($oldTimetable, $newTimetable) {
        $oldFitness = $this->calculate($oldTimetable, true);
        $oldBreakdown = clone $this->lastBreakdown;
        
        $newFitness = $this->calculate($newTimetable, true);
        $newBreakdown = $this->lastBreakdown;
        
        return [
            'fitness_improvement' => $newFitness - $oldFitness,
            'fitness_improvement_percent' => (($newFitness - $oldFitness) / max(0.001, $oldFitness)) * 100,
            'old_fitness' => $oldFitness,
            'new_fitness' => $newFitness,
            'violation_reduction' => $oldBreakdown->getTotalViolationCount() - $newBreakdown->getTotalViolationCount(),
            'penalty_reduction' => $oldBreakdown->totalPenalty - $newBreakdown->totalPenalty,
            'quality_improvement' => $newBreakdown->totalQualityBonus - $oldBreakdown->totalQualityBonus,
            'old_violations' => $oldBreakdown->getTotalViolationCount(),
            'new_violations' => $newBreakdown->getTotalViolationCount(),
        ];
    }
}
