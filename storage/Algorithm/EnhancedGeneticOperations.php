<?php
/**
 * Enhanced Genetic Operations for School Timetable Scheduling
 * Implements advanced crossover operators, adaptive mutation system, and violation-aware selection
 * 
 * @version 2.0.0
 * @package TimetableScheduler
 */

/**
 * Enhanced GeneticOperations class with adaptive mutation and multiple crossover operators
 */
class EnhancedGeneticOperations {
    /** @var array Indexed array of Subject objects */
    private $subjects;
    
    /** @var array Indexed array of Teacher objects */
    private $teachers;
    
    /** @var array Array of Section objects */
    private $sections;
    
    /** @var TimetableRepair Repair helper instance */
    private $repairHelper;
    
    /** @var ConstraintChecker Constraint checker instance */
    private $constraintChecker;
    
    /** @var FitnessCalculator Fitness calculator instance */
    private $fitnessCalculator;
    
    /**
     * Create a new EnhancedGeneticOperations instance
     * 
     * @param array $subjects Subject objects
     * @param array $teachers Teacher objects
     * @param array $sections Section objects
     */
    public function __construct($subjects, $teachers, $sections) {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
        $this->repairHelper = new TimetableRepair($subjects, $teachers, $sections);
        $this->constraintChecker = new ConstraintChecker($subjects, $teachers, $sections);
        $this->fitnessCalculator = new FitnessCalculator($this->constraintChecker);
    }
    
    // ========================================
    // ENHANCED CROSSOVER OPERATORS
    // ========================================
    
    /**
     * Perform crossover between two parent timetables using random selection from available operators
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @param string|null $method Specific crossover method to use (null = random selection)
     * @return Timetable Offspring timetable
     */
    public function crossover($parent1, $parent2, $method = null) {
        if ($method === null) {
            // Randomly select crossover method with weighted probabilities
            $rand = mt_rand(1, 100);
            if ($rand <= 40) {
                $method = 'single_point'; // 40% - Original method
            } elseif ($rand <= 65) {
                $method = 'uniform'; // 25% - Uniform crossover
            } elseif ($rand <= 85) {
                $method = 'two_point'; // 20% - Two-point crossover
            } else {
                $method = 'kempe_chain'; // 15% - Kempe chain crossover
            }
        }
        
        switch ($method) {
            case 'uniform':
                return $this->uniformCrossover($parent1, $parent2);
            case 'kempe_chain':
                return $this->kempeChainCrossover($parent1, $parent2);
            case 'two_point':
                return $this->twoPointCrossover($parent1, $parent2);
            case 'single_point':
            default:
                return $this->singlePointCrossover($parent1, $parent2);
        }
    }
    
