<?php
/**
 * School Timetable Scheduling using Genetic Algorithm
 * Based on optimization algorithms for educational timetabling
 * 
 * @author Timetable System
 * @version 2.0.0
 * @package TimetableScheduler
 */

/**
 * Configuration class for algorithm parameters
 */
class SchedulerConfig {
    // Schedule structure constants
    const DAYS_PER_WEEK = 6;
    const PERIODS_PER_DAY = 8;
    const TOTAL_PERIODS = 48;
    
    // Genetic algorithm parameters
    const DEFAULT_POPULATION_SIZE = 25;
    const DEFAULT_MAX_GENERATIONS = 100;
    const DEFAULT_MUTATION_RATE = 0.12;
    const DEFAULT_CROSSOVER_RATE = 0.85;
    const ELITE_PERCENTAGE = 0.15;
    const TOURNAMENT_SIZE = 4;
    
    // Constraint weights
    const HARD_CONSTRAINT_WEIGHT = 100;
    const SOFT_CONSTRAINT_WEIGHT = 1;
    
    // Hard constraint weights (higher = more critical)
    const WEIGHT_NO_EMPTY_SLOTS = 50;              // Must fill all 48 periods
    const WEIGHT_TEACHER_CONFLICTS = 40;           // No teacher double-booking
    const WEIGHT_TEACHER_WORKLOAD = 35;            // Max 7 periods per day per teacher
    const WEIGHT_CO_CURRICULAR_SAME_DAY = 30;      // Only 1 co-curricular per day
    const WEIGHT_CO_CURRICULAR_CONSECUTIVE = 25;   // Co-curricular 2 periods must be consecutive
    const WEIGHT_MAX_TWO_PERIODS_PER_DAY = 20;     // Max 2 periods per subject per day
    const WEIGHT_COMBINED_SUBJECTS = 18;           // Combined subjects same time
    const WEIGHT_SUBJECT_WEEKLY_ALLOCATION = 15;   // Meet min/max weekly requirements
    
    // Soft constraint weights
    const WEIGHT_POSITIONAL_CONSISTENCY = 3;
    const WEIGHT_MAX_ONE_PER_SUBJECT_PER_DAY = 2;
    const WEIGHT_CORE_SUBJECT_CONSISTENCY = 2;
    const WEIGHT_HEAVY_SUBJECT_SPACING = 2;
    const WEIGHT_CO_CURRICULAR_PLACEMENT = 1;
    
    const STAGNATION_THRESHOLD = 4;
    const STAGNATION_TOLERANCE = 0.002;
    const OPTIMAL_FITNESS_THRESHOLD = 0.82;
    const MAX_POSSIBLE_VIOLATIONS = 1000;
    
    // Co-curricular placement preferences
    const CO_CURRICULAR_PREFERRED_START_PERIOD = 4;
    const MAX_PERIODS_PER_SUBJECT_PER_DAY = 2;
    
    // Subject types
    const TYPE_COMPULSORY = 'compulsory';
    const TYPE_OPTIONAL = 'optional';
    const TYPE_CO_CURRICULAR = 'co_curricular';
}

/**
 * Represents a subject in the timetable
 */
class Subject {
    /** @var string Subject identifier */
    public $id;
    
    /** @var string Subject name */
    public $name;
    
    /** @var string Subject type: compulsory, optional, or co_curricular */
    public $type;
    
    /** @var int Minimum periods per week */
    public $minPeriodsPerWeek;
    
    /** @var int Maximum periods per week */
    public $maxPeriodsPerWeek;
    
    /** @var bool Whether subject is combined across sections */
    public $isCombined;
    
    /**
     * Create a new Subject instance
     * 
     * @param string $id Subject identifier
     * @param string $name Subject name
     * @param string $type Subject type (compulsory/optional/co_curricular)
     * @param int $minPeriods Minimum periods per week
     * @param int $maxPeriods Maximum periods per week
     * @param bool $isCombined Whether subject is combined across sections
     */
    public function __construct($id, $name, $type, $minPeriods, $maxPeriods, $isCombined = false) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->minPeriodsPerWeek = $minPeriods;
        $this->maxPeriodsPerWeek = $maxPeriods;
        $this->isCombined = $isCombined;
    }
}

/**
 * Represents a teacher in the timetable
 */
class Teacher {
    /** @var string Teacher identifier */
    public $id;
    
    /** @var string Teacher name */
    public $name;
    
    /** @var array Array of subject IDs this teacher can teach */
    public $subjects;
    
    /** @var int Maximum periods per day for this teacher */
    public $maxPeriodsPerDay;
    
    /** @var array|null Availability matrix {"Sun": {1: true, 2: false}, "Mon": {...}} */
    public $availabilityMatrix;
    
    /**
     * Create a new Teacher instance
     * 
     * @param string $id Teacher identifier
     * @param string $name Teacher name
     * @param array $subjects Array of subject IDs
     * @param int $maxPeriodsPerDay Maximum periods per day (default: 7)
     * @param array|null $availabilityMatrix Availability matrix (default: null means available all times)
     */
    public function __construct($id, $name, $subjects, $maxPeriodsPerDay = 7, $availabilityMatrix = null) {
        $this->id = $id;
        $this->name = $name;
        $this->subjects = $subjects;
        $this->maxPeriodsPerDay = $maxPeriodsPerDay;
        $this->availabilityMatrix = $availabilityMatrix;
    }
}

/**
 * Represents a section/class in the school
 */
class Section {
    /** @var string Section identifier */
    public $id;
    
    /** @var string Grade/year level */
    public $grade;
    
    /** @var string Section name */
    public $name;
    
    /** @var array Array of compulsory subject IDs */
    public $compulsorySubjects;
    
    /** @var array Array of optional subject IDs */
    public $optionalSubjects;
    
    /** @var array Array of co-curricular subject IDs */
    public $coCurricularSubjects;
    
    /**
     * Create a new Section instance
     * 
     * @param string $id Section identifier
     * @param string $grade Grade/year level
     * @param string $name Section name
     */
    public function __construct($id, $grade, $name) {
        $this->id = $id;
        $this->grade = $grade;
        $this->name = $name;
        $this->compulsorySubjects = [];
        $this->optionalSubjects = [];
        $this->coCurricularSubjects = [];
    }
}

/**
 * Represents a single time slot in the timetable
 */
class TimeSlot {
    /** @var int Day of the week (0-5) */
    public $day;
    
    /** @var int Period number (0-7) */
    public $period;
    
    /** @var string|null Subject ID assigned to this slot */
    public $subjectId;
    
    /** @var string|null Teacher ID assigned to this slot */
    public $teacherId;
    
    /** @var string|null Section ID for this slot */
    public $sectionId;
    
    /**
     * Create a new TimeSlot instance
     * 
     * @param int $day Day of the week (0-5)
     * @param int $period Period number (0-7)
     * @param string|null $subjectId Subject identifier
     * @param string|null $teacherId Teacher identifier
     * @param string|null $sectionId Section identifier
     */
    public function __construct($day, $period, $subjectId = null, $teacherId = null, $sectionId = null) {
        $this->day = $day;
        $this->period = $period;
        $this->subjectId = $subjectId;
        $this->teacherId = $teacherId;
        $this->sectionId = $sectionId;
    }
}

/**
 * Represents a complete timetable for all sections
 */
class Timetable {
    /** @var array 3D array of time slots: [section][day][period] */
    public $slots;
    
    /** @var float Fitness score of this timetable */
    public $fitness;
    
    /**
     * Create a new Timetable instance
     * 
     * @param array $sections Array of Section objects
     */
    public function __construct($sections) {
        $this->slots = [];
        foreach ($sections as $section) {
            $this->slots[$section->id] = [];
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $this->slots[$section->id][$day] = [];
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $this->slots[$section->id][$day][$period] = new TimeSlot($day, $period, null, null, $section->id);
                }
            }
        }
        $this->fitness = 0;
    }
    
    /**
     * Create a deep copy of this timetable
     * 
     * @return Timetable Cloned timetable instance
     */
    public function clone() {
        $clone = new Timetable([]);
        $clone->slots = unserialize(serialize($this->slots));
        $clone->fitness = $this->fitness;
        return $clone;
    }
}

/**
 * Main scheduler class implementing genetic algorithm for timetable optimization
 */
class GeneticAlgorithmScheduler {
    /** @var array Indexed array of Subject objects */
    private $subjects;
    
    /** @var array Indexed array of Teacher objects */
    private $teachers;
    
    /** @var array Array of Section objects */
    private $sections;
    
    /** @var int Population size for genetic algorithm */
    private $populationSize;
    
    /** @var int Maximum number of generations */
    private $maxGenerations;
    
    /** @var float Mutation rate (0-1) */
    private $mutationRate;
    
    /** @var float Crossover rate (0-1) */
    private $crossoverRate;
    
    /** @var array Current population of timetables */
    private $population;
    
    /** @var ConstraintChecker Constraint validation helper */
    private $constraintChecker;
    
    /** @var FitnessCalculator Fitness calculation helper */
    private $fitnessCalculator;
    
    /** @var GeneticOperations Genetic algorithm operations helper */
    private $geneticOps;
    
    /** @var TimetableRepair Timetable repair helper */
    private $repairHelper;
    
    /** @var array Locked slots that should not be modified [sectionId][day][period] */
    private $lockedSlots;
    
    /** @var array Cache for fitness calculations to avoid redundant evaluations */
    private $fitnessCache = [];
    
    /**
     * Create a new scheduler instance
     * 
     * @param array $subjects Indexed array of Subject objects
     * @param array $teachers Indexed array of Teacher objects
     * @param array $sections Array of Section objects
     * @param int $populationSize Population size (default: 20)
     * @param int $maxGenerations Maximum generations (default: 150)
     * @param array $lockedSlots Pre-locked slots that must be preserved
     */
    public function __construct($subjects, $teachers, $sections, $populationSize = 20, $maxGenerations = 150, $lockedSlots = []) {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
        $this->populationSize = $populationSize;
        $this->maxGenerations = $maxGenerations;
        $this->mutationRate = SchedulerConfig::DEFAULT_MUTATION_RATE;
        $this->crossoverRate = SchedulerConfig::DEFAULT_CROSSOVER_RATE;
        $this->population = [];
        $this->lockedSlots = $lockedSlots;
        
        // Initialize helper classes
        $this->constraintChecker = new ConstraintChecker($subjects, $teachers, $sections);
        $this->fitnessCalculator = new FitnessCalculator($this->constraintChecker);
        $this->geneticOps = new GeneticOperations($subjects, $teachers, $sections, $lockedSlots);
        $this->repairHelper = new TimetableRepair($subjects, $teachers, $sections);
    }
    