    /**
     * Single-point crossover (original method)
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @return Timetable Offspring timetable
     */
    private function singlePointCrossover($parent1, $parent2) {
        $offspring = new Timetable($this->sections);
        
        foreach ($this->sections as $section) {
            $crossoverDay = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($day < $crossoverDay) {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent1->slots[$section->id][$day][$period];
                    } else {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent2->slots[$section->id][$day][$period];
                    }
                }
            }
        }
        
        $this->repairHelper->repair($offspring);
        return $offspring;
    }
    
    /**
     * Two-point crossover - select two crossover points
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @return Timetable Offspring timetable
     */
    private function twoPointCrossover($parent1, $parent2) {
        $offspring = new Timetable($this->sections);
        
        foreach ($this->sections as $section) {
            $point1 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            $point2 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            
            // Ensure point1 <= point2
            if ($point1 > $point2) {
                list($point1, $point2) = [$point2, $point1];
            }
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    // Use parent2 genes between points, parent1 genes outside
                    if ($day >= $point1 && $day <= $point2) {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent2->slots[$section->id][$day][$period];
                    } else {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent1->slots[$section->id][$day][$period];
                    }
                }
            }
        }
        
        $this->repairHelper->repair($offspring);
        return $offspring;
    }
    
    /**
     * Uniform crossover - slot-by-slot gene exchange with 50% probability
     * Each time slot has equal probability of coming from either parent
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @return Timetable Offspring timetable
     */
    private function uniformCrossover($parent1, $parent2) {
        $offspring = new Timetable($this->sections);
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    // 50% probability: choose from parent1 or parent2
                    if (mt_rand(0, 1) == 0) {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent1->slots[$section->id][$day][$period];
                    } else {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent2->slots[$section->id][$day][$period];
                    }
                }
            }
        }
        
        $this->repairHelper->repair($offspring);
        return $offspring;
    }
    
    /**
     * Kempe chain crossover - exchange connected chains of teacher assignments
     * A Kempe chain is a set of connected time slots involving a specific teacher
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @return Timetable Offspring timetable
     */
    private function kempeChainCrossover($parent1, $parent2) {
        $offspring = $parent1->clone();
        
        // Select a random section and teacher
        $section = $this->sections[array_rand($this->sections)];
        $teacherIds = array_keys($this->teachers);
        
        if (empty($teacherIds)) {
            return $offspring;
        }
        
        $selectedTeacherId = $teacherIds[array_rand($teacherIds)];
        
        // Find all slots where this teacher appears in parent2
        $teacherChain = [];
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($parent2->slots[$section->id][$day][$period]->teacherId === $selectedTeacherId) {
                    $teacherChain[] = ['day' => $day, 'period' => $period];
                }
            }
        }
        
        // Exchange these slots from parent2 to offspring
        foreach ($teacherChain as $slot) {
            $offspring->slots[$section->id][$slot['day']][$slot['period']] = 
                clone $parent2->slots[$section->id][$slot['day']][$slot['period']];
        }
        
        // Also apply to connected subjects (subjects taught by this teacher)
        $teacher = $this->teachers[$selectedTeacherId];
        foreach ($teacher->subjects as $subjectId) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($parent2->slots[$section->id][$day][$period]->subjectId === $subjectId &&
                        $parent2->slots[$section->id][$day][$period]->teacherId === $selectedTeacherId) {
                        $offspring->slots[$section->id][$day][$period] = 
                            clone $parent2->slots[$section->id][$day][$period];
                    }
                }
            }
        }
        
        $this->repairHelper->repair($offspring);
        return $offspring;
    }
    
    // ========================================
    // ADAPTIVE MUTATION SYSTEM
    // ========================================
    
    /**
     * Mutate a timetable using adaptive mutation selection
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param bool $adaptiveMutate Use violation-aware adaptive selection (default: true)
     */
    public function mutate($timetable, $adaptiveMutate = true) {
        if ($adaptiveMutate) {
            $this->adaptiveMutation($timetable);
        } else {
            $this->randomMutation($timetable);
        }
    }
    
    /**
     * Adaptive mutation - selects mutation operator based on violation profile
     * 
     * @param Timetable $timetable Timetable to mutate
     */
    private function adaptiveMutation($timetable) {
        // Calculate current fitness and violation profile
        $originalFitness = $this->fitnessCalculator->calculate($timetable);
        $violations = $this->analyzeViolations($timetable);
        
        // Select mutation operator based on violations
        $mutationType = $this->selectAdaptiveMutationType($violations);
        
        // Create a backup in case mutation worsens fitness
        $backup = $timetable->clone();
        
        // Apply selected mutation
        $this->applyMutation($timetable, $mutationType);
        
        // Validate and calculate new fitness
        $newFitness = $this->fitnessCalculator->calculate($timetable);
        
        // Keep mutation only if it improves or maintains fitness
        if ($newFitness < $originalFitness) {
            // Restore backup if fitness decreased
            $timetable->slots = $backup->slots;
            $timetable->fitness = $backup->fitness;
        } else {
            $timetable->fitness = $newFitness;
        }
    }
    
    /**
     * Random mutation - randomly selects mutation operator
     * 
     * @param Timetable $timetable Timetable to mutate
     */
    private function randomMutation($timetable) {
        $mutationTypes = [
            'swap_periods_same_day',
            'swap_days',
            'change_teacher',
            'redistribute_subject',
            'repair_empty_slot',
            'local_search',
            'swap_periods_across_days',
            'reassign_subject'
        ];
        
        $mutationType = $mutationTypes[array_rand($mutationTypes)];
        $this->applyMutation($timetable, $mutationType);
        $this->repairHelper->repair($timetable);
    }
    
    /**
     * Analyze current violation profile
     * 
     * @param Timetable $timetable Timetable to analyze
     * @return array Violation counts by type
     */
    private function analyzeViolations($timetable) {
        $violations = [
            'teacher_conflicts' => 0,
            'empty_slots' => 0,
            'workload_violations' => 0,
            'daily_limit_violations' => 0,
            'subject_allocation_violations' => 0
        ];
        
        // Count teacher conflicts
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $teacherAssignments = [];
                foreach ($this->sections as $section) {
                    $teacherId = $timetable->slots[$section->id][$day][$period]->teacherId;
                    if ($teacherId) {
                        $teacherAssignments[$teacherId] = ($teacherAssignments[$teacherId] ?? 0) + 1;
                    }
                }
                
                foreach ($teacherAssignments as $count) {
                    if ($count > 1) {
                        $violations['teacher_conflicts'] += ($count - 1);
                    }
                }
            }
        }
        
        // Count empty slots
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                        $violations['empty_slots']++;
                    }
                }
            }
        }
        
        // Count workload violations
        foreach ($this->teachers as $teacher) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $dailyCount = 0;
                foreach ($this->sections as $section) {
                    for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                        if ($timetable->slots[$section->id][$day][$period]->teacherId === $teacher->id) {
                            $dailyCount++;
                        }
                    }
                }
                
                if ($dailyCount > $teacher->maxPeriodsPerDay) {
                    $violations['workload_violations'] += ($dailyCount - $teacher->maxPeriodsPerDay);
                }
            }
        }
        
        // Count daily subject limit violations (max 2 per day)
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $subjectCounts = [];
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if ($subjectId) {
                        $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                    }
                }
                
                foreach ($subjectCounts as $count) {
                    if ($count > SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                        $violations['daily_limit_violations'] += ($count - SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY);
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Select mutation type based on violation profile
     * 
     * @param array $violations Violation counts
     * @return string Selected mutation type
     */
    private function selectAdaptiveMutationType($violations) {
        // If high teacher conflicts, prioritize change_teacher mutation
        if ($violations['teacher_conflicts'] > 5) {
            if (mt_rand(1, 100) <= 60) { // 60% probability
                return 'change_teacher';
            }
        }
        
        // If empty slots exist, prioritize repair_empty_slot
        if ($violations['empty_slots'] > 3) {
            if (mt_rand(1, 100) <= 50) { // 50% probability
                return 'repair_empty_slot';
            }
        }
        
        // If workload violations, prioritize redistribute_subject
        if ($violations['workload_violations'] > 3) {
            if (mt_rand(1, 100) <= 50) { // 50% probability
                return 'redistribute_subject';
            }
        }
        
        // If daily limit violations, prioritize swap operations
        if ($violations['daily_limit_violations'] > 2) {
            if (mt_rand(1, 100) <= 40) { // 40% probability
                return 'swap_periods_same_day';
            }
        }
        
        // Otherwise, random selection with weighted probabilities
        $rand = mt_rand(1, 100);
        if ($rand <= 20) {
            return 'swap_periods_same_day';
        } elseif ($rand <= 35) {
            return 'swap_days';
        } elseif ($rand <= 50) {
            return 'change_teacher';
        } elseif ($rand <= 65) {
            return 'redistribute_subject';
        } elseif ($rand <= 80) {
            return 'local_search';
        } else {
            return 'repair_empty_slot';
        }
    }
    
    /**
     * Apply specific mutation to timetable
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param string $mutationType Type of mutation to apply
     */
    private function applyMutation($timetable, $mutationType) {
        $section = $this->sections[array_rand($this->sections)];
        
        switch ($mutationType) {
            case 'swap_periods_same_day':
                $this->swapPeriodsMutation($timetable, $section);
                break;
            case 'swap_days':
                $this->swapDaysMutation($timetable, $section);
                break;
            case 'change_teacher':
                $this->changeTeacherMutation($timetable, $section);
                break;
            case 'redistribute_subject':
                $this->redistributeSubjectMutation($timetable, $section);
                break;
            case 'repair_empty_slot':
                $this->repairEmptySlotMutation($timetable, $section);
                break;
            case 'local_search':
                $this->localSearchMutation($timetable, $section);
                break;
            case 'swap_periods_across_days':
                $this->swapPeriodsAcrossDays($timetable, $section);
                break;
            case 'reassign_subject':
                $this->reassignRandomSubject($timetable, $section);
                break;
        }
    }
    
    // ========================================
    // MUTATION OPERATORS
    // ========================================
    
    /**
     * Swap two random periods on the same day
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function swapPeriodsMutation($timetable, $section) {
        $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period1 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        $period2 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        if ($period1 !== $period2) {
            $temp = clone $timetable->slots[$section->id][$day][$period1];
            $timetable->slots[$section->id][$day][$period1] = 
                clone $timetable->slots[$section->id][$day][$period2];
            $timetable->slots[$section->id][$day][$period2] = $temp;
        }
    }
    
    /**
     * Swap entire days in the timetable
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function swapDaysMutation($timetable, $section) {
        $day1 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $day2 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        
        if ($day1 !== $day2) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $temp = clone $timetable->slots[$section->id][$day1][$period];
                $timetable->slots[$section->id][$day1][$period] = 
                    clone $timetable->slots[$section->id][$day2][$period];
                $timetable->slots[$section->id][$day2][$period] = $temp;
            }
        }
    }
    
    /**
     * Replace teacher for a random slot with a conflict-free alternative
     * Validates using ConstraintChecker before applying
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function changeTeacherMutation($timetable, $section) {
        $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        $slot = $timetable->slots[$section->id][$day][$period];
        $subjectId = $slot->subjectId;
        
        if (!$subjectId) {
            return;
        }
        
        // Find alternative teachers who can teach this subject
        $eligibleTeachers = [];
        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects) && $teacher->id !== $slot->teacherId) {
                // Validate using ConstraintChecker
                if ($this->constraintChecker->isTeacherAvailable($teacher->id, $day, $period, $timetable) &&
                    $this->constraintChecker->isTeacherUnderDailyLimit($teacher->id, $day, $timetable)) {
                    $eligibleTeachers[] = $teacher->id;
                }
            }
        }
        
        // Apply change if valid alternative exists
        if (!empty($eligibleTeachers)) {
            $slot->teacherId = $eligibleTeachers[array_rand($eligibleTeachers)];
        }
    }
    
    /**
     * Move subject from overloaded day to underloaded day
     * Balances workload distribution across the week
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function redistributeSubjectMutation($timetable, $section) {
        // Analyze daily workload for each subject
        $dailySubjectCounts = [];
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $dailySubjectCounts[$day] = [];
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId) {
                    $dailySubjectCounts[$day][$subjectId] = 
                        ($dailySubjectCounts[$day][$subjectId] ?? 0) + 1;
                }
            }
        }
        
        // Find overloaded and underloaded days for a random subject
        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
        
        if (empty($allSubjects)) {
            return;
        }
        
        $targetSubject = $allSubjects[array_rand($allSubjects)];
        
        $overloadedDay = null;
        $underloadedDay = null;
        
        // Find day with most occurrences
        $maxCount = 0;
        $minCount = PHP_INT_MAX;
        
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $count = $dailySubjectCounts[$day][$targetSubject] ?? 0;
            
            if ($count > $maxCount) {
                $maxCount = $count;
                $overloadedDay = $day;
            }
            
            if ($count < $minCount) {
                $minCount = $count;
                $underloadedDay = $day;
            }
        }
        
        // Move one occurrence from overloaded to underloaded day
        if ($overloadedDay !== null && $underloadedDay !== null && $overloadedDay !== $underloadedDay && $maxCount > 1) {
            // Find a slot with target subject on overloaded day
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($timetable->slots[$section->id][$overloadedDay][$period]->subjectId === $targetSubject) {
                    // Find empty or suitable slot on underloaded day
                    for ($targetPeriod = 0; $targetPeriod < SchedulerConfig::PERIODS_PER_DAY; $targetPeriod++) {
                        $targetSlot = $timetable->slots[$section->id][$underloadedDay][$targetPeriod];
                        
                        // Swap slots
                        $temp = clone $timetable->slots[$section->id][$overloadedDay][$period];
                        $timetable->slots[$section->id][$overloadedDay][$period] = clone $targetSlot;
                        $timetable->slots[$section->id][$underloadedDay][$targetPeriod] = $temp;
                        
                        return; // Done after one swap
                    }
                    break;
                }
            }
        }
    }
    
    /**
     * Target and fill empty slots with appropriate subjects
     * Validates constraint satisfaction before filling
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function repairEmptySlotMutation($timetable, $section) {
        // Find all empty slots
        $emptySlots = [];
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                    $emptySlots[] = ['day' => $day, 'period' => $period];
                }
            }
        }
        
        if (empty($emptySlots)) {
            return;
        }
        
        // Select a random empty slot
        $emptySlot = $emptySlots[array_rand($emptySlots)];
        $day = $emptySlot['day'];
        $period = $emptySlot['period'];
        
        // Calculate which subjects need more periods
        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
        
        $subjectCounts = [];
        for ($d = 0; $d < SchedulerConfig::DAYS_PER_WEEK; $d++) {
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $subjectId = $timetable->slots[$section->id][$d][$p]->subjectId;
                if ($subjectId) {
                    $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                }
            }
        }
        
        // Find subjects below their minimum allocation
        $needMoreSubjects = [];
        foreach ($allSubjects as $subjectId) {
            $subject = $this->subjects[$subjectId];
            $currentCount = $subjectCounts[$subjectId] ?? 0;
            
            if ($currentCount < $subject->minPeriodsPerWeek) {
                $needMoreSubjects[] = $subjectId;
            }
        }
        
        // Prefer subjects that need more periods, otherwise use any subject
        $candidateSubjects = !empty($needMoreSubjects) ? $needMoreSubjects : $allSubjects;
        
        if (!empty($candidateSubjects)) {
            $subjectId = $candidateSubjects[array_rand($candidateSubjects)];
            
            // Validate daily limit
            $dailyCount = 0;
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                if ($timetable->slots[$section->id][$day][$p]->subjectId === $subjectId) {
                    $dailyCount++;
                }
            }
            
            if ($dailyCount < SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                // Find suitable teacher
                $teacherId = $this->findSafeTeacher($timetable, $section, $day, $period, $subjectId);
                
                $timetable->slots[$section->id][$day][$period]->subjectId = $subjectId;
                $timetable->slots[$section->id][$day][$period]->teacherId = $teacherId;
            }
        }
    }
    
    /**
     * Small neighborhood search to optimize local area
     * Tests multiple small changes and keeps the best
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function localSearchMutation($timetable, $section) {
        // Select a random day for local optimization
        $targetDay = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        
        $originalFitness = $this->fitnessCalculator->calculate($timetable);
        $bestConfiguration = null;
        $bestFitness = $originalFitness;
        
        // Try multiple local swaps within this day
        $attempts = 5;
        for ($i = 0; $i < $attempts; $i++) {
            $testTimetable = $timetable->clone();
            
            // Perform random local swap
            $period1 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
            $period2 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
            
            if ($period1 !== $period2) {
                $temp = clone $testTimetable->slots[$section->id][$targetDay][$period1];
                $testTimetable->slots[$section->id][$targetDay][$period1] = 
                    clone $testTimetable->slots[$section->id][$targetDay][$period2];
                $testTimetable->slots[$section->id][$targetDay][$period2] = $temp;
                
                // Evaluate
                $testFitness = $this->fitnessCalculator->calculate($testTimetable);
                
                if ($testFitness > $bestFitness) {
                    $bestFitness = $testFitness;
                    $bestConfiguration = $testTimetable->clone();
                }
            }
        }
        
        // Apply best configuration if improvement found
        if ($bestConfiguration !== null) {
            $timetable->slots = $bestConfiguration->slots;
        }
    }
    
    /**
     * Swap two random periods across different days (kept for compatibility)
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function swapPeriodsAcrossDays($timetable, $section) {
        $day1 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $day2 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period1 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        $period2 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        $temp = clone $timetable->slots[$section->id][$day1][$period1];
        $timetable->slots[$section->id][$day1][$period1] = 
            clone $timetable->slots[$section->id][$day2][$period2];
        $timetable->slots[$section->id][$day2][$period2] = $temp;
    }
    
    /**
     * Reassign a random subject to a random slot (kept for compatibility)
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function reassignRandomSubject($timetable, $section) {
        $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
        
        if (empty($allSubjects)) {
            return;
        }
        
        $newSubjectId = $allSubjects[array_rand($allSubjects)];
        $newTeacherId = $this->assignTeacher($newSubjectId);
        
        $timetable->slots[$section->id][$day][$period]->subjectId = $newSubjectId;
        $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    /**
     * Find a safe teacher for a subject at a specific time
     * Uses constraint checking to avoid conflicts
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @param int $day Day index
     * @param int $period Period index
     * @param string $subjectId Subject ID
     * @return string|null Teacher ID or null if none found
     */
    private function findSafeTeacher($timetable, $section, $day, $period, $subjectId) {
        $eligibleTeachers = [];
        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects)) {
                $eligibleTeachers[] = $teacher->id;
            }
        }
        
        if (empty($eligibleTeachers)) {
            return null;
        }
        
        // Shuffle to add randomization
        shuffle($eligibleTeachers);
        
        // First pass: Find a teacher with NO conflicts
        foreach ($eligibleTeachers as $teacherId) {
            if ($this->constraintChecker->canTeacherTeachSubject($teacherId, $subjectId) &&
                $this->constraintChecker->isTeacherAvailable($teacherId, $day, $period, $timetable) &&
                $this->constraintChecker->isTeacherUnderDailyLimit($teacherId, $day, $timetable)) {
                return $teacherId;
            }
        }
        
        // Second pass: Find a teacher who's at least available
        foreach ($eligibleTeachers as $teacherId) {
            if ($this->constraintChecker->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
                return $teacherId;
            }
        }
        
        // Fallback: Return first eligible teacher
        return $eligibleTeachers[0];
    }
    
    /**
     * Assign a random eligible teacher to a subject
     * 
     * @param string $subjectId Subject identifier
     * @return string|null Teacher ID or null
     */
    private function assignTeacher($subjectId) {
        $eligibleTeachers = [];
        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects)) {
                $eligibleTeachers[] = $teacher->id;
            }
        }
        return !empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : null;
    }
}