    /**
     * Generate an optimized timetable using genetic algorithm
     * 
     * @return Timetable Best timetable found
     */
    public function generateTimetable() {
        $this->initializePopulation();
        
        $bestFitness = 0;
        $stagnantGenerations = 0;
        
        for ($generation = 0; $generation < $this->maxGenerations; $generation++) {
            $this->evaluatePopulation();
            $this->sortPopulationByFitness();
            
            $currentBestFitness = $this->population[0]->fitness;
            
            $this->logProgress($generation, $currentBestFitness);
            
            if ($this->isOptimalSolution($currentBestFitness)) {
                echo "Optimal solution found!\n";
                break;
            }
            
            $stagnantGenerations = $this->handleStagnation(
                $currentBestFitness, 
                $bestFitness, 
                $stagnantGenerations
            );
            
            $bestFitness = $currentBestFitness;
            $this->evolvePopulation();
        }
        
        return $this->population[0];
    }
    
    /**
     * Sort population by fitness in descending order
     */
    private function sortPopulationByFitness() {
        usort($this->population, function($a, $b) {
            return $b->fitness <=> $a->fitness;
        });
    }
    
    /**
     * Log generation progress
     * 
     * @param int $generation Current generation number
     * @param float $fitness Current best fitness
     */
    private function logProgress($generation, $fitness) {
        if ($generation % 20 == 0 || $generation == 0) {
            echo "Generation $generation: Best Fitness = " . round($fitness, 4) . "\n";
        }
    }
    
    /**
     * Check if current solution is optimal
     * 
     * @param float $fitness Current fitness score
     * @return bool True if optimal
     */
    private function isOptimalSolution($fitness) {
        return $fitness >= SchedulerConfig::OPTIMAL_FITNESS_THRESHOLD;
    }
    
    /**
     * Handle stagnation by introducing diversity if needed
     * 
     * @param float $currentFitness Current best fitness
     * @param float $previousBestFitness Previous best fitness
     * @param int $stagnantCount Current stagnation counter
     * @return int Updated stagnation counter
     */
    private function handleStagnation($currentFitness, $previousBestFitness, $stagnantCount) {
        if (abs($currentFitness - $previousBestFitness) < SchedulerConfig::STAGNATION_TOLERANCE) {
            $stagnantCount++;
            if ($stagnantCount > SchedulerConfig::STAGNATION_THRESHOLD) {
                echo "Stagnation detected, applying diversity boost...\n";
                $this->introduceDiversity();
                return 0;
            }
        } else {
            return 0;
        }
        return $stagnantCount;
    }
    
    /**
     * Initialize the population with constructive, intelligent timetables
     */
    private function initializePopulation() {
        echo "Initializing population of {$this->populationSize} timetables...\n";
        
        // First 10% use pure constructive initialization for high-quality seeds (reduced from 20%)
        $constructiveCount = max(1, (int)($this->populationSize * 0.1));
        for ($i = 0; $i < $constructiveCount; $i++) {
            $this->population[] = $this->createConstructiveTimetable();
        }
        
        // Remaining use hybrid approach with more randomization for faster diversity
        for ($i = $constructiveCount; $i < $this->populationSize; $i++) {
            $this->population[] = $this->createConstructiveTimetable(0.5); // 50% randomization for speed
        }
        
        echo "Population initialization complete!\n\n";
    }
    
    /**
     * Create an intelligently constructed timetable using constraint-aware placement
     * 
     * @param float $randomizationFactor Factor for introducing randomization (0-1). 0 = pure constructive, 1 = fully random
     * @return Timetable Constructively generated timetable
     */
    private function createConstructiveTimetable($randomizationFactor = 0) {
        $timetable = new Timetable($this->sections);
        
        // Phase 1: Place combined subjects first (hardest constraint - must sync across sections)
        $this->placeCombinedSubjects($timetable, $randomizationFactor);
        
        // Phase 2: Place co-curricular subjects (limited to middle/end periods)
        $this->placeCoCurricularSubjects($timetable, $randomizationFactor);
        
        // Phase 3: Place compulsory subjects (most periods needed)
        $this->placeCompulsorySubjects($timetable, $randomizationFactor);
        
        // Phase 4: Fill remaining slots with optional subjects
        $this->placeOptionalSubjects($timetable, $randomizationFactor);
        
        return $timetable;
    }
    
    /**
     * Phase 1: Place combined subjects across all sections simultaneously
     */
    private function placeCombinedSubjects($timetable, $randomizationFactor) {
        $combinedSubjects = $this->getCombinedSubjects();
        
        if (empty($combinedSubjects)) {
            return;
        }
        
        echo "    Phase 1: Placing " . count($combinedSubjects) . " combined subjects...\n";
        
        foreach ($combinedSubjects as $subjectId) {
            $subject = $this->subjects[$subjectId];
            $periodsNeeded = $this->calculateOptimalPeriods($subject, $randomizationFactor);
            
            for ($i = 0; $i < $periodsNeeded; $i++) {
                $slot = $this->findSynchronizedSlot($timetable, $subjectId, $randomizationFactor);
                
                if ($slot) {
                    // Assign same slot to all sections that have this combined subject
                    foreach ($this->sections as $section) {
                        if ($this->sectionHasSubject($section, $subjectId)) {
                            $teacherId = $this->findSafeTeacher($timetable, $section, $slot['day'], $slot['period'], $subjectId);
                            $timetable->slots[$section->id][$slot['day']][$slot['period']]->subjectId = $subjectId;
                            $timetable->slots[$section->id][$slot['day']][$slot['period']]->teacherId = $teacherId;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Phase 2: Place co-curricular subjects in preferred periods (4-8)
     */
    private function placeCoCurricularSubjects($timetable, $randomizationFactor) {
        foreach ($this->sections as $section) {
            if (empty($section->coCurricularSubjects)) {
                continue;
            }
            
            foreach ($section->coCurricularSubjects as $subjectId) {
                if ($this->subjects[$subjectId]->isCombined) {
                    continue; // Already placed in Phase 1
                }
                
                $subject = $this->subjects[$subjectId];
                $periodsNeeded = $this->calculateOptimalPeriods($subject, $randomizationFactor);
                
                // Try to place consecutive periods for co-curricular subjects
                if ($periodsNeeded >= 2) {
                    $consecutiveSlot = $this->findConsecutiveCoCurricularSlots($timetable, $section, $subjectId, $periodsNeeded, $randomizationFactor);
                    
                    if ($consecutiveSlot) {
                        for ($i = 0; $i < $periodsNeeded; $i++) {
                            $period = $consecutiveSlot['period'] + $i;
                            $teacherId = $this->findSafeTeacher($timetable, $section, $consecutiveSlot['day'], $period, $subjectId);
                            $timetable->slots[$section->id][$consecutiveSlot['day']][$period]->subjectId = $subjectId;
                            $timetable->slots[$section->id][$consecutiveSlot['day']][$period]->teacherId = $teacherId;
                        }
                        continue;
                    }
                }
                
                // Place individually in preferred periods
                for ($i = 0; $i < $periodsNeeded; $i++) {
                    $slot = $this->findCoCurricularSlot($timetable, $section, $subjectId, $randomizationFactor);
                    if ($slot) {
                        $teacherId = $this->findSafeTeacher($timetable, $section, $slot['day'], $slot['period'], $subjectId);
                        $timetable->slots[$section->id][$slot['day']][$slot['period']]->subjectId = $subjectId;
                        $timetable->slots[$section->id][$slot['day']][$slot['period']]->teacherId = $teacherId;
                    }
                }
            }
        }
    }
    
    /**
     * Phase 3: Place compulsory subjects
     */
    private function placeCompulsorySubjects($timetable, $randomizationFactor) {
        foreach ($this->sections as $section) {
            if (empty($section->compulsorySubjects)) {
                continue;
            }
            
            // Sort compulsory subjects by periods needed (descending) for better packing
            $subjectsWithPeriods = [];
            foreach ($section->compulsorySubjects as $subjectId) {
                if ($this->subjects[$subjectId]->isCombined) {
                    continue; // Already placed in Phase 1
                }
                $subject = $this->subjects[$subjectId];
                $periodsNeeded = $this->calculateOptimalPeriods($subject, $randomizationFactor);
                $subjectsWithPeriods[] = ['id' => $subjectId, 'periods' => $periodsNeeded];
            }
            
            usort($subjectsWithPeriods, function($a, $b) {
                return $b['periods'] <=> $a['periods'];
            });
            
            foreach ($subjectsWithPeriods as $subjectData) {
                $subjectId = $subjectData['id'];
                $periodsNeeded = $subjectData['periods'];
                
                for ($i = 0; $i < $periodsNeeded; $i++) {
                    $slot = $this->findBestSlotForSubject($timetable, $section, $subjectId, $randomizationFactor);
                    if ($slot) {
                        $teacherId = $this->findSafeTeacher($timetable, $section, $slot['day'], $slot['period'], $subjectId);
                        $timetable->slots[$section->id][$slot['day']][$slot['period']]->subjectId = $subjectId;
                        $timetable->slots[$section->id][$slot['day']][$slot['period']]->teacherId = $teacherId;
                    }
                }
            }
        }
    }
    
    /**
     * Phase 4: Place optional subjects in remaining slots
     */
    private function placeOptionalSubjects($timetable, $randomizationFactor) {
        foreach ($this->sections as $section) {
            // Fill empty slots with optional subjects
            $emptySlots = $this->getEmptySlots($timetable, $section);
            
            if (empty($emptySlots) || empty($section->optionalSubjects)) {
                continue;
            }
            
            // Calculate how many periods each optional subject should get
            $subjectPeriods = [];
            foreach ($section->optionalSubjects as $subjectId) {
                if ($this->subjects[$subjectId]->isCombined) {
                    continue; // Already placed
                }
                $subject = $this->subjects[$subjectId];
                $periodsNeeded = $this->calculateOptimalPeriods($subject, $randomizationFactor);
                $subjectPeriods[$subjectId] = $periodsNeeded;
            }
            
            // Fill empty slots
            $subjectIndex = 0;
            $subjectIds = array_keys($subjectPeriods);
            
            foreach ($emptySlots as $emptySlot) {
                if (empty($subjectIds)) {
                    break;
                }
                
                // Round-robin assignment with constraint checking
                $attempts = 0;
                $assigned = false;
                
                while ($attempts < count($subjectIds) && !$assigned) {
                    $subjectId = $subjectIds[$subjectIndex % count($subjectIds)];
                    
                    if ($subjectPeriods[$subjectId] > 0) {
                        // Check if we can place this subject here
                        if ($this->canPlaceSubjectInSlot($timetable, $section, $emptySlot['day'], $emptySlot['period'], $subjectId)) {
                            $teacherId = $this->findSafeTeacher($timetable, $section, $emptySlot['day'], $emptySlot['period'], $subjectId);
                            $timetable->slots[$section->id][$emptySlot['day']][$emptySlot['period']]->subjectId = $subjectId;
                            $timetable->slots[$section->id][$emptySlot['day']][$emptySlot['period']]->teacherId = $teacherId;
                            $subjectPeriods[$subjectId]--;
                            $assigned = true;
                        }
                    }
                    
                    $subjectIndex++;
                    $attempts++;
                }
                
                // If no subject fits, just place randomly as fallback
                if (!$assigned && !empty($subjectIds)) {
                    $subjectId = $subjectIds[array_rand($subjectIds)];
                    $teacherId = $this->findSafeTeacher($timetable, $section, $emptySlot['day'], $emptySlot['period'], $subjectId);
                    $timetable->slots[$section->id][$emptySlot['day']][$emptySlot['period']]->subjectId = $subjectId;
                    $timetable->slots[$section->id][$emptySlot['day']][$emptySlot['period']]->teacherId = $teacherId;
                }
            }
        }
    }
    
    /**
     * Create a random timetable (kept for backward compatibility)
     * 
     * @return Timetable Randomly generated timetable
     */
    private function createRandomTimetable() {
        $timetable = new Timetable($this->sections);
        
        foreach ($this->sections as $section) {
            $this->fillSectionTimetable($timetable, $section);
        }
        
        // Apply locked slots after generation
        $this->applyLockedSlots($timetable);
        
        return $timetable;
    }
    
    /**
     * Fill a section's timetable with random subject assignments
     * 
     * @param Timetable $timetable Timetable to fill
     * @param Section $section Section to fill
     */
    private function fillSectionTimetable($timetable, $section) {
        $allSubjects = $this->getAllSubjectsForSection($section);
        $subjectPeriods = $this->calculateSubjectPeriods($allSubjects);
        $subjectPeriods = $this->adjustToTotalPeriods($subjectPeriods);
        $subjectPool = $this->createSubjectPool($subjectPeriods);
        
        $this->assignSubjectsToSlots($timetable, $section, $subjectPool);
    }
    
    /**
     * Get all subjects for a section
     * 
     * @param Section $section Section object
     * @return array Array of subject IDs
     */
    private function getAllSubjectsForSection($section) {
        return array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
    }
    
    /**
     * Calculate random period allocation for subjects
     * 
     * @param array $subjectIds Array of subject IDs
     * @return array Map of subject ID to period count
     */
    private function calculateSubjectPeriods($subjectIds) {
        $subjectPeriods = [];
        foreach ($subjectIds as $subjectId) {
            $subject = $this->subjects[$subjectId];
            $subjectPeriods[$subjectId] = rand(
                $subject->minPeriodsPerWeek, 
                $subject->maxPeriodsPerWeek
            );
        }
        return $subjectPeriods;
    }
    
    /**
     * Adjust subject periods to exactly match total required periods
     * 
     * @param array $subjectPeriods Map of subject ID to period count
     * @return array Adjusted period allocations
     */
    private function adjustToTotalPeriods($subjectPeriods) {
        $totalPeriods = array_sum($subjectPeriods);
        $maxIterations = 1000; // Prevent infinite loops
        $iterations = 0;
        
        while ($totalPeriods != SchedulerConfig::TOTAL_PERIODS && $iterations < $maxIterations) {
            $iterations++;
            
            // Build lists of subjects that can be increased or decreased
            $canIncrease = [];
            $canDecrease = [];
            
            foreach ($subjectPeriods as $subjectId => $count) {
                $subject = $this->subjects[$subjectId];
                if ($count < $subject->maxPeriodsPerWeek) {
                    $canIncrease[] = $subjectId;
                }
                if ($count > $subject->minPeriodsPerWeek) {
                    $canDecrease[] = $subjectId;
                }
            }
            
            if ($totalPeriods < SchedulerConfig::TOTAL_PERIODS && !empty($canIncrease)) {
                $subjectId = $canIncrease[array_rand($canIncrease)];
                $subjectPeriods[$subjectId]++;
                $totalPeriods++;
            } elseif ($totalPeriods > SchedulerConfig::TOTAL_PERIODS && !empty($canDecrease)) {
                $subjectId = $canDecrease[array_rand($canDecrease)];
                $subjectPeriods[$subjectId]--;
                $totalPeriods--;
            } else {
                // Cannot adjust further - force adjustment to reach total
                if ($totalPeriods < SchedulerConfig::TOTAL_PERIODS && !empty($subjectPeriods)) {
                    $subjectId = array_rand($subjectPeriods);
                    $subjectPeriods[$subjectId]++;
                    $totalPeriods++;
                } elseif ($totalPeriods > SchedulerConfig::TOTAL_PERIODS && !empty($subjectPeriods)) {
                    $subjectId = array_rand($subjectPeriods);
                    if ($subjectPeriods[$subjectId] > 0) {
                        $subjectPeriods[$subjectId]--;
                        $totalPeriods--;
                    }
                }
            }
        }
        
        if ($iterations >= $maxIterations) {
            echo "Warning: adjustToTotalPeriods hit max iterations. Total: $totalPeriods\n";
        }
        
        return $subjectPeriods;
    }
    
    /**
     * Create a pool of subject assignments from period counts
     * 
     * @param array $subjectPeriods Map of subject ID to period count
     * @return array Shuffled array of subject IDs
     */
    private function createSubjectPool($subjectPeriods) {
        $subjectPool = [];
        foreach ($subjectPeriods as $subjectId => $count) {
            for ($i = 0; $i < $count; $i++) {
                $subjectPool[] = $subjectId;
            }
        }
        shuffle($subjectPool);
        return $subjectPool;
    }
    
    /**
     * Assign subjects from pool to time slots
     * 
     * @param Timetable $timetable Timetable to fill
     * @param Section $section Section to fill
     * @param array $subjectPool Pool of subject IDs
     */
    private function assignSubjectsToSlots($timetable, $section, $subjectPool) {
        $index = 0;
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $subjectPool[$index];
                $teacherId = $this->assignTeacher($subjectId);
                
                $timetable->slots[$section->id][$day][$period]->subjectId = $subjectId;
                $timetable->slots[$section->id][$day][$period]->teacherId = $teacherId;
                
                $index++;
            }
        }
    }
    
    /**
     * Assign a random eligible teacher to a subject
     * 
     * @param string $subjectId Subject identifier
     * @return string|null Teacher ID or null if no eligible teacher
     */
    private function assignTeacher($subjectId) {
        $eligibleTeachers = $this->getEligibleTeachers($subjectId);
        return !empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : null;
    }
    
    /**
     * Get list of teachers eligible to teach a subject
     * 
     * @param string $subjectId Subject identifier
     * @return array Array of teacher IDs
     */
    private function getEligibleTeachers($subjectId) {
        $eligibleTeachers = [];
        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects)) {
                $eligibleTeachers[] = $teacher->id;
            }
        }
        return $eligibleTeachers;
    }
    
    /**
     * Evaluate fitness for all timetables in population
     */
    private function evaluatePopulation() {
        foreach ($this->population as $timetable) {
            if ($timetable->fitness === null) {
                $timetable->fitness = $this->fitnessCalculator->calculate($timetable);
            }
        }
    }
    
    /**
     * Introduce diversity into population to escape local optima
     */
    private function introduceDiversity() {
        $keepCount = (int)($this->populationSize * 0.2);
        
        for ($i = $keepCount; $i < $this->populationSize; $i++) {
            $this->population[$i] = $this->createRandomTimetable();
        }
    }
    
    // ========================================
    // CONSTRUCTIVE INITIALIZATION HELPERS
    // ========================================
    
    /**
     * Calculate optimal number of periods for a subject
     * 
     * @param Subject $subject Subject object
     * @param float $randomizationFactor Randomization factor (0-1)
     * @return int Number of periods
     */
    private function calculateOptimalPeriods($subject, $randomizationFactor) {
        if ($randomizationFactor > 0 && mt_rand() / mt_getrandmax() < $randomizationFactor) {
            // Use random allocation
            return rand($subject->minPeriodsPerWeek, $subject->maxPeriodsPerWeek);
        }
        
        // Use intelligent allocation - prefer max for compulsory, average for others
        if ($subject->type === SchedulerConfig::TYPE_COMPULSORY) {
            return $subject->maxPeriodsPerWeek;
        } else {
            return (int)ceil(($subject->minPeriodsPerWeek + $subject->maxPeriodsPerWeek) / 2);
        }
    }
    
    /**
     * Get list of combined subjects
     * 
     * @return array Array of combined subject IDs
     */
    private function getCombinedSubjects() {
        $combined = [];
        foreach ($this->subjects as $subject) {
            if ($subject->isCombined) {
                $combined[] = $subject->id;
            }
        }
        return $combined;
    }
    
    /**
     * Check if a section has a specific subject
     * 
     * @param Section $section Section object
     * @param string $subjectId Subject ID
     * @return bool True if section has the subject
     */
    private function sectionHasSubject($section, $subjectId) {
        return in_array($subjectId, $section->compulsorySubjects) ||
               in_array($subjectId, $section->optionalSubjects) ||
               in_array($subjectId, $section->coCurricularSubjects);
    }
    
    /**
     * Find a synchronized slot for combined subjects across all sections
     * 
     * @param Timetable $timetable Current timetable
     * @param string $subjectId Subject ID
     * @param float $randomizationFactor Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findSynchronizedSlot($timetable, $subjectId, $randomizationFactor) {
        $subject = $this->subjects[$subjectId];
        $isCoCurrenticular = $subject->type === SchedulerConfig::TYPE_CO_CURRICULAR;
        
        // Get all sections that need this combined subject
        $sectionsNeeded = [];
        foreach ($this->sections as $section) {
            if ($this->sectionHasSubject($section, $subjectId)) {
                $sectionsNeeded[] = $section;
            }
        }
        
        if (empty($sectionsNeeded)) {
            return null;
        }
        
        $attempts = 0;
        $maxAttempts = 100;
        
        while ($attempts < $maxAttempts) {
            // Select day and period
            $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            
            if ($isCoCurrenticular && $randomizationFactor < 0.5) {
                // Prefer periods 4-7 for co-curricular
                $period = rand(SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD, SchedulerConfig::PERIODS_PER_DAY - 1);
            } else {
                $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
            }
            
            // Check if this slot is empty for all sections
            $slotAvailable = true;
            foreach ($sectionsNeeded as $section) {
                if ($timetable->slots[$section->id][$day][$period]->subjectId !== null) {
                    $slotAvailable = false;
                    break;
                }
            }
            
            if ($slotAvailable) {
                // Check if we can place subject here without violating constraints
                if ($this->canPlaceCombinedSubjectInSlot($timetable, $sectionsNeeded, $day, $period, $subjectId)) {
                    return ['day' => $day, 'period' => $period];
                }
            }
            
            $attempts++;
        }
        
        return null;
    }
    
    /**
     * Check if combined subject can be placed in a specific slot
     * 
     * @param Timetable $timetable Current timetable
     * @param array $sections Array of sections
     * @param int $day Day index
     * @param int $period Period index
     * @param string $subjectId Subject ID
     * @return bool True if can be placed
     */
    private function canPlaceCombinedSubjectInSlot($timetable, $sections, $day, $period, $subjectId) {
        $subject = $this->subjects[$subjectId];
        
        foreach ($sections as $section) {
            // Check if there's already another co-curricular on this day
            if ($subject->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                    $existingSubjectId = $timetable->slots[$section->id][$day][$p]->subjectId;
                    if ($existingSubjectId && isset($this->subjects[$existingSubjectId])) {
                        if ($this->subjects[$existingSubjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR && 
                            $existingSubjectId !== $subjectId) {
                            return false;
                        }
                    }
                }
            }
            
            // Check subject daily limit
            $dailyCount = 0;
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                if ($timetable->slots[$section->id][$day][$p]->subjectId === $subjectId) {
                    $dailyCount++;
                }
            }
            
            if ($dailyCount >= SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Find consecutive co-curricular slots
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @param string $subjectId Subject ID
     * @param int $consecutiveCount Number of consecutive periods needed
     * @param float $randomizationFactor Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findConsecutiveCoCurricularSlots($timetable, $section, $subjectId, $consecutiveCount, $randomizationFactor) {
        // Only look for 2 consecutive periods maximum
        $consecutiveCount = min($consecutiveCount, 2);
        
        $attempts = 0;
        $maxAttempts = 50;
        
        while ($attempts < $maxAttempts) {
            $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            
            // Check if day already has a co-curricular
            $hasCoCurricular = false;
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $existingSubjectId = $timetable->slots[$section->id][$day][$p]->subjectId;
                if ($existingSubjectId && isset($this->subjects[$existingSubjectId])) {
                    if ($this->subjects[$existingSubjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $hasCoCurricular = true;
                        break;
                    }
                }
            }
            
            if ($hasCoCurricular) {
                $attempts++;
                continue;
            }
            
            // Try to find consecutive empty periods starting from period 4
            $startPeriod = rand(SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD, 
                               SchedulerConfig::PERIODS_PER_DAY - $consecutiveCount);
            
            $allEmpty = true;
            for ($i = 0; $i < $consecutiveCount; $i++) {
                if ($timetable->slots[$section->id][$day][$startPeriod + $i]->subjectId !== null) {
                    $allEmpty = false;
                    break;
                }
            }
            
            if ($allEmpty) {
                return ['day' => $day, 'period' => $startPeriod];
            }
            
            $attempts++;
        }
        
        return null;
    }
    
    /**
     * Find a suitable slot for co-curricular subject
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @param string $subjectId Subject ID
     * @param float $randomizationFactor Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findCoCurricularSlot($timetable, $section, $subjectId, $randomizationFactor) {
        $attempts = 0;
        $maxAttempts = 100;
        
        while ($attempts < $maxAttempts) {
            $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            
            // Check if this day already has a co-curricular
            $hasCoCurricular = false;
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $existingSubjectId = $timetable->slots[$section->id][$day][$p]->subjectId;
                if ($existingSubjectId && isset($this->subjects[$existingSubjectId])) {
                    if ($this->subjects[$existingSubjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $hasCoCurricular = true;
                        break;
                    }
                }
            }
            
            if ($hasCoCurricular) {
                $attempts++;
                continue;
            }
            
            // Prefer periods 4-7 for co-curricular
            if ($randomizationFactor < 0.5) {
                $period = rand(SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD, SchedulerConfig::PERIODS_PER_DAY - 1);
            } else {
                $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
            }
            
            if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                if ($this->canPlaceSubjectInSlot($timetable, $section, $day, $period, $subjectId)) {
                    return ['day' => $day, 'period' => $period];
                }
            }
            
            $attempts++;
        }
        
        return null;
    }
    
    /**
     * Find best slot for a subject (non-co-curricular)
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @param string $subjectId Subject ID
     * @param float $randomizationFactor Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findBestSlotForSubject($timetable, $section, $subjectId, $randomizationFactor) {
        $attempts = 0;
        $maxAttempts = 100;
        
        while ($attempts < $maxAttempts) {
            $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
            $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
            
            if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                if ($this->canPlaceSubjectInSlot($timetable, $section, $day, $period, $subjectId)) {
                    return ['day' => $day, 'period' => $period];
                }
            }
            
            $attempts++;
        }
        
        return null;
    }
    
    /**
     * Check if a subject can be placed in a specific slot
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @param int $day Day index
     * @param int $period Period index
     * @param string $subjectId Subject ID
     * @return bool True if can be placed
     */
    private function canPlaceSubjectInSlot($timetable, $section, $day, $period, $subjectId) {
        $subject = $this->subjects[$subjectId];
        
        // Check if slot is empty
        if ($timetable->slots[$section->id][$day][$period]->subjectId !== null) {
            return false;
        }
        
        // Check daily limit for this subject
        $dailyCount = 0;
        for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
            if ($timetable->slots[$section->id][$day][$p]->subjectId === $subjectId) {
                $dailyCount++;
            }
        }
        
        if ($dailyCount >= SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
            return false;
        }
        
        // For co-curricular, check if there's already another co-curricular this day
        if ($subject->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $existingSubjectId = $timetable->slots[$section->id][$day][$p]->subjectId;
                if ($existingSubjectId && isset($this->subjects[$existingSubjectId])) {
                    if ($this->subjects[$existingSubjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR &&
                        $existingSubjectId !== $subjectId) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get list of empty slots for a section
     * 
     * @param Timetable $timetable Current timetable
     * @param Section $section Section object
     * @return array Array of empty slots with 'day' and 'period' keys
     */
    private function getEmptySlots($timetable, $section) {
        $emptySlots = [];
        
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                    $emptySlots[] = ['day' => $day, 'period' => $period];
                }
            }
        }
        
        return $emptySlots;
    }
    
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
        $eligibleTeachers = $this->getEligibleTeachers($subjectId);
        
        if (empty($eligibleTeachers)) {
            return null;
        }
        
        // Shuffle to add randomization
        shuffle($eligibleTeachers);
        
        // First pass: Find a teacher with NO conflicts
        foreach ($eligibleTeachers as $teacherId) {
            if ($this->isTeacherSafeForSlot($timetable, $teacherId, $day, $period, $subjectId)) {
                return $teacherId;
            }
        }
        
        // Second pass: Find a teacher who's at least available (not double-booked)
        foreach ($eligibleTeachers as $teacherId) {
            if ($this->constraintChecker->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
                return $teacherId;
            }
        }
        
        // Fallback: Return first eligible teacher (will create conflict, but evolution will fix)
        return $eligibleTeachers[0];
    }
    
    /**
     * Check if a teacher is safe for a specific slot (no conflicts)
     * 
     * @param Timetable $timetable Current timetable
     * @param string $teacherId Teacher ID
     * @param int $day Day index
     * @param int $period Period index
     * @param string $subjectId Subject ID
     * @return bool True if teacher is safe to assign
     */
    private function isTeacherSafeForSlot($timetable, $teacherId, $day, $period, $subjectId) {
        // Check if teacher can teach this subject
        if (!$this->constraintChecker->canTeacherTeachSubject($teacherId, $subjectId)) {
            return false;
        }
        
        // Check if teacher has this time slot in their availability matrix
        if (!$this->constraintChecker->hasTeacherAvailability($teacherId, $day, $period)) {
            return false;
        }
        
        // Check if teacher is available at this time
        if (!$this->constraintChecker->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
            return false;
        }
        
        // Check if teacher is under daily limit
        if (!$this->constraintChecker->isTeacherUnderDailyLimit($teacherId, $day, $timetable)) {
            return false;
        }
        
        // Check if teacher is under weekly limit
        if (!$this->constraintChecker->isTeacherUnderWeeklyLimit($teacherId, $timetable)) {
            return false;
        }
        
        return true;
    }
    
    // ========================================
    // END CONSTRUCTIVE INITIALIZATION HELPERS
    // ========================================
    
    // Genetic Algorithm Operations
    
    /**
     * Evolve population to next generation
     */
    private function evolvePopulation() {
        $newPopulation = $this->selectElite();
        
        while (count($newPopulation) < $this->populationSize) {
            $offspring = $this->createOffspring();
            $newPopulation[] = $offspring;
        }
        
        $this->population = $newPopulation;
    }
    
    /**
     * Select elite individuals for next generation
     * 
     * @return array Array of elite timetables
     */
    private function selectElite() {
        $eliteCount = (int)($this->populationSize * SchedulerConfig::ELITE_PERCENTAGE);
        $elite = [];
        
        for ($i = 0; $i < $eliteCount; $i++) {
            $elite[] = $this->population[$i]->clone();
        }
        
        return $elite;
    }
    
    /**
     * Create offspring through selection, crossover, and mutation
     * 
     * @return Timetable New offspring timetable
     */
    private function createOffspring() {
        $parent1 = $this->tournamentSelection();
        $parent2 = $this->tournamentSelection();
        
        if (rand() / getrandmax() < $this->crossoverRate) {
            $offspring = $this->geneticOps->crossover($parent1, $parent2);
        } else {
            $offspring = $parent1->clone();
        }
        
        if (rand() / getrandmax() < $this->mutationRate) {
            $this->geneticOps->mutate($offspring);
        }
        
        return $offspring;
    }
    
    /**
     * Select an individual using tournament selection
     * 
     * @param int $tournamentSize Size of tournament (default: 5)
     * @return Timetable Selected timetable
     */
    private function tournamentSelection($tournamentSize = null) {
        if ($tournamentSize === null) {
            $tournamentSize = SchedulerConfig::TOURNAMENT_SIZE;
        }
        
        $tournament = [];
        for ($i = 0; $i < $tournamentSize; $i++) {
            $tournament[] = $this->population[rand(0, count($this->population) - 1)];
        }
        
        usort($tournament, function($a, $b) {
            return $b->fitness <=> $a->fitness;
        });
        
        return $tournament[0];
    }
    
    /**
     * Apply locked slots to a timetable
     * Locked slots should not be modified by the genetic algorithm
     * 
     * @param Timetable $timetable Timetable to apply locked slots to
     */
    private function applyLockedSlots($timetable) {
        // Locked slots functionality not implemented yet
        // This method is a placeholder to prevent errors
        // In the future, this would read locked slots from the database
        // and ensure they are preserved in the timetable
        return;
    }
}

/**
 * Constraint checker class - validates timetable constraints
 */
class ConstraintChecker {
    /** @var array Indexed array of Subject objects */
    private $subjects;
    
    /** @var array Indexed array of Teacher objects */
    private $teachers;
    
    /** @var array Array of Section objects */
    private $sections;
    
    /** @var int Maximum periods per week for any teacher */
    const MAX_TEACHER_WEEKLY_PERIODS = 40;
    
    /**
     * Create a new ConstraintChecker
     * 
     * @param array $subjects Subject objects
     * @param array $teachers Teacher objects
     * @param array $sections Section objects
     */
    public function __construct($subjects, $teachers, $sections) {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
    }
    
    // ========================================
    // ENHANCED TEACHER CONFLICT DETECTION
    // ========================================
    
    /**
     * Check if a teacher is available at a specific time slot
     * 
     * @param string $teacherId Teacher identifier
     * @param int $day Day index (0-5)
     * @param int $period Period index (0-7)
     * @param Timetable $timetable Current timetable
     * @return bool True if teacher is available (not assigned elsewhere)
     */
    public function isTeacherAvailable($teacherId, $day, $period, $timetable) {
        if (!$teacherId) {
            return true;
        }
        
        foreach ($this->sections as $section) {
            $slot = $timetable->slots[$section->id][$day][$period];
            if ($slot->teacherId === $teacherId) {
                return false; // Teacher already assigned at this time
            }
        }
        
        return true;
    }
    
    /**
     * Check if teacher is available based on their availability matrix
     * 
     * @param string $teacherId Teacher identifier
     * @param int $day Day index (0-5 for Sun-Fri)
     * @param int $period Period index (0-7)
     * @return bool True if teacher has this time slot marked as available
     */
    public function hasTeacherAvailability($teacherId, $day, $period) {
        if (!$teacherId || !isset($this->teachers[$teacherId])) {
            return true; // Unknown teacher, assume available
        }
        
        $teacher = $this->teachers[$teacherId];
        
        // If no availability matrix, assume available all times
        if (!$teacher->availabilityMatrix || empty($teacher->availabilityMatrix)) {
            return true;
        }
        
        // Map day index to day short name
        $dayMap = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        $dayShort = $dayMap[$day] ?? null;
        
        if (!$dayShort || !isset($teacher->availabilityMatrix[$dayShort])) {
            return false; // Day not in availability matrix
        }
        
        // Period is 0-indexed in algorithm but 1-indexed in matrix
        $periodKey = $period + 1;
        
        return isset($teacher->availabilityMatrix[$dayShort][$periodKey]) 
            && $teacher->availabilityMatrix[$dayShort][$periodKey] === true;
    }
    
    /**
     * Check if teacher is under daily period limit
     * 
     * @param string $teacherId Teacher identifier
     * @param int $day Day index (0-5)
     * @param Timetable $timetable Current timetable
     * @return bool True if under the daily limit
     */
    public function isTeacherUnderDailyLimit($teacherId, $day, $timetable) {
        if (!$teacherId || !isset($this->teachers[$teacherId])) {
            return true;
        }
        
        $teacher = $this->teachers[$teacherId];
        $dailyCount = $this->getTeacherDailyPeriodCount($teacherId, $day, $timetable);
        
        return $dailyCount < $teacher->maxPeriodsPerDay;
    }
    
    /**
     * Check if teacher is under weekly period limit (40 periods)
     * 
     * @param string $teacherId Teacher identifier
     * @param Timetable $timetable Current timetable
     * @return bool True if under the weekly limit
     */
    public function isTeacherUnderWeeklyLimit($teacherId, $timetable) {
        if (!$teacherId) {
            return true;
        }
        
        $weeklyCount = $this->getTeacherWeeklyPeriodCount($teacherId, $timetable);
        
        return $weeklyCount < self::MAX_TEACHER_WEEKLY_PERIODS;
    }
    
    /**
     * Check if teacher can teach a specific subject
     * 
     * @param string $teacherId Teacher identifier
     * @param string $subjectId Subject identifier
     * @return bool True if teacher is qualified to teach this subject
     */
    public function canTeacherTeachSubject($teacherId, $subjectId) {
        if (!$teacherId || !$subjectId) {
            return false;
        }
        
        if (!isset($this->teachers[$teacherId])) {
            return false;
        }
        
        $teacher = $this->teachers[$teacherId];
        return in_array($subjectId, $teacher->subjects);
    }
    
    /**
     * Get teacher's daily period count
     * 
     * @param string $teacherId Teacher identifier
     * @param int $day Day index
     * @param Timetable $timetable Current timetable
     * @return int Number of periods assigned
     */
    private function getTeacherDailyPeriodCount($teacherId, $day, $timetable) {
        $count = 0;
        
        foreach ($this->sections as $section) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($timetable->slots[$section->id][$day][$period]->teacherId === $teacherId) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Get teacher's weekly period count
     * 
     * @param string $teacherId Teacher identifier
     * @param Timetable $timetable Current timetable
     * @return int Number of periods assigned for the week
     */
    private function getTeacherWeeklyPeriodCount($teacherId, $timetable) {
        $count = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($timetable->slots[$section->id][$day][$period]->teacherId === $teacherId) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Get comprehensive violation details for a specific slot
     * 
     * @param Timetable $timetable Current timetable
     * @param string $sectionId Section identifier
     * @param int $day Day index
     * @param int $period Period index
     * @return array Array of violation types and details
     */
    public function getViolationType($timetable, $sectionId, $day, $period) {
        $violations = [];
        $slot = $timetable->slots[$sectionId][$day][$period];
        
        if (!$slot->subjectId || !$slot->teacherId) {
            if (!$slot->subjectId) {
                $violations[] = ['type' => 'empty_slot', 'severity' => 'critical'];
            }
            return $violations;
        }
        
        $teacherId = $slot->teacherId;
        $subjectId = $slot->subjectId;
        
        // Teacher availability check
        if (!$this->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
            $violations[] = [
                'type' => 'teacher_conflict',
                'severity' => 'critical',
                'message' => 'Teacher assigned to multiple sections simultaneously'
            ];
        }
        
        // Teacher capability check
        if (!$this->canTeacherTeachSubject($teacherId, $subjectId)) {
            $violations[] = [
                'type' => 'teacher_qualification',
                'severity' => 'critical',
                'message' => 'Teacher not qualified to teach this subject'
            ];
        }
        
        // Teacher daily limit check
        if (!$this->isTeacherUnderDailyLimit($teacherId, $day, $timetable)) {
            $dailyCount = $this->getTeacherDailyPeriodCount($teacherId, $day, $timetable);
            $maxDaily = $this->teachers[$teacherId]->maxPeriodsPerDay;
            $violations[] = [
                'type' => 'teacher_daily_overload',
                'severity' => 'high',
                'message' => "Teacher has {$dailyCount} periods (max: {$maxDaily})"
            ];
        }
        
        // Teacher weekly limit check
        if (!$this->isTeacherUnderWeeklyLimit($teacherId, $timetable)) {
            $weeklyCount = $this->getTeacherWeeklyPeriodCount($teacherId, $timetable);
            $violations[] = [
                'type' => 'teacher_weekly_overload',
                'severity' => 'high',
                'message' => "Teacher has {$weeklyCount} periods (max: 40)"
            ];
        }
        
        // Co-curricular subject checks
        if (isset($this->subjects[$subjectId]) && 
            $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
            
            // Check for multiple co-curricular subjects same day
            $coCurricularCount = $this->countCoCurricularSubjectsOnDay($timetable, $sectionId, $day);
            if ($coCurricularCount > 1) {
                $violations[] = [
                    'type' => 'multiple_cocurricular_same_day',
                    'severity' => 'high',
                    'message' => "{$coCurricularCount} co-curricular subjects on same day"
                ];
            }
        }
        
        // Subject daily limit check
        $subjectDailyCount = $this->countSubjectOnDay($timetable, $sectionId, $day, $subjectId);
        if ($subjectDailyCount > SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
            $violations[] = [
                'type' => 'subject_daily_excess',
                'severity' => 'medium',
                'message' => "Subject appears {$subjectDailyCount} times (max: 2)"
            ];
        }
        
        return $violations;
    }
    
    /**
     * Count how many times a subject appears on a specific day for a section
     * 
     * @param Timetable $timetable Current timetable
     * @param string $sectionId Section identifier
     * @param int $day Day index
     * @param string $subjectId Subject identifier
     * @return int Count of subject occurrences
     */
    private function countSubjectOnDay($timetable, $sectionId, $day, $subjectId) {
        $count = 0;
        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
            if ($timetable->slots[$sectionId][$day][$period]->subjectId === $subjectId) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Count distinct co-curricular subjects on a specific day for a section
     * 
     * @param Timetable $timetable Current timetable
     * @param string $sectionId Section identifier
     * @param int $day Day index
     * @return int Count of distinct co-curricular subjects
     */
    private function countCoCurricularSubjectsOnDay($timetable, $sectionId, $day) {
        $coCurricularSubjects = [];
        
        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
            $subjectId = $timetable->slots[$sectionId][$day][$period]->subjectId;
            if ($subjectId && 
                isset($this->subjects[$subjectId]) && 
                $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                
                if (!in_array($subjectId, $coCurricularSubjects)) {
                    $coCurricularSubjects[] = $subjectId;
                }
            }
        }
        
        return count($coCurricularSubjects);
    }
    
    // ========================================
    // HARD CONSTRAINT CHECKS (ENHANCED)
    // ========================================
    
    /**
     * Count co-curricular subjects appearing on same day (violation)
     * ENHANCED: More accurate detection of distinct co-curricular subjects
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkNoTwoCoCurricularSameDay($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $coCurricularSubjects = [];
                
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    
                    if ($subjectId && 
                        isset($this->subjects[$subjectId]) && 
                        $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        
                        // Track unique co-curricular subjects
                        if (!in_array($subjectId, $coCurricularSubjects)) {
                            $coCurricularSubjects[] = $subjectId;
                        }
                    }
                }
                
                // If more than one distinct co-curricular subject on same day
                if (count($coCurricularSubjects) > 1) {
                    $violations += count($coCurricularSubjects) - 1;
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check that subjects don't exceed max periods per day
     * ENHANCED: Better tracking and violation reporting
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkMaxTwoPeriodsPerSubjectPerDay($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $subjectCounts = $this->countSubjectsOnDay($timetable, $section, $day);
                
                foreach ($subjectCounts as $subjectId => $count) {
                    if ($count > SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                        $violations += $count - SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Helper: Count subjects on a specific day
     * 
     * @param Timetable $timetable Timetable to check
     * @param Section $section Section object
     * @param int $day Day number
     * @return array Map of subject ID to count
     */
    private function countSubjectsOnDay($timetable, $section, $day) {
        $subjectCounts = [];
        
        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
            $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
            if ($subjectId) {
                $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
            }
        }
        
        return $subjectCounts;
    }
    
    /**
     * Check that co-curricular periods (if 2) are consecutive
     * ENHANCED: Better handling of co-curricular scheduling
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkCoCurricularConsecutive($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $coCurricularPeriods = [];
                
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    
                    if ($subjectId && 
                        isset($this->subjects[$subjectId]) && 
                        $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        
                        $coCurricularPeriods[$subjectId][] = $period;
                    }
                }
                
                // Check each co-curricular subject's period arrangement
                foreach ($coCurricularPeriods as $subjectId => $periods) {
                    if (count($periods) == 2) {
                        // Must be consecutive
                        if ($periods[1] - $periods[0] != 1) {
                            $violations++;
                        }
                    } elseif (count($periods) > 2) {
                        // Should not have more than 2 periods
                        $violations += count($periods) - 2;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check that subjects meet weekly period requirements
     * ENHANCED: Tracks both under and over allocation with detailed reporting
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkSubjectWeeklyAllocation($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            $subjectCounts = [];
            
            // Count total periods per subject for the week
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if ($subjectId) {
                        $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                    }
                }
            }
            
            // Check each subject's allocation against requirements
            foreach ($subjectCounts as $subjectId => $count) {
                if (!isset($this->subjects[$subjectId])) {
                    continue;
                }
                
                $subject = $this->subjects[$subjectId];
                
                // Under-allocated
                if ($count < $subject->minPeriodsPerWeek) {
                    $violations += $subject->minPeriodsPerWeek - $count;
                }
                
                // Over-allocated
                if ($count > $subject->maxPeriodsPerWeek) {
                    $violations += $count - $subject->maxPeriodsPerWeek;
                }
            }
            
            // Check for subjects that should be assigned but aren't
            foreach ($this->subjects as $subject) {
                if (!isset($subjectCounts[$subject->id]) && $subject->minPeriodsPerWeek > 0) {
                    $violations += $subject->minPeriodsPerWeek;
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check for teacher conflicts (same teacher in multiple places at once)
     * ENHANCED: Uses new helper methods for comprehensive detection
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkTeacherConflicts($timetable) {
        $violations = 0;
        
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $teacherAssignments = [];
                
                foreach ($this->sections as $section) {
                    $slot = $timetable->slots[$section->id][$day][$period];
                    $teacherId = $slot->teacherId;
                    $subjectId = $slot->subjectId;
                    
                    if ($teacherId) {
                        // Track assignments
                        if (!isset($teacherAssignments[$teacherId])) {
                            $teacherAssignments[$teacherId] = [];
                        }
                        $teacherAssignments[$teacherId][] = [
                            'section' => $section->id,
                            'subject' => $subjectId
                        ];
                        
                        // Check if teacher can teach this subject
                        if ($subjectId && !$this->canTeacherTeachSubject($teacherId, $subjectId)) {
                            $violations++;
                        }
                    }
                }
                
                // Count simultaneous assignments (teacher in multiple places)
                foreach ($teacherAssignments as $teacherId => $assignments) {
                    if (count($assignments) > 1) {
                        $violations += count($assignments) - 1;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check teacher daily workload constraints
     * ENHANCED: Uses new daily limit checking method
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkTeacherWorkload($timetable) {
        $violations = 0;
        
        foreach ($this->teachers as $teacher) {
            // Check daily limits
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $dailyCount = $this->getTeacherDailyPeriodCount($teacher->id, $day, $timetable);
                
                if ($dailyCount > $teacher->maxPeriodsPerDay) {
                    $violations += $dailyCount - $teacher->maxPeriodsPerDay;
                }
            }
            
            // Check weekly limits
            $weeklyCount = $this->getTeacherWeeklyPeriodCount($teacher->id, $timetable);
            
            if ($weeklyCount > self::MAX_TEACHER_WEEKLY_PERIODS) {
                $violations += $weeklyCount - self::MAX_TEACHER_WEEKLY_PERIODS;
            }
        }
        
        return $violations;
    }
    
    /**
     * Check for empty slots in timetable
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkNoEmptySlots($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                        $violations++;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check combined subjects are scheduled simultaneously across sections
     * ENHANCED: Better validation logic for combined subject synchronization
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkCombinedSubjects($timetable) {
        $violations = 0;
        
        $gradeGroups = $this->groupSectionsByGrade();
        
        foreach ($gradeGroups as $grade => $sections) {
            if (count($sections) < 2) {
                continue;
            }
            
            $combinedSubjects = $this->getCombinedSubjects();
            
            foreach ($combinedSubjects as $subjectId) {
                // Check each day separately
                for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                    $subjectSchedules = [];
                    
                    // Collect schedules for this subject across all sections
                    foreach ($sections as $section) {
                        $periods = [];
                        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                            if ($timetable->slots[$section->id][$day][$period]->subjectId === $subjectId) {
                                $periods[] = $period;
                            }
                        }
                        
                        if (!empty($periods)) {
                            $subjectSchedules[$section->id] = $periods;
                        }
                    }
                    
                    // If multiple sections have this subject on same day, they must match
                    if (count($subjectSchedules) > 1) {
                        $firstSchedule = reset($subjectSchedules);
                        
                        foreach ($subjectSchedules as $sectionId => $schedule) {
                            // Compare period arrays
                            if (count(array_diff($schedule, $firstSchedule)) > 0 || 
                                count(array_diff($firstSchedule, $schedule)) > 0) {
                                $violations++;
                            }
                        }
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Group sections by grade
     * 
     * @return array Map of grade to sections array
     */
    private function groupSectionsByGrade() {
        $gradeGroups = [];
        foreach ($this->sections as $section) {
            $gradeGroups[$section->grade][] = $section;
        }
        return $gradeGroups;
    }
    
    /**
     * Get array of combined subject IDs
     * 
     * @return array Combined subject IDs
     */
    private function getCombinedSubjects() {
        $combinedSubjects = [];
        foreach ($this->subjects as $subject) {
            if ($subject->isCombined) {
                $combinedSubjects[] = $subject->id;
            }
        }
        return $combinedSubjects;
    }
    
    // ========================================
    // SOFT CONSTRAINT CHECKS
    // ========================================
    
    /**
     * Check positional consistency across days
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkPositionalConsistency($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            $dayPatterns = [];
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $pattern = [];
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    $subject = $subjectId ? $this->subjects[$subjectId] : null;
                    
                    if ($subject && $subject->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $pattern[] = 'CO_CURRICULAR';
                    } else {
                        $pattern[] = $subjectId;
                    }
                }
                $dayPatterns[] = $pattern;
            }
            
            for ($i = 1; $i < count($dayPatterns); $i++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($dayPatterns[0][$period] !== 'CO_CURRICULAR' && 
                        $dayPatterns[$i][$period] !== 'CO_CURRICULAR' &&
                        $dayPatterns[0][$period] !== $dayPatterns[$i][$period]) {
                        $violations++;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Prefer only one period per subject per day
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkMaxOnePerSubjectPerDay($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $subjectCounts = $this->countSubjectsOnDay($timetable, $section, $day);
                
                foreach ($subjectCounts as $count) {
                    if ($count > 1) {
                        $violations += $count - 1;
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check core subjects maintain consistent positions
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkCoreSubjectConsistency($timetable) {
        $violations = 0;
        $coreSubjects = ['English', 'Math', 'Science'];
        
        foreach ($this->sections as $section) {
            $corePositions = [];
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if ($subjectId) {
                        $subject = $this->subjects[$subjectId];
                        if (in_array($subject->name, $coreSubjects)) {
                            $corePositions[$subject->name][$day] = $period;
                        }
                    }
                }
            }
            
            foreach ($corePositions as $subjectName => $positions) {
                $uniquePositions = array_unique($positions);
                if (count($uniquePositions) > 1) {
                    $violations += count($uniquePositions) - 1;
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Check for spacing between heavy subjects
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkHeavySubjectSpacing($timetable) {
        $violations = 0;
        $heavySubjects = ['Math', 'Science'];
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY - 1; $period++) {
                    $current = $timetable->slots[$section->id][$day][$period]->subjectId;
                    $next = $timetable->slots[$section->id][$day][$period + 1]->subjectId;
                    
                    if ($current && $next) {
                        $currentSubject = $this->subjects[$current];
                        $nextSubject = $this->subjects[$next];
                        
                        if (in_array($currentSubject->name, $heavySubjects) && 
                            in_array($nextSubject->name, $heavySubjects)) {
                            $violations++;
                        }
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * Prefer co-curricular subjects in later periods
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Violation count
     */
    public function checkCoCurricularPlacement($timetable) {
        $violations = 0;
        
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if ($subjectId && $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $violations++;
                    }
                }
            }
        }
        
        return $violations;
    }
}

/**
 * Fitness calculator class - calculates timetable fitness scores
 */
class FitnessCalculator {
    /** @var ConstraintChecker Constraint checker instance */
    private $checker;
    
    /**
     * Create a new FitnessCalculator
     * 
     * @param ConstraintChecker $checker Constraint checker instance
     */
    public function __construct($checker) {
        $this->checker = $checker;
    }
    
    /**
     * Calculate fitness score for a timetable
     * 
     * @param Timetable $timetable Timetable to evaluate
     * @return float Fitness score (0-1, higher is better)
     */
    public function calculate($timetable) {
        $hardViolations = $this->calculateHardViolations($timetable);
        $softViolations = $this->calculateSoftViolations($timetable);
        
        $totalPenalty = ($hardViolations * SchedulerConfig::HARD_CONSTRAINT_WEIGHT) + 
                        ($softViolations * SchedulerConfig::SOFT_CONSTRAINT_WEIGHT);
        
        $fitness = 1 - ($totalPenalty / SchedulerConfig::MAX_POSSIBLE_VIOLATIONS);
        
        return max(0, $fitness);
    }
    
    /**
     * Calculate hard constraint violations
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Total hard violations
     */
    private function calculateHardViolations($timetable) {
        $violations = 0;
        
        $violations += $this->checker->checkNoTwoCoCurricularSameDay($timetable) * 
                       SchedulerConfig::WEIGHT_CO_CURRICULAR_SAME_DAY;
        $violations += $this->checker->checkMaxTwoPeriodsPerSubjectPerDay($timetable) * 
                       SchedulerConfig::WEIGHT_MAX_TWO_PERIODS_PER_DAY;
        $violations += $this->checker->checkCoCurricularConsecutive($timetable) * 
                       SchedulerConfig::WEIGHT_CO_CURRICULAR_CONSECUTIVE;
        $violations += $this->checker->checkSubjectWeeklyAllocation($timetable) * 
                       SchedulerConfig::WEIGHT_SUBJECT_WEEKLY_ALLOCATION;
        $violations += $this->checker->checkTeacherConflicts($timetable) * 
                       SchedulerConfig::WEIGHT_TEACHER_CONFLICTS;
        $violations += $this->checker->checkTeacherWorkload($timetable) * 
                       SchedulerConfig::WEIGHT_TEACHER_WORKLOAD;
        $violations += $this->checker->checkNoEmptySlots($timetable) * 
                       SchedulerConfig::WEIGHT_NO_EMPTY_SLOTS;
        $violations += $this->checker->checkCombinedSubjects($timetable) * 
                       SchedulerConfig::WEIGHT_COMBINED_SUBJECTS;
        
        return $violations;
    }
    
    /**
     * Calculate soft constraint violations
     * 
     * @param Timetable $timetable Timetable to check
     * @return int Total soft violations
     */
    private function calculateSoftViolations($timetable) {
        $violations = 0;
        
        $violations += $this->checker->checkPositionalConsistency($timetable) * 
                       SchedulerConfig::WEIGHT_POSITIONAL_CONSISTENCY;
        $violations += $this->checker->checkMaxOnePerSubjectPerDay($timetable) * 
                       SchedulerConfig::WEIGHT_MAX_ONE_PER_SUBJECT_PER_DAY;
        $violations += $this->checker->checkCoreSubjectConsistency($timetable) * 
                       SchedulerConfig::WEIGHT_CORE_SUBJECT_CONSISTENCY;
        $violations += $this->checker->checkHeavySubjectSpacing($timetable) * 
                       SchedulerConfig::WEIGHT_HEAVY_SUBJECT_SPACING;
        $violations += $this->checker->checkCoCurricularPlacement($timetable) * 
                       SchedulerConfig::WEIGHT_CO_CURRICULAR_PLACEMENT;
        
        return $violations;
    }
}

/**
 * Genetic operations class - handles crossover and mutation
 */
class GeneticOperations {
    /** @var array Indexed array of Subject objects */
    private $subjects;
    
    /** @var array Indexed array of Teacher objects */
    private $teachers;
    
    /** @var array Array of Section objects */
    private $sections;
    
    /** @var TimetableRepair Repair helper instance */
    private $repairHelper;
    
    /**
     * Create a new GeneticOperations instance
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
    }
    
    /**
     * Perform crossover between two parent timetables
     * 
     * @param Timetable $parent1 First parent
     * @param Timetable $parent2 Second parent
     * @return Timetable Offspring timetable
     */
    public function crossover($parent1, $parent2) {
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
     * Mutate a timetable by applying random changes
     * 
     * @param Timetable $timetable Timetable to mutate
     */
    public function mutate($timetable) {
        $section = $this->sections[array_rand($this->sections)];
        $mutationType = rand(0, 2);
        
        switch ($mutationType) {
            case 0:
                $this->swapPeriodsInSameDay($timetable, $section);
                break;
            case 1:
                $this->swapPeriodsAcrossDays($timetable, $section);
                break;
            case 2:
                $this->reassignRandomSubject($timetable, $section);
                break;
        }
        
        $this->repairHelper->repair($timetable);
    }
    
    /**
     * Swap two random periods in the same day
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function swapPeriodsInSameDay($timetable, $section) {
        $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period1 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        $period2 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        $temp = $timetable->slots[$section->id][$day][$period1];
        $timetable->slots[$section->id][$day][$period1] = 
            $timetable->slots[$section->id][$day][$period2];
        $timetable->slots[$section->id][$day][$period2] = $temp;
    }
    
    /**
     * Swap two random periods across different days
     * 
     * @param Timetable $timetable Timetable to mutate
     * @param Section $section Section to mutate
     */
    private function swapPeriodsAcrossDays($timetable, $section) {
        $day1 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $day2 = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period1 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        $period2 = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);
        
        $temp = $timetable->slots[$section->id][$day1][$period1];
        $timetable->slots[$section->id][$day1][$period1] = 
            $timetable->slots[$section->id][$day2][$period2];
        $timetable->slots[$section->id][$day2][$period2] = $temp;
    }
    
    /**
     * Reassign a random subject to a random slot
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
        
        $newSubjectId = $allSubjects[array_rand($allSubjects)];
        $newTeacherId = $this->assignTeacher($newSubjectId);
        
        $timetable->slots[$section->id][$day][$period]->subjectId = $newSubjectId;
        $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
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

/**
 * Timetable repair class - fixes constraint violations
 */
class TimetableRepair {
    /** @var array Indexed array of Subject objects */
    private $subjects;
    
    /** @var array Indexed array of Teacher objects */
    private $teachers;
    
    /** @var array Array of Section objects */
    private $sections;
    
    /** @var ConstraintChecker Constraint checker instance */
    private $constraintChecker;
    
    /**
     * Create a new TimetableRepair instance
     * 
     * @param array $subjects Subject objects
     * @param array $teachers Teacher objects
     * @param array $sections Section objects
     */
    public function __construct($subjects, $teachers, $sections) {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
        $this->constraintChecker = new ConstraintChecker($subjects, $teachers, $sections);
    }
    
    /**
     * Repair a timetable to fix constraint violations
     * 
     * @param Timetable $timetable Timetable to repair
     */
    public function repair($timetable) {
        static $repairCount = 0;
        $repairCount++;
        
        // Only show debug output for first few repairs to avoid spam
        $debug = ($repairCount <= 5);
        
        foreach ($this->sections as $section) {
            if ($debug) echo "    Repairing section {$section->id}...\n";
            $this->ensureTotalPeriods($timetable, $section);
            $this->fixSubjectAllocations($timetable, $section);
            $this->fixCoCurricularConstraints($timetable, $section);
            $this->fixDailySubjectLimits($timetable, $section);
        }
        
        if ($debug) echo "    Fixing teacher constraints...\n";
        $this->fixTeacherConstraints($timetable);
        if ($debug) echo "    Fixing combined subjects...\n";
        $this->fixCombinedSubjects($timetable);
    }

    /**
     * Ensure each section has exactly 48 periods filled
     * 
     * @param Timetable $timetable Timetable to repair
     * @param Section $section Section to process
     */
    private function ensureTotalPeriods($timetable, $section) {
        $filledCount = 0;
        
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($timetable->slots[$section->id][$day][$period]->subjectId !== null) {
                    $filledCount++;
                }
            }
        }
        
        if ($filledCount < SchedulerConfig::TOTAL_PERIODS) {
            $allSubjects = array_merge(
                $section->compulsorySubjects,
                $section->optionalSubjects,
                $section->coCurricularSubjects
            );
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    if ($timetable->slots[$section->id][$day][$period]->subjectId === null) {
                        $subjectId = $allSubjects[array_rand($allSubjects)];
                        $teacherId = $this->assignTeacher($subjectId);
                        
                        $timetable->slots[$section->id][$day][$period]->subjectId = $subjectId;
                        $timetable->slots[$section->id][$day][$period]->teacherId = $teacherId;
                    }
                }
            }
        }
    }

    /**
     * Fix subject weekly allocations to be within min/max bounds
     * 
     * @param Timetable $timetable Timetable to repair
     * @param Section $section Section to process
     */
    private function fixSubjectAllocations($timetable, $section) {
        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
        
        $subjectCounts = [];
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId) {
                    $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                }
            }
        }
        
        // Identify subjects that need adjustment
        $needMore = [];
        $needLess = [];
        
        foreach ($allSubjects as $subjectId) {
            $subject = $this->subjects[$subjectId];
            $currentCount = $subjectCounts[$subjectId] ?? 0;
            
            if ($currentCount < $subject->minPeriodsPerWeek) {
                $needMore[$subjectId] = $subject->minPeriodsPerWeek - $currentCount;
            } elseif ($currentCount > $subject->maxPeriodsPerWeek) {
                $needLess[$subjectId] = $currentCount - $subject->maxPeriodsPerWeek;
            }
        }
        
        // Process subjects with excess periods first (with limit)
        $processedExcess = 0;
        foreach ($needLess as $excessSubjectId => $excessCount) {
            $removed = 0;
            $maxAttemptsPerSubject = SchedulerConfig::TOTAL_PERIODS;
            $attempts = 0;
            
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK && $removed < $excessCount && $attempts < $maxAttemptsPerSubject; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY && $removed < $excessCount && $attempts < $maxAttemptsPerSubject; $period++) {
                    $attempts++;
                    if ($timetable->slots[$section->id][$day][$period]->subjectId === $excessSubjectId) {
                        // Find a subject that needs more periods
                        $replacementSubjectId = null;
                        foreach ($needMore as $neededSubjectId => $neededCount) {
                            if ($neededCount > 0) {
                                $replacementSubjectId = $neededSubjectId;
                                $needMore[$neededSubjectId]--;
                                break;
                            }
                        }
                        
                        if ($replacementSubjectId) {
                            $newTeacherId = $this->assignTeacher($replacementSubjectId);
                            $timetable->slots[$section->id][$day][$period]->subjectId = $replacementSubjectId;
                            $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
                            $removed++;
                        }
                    }
                }
            }
            $processedExcess++;
        }
        
        // Process subjects that need more periods (with limit)
        foreach ($needMore as $neededSubjectId => $neededCount) {
            if ($neededCount > 0) {
                $added = 0;
                $maxAttemptsPerSubject = SchedulerConfig::TOTAL_PERIODS;
                $attempts = 0;
                
                for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK && $added < $neededCount && $attempts < $maxAttemptsPerSubject; $day++) {
                    for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY && $added < $neededCount && $attempts < $maxAttemptsPerSubject; $period++) {
                        $attempts++;
                        $currentSubjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                        
                        if ($currentSubjectId !== $neededSubjectId) {
                            $currentSubject = $this->subjects[$currentSubjectId];
                            $currentCount = $subjectCounts[$currentSubjectId] ?? 0;
                            
                            // Only swap if current subject is above minimum
                            if ($currentCount > $currentSubject->minPeriodsPerWeek) {
                                $newTeacherId = $this->assignTeacher($neededSubjectId);
                                $timetable->slots[$section->id][$day][$period]->subjectId = $neededSubjectId;
                                $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
                                
                                $subjectCounts[$currentSubjectId]--;
                                $added++;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix co-curricular subject constraints:
     * - No two different co-curricular subjects on same day
     * - If 2 periods, must be consecutive
     * 
     * @param Timetable $timetable Timetable to repair
     * @param Section $section Section to process
     */
    private function fixCoCurricularConstraints($timetable, $section) {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $coCurricularSubjects = [];
            $coCurricularPositions = [];
            
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId && $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                    if (!isset($coCurricularPositions[$subjectId])) {
                        $coCurricularPositions[$subjectId] = [];
                    }
                    $coCurricularPositions[$subjectId][] = $period;
                    
                    if (!in_array($subjectId, $coCurricularSubjects)) {
                        $coCurricularSubjects[] = $subjectId;
                    }
                }
            }
            
            // Fix: More than one type of co-curricular subject
            if (count($coCurricularSubjects) > 1) {
                // Keep the first one, replace others with non-co-curricular subjects
                $keepSubjectId = $coCurricularSubjects[0];
                
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    
                    if ($subjectId && $this->subjects[$subjectId]->type === 'co_curricular' && $subjectId !== $keepSubjectId) {
                        // Replace with a compulsory or optional subject
                        $replacementSubjects = array_merge(
                            $section->compulsorySubjects,
                            $section->optionalSubjects
                        );
                        
                        $newSubjectId = $replacementSubjects[array_rand($replacementSubjects)];
                        $newTeacherId = $this->assignTeacher($newSubjectId);
                        
                        $timetable->slots[$section->id][$day][$period]->subjectId = $newSubjectId;
                        $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
                    }
                }
                
                // Update positions after replacement
                $coCurricularPositions = [$keepSubjectId => $coCurricularPositions[$keepSubjectId]];
            }
            
            foreach ($coCurricularPositions as $subjectId => $positions) {
                if (count($positions) == 2) {
                    if ($positions[1] - $positions[0] != 1) {
                        $firstPeriod = $positions[0];
                        $secondPeriod = $positions[1];
                        
                        if ($firstPeriod < SchedulerConfig::PERIODS_PER_DAY - 1) {
                            $targetPeriod = $firstPeriod + 1;
                            
                            // Swap with whatever is in target period
                            $temp = clone $timetable->slots[$section->id][$day][$targetPeriod];
                            $timetable->slots[$section->id][$day][$targetPeriod] = clone $timetable->slots[$section->id][$day][$secondPeriod];
                            $timetable->slots[$section->id][$day][$secondPeriod] = $temp;
                        }
                    }
                } elseif (count($positions) > 2) {
                    // Remove excess periods (keep only 2 consecutive)
                    $firstPeriod = $positions[0];
                    
                    // Replace excess periods with other subjects
                    for ($i = 2; $i < count($positions); $i++) {
                        $periodToReplace = $positions[$i];
                        $replacementSubjects = array_merge(
                            $section->compulsorySubjects,
                            $section->optionalSubjects
                        );
                        
                        $newSubjectId = $replacementSubjects[array_rand($replacementSubjects)];
                        $newTeacherId = $this->assignTeacher($newSubjectId);
                        
                        $timetable->slots[$section->id][$day][$periodToReplace]->subjectId = $newSubjectId;
                        $timetable->slots[$section->id][$day][$periodToReplace]->teacherId = $newTeacherId;
                    }
                }
            }
        }
    }

    /**
     * Fix daily subject limits (max 2 periods per subject per day)
     * 
     * @param Timetable $timetable Timetable to repair
     * @param Section $section Section to process
     */
    private function fixDailySubjectLimits($timetable, $section) {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $subjectCounts = [];
            $subjectPositions = [];
            
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId) {
                    $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;
                    
                    if (!isset($subjectPositions[$subjectId])) {
                        $subjectPositions[$subjectId] = [];
                    }
                    $subjectPositions[$subjectId][] = $period;
                }
            }
            
            // Fix subjects with more than 2 periods
            foreach ($subjectCounts as $subjectId => $count) {
                if ($count > 2) {
                    $excessCount = $count - 2;
                    $positions = $subjectPositions[$subjectId];
                    
                    // Keep first 2 occurrences, replace others
                    for ($i = 2; $i < count($positions); $i++) {
                        $periodToReplace = $positions[$i];
                        
                        // Find a subject that appears less than 2 times today
                        $replacementSubjectId = null;
                        $allSubjects = array_merge(
                            $section->compulsorySubjects,
                            $section->optionalSubjects
                        );
                        
                        shuffle($allSubjects);
                        foreach ($allSubjects as $candidateSubjectId) {
                            if (($subjectCounts[$candidateSubjectId] ?? 0) < 2 && $candidateSubjectId !== $subjectId) {
                                $replacementSubjectId = $candidateSubjectId;
                                break;
                            }
                        }
                        
                        if ($replacementSubjectId) {
                            $newTeacherId = $this->assignTeacher($replacementSubjectId);
                            $timetable->slots[$section->id][$day][$periodToReplace]->subjectId = $replacementSubjectId;
                            $timetable->slots[$section->id][$day][$periodToReplace]->teacherId = $newTeacherId;
                            
                            $subjectCounts[$replacementSubjectId] = ($subjectCounts[$replacementSubjectId] ?? 0) + 1;
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix teacher conflicts and workload constraints
     * 
     * @param Timetable $timetable Timetable to repair
     */
    private function fixTeacherConstraints($timetable) {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $teacherAssignments = [];
                
                // Collect all teacher assignments for this time slot
                foreach ($this->sections as $section) {
                    $teacherId = $timetable->slots[$section->id][$day][$period]->teacherId;
                    if ($teacherId) {
                        if (!isset($teacherAssignments[$teacherId])) {
                            $teacherAssignments[$teacherId] = [];
                        }
                        $teacherAssignments[$teacherId][] = $section->id;
                    }
                }
                
                // Fix conflicts (teacher assigned to multiple sections)
                foreach ($teacherAssignments as $teacherId => $sectionIds) {
                    if (count($sectionIds) > 1) {
                        // Keep first assignment, reassign others
                        for ($i = 1; $i < count($sectionIds); $i++) {
                            $sectionId = $sectionIds[$i];
                            $subjectId = $timetable->slots[$sectionId][$day][$period]->subjectId;
                            
                            // Find alternative teacher
                            $newTeacherId = $this->findAlternativeTeacher($subjectId, $teacherId, $day, $period, $timetable);
                            $timetable->slots[$sectionId][$day][$period]->teacherId = $newTeacherId;
                        }
                    }
                }
            }
        }
        
        foreach ($this->teachers as $teacher) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $periodCount = 0;
                $assignments = [];
                
                foreach ($this->sections as $section) {
                    for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                        if ($timetable->slots[$section->id][$day][$period]->teacherId === $teacher->id) {
                            $periodCount++;
                            $assignments[] = [
                                'section' => $section->id,
                                'period' => $period
                            ];
                        }
                    }
                }
                
                // If exceeds max, reassign excess periods
                if ($periodCount > $teacher->maxPeriodsPerDay) {
                    $excess = $periodCount - $teacher->maxPeriodsPerDay;
                    
                    // Reassign last few assignments
                    for ($i = 0; $i < $excess && $i < count($assignments); $i++) {
                        $assignment = $assignments[count($assignments) - 1 - $i];
                        $sectionId = $assignment['section'];
                        $period = $assignment['period'];
                        $subjectId = $timetable->slots[$sectionId][$day][$period]->subjectId;
                        
                        $newTeacherId = $this->findAlternativeTeacher($subjectId, $teacher->id, $day, $period, $timetable);
                        $timetable->slots[$sectionId][$day][$period]->teacherId = $newTeacherId;
                    }
                }
            }
        }
    }

    /**
     * Find an alternative teacher for a subject
     * 
     * @param string $subjectId Subject identifier
     * @param string $excludeTeacherId Teacher to exclude
     * @param int $day Day number
     * @param int $period Period number
     * @param Timetable $timetable Current timetable
     * @return string Teacher ID
     */
    private function findAlternativeTeacher($subjectId, $excludeTeacherId, $day, $period, $timetable) {
        $eligibleTeachers = [];
        
        foreach ($this->teachers as $teacher) {
            if ($teacher->id === $excludeTeacherId) continue;
            
            if (in_array($subjectId, $teacher->subjects)) {
                // Check if teacher has this time slot in their availability
                if (!$this->constraintChecker->hasTeacherAvailability($teacher->id, $day, $period)) {
                    continue;
                }
                
                // Check if teacher is free at this time
                $isFree = true;
                foreach ($this->sections as $section) {
                    if ($timetable->slots[$section->id][$day][$period]->teacherId === $teacher->id) {
                        $isFree = false;
                        break;
                    }
                }
                
                if ($isFree) {
                    $eligibleTeachers[] = $teacher->id;
                }
            }
        }
        
        return !empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : $excludeTeacherId;
    }

    /**
     * Fix combined subjects (must be scheduled simultaneously across sections of same grade)
     * 
     * @param Timetable $timetable Timetable to repair
     */
    private function fixCombinedSubjects($timetable) {
        $gradeGroups = [];
        foreach ($this->sections as $section) {
            $gradeGroups[$section->grade][] = $section;
        }
        
        foreach ($gradeGroups as $grade => $sections) {
            if (count($sections) < 2) continue;
            
            $combinedSubjects = [];
            foreach ($this->subjects as $subject) {
                if ($subject->isCombined) {
                    $combinedSubjects[] = $subject->id;
                }
            }
            
            foreach ($combinedSubjects as $subjectId) {
                $schedules = [];
                
                foreach ($sections as $section) {
                    for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                            if ($timetable->slots[$section->id][$day][$period]->subjectId === $subjectId) {
                                $schedules[$section->id][$day][] = $period;
                            }
                        }
                    }
                }
                
                if (empty($schedules)) continue;
                
                // Use first section's schedule as reference
                $referenceSection = $sections[0];
                $referenceSchedule = $schedules[$referenceSection->id] ?? [];
                
                // Align other sections to match reference
                for ($i = 1; $i < count($sections); $i++) {
                    $section = $sections[$i];
                    
                    foreach ($referenceSchedule as $day => $periods) {
                        $currentPeriods = $schedules[$section->id][$day] ?? [];
                        
                        // Remove current occurrences of this subject on this day
                        foreach ($currentPeriods as $p) {
                            $timetable->slots[$section->id][$day][$p]->subjectId = null;
                            $timetable->slots[$section->id][$day][$p]->teacherId = null;
                        }
                        
                        // Add subject at reference periods
                        foreach ($periods as $p) {
                            $teacherId = $this->assignTeacher($subjectId);
                            $timetable->slots[$section->id][$day][$p]->subjectId = $subjectId;
                            $timetable->slots[$section->id][$day][$p]->teacherId = $teacherId;
                        }
                    }
                }
            }
        }
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