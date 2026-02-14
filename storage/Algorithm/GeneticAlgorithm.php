<?php

/**
 * School Timetable Scheduling using Genetic Algorithm
 * Based on optimization algorithms for educational timetabling
 *
 * @author Timetable System
 *
 * @version 2.0.0
 */

/**
 * Configuration class for algorithm parameters
 */
class SchedulerConfig
{
    // Schedule structure constants
    const DAYS_PER_WEEK = 6;

    const PERIODS_PER_DAY = 8;

    const TOTAL_PERIODS = 48;

    const BREAK_PERIOD = 4;  // Period 4 is typically break time (0-indexed: period 5 in 1-indexed)

    // Genetic algorithm parameters
    const DEFAULT_POPULATION_SIZE = 25;

    const DEFAULT_MAX_GENERATIONS = 100;

    const DEFAULT_MUTATION_RATE = 0.12;

    const DEFAULT_CROSSOVER_RATE = 0.85;

    const ELITE_PERCENTAGE = 0.15;

    const TOURNAMENT_SIZE = 4;

    // Constraint weights — individual check weights already encode importance
    const HARD_CONSTRAINT_WEIGHT = 1;

    const SOFT_CONSTRAINT_WEIGHT = 1;

    // Hard constraint weights (higher = more critical)
    const WEIGHT_NO_EMPTY_SLOTS = 50;              // Must fill all 48 periods

    const WEIGHT_TEACHER_UNAVAILABILITY = 45;      // Teacher must be available at scheduled time

    const WEIGHT_TEACHER_CONFLICTS = 40;           // No teacher double-booking

    const WEIGHT_TEACHER_WORKLOAD = 35;            // Max 7 periods per day per teacher

    const WEIGHT_PHYSICAL_NOT_IN_PERIOD_5 = 50;    // Physical/sports subjects must NOT be in period 5

    const WEIGHT_CO_CURRICULAR_SAME_DAY = 30;      // Only 1 co-curricular per day

    const WEIGHT_CO_CURRICULAR_CONSECUTIVE = 25;   // Co-curricular 2 periods must be consecutive

    const WEIGHT_SUBJECT_WEEKLY_ALLOCATION = 25;   // Meet min/max weekly requirements

    const WEIGHT_COMBINED_SUBJECTS = 25;           // Combined subjects same time

    const WEIGHT_MAX_TWO_PERIODS_PER_DAY = 20;     // Max 2 periods per subject per day

    // Soft constraint weights
    const WEIGHT_POSITIONAL_CONSISTENCY = 20;

    const WEIGHT_MAX_ONE_PER_SUBJECT_PER_DAY = 20;

    const WEIGHT_CORE_SUBJECT_CONSISTENCY = 18;

    const WEIGHT_HEAVY_SUBJECT_SPACING = 22;

    const WEIGHT_CO_CURRICULAR_PLACEMENT = 15;

    const WEIGHT_COMBINED_PERIOD_ADJACENCY = 25;

    const STAGNATION_THRESHOLD = 4;

    const STAGNATION_TOLERANCE = 0.002;

    const OPTIMAL_FITNESS_THRESHOLD = 0.98;

    const MAX_POSSIBLE_VIOLATIONS = 20000;

    // Co-curricular placement preferences (0-indexed: 3 = DB period 4, matching ConflictChecker's period < 4)
    const CO_CURRICULAR_PREFERRED_START_PERIOD = 3;

    const MAX_PERIODS_PER_SUBJECT_PER_DAY = 2;

    // Subject types
    const TYPE_COMPULSORY = 'compulsory';

    const TYPE_OPTIONAL = 'optional';

    const TYPE_CO_CURRICULAR = 'co_curricular';
}

/**
 * Represents a subject in the timetable
 */
class Subject
{
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
     * @param  string  $id  Subject identifier
     * @param  string  $name  Subject name
     * @param  string  $type  Subject type (compulsory/optional/co_curricular)
     * @param  int  $minPeriods  Minimum periods per week
     * @param  int  $maxPeriods  Maximum periods per week
     * @param  bool  $isCombined  Whether subject is combined across sections
     */
    public function __construct($id, $name, $type, $minPeriods, $maxPeriods, $isCombined = false)
    {
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
class Teacher
{
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
     * @param  string  $id  Teacher identifier
     * @param  string  $name  Teacher name
     * @param  array  $subjects  Array of subject IDs
     * @param  int  $maxPeriodsPerDay  Maximum periods per day (default: 7)
     * @param  array|null  $availabilityMatrix  Availability matrix (default: null means available all times)
     */
    public function __construct($id, $name, $subjects, $maxPeriodsPerDay = 7, $availabilityMatrix = null)
    {
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
class Section
{
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
     * @param  string  $id  Section identifier
     * @param  string  $grade  Grade/year level
     * @param  string  $name  Section name
     */
    public function __construct($id, $grade, $name)
    {
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
class TimeSlot
{
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
     * @param  int  $day  Day of the week (0-5)
     * @param  int  $period  Period number (0-7)
     * @param  string|null  $subjectId  Subject identifier
     * @param  string|null  $teacherId  Teacher identifier
     * @param  string|null  $sectionId  Section identifier
     */
    public function __construct($day, $period, $subjectId = null, $teacherId = null, $sectionId = null)
    {
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
class Timetable
{
    /** @var array 3D array of time slots: [section][day][period] */
    public $slots;

    /** @var float Fitness score of this timetable */
    public $fitness;

    /**
     * Create a new Timetable instance
     *
     * @param  array  $sections  Array of Section objects
     */
    public function __construct($sections)
    {
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
        $this->fitness = null;
    }

    /**
     * Create a deep copy of this timetable
     *
     * @return Timetable Cloned timetable instance
     */
    public function clone()
    {
        $clone = new Timetable([]);
        $clone->slots = unserialize(serialize($this->slots));
        $clone->fitness = $this->fitness;

        return $clone;
    }
}

/**
 * Main scheduler class implementing genetic algorithm for timetable optimization
 */
class GeneticAlgorithmScheduler
{
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
     * @param  array  $subjects  Indexed array of Subject objects
     * @param  array  $teachers  Indexed array of Teacher objects
     * @param  array  $sections  Array of Section objects
     * @param  int  $populationSize  Population size (default: 20)
     * @param  int  $maxGenerations  Maximum generations (default: 150)
     * @param  array  $lockedSlots  Pre-locked slots that must be preserved
     */
    public function __construct($subjects, $teachers, $sections, $populationSize = 20, $maxGenerations = 150, $lockedSlots = [])
    {
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
    public function generateTimetable()
    {
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
    private function sortPopulationByFitness()
    {
        usort($this->population, function ($a, $b) {
            return $b->fitness <=> $a->fitness;
        });
    }

    /**
     * Log generation progress
     *
     * @param  int  $generation  Current generation number
     * @param  float  $fitness  Current best fitness
     */
    private function logProgress($generation, $fitness)
    {
        if ($generation % 20 == 0 || $generation == 0) {
            echo "Generation $generation: Best Fitness = ".round($fitness, 4)."\n";
        }
    }

    /**
     * Check if current solution is optimal
     *
     * @param  float  $fitness  Current fitness score
     * @return bool True if optimal
     */
    private function isOptimalSolution($fitness)
    {
        return $fitness >= SchedulerConfig::OPTIMAL_FITNESS_THRESHOLD;
    }

    /**
     * Handle stagnation by introducing diversity if needed
     *
     * @param  float  $currentFitness  Current best fitness
     * @param  float  $previousBestFitness  Previous best fitness
     * @param  int  $stagnantCount  Current stagnation counter
     * @return int Updated stagnation counter
     */
    private function handleStagnation($currentFitness, $previousBestFitness, $stagnantCount)
    {
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
    private function initializePopulation()
    {
        echo "Initializing population of {$this->populationSize} timetables...\n";

        // First 10% use pure constructive initialization for high-quality seeds (reduced from 20%)
        $constructiveCount = max(1, (int) ($this->populationSize * 0.1));
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
     * @param  float  $randomizationFactor  Factor for introducing randomization (0-1). 0 = pure constructive, 1 = fully random
     * @return Timetable Constructively generated timetable
     */
    private function createConstructiveTimetable($randomizationFactor = 0)
    {
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
    private function placeCombinedSubjects($timetable, $randomizationFactor)
    {
        $combinedSubjects = $this->getCombinedSubjects();

        if (empty($combinedSubjects)) {
            return;
        }

        echo '    Phase 1: Placing '.count($combinedSubjects)." combined subjects...\n";

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
    private function placeCoCurricularSubjects($timetable, $randomizationFactor)
    {
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
    private function placeCompulsorySubjects($timetable, $randomizationFactor)
    {
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

            usort($subjectsWithPeriods, function ($a, $b) {
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
    private function placeOptionalSubjects($timetable, $randomizationFactor)
    {
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

                while ($attempts < count($subjectIds) && ! $assigned) {
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
                if (! $assigned && ! empty($subjectIds)) {
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
    private function createRandomTimetable()
    {
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
     * @param  Timetable  $timetable  Timetable to fill
     * @param  Section  $section  Section to fill
     */
    private function fillSectionTimetable($timetable, $section)
    {
        $allSubjects = $this->getAllSubjectsForSection($section);
        $subjectPeriods = $this->calculateSubjectPeriods($allSubjects);
        $subjectPeriods = $this->adjustToTotalPeriods($subjectPeriods);
        $subjectPool = $this->createSubjectPool($subjectPeriods);

        $this->assignSubjectsToSlots($timetable, $section, $subjectPool);
    }

    /**
     * Get all subjects for a section
     *
     * @param  Section  $section  Section object
     * @return array Array of subject IDs
     */
    private function getAllSubjectsForSection($section)
    {
        return array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );
    }

    /**
     * Calculate random period allocation for subjects
     *
     * @param  array  $subjectIds  Array of subject IDs
     * @return array Map of subject ID to period count
     */
    private function calculateSubjectPeriods($subjectIds)
    {
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
     * @param  array  $subjectPeriods  Map of subject ID to period count
     * @return array Adjusted period allocations
     */
    private function adjustToTotalPeriods($subjectPeriods)
    {
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

            if ($totalPeriods < SchedulerConfig::TOTAL_PERIODS && ! empty($canIncrease)) {
                $subjectId = $canIncrease[array_rand($canIncrease)];
                $subjectPeriods[$subjectId]++;
                $totalPeriods++;
            } elseif ($totalPeriods > SchedulerConfig::TOTAL_PERIODS && ! empty($canDecrease)) {
                $subjectId = $canDecrease[array_rand($canDecrease)];
                $subjectPeriods[$subjectId]--;
                $totalPeriods--;
            } else {
                // Cannot adjust further - force adjustment to reach total
                if ($totalPeriods < SchedulerConfig::TOTAL_PERIODS && ! empty($subjectPeriods)) {
                    $subjectId = array_rand($subjectPeriods);
                    $subjectPeriods[$subjectId]++;
                    $totalPeriods++;
                } elseif ($totalPeriods > SchedulerConfig::TOTAL_PERIODS && ! empty($subjectPeriods)) {
                    // Find a subject above minimum to reduce
                    $reduced = false;
                    foreach ($subjectPeriods as $sid => $cnt) {
                        $subject = $this->subjects[$sid];
                        if ($cnt > $subject->minPeriodsPerWeek) {
                            $subjectPeriods[$sid]--;
                            $totalPeriods--;
                            $reduced = true;
                            break;
                        }
                    }
                    // Only as a last resort, reduce any subject with > 0 periods
                    if (! $reduced) {
                        $subjectId = array_rand($subjectPeriods);
                        if ($subjectPeriods[$subjectId] > 0) {
                            $subjectPeriods[$subjectId]--;
                            $totalPeriods--;
                        }
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
     * @param  array  $subjectPeriods  Map of subject ID to period count
     * @return array Shuffled array of subject IDs
     */
    private function createSubjectPool($subjectPeriods)
    {
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
     * @param  Timetable  $timetable  Timetable to fill
     * @param  Section  $section  Section to fill
     * @param  array  $subjectPool  Pool of subject IDs
     */
    private function assignSubjectsToSlots($timetable, $section, $subjectPool)
    {
        $index = 0;
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $subjectPool[$index];
                $teacherId = $this->assignTeacher($subjectId, $day, $period);

                $timetable->slots[$section->id][$day][$period]->subjectId = $subjectId;
                $timetable->slots[$section->id][$day][$period]->teacherId = $teacherId;

                $index++;
            }
        }
    }

    /**
     * Assign an eligible teacher to a subject, preferring available teachers
     *
     * @param  string  $subjectId  Subject identifier
     * @param  int|null  $day  Day index (0-indexed) for availability check
     * @param  int|null  $period  Period index (0-indexed) for availability check
     * @return string|null Teacher ID or null if no eligible teacher
     */
    private function assignTeacher($subjectId, $day = null, $period = null)
    {
        $eligibleTeachers = $this->getEligibleTeachers($subjectId);

        if (empty($eligibleTeachers)) {
            return null;
        }

        // If day/period provided, prefer teachers who are available at that time
        if ($day !== null && $period !== null) {
            $availableTeachers = array_filter($eligibleTeachers, function ($teacherId) use ($day, $period) {
                return $this->constraintChecker->hasTeacherAvailability($teacherId, $day, $period);
            });

            if (! empty($availableTeachers)) {
                return $availableTeachers[array_rand($availableTeachers)];
            }
        }

        // Fallback to any eligible teacher
        return $eligibleTeachers[array_rand($eligibleTeachers)];
    }

    /**
     * Get list of teachers eligible to teach a subject
     *
     * @param  string  $subjectId  Subject identifier
     * @return array Array of teacher IDs
     */
    private function getEligibleTeachers($subjectId)
    {
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
    private function evaluatePopulation()
    {
        foreach ($this->population as $timetable) {
            if ($timetable->fitness === null) {
                $timetable->fitness = $this->fitnessCalculator->calculate($timetable);
            }
        }
    }

    /**
     * Introduce diversity into population to escape local optima
     */
    private function introduceDiversity()
    {
        $keepCount = (int) ($this->populationSize * 0.2);

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
     * @param  Subject  $subject  Subject object
     * @param  float  $randomizationFactor  Randomization factor (0-1)
     * @return int Number of periods
     */
    private function calculateOptimalPeriods($subject, $randomizationFactor)
    {
        if ($randomizationFactor > 0 && mt_rand() / mt_getrandmax() < $randomizationFactor) {
            // Use random allocation
            return rand($subject->minPeriodsPerWeek, $subject->maxPeriodsPerWeek);
        }

        // Use intelligent allocation - prefer max for compulsory, average for others
        if ($subject->type === SchedulerConfig::TYPE_COMPULSORY) {
            return $subject->maxPeriodsPerWeek;
        } else {
            return (int) ceil(($subject->minPeriodsPerWeek + $subject->maxPeriodsPerWeek) / 2);
        }
    }

    /**
     * Get list of combined subjects
     *
     * @return array Array of combined subject IDs
     */
    private function getCombinedSubjects()
    {
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
     * @param  Section  $section  Section object
     * @param  string  $subjectId  Subject ID
     * @return bool True if section has the subject
     */
    private function sectionHasSubject($section, $subjectId)
    {
        return in_array($subjectId, $section->compulsorySubjects) ||
               in_array($subjectId, $section->optionalSubjects) ||
               in_array($subjectId, $section->coCurricularSubjects);
    }

    /**
     * Find a synchronized slot for combined subjects across all sections
     *
     * @param  Timetable  $timetable  Current timetable
     * @param  string  $subjectId  Subject ID
     * @param  float  $randomizationFactor  Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findSynchronizedSlot($timetable, $subjectId, $randomizationFactor)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  array  $sections  Array of sections
     * @param  int  $day  Day index
     * @param  int  $period  Period index
     * @param  string  $subjectId  Subject ID
     * @return bool True if can be placed
     */
    private function canPlaceCombinedSubjectInSlot($timetable, $sections, $day, $period, $subjectId)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @param  string  $subjectId  Subject ID
     * @param  int  $consecutiveCount  Number of consecutive periods needed
     * @param  float  $randomizationFactor  Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findConsecutiveCoCurricularSlots($timetable, $section, $subjectId, $consecutiveCount, $randomizationFactor)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @param  string  $subjectId  Subject ID
     * @param  float  $randomizationFactor  Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findCoCurricularSlot($timetable, $section, $subjectId, $randomizationFactor)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @param  string  $subjectId  Subject ID
     * @param  float  $randomizationFactor  Randomization factor
     * @return array|null Array with 'day' and 'period' keys, or null if not found
     */
    private function findBestSlotForSubject($timetable, $section, $subjectId, $randomizationFactor)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @param  int  $day  Day index
     * @param  int  $period  Period index
     * @param  string  $subjectId  Subject ID
     * @return bool True if can be placed
     */
    private function canPlaceSubjectInSlot($timetable, $section, $day, $period, $subjectId)
    {
        $subject = $this->subjects[$subjectId];

        // Check if slot is empty
        if ($timetable->slots[$section->id][$day][$period]->subjectId !== null) {
            return false;
        }

        // Block physical/co-curricular subjects from period 5
        if ($period === SchedulerConfig::BREAK_PERIOD) {
            if ($subject->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                return false;
            }
            $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];
            $lName = strtolower($subject->name);
            foreach ($physicalKeywords as $kw) {
                if (strpos($lName, $kw) !== false) {
                    return false;
                }
            }
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @return array Array of empty slots with 'day' and 'period' keys
     */
    private function getEmptySlots($timetable, $section)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  Section  $section  Section object
     * @param  int  $day  Day index
     * @param  int  $period  Period index
     * @param  string  $subjectId  Subject ID
     * @return string|null Teacher ID or null if none found
     */
    private function findSafeTeacher($timetable, $section, $day, $period, $subjectId)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  string  $teacherId  Teacher ID
     * @param  int  $day  Day index
     * @param  int  $period  Period index
     * @param  string  $subjectId  Subject ID
     * @return bool True if teacher is safe to assign
     */
    private function isTeacherSafeForSlot($timetable, $teacherId, $day, $period, $subjectId)
    {
        // Check if teacher can teach this subject
        if (! $this->constraintChecker->canTeacherTeachSubject($teacherId, $subjectId)) {
            return false;
        }

        // Check if teacher has this time slot in their availability matrix
        if (! $this->constraintChecker->hasTeacherAvailability($teacherId, $day, $period)) {
            return false;
        }

        // Check if teacher is available at this time
        if (! $this->constraintChecker->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
            return false;
        }

        // Check if teacher is under daily limit
        if (! $this->constraintChecker->isTeacherUnderDailyLimit($teacherId, $day, $timetable)) {
            return false;
        }

        // Check if teacher is under weekly limit
        if (! $this->constraintChecker->isTeacherUnderWeeklyLimit($teacherId, $timetable)) {
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
    private function evolvePopulation()
    {
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
    private function selectElite()
    {
        $eliteCount = (int) ($this->populationSize * SchedulerConfig::ELITE_PERCENTAGE);
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
    private function createOffspring()
    {
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
     * @param  int  $tournamentSize  Size of tournament (default: 5)
     * @return Timetable Selected timetable
     */
    private function tournamentSelection($tournamentSize = null)
    {
        if ($tournamentSize === null) {
            $tournamentSize = SchedulerConfig::TOURNAMENT_SIZE;
        }

        $tournament = [];
        for ($i = 0; $i < $tournamentSize; $i++) {
            $tournament[] = $this->population[rand(0, count($this->population) - 1)];
        }

        usort($tournament, function ($a, $b) {
            return $b->fitness <=> $a->fitness;
        });

        return $tournament[0];
    }

    /**
     * Apply locked slots to a timetable
     * Locked slots should not be modified by the genetic algorithm
     *
     * @param  Timetable  $timetable  Timetable to apply locked slots to
     */
    private function applyLockedSlots($timetable)
    {
        // Locked slots functionality not implemented yet
        // This method is a placeholder to prevent errors
        // In the future, this would read locked slots from the database
        // and ensure they are preserved in the timetable

    }
}

/**
 * Constraint checker class - validates timetable constraints
 */
class ConstraintChecker
{
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
     * @param  array  $subjects  Subject objects
     * @param  array  $teachers  Teacher objects
     * @param  array  $sections  Section objects
     */
    public function __construct($subjects, $teachers, $sections)
    {
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
     * @param  string  $teacherId  Teacher identifier
     * @param  int  $day  Day index (0-5)
     * @param  int  $period  Period index (0-7)
     * @param  Timetable  $timetable  Current timetable
     * @return bool True if teacher is available (not assigned elsewhere)
     */
    public function isTeacherAvailable($teacherId, $day, $period, $timetable)
    {
        if (! $teacherId) {
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
     * @param  string  $teacherId  Teacher identifier
     * @param  int  $day  Day index (0-5 for Sun-Fri)
     * @param  int  $period  Period index (0-7)
     * @return bool True if teacher has this time slot marked as available
     */
    public function hasTeacherAvailability($teacherId, $day, $period)
    {
        if (! $teacherId || ! isset($this->teachers[$teacherId])) {
            return true; // Unknown teacher, assume available
        }

        $teacher = $this->teachers[$teacherId];

        // If no availability matrix, assume available all times
        if (! $teacher->availabilityMatrix || empty($teacher->availabilityMatrix)) {
            return true;
        }

        // Map day index to day short name
        $dayMap = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        $dayShort = $dayMap[$day] ?? null;

        if (! $dayShort || ! isset($teacher->availabilityMatrix[$dayShort])) {
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
     * @param  string  $teacherId  Teacher identifier
     * @param  int  $day  Day index (0-5)
     * @param  Timetable  $timetable  Current timetable
     * @return bool True if under the daily limit
     */
    public function isTeacherUnderDailyLimit($teacherId, $day, $timetable)
    {
        if (! $teacherId || ! isset($this->teachers[$teacherId])) {
            return true;
        }

        $teacher = $this->teachers[$teacherId];
        $dailyCount = $this->getTeacherDailyPeriodCount($teacherId, $day, $timetable);

        return $dailyCount < $teacher->maxPeriodsPerDay;
    }

    /**
     * Check if teacher is under weekly period limit (40 periods)
     *
     * @param  string  $teacherId  Teacher identifier
     * @param  Timetable  $timetable  Current timetable
     * @return bool True if under the weekly limit
     */
    public function isTeacherUnderWeeklyLimit($teacherId, $timetable)
    {
        if (! $teacherId) {
            return true;
        }

        $weeklyCount = $this->getTeacherWeeklyPeriodCount($teacherId, $timetable);

        return $weeklyCount < self::MAX_TEACHER_WEEKLY_PERIODS;
    }

    /**
     * Check if teacher can teach a specific subject
     *
     * @param  string  $teacherId  Teacher identifier
     * @param  string  $subjectId  Subject identifier
     * @return bool True if teacher is qualified to teach this subject
     */
    public function canTeacherTeachSubject($teacherId, $subjectId)
    {
        if (! $teacherId || ! $subjectId) {
            return false;
        }

        if (! isset($this->teachers[$teacherId])) {
            return false;
        }

        $teacher = $this->teachers[$teacherId];

        return in_array($subjectId, $teacher->subjects);
    }

    /**
     * Get teacher's daily period count
     *
     * @param  string  $teacherId  Teacher identifier
     * @param  int  $day  Day index
     * @param  Timetable  $timetable  Current timetable
     * @return int Number of periods assigned
     */
    private function getTeacherDailyPeriodCount($teacherId, $day, $timetable)
    {
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
     * @param  string  $teacherId  Teacher identifier
     * @param  Timetable  $timetable  Current timetable
     * @return int Number of periods assigned for the week
     */
    private function getTeacherWeeklyPeriodCount($teacherId, $timetable)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  string  $sectionId  Section identifier
     * @param  int  $day  Day index
     * @param  int  $period  Period index
     * @return array Array of violation types and details
     */
    public function getViolationType($timetable, $sectionId, $day, $period)
    {
        $violations = [];
        $slot = $timetable->slots[$sectionId][$day][$period];

        if (! $slot->subjectId || ! $slot->teacherId) {
            if (! $slot->subjectId) {
                $violations[] = ['type' => 'empty_slot', 'severity' => 'critical'];
            }

            return $violations;
        }

        $teacherId = $slot->teacherId;
        $subjectId = $slot->subjectId;

        // Teacher availability check
        if (! $this->isTeacherAvailable($teacherId, $day, $period, $timetable)) {
            $violations[] = [
                'type' => 'teacher_conflict',
                'severity' => 'critical',
                'message' => 'Teacher assigned to multiple sections simultaneously',
            ];
        }

        // Teacher capability check
        if (! $this->canTeacherTeachSubject($teacherId, $subjectId)) {
            $violations[] = [
                'type' => 'teacher_qualification',
                'severity' => 'critical',
                'message' => 'Teacher not qualified to teach this subject',
            ];
        }

        // Teacher daily limit check
        if (! $this->isTeacherUnderDailyLimit($teacherId, $day, $timetable)) {
            $dailyCount = $this->getTeacherDailyPeriodCount($teacherId, $day, $timetable);
            $maxDaily = $this->teachers[$teacherId]->maxPeriodsPerDay;
            $violations[] = [
                'type' => 'teacher_daily_overload',
                'severity' => 'high',
                'message' => "Teacher has {$dailyCount} periods (max: {$maxDaily})",
            ];
        }

        // Teacher weekly limit check
        if (! $this->isTeacherUnderWeeklyLimit($teacherId, $timetable)) {
            $weeklyCount = $this->getTeacherWeeklyPeriodCount($teacherId, $timetable);
            $violations[] = [
                'type' => 'teacher_weekly_overload',
                'severity' => 'high',
                'message' => "Teacher has {$weeklyCount} periods (max: 40)",
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
                    'message' => "{$coCurricularCount} co-curricular subjects on same day",
                ];
            }
        }

        // Subject daily limit check
        $subjectDailyCount = $this->countSubjectOnDay($timetable, $sectionId, $day, $subjectId);
        if ($subjectDailyCount > SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
            $violations[] = [
                'type' => 'subject_daily_excess',
                'severity' => 'medium',
                'message' => "Subject appears {$subjectDailyCount} times (max: 2)",
            ];
        }

        return $violations;
    }

    /**
     * Count how many times a subject appears on a specific day for a section
     *
     * @param  Timetable  $timetable  Current timetable
     * @param  string  $sectionId  Section identifier
     * @param  int  $day  Day index
     * @param  string  $subjectId  Subject identifier
     * @return int Count of subject occurrences
     */
    private function countSubjectOnDay($timetable, $sectionId, $day, $subjectId)
    {
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
     * @param  Timetable  $timetable  Current timetable
     * @param  string  $sectionId  Section identifier
     * @param  int  $day  Day index
     * @return int Count of distinct co-curricular subjects
     */
    private function countCoCurricularSubjectsOnDay($timetable, $sectionId, $day)
    {
        $coCurricularSubjects = [];

        for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
            $subjectId = $timetable->slots[$sectionId][$day][$period]->subjectId;
            if ($subjectId &&
                isset($this->subjects[$subjectId]) &&
                $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {

                if (! in_array($subjectId, $coCurricularSubjects)) {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkNoTwoCoCurricularSameDay($timetable)
    {
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
                        if (! in_array($subjectId, $coCurricularSubjects)) {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkMaxTwoPeriodsPerSubjectPerDay($timetable)
    {
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
     * @param  Timetable  $timetable  Timetable to check
     * @param  Section  $section  Section object
     * @param  int  $day  Day number
     * @return array Map of subject ID to count
     */
    private function countSubjectsOnDay($timetable, $section, $day)
    {
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
     * ENHANCED: Skips multi-subject days (caught by checkNoTwoCoCurricularSameDay)
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkCoCurricularConsecutive($timetable)
    {
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

                // Skip if multiple different co-curricular subjects on same day
                // (already caught by checkNoTwoCoCurricularSameDay)
                if (count($coCurricularPeriods) > 1) {
                    continue;
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
     * Check that physical/co-curricular subjects are NOT placed in period 5 (0-indexed: 4)
     * Hard requirement: Physical subjects must NOT be scheduled in period 5
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkPhysicalPeriodPlacement($timetable)
    {
        $violations = 0;
        $forbiddenPeriod = SchedulerConfig::BREAK_PERIOD; // Period 5 in 1-indexed = 4 in 0-indexed

        $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];

        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $subjectId = $timetable->slots[$section->id][$day][$forbiddenPeriod]->subjectId;

                if (! $subjectId || ! isset($this->subjects[$subjectId])) {
                    continue;
                }

                $subject = $this->subjects[$subjectId];
                $lowerName = strtolower($subject->name);

                foreach ($physicalKeywords as $keyword) {
                    if (strpos($lowerName, $keyword) !== false) {
                        $violations++;
                        break;
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Check that teachers are not scheduled during unavailable times
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkTeacherUnavailability($timetable)
    {
        $violations = 0;

        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $teacherId = $timetable->slots[$section->id][$day][$period]->teacherId;
                    if ($teacherId && ! $this->hasTeacherAvailability($teacherId, $day, $period)) {
                        $violations++;
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkSubjectWeeklyAllocation($timetable)
    {
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
                if (! isset($this->subjects[$subjectId])) {
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
                if (! isset($subjectCounts[$subject->id]) && $subject->minPeriodsPerWeek > 0) {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkTeacherConflicts($timetable)
    {
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
                        if (! isset($teacherAssignments[$teacherId])) {
                            $teacherAssignments[$teacherId] = [];
                        }
                        $teacherAssignments[$teacherId][] = [
                            'section' => $section->id,
                            'subject' => $subjectId,
                        ];

                        // Check if teacher can teach this subject
                        if ($subjectId && ! $this->canTeacherTeachSubject($teacherId, $subjectId)) {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkTeacherWorkload($timetable)
    {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkNoEmptySlots($timetable)
    {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkCombinedSubjects($timetable)
    {
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

                        if (! empty($periods)) {
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
    private function groupSectionsByGrade()
    {
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
    private function getCombinedSubjects()
    {
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
     * Check positional consistency across days (aligned with ConflictChecker).
     * Excludes co-curricular subjects from the order sequence.
     * Uses day 0 as reference. Only flags a day if >30% of positions differ.
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkPositionalConsistency($timetable)
    {
        $violations = 0;

        foreach ($this->sections as $section) {
            // Build subject order per day, EXCLUDING co-curricular
            $dayOrders = [];

            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $order = [];
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if ($subjectId && isset($this->subjects[$subjectId]) &&
                        $this->subjects[$subjectId]->type !== SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $order[] = $subjectId;
                    }
                }
                $dayOrders[$day] = $order;
            }

            // Use day 0 as reference
            $reference = $dayOrders[0];
            if (empty($reference)) {
                continue;
            }

            for ($day = 1; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $order = $dayOrders[$day];
                if (empty($order)) {
                    continue;
                }

                $mismatches = 0;
                $totalComparable = min(count($reference), count($order));
                for ($i = 0; $i < $totalComparable; $i++) {
                    if (($reference[$i] ?? null) !== ($order[$i] ?? null)) {
                        $mismatches++;
                    }
                }

                // Only flag if more than 30% differ (matches ConflictChecker)
                if ($totalComparable > 0 && ($mismatches / $totalComparable) > 0.3) {
                    $violations++;
                }
            }
        }

        return $violations;
    }

    /**
     * Prefer only one period per subject per day (skip co-curricular — they're allowed 2/day)
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkMaxOnePerSubjectPerDay($timetable)
    {
        $violations = 0;

        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                $subjectCounts = $this->countSubjectsOnDay($timetable, $section, $day);

                foreach ($subjectCounts as $subjectId => $count) {
                    // Skip co-curricular subjects — they're allowed 2 per day
                    if (isset($this->subjects[$subjectId]) &&
                        $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        continue;
                    }
                    if ($count > 1) {
                        $violations += $count - 1;
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Check core subjects maintain consistent positions (aligned with ConflictChecker).
     * Uses case-insensitive keyword matching. Takes FIRST period per day.
     * Only flags if subject appears on >=3 days AND uses >=3 different first-period positions.
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkCoreSubjectConsistency($timetable)
    {
        $violations = 0;
        $coreKeywords = ['english', 'math', 'maths', 'mathematics', 'science'];

        foreach ($this->sections as $section) {
            $corePositions = [];

            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    if (! $subjectId || ! isset($this->subjects[$subjectId])) {
                        continue;
                    }

                    $subject = $this->subjects[$subjectId];
                    $lowerName = strtolower($subject->name);

                    $isCore = false;
                    foreach ($coreKeywords as $keyword) {
                        if (strpos($lowerName, $keyword) !== false) {
                            $isCore = true;
                            break;
                        }
                    }

                    // Take FIRST period per day per subject (matches ConflictChecker)
                    if ($isCore && ! isset($corePositions[$subjectId][$day])) {
                        $corePositions[$subjectId][$day] = $period;
                    }
                }
            }

            foreach ($corePositions as $subjectId => $positions) {
                $uniquePositions = count(array_unique($positions));
                $totalDays = count($positions);

                // Match ConflictChecker: only flag if >=3 days AND >=3 unique positions
                if ($totalDays >= 3 && $uniquePositions >= 3) {
                    $violations++;
                }
            }
        }

        return $violations;
    }

    /**
     * Check for spacing between heavy subjects
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkHeavySubjectSpacing($timetable)
    {
        $violations = 0;
        $heavyKeywords = ['math', 'maths', 'mathematics', 'science', 'english'];

        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY - 1; $period++) {
                    $current = $timetable->slots[$section->id][$day][$period]->subjectId;
                    $next = $timetable->slots[$section->id][$day][$period + 1]->subjectId;

                    if ($current && $next) {
                        $currentSubject = $this->subjects[$current];
                        $nextSubject = $this->subjects[$next];

                        $currentIsHeavy = false;
                        $nextIsHeavy = false;
                        $currentLower = strtolower($currentSubject->name);
                        $nextLower = strtolower($nextSubject->name);

                        foreach ($heavyKeywords as $keyword) {
                            if (strpos($currentLower, $keyword) !== false) {
                                $currentIsHeavy = true;
                            }
                            if (strpos($nextLower, $keyword) !== false) {
                                $nextIsHeavy = true;
                            }
                        }

                        if ($currentIsHeavy && $nextIsHeavy) {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkCoCurricularPlacement($timetable)
    {
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

    /**
     * Check that combined subjects have adjacent periods on the same day.
     * This catches the 'combined_period_violation' from ConflictChecker which
     * was previously MISSING entirely from the GA.
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Violation count
     */
    public function checkCombinedPeriodAdjacency($timetable)
    {
        $violations = 0;

        foreach ($this->sections as $section) {
            foreach ($this->subjects as $subject) {
                if (! $subject->isCombined) {
                    continue;
                }

                for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                    $periods = [];
                    for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                        if ($timetable->slots[$section->id][$day][$period]->subjectId === $subject->id) {
                            $periods[] = $period;
                        }
                    }

                    if (count($periods) < 2) {
                        continue;
                    }

                    sort($periods);
                    for ($i = 0; $i < count($periods) - 1; $i++) {
                        if ($periods[$i + 1] - $periods[$i] > 1) {
                            $violations++;
                        }
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Helper: check if a subject is "heavy" (mentally demanding)
     *
     * @param  Subject  $subject  Subject to check
     * @param  array  $keywords  Heavy subject keywords
     * @return bool True if subject is heavy
     */
    public function isHeavySubject($subject, $keywords = null)
    {
        if ($keywords === null) {
            $keywords = ['math', 'maths', 'mathematics', 'science', 'english'];
        }

        $lower = strtolower($subject->name);
        foreach ($keywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Fitness calculator class - calculates timetable fitness scores
 */
class FitnessCalculator
{
    /** @var ConstraintChecker Constraint checker instance */
    private $checker;

    /**
     * Create a new FitnessCalculator
     *
     * @param  ConstraintChecker  $checker  Constraint checker instance
     */
    public function __construct($checker)
    {
        $this->checker = $checker;
    }

    /**
     * Calculate fitness score for a timetable
     *
     * @param  Timetable  $timetable  Timetable to evaluate
     * @return float Fitness score (0-1, higher is better)
     */
    public function calculate($timetable)
    {
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
     * @param  Timetable  $timetable  Timetable to check
     * @return int Total hard violations
     */
    private function calculateHardViolations($timetable)
    {
        $violations = 0;

        $violations += $this->checker->checkTeacherUnavailability($timetable) *
                       SchedulerConfig::WEIGHT_TEACHER_UNAVAILABILITY;
        $violations += $this->checker->checkNoTwoCoCurricularSameDay($timetable) *
                       SchedulerConfig::WEIGHT_CO_CURRICULAR_SAME_DAY;
        $violations += $this->checker->checkMaxTwoPeriodsPerSubjectPerDay($timetable) *
                       SchedulerConfig::WEIGHT_MAX_TWO_PERIODS_PER_DAY;
        $violations += $this->checker->checkCoCurricularConsecutive($timetable) *
                       SchedulerConfig::WEIGHT_CO_CURRICULAR_CONSECUTIVE;
        $violations += $this->checker->checkPhysicalPeriodPlacement($timetable) *
                       SchedulerConfig::WEIGHT_PHYSICAL_NOT_IN_PERIOD_5;
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
        $violations += $this->checker->checkCombinedPeriodAdjacency($timetable) *
                       SchedulerConfig::WEIGHT_COMBINED_PERIOD_ADJACENCY;

        return $violations;
    }

    /**
     * Calculate soft constraint violations
     *
     * @param  Timetable  $timetable  Timetable to check
     * @return int Total soft violations
     */
    private function calculateSoftViolations($timetable)
    {
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
class GeneticOperations
{
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

    /**
     * Create a new GeneticOperations instance
     *
     * @param  array  $subjects  Subject objects
     * @param  array  $teachers  Teacher objects
     * @param  array  $sections  Section objects
     */
    public function __construct($subjects, $teachers, $sections)
    {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
        $this->repairHelper = new TimetableRepair($subjects, $teachers, $sections);
        $this->constraintChecker = new ConstraintChecker($subjects, $teachers, $sections);
    }

    /**
     * Perform crossover between two parent timetables
     *
     * @param  Timetable  $parent1  First parent
     * @param  Timetable  $parent2  Second parent
     * @return Timetable Offspring timetable
     */
    public function crossover($parent1, $parent2)
    {
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
     * @param  Timetable  $timetable  Timetable to mutate
     */
    public function mutate($timetable)
    {
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

        // Reset fitness so it gets recalculated
        $timetable->fitness = null;
    }

    /**
     * Swap two random periods in the same day
     *
     * @param  Timetable  $timetable  Timetable to mutate
     * @param  Section  $section  Section to mutate
     */
    private function swapPeriodsInSameDay($timetable, $section)
    {
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
     * @param  Timetable  $timetable  Timetable to mutate
     * @param  Section  $section  Section to mutate
     */
    private function swapPeriodsAcrossDays($timetable, $section)
    {
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
     * @param  Timetable  $timetable  Timetable to mutate
     * @param  Section  $section  Section to mutate
     */
    private function reassignRandomSubject($timetable, $section)
    {
        $day = rand(0, SchedulerConfig::DAYS_PER_WEEK - 1);
        $period = rand(0, SchedulerConfig::PERIODS_PER_DAY - 1);

        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects
        );

        $newSubjectId = $allSubjects[array_rand($allSubjects)];
        $newTeacherId = $this->assignTeacher($newSubjectId, $day, $period);

        $timetable->slots[$section->id][$day][$period]->subjectId = $newSubjectId;
        $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
    }

    /**
     * Assign an eligible teacher to a subject, preferring available teachers
     *
     * @param  string  $subjectId  Subject identifier
     * @param  int|null  $day  Day index for availability check
     * @param  int|null  $period  Period index for availability check
     * @return string|null Teacher ID or null
     */
    private function assignTeacher($subjectId, $day = null, $period = null)
    {
        $eligibleTeachers = [];
        $availableTeachers = [];

        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects)) {
                $eligibleTeachers[] = $teacher->id;
                if ($day !== null && $period !== null &&
                    $this->constraintChecker->hasTeacherAvailability($teacher->id, $day, $period)) {
                    $availableTeachers[] = $teacher->id;
                }
            }
        }

        // Prefer available teachers
        if (! empty($availableTeachers)) {
            return $availableTeachers[array_rand($availableTeachers)];
        }

        return ! empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : null;
    }
}

/**
 * Timetable repair class - fixes constraint violations
 */
class TimetableRepair
{
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
     * @param  array  $subjects  Subject objects
     * @param  array  $teachers  Teacher objects
     * @param  array  $sections  Section objects
     */
    public function __construct($subjects, $teachers, $sections)
    {
        $this->subjects = $subjects;
        $this->teachers = $teachers;
        $this->sections = $sections;
        $this->constraintChecker = new ConstraintChecker($subjects, $teachers, $sections);
    }

    /**
     * Repair a timetable to fix constraint violations
     *
     * @param  Timetable  $timetable  Timetable to repair
     */
    public function repair($timetable)
    {
        static $repairCount = 0;
        $repairCount++;

        // Only show debug output for first few repairs to avoid spam
        $debug = ($repairCount <= 5);

        foreach ($this->sections as $section) {
            if ($debug) {
                echo "    Repairing section {$section->id}...\n";
            }
            $this->ensureTotalPeriods($timetable, $section);
            $this->fixSubjectAllocations($timetable, $section);
            $this->fixDailySubjectLimits($timetable, $section);
        }

        if ($debug) {
            echo "    Fixing teacher constraints...\n";
        }
        $this->fixTeacherConstraints($timetable);
        $this->fixTeacherAvailability($timetable);
        if ($debug) {
            echo "    Fixing combined subjects...\n";
        }
        $this->fixCombinedSubjects($timetable);

        // Fix subject daily balance (non-co-curricular appearing 2+ per day)
        foreach ($this->sections as $section) {
            $this->fixSubjectDailyBalance($timetable, $section);
        }

        // Break up consecutive heavy subjects
        foreach ($this->sections as $section) {
            $this->fixConsecutiveHeavySubjects($timetable, $section);
        }

        // Move co-curricular from early periods to later ones
        foreach ($this->sections as $section) {
            $this->fixCoCurricularPlacement($timetable, $section);
        }

        // Run co-curricular fixes (same-day, consecutiveness)
        foreach ($this->sections as $section) {
            $this->fixCoCurricularConstraints($timetable, $section);
        }

        // Move physical subjects OUT of period 5 — LAST so other repairs don't undo it
        foreach ($this->sections as $section) {
            $this->fixPhysicalPeriodPlacement($timetable, $section);
        }

        // Final lightweight cleanup: fix any weekly max and daily >2 violations
        // without touching co-curricular subjects
        foreach ($this->sections as $section) {
            $this->fixRemainingHardViolations($timetable, $section);
        }
    }

    /**
     * Ensure each section has exactly 48 periods filled
     *
     * @param  Timetable  $timetable  Timetable to repair
     * @param  Section  $section  Section to process
     */
    private function ensureTotalPeriods($timetable, $section)
    {
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
                        $teacherId = $this->assignTeacher($subjectId, $day, $period);

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
     * @param  Timetable  $timetable  Timetable to repair
     * @param  Section  $section  Section to process
     */
    private function fixSubjectAllocations($timetable, $section)
    {
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
                            $newTeacherId = $this->assignTeacher($replacementSubjectId, $day, $period);
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
                                $newTeacherId = $this->assignTeacher($neededSubjectId, $day, $period);
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
     * @param  Timetable  $timetable  Timetable to repair
     * @param  Section  $section  Section to process
     */
    private function fixCoCurricularConstraints($timetable, $section)
    {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $coCurricularSubjects = [];
            $coCurricularPositions = [];

            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId && $this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                    if (! isset($coCurricularPositions[$subjectId])) {
                        $coCurricularPositions[$subjectId] = [];
                    }
                    $coCurricularPositions[$subjectId][] = $period;

                    if (! in_array($subjectId, $coCurricularSubjects)) {
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
                        $newSubjectId = $this->findBestReplacementSubject($timetable, $section, $day);
                        $newTeacherId = $this->assignTeacher($newSubjectId, $day, $period);

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

                        $newSubjectId = $this->findBestReplacementSubject($timetable, $section, $day);
                        $newTeacherId = $this->assignTeacher($newSubjectId, $day, $periodToReplace);
                        $newTeacherId = $this->assignTeacher($newSubjectId, $day, $periodToReplace);

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
     * @param  Timetable  $timetable  Timetable to repair
     * @param  Section  $section  Section to process
     */
    private function fixDailySubjectLimits($timetable, $section)
    {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $subjectCounts = [];
            $subjectPositions = [];

            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($subjectId) {
                    $subjectCounts[$subjectId] = ($subjectCounts[$subjectId] ?? 0) + 1;

                    if (! isset($subjectPositions[$subjectId])) {
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
                            $newTeacherId = $this->assignTeacher($replacementSubjectId, $day, $periodToReplace);
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
     * @param  Timetable  $timetable  Timetable to repair
     */
    private function fixTeacherConstraints($timetable)
    {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $teacherAssignments = [];

                // Collect all teacher assignments for this time slot
                foreach ($this->sections as $section) {
                    $teacherId = $timetable->slots[$section->id][$day][$period]->teacherId;
                    if ($teacherId) {
                        if (! isset($teacherAssignments[$teacherId])) {
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
                                'period' => $period,
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
     * @param  string  $subjectId  Subject identifier
     * @param  string  $excludeTeacherId  Teacher to exclude
     * @param  int  $day  Day number
     * @param  int  $period  Period number
     * @param  Timetable  $timetable  Current timetable
     * @return string Teacher ID
     */
    private function findAlternativeTeacher($subjectId, $excludeTeacherId, $day, $period, $timetable)
    {
        $eligibleTeachers = [];

        foreach ($this->teachers as $teacher) {
            if ($teacher->id === $excludeTeacherId) {
                continue;
            }

            if (in_array($subjectId, $teacher->subjects)) {
                // Check if teacher has this time slot in their availability
                if (! $this->constraintChecker->hasTeacherAvailability($teacher->id, $day, $period)) {
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

        return ! empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : $excludeTeacherId;
    }

    /**
     * Fix teacher availability violations - swap unavailable teachers for available ones
     *
     * @param  Timetable  $timetable  Timetable to repair
     */
    private function fixTeacherAvailability($timetable)
    {
        foreach ($this->sections as $section) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                    $teacherId = $timetable->slots[$section->id][$day][$period]->teacherId;
                    $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;

                    if (! $teacherId || ! $subjectId) {
                        continue;
                    }

                    // Check if teacher is available at this time
                    if (! $this->constraintChecker->hasTeacherAvailability($teacherId, $day, $period)) {
                        // Find an alternative teacher who is available
                        $newTeacherId = $this->findAlternativeTeacher(
                            $subjectId,
                            $teacherId,
                            $day,
                            $period,
                            $timetable
                        );

                        if ($newTeacherId !== $teacherId) {
                            $timetable->slots[$section->id][$day][$period]->teacherId = $newTeacherId;
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix combined subjects (must be scheduled simultaneously across sections of same grade)
     *
     * @param  Timetable  $timetable  Timetable to repair
     */
    private function fixCombinedSubjects($timetable)
    {
        $gradeGroups = [];
        foreach ($this->sections as $section) {
            $gradeGroups[$section->grade][] = $section;
        }

        foreach ($gradeGroups as $grade => $sections) {
            if (count($sections) < 2) {
                continue;
            }

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

                if (empty($schedules)) {
                    continue;
                }

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
                            $teacherId = $this->assignTeacher($subjectId, $day, $p);
                            $timetable->slots[$section->id][$day][$p]->subjectId = $subjectId;
                            $timetable->slots[$section->id][$day][$p]->teacherId = $teacherId;
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix physical/sports subjects that ARE in period 5 (0-indexed: 4).
     * Swaps them AWAY from period 5 to another available period.
     */
    private function fixPhysicalPeriodPlacement($timetable, $section)
    {
        $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];
        $forbiddenPeriod = SchedulerConfig::BREAK_PERIOD; // 4 (0-indexed) = period 5

        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            // Check if period 5 has a physical subject
            $sid = $timetable->slots[$section->id][$day][$forbiddenPeriod]->subjectId;
            if (! $sid || ! isset($this->subjects[$sid])) {
                continue;
            }

            $lowerName = strtolower($this->subjects[$sid]->name);
            $isPhysical = false;
            foreach ($physicalKeywords as $kw) {
                if (strpos($lowerName, $kw) !== false) {
                    $isPhysical = true;
                    break;
                }
            }

            if (! $isPhysical) {
                continue;
            }

            // Find a non-physical subject in another period to swap with
            $swapped = false;
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                if ($period === $forbiddenPeriod) {
                    continue;
                }

                $otherSid = $timetable->slots[$section->id][$day][$period]->subjectId;
                if (! $otherSid || ! isset($this->subjects[$otherSid])) {
                    continue;
                }

                // Make sure the swap target is NOT physical
                $otherLower = strtolower($this->subjects[$otherSid]->name);
                $otherIsPhysical = false;
                foreach ($physicalKeywords as $kw) {
                    if (strpos($otherLower, $kw) !== false) {
                        $otherIsPhysical = true;
                        break;
                    }
                }

                if ($otherIsPhysical) {
                    continue;
                }

                // Swap physical out of period 5
                $temp = clone $timetable->slots[$section->id][$day][$forbiddenPeriod];
                $timetable->slots[$section->id][$day][$forbiddenPeriod] = clone $timetable->slots[$section->id][$day][$period];
                $timetable->slots[$section->id][$day][$period] = $temp;
                $swapped = true;
                break;
            }
        }
    }

    /**
     * Lightweight final cleanup: fix weekly max/min violations and daily >2 excess.
     * Does NOT touch co-curricular subjects or period-5 physical subjects.
     * Runs multiple passes to stabilize.
     */
    private function fixRemainingHardViolations($timetable, $section)
    {
        $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];

        // Helper: check if a slot is protected (co-curricular only)
        $isProtected = function ($subjectId, $period) use ($physicalKeywords) {
            if (! isset($this->subjects[$subjectId])) {
                return true;
            }
            $subject = $this->subjects[$subjectId];
            if ($subject->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                return true;
            }

            return false;
        };

        // Helper: count weekly allocations fresh
        $countWeekly = function () use ($timetable, $section) {
            $counts = [];
            for ($d = 0; $d < SchedulerConfig::DAYS_PER_WEEK; $d++) {
                for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                    $sid = $timetable->slots[$section->id][$d][$p]->subjectId;
                    if ($sid) {
                        $counts[$sid] = ($counts[$sid] ?? 0) + 1;
                    }
                }
            }

            return $counts;
        };

        // Helper: find subject below minimum that can go on this day
        $findUnderMinSubject = function ($day) use ($timetable, $section, $countWeekly) {
            $weeklyCounts = $countWeekly();
            $dailyCounts = [];
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $sid = $timetable->slots[$section->id][$day][$p]->subjectId;
                if ($sid) {
                    $dailyCounts[$sid] = ($dailyCounts[$sid] ?? 0) + 1;
                }
            }

            $best = null;
            $bestDeficit = 0;
            $allSubjects = array_merge(
                $section->compulsorySubjects,
                $section->optionalSubjects,
                $section->coCurricularSubjects ?? []
            );
            foreach ($allSubjects as $candidateId) {
                if (! isset($this->subjects[$candidateId])) {
                    continue;
                }
                $subject = $this->subjects[$candidateId];
                $weeklyCount = $weeklyCounts[$candidateId] ?? 0;
                $dailyCount = $dailyCounts[$candidateId] ?? 0;
                $deficit = $subject->minPeriodsPerWeek - $weeklyCount;
                if ($deficit > 0 && $weeklyCount < $subject->maxPeriodsPerWeek
                    && $dailyCount < SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                    if ($deficit > $bestDeficit) {
                        $bestDeficit = $deficit;
                        $best = $candidateId;
                    }
                }
            }

            return $best;
        };

        // Iterate all 3 passes up to 3 times to catch cascading fixes
        for ($iteration = 0; $iteration < 3; $iteration++) {
            $changed = false;

        // Pass 1: Fix subjects exceeding max weekly periods
        $weeklyCounts = $countWeekly();
        foreach ($weeklyCounts as $subjectId => $count) {
            if (! isset($this->subjects[$subjectId])) {
                continue;
            }
            $subject = $this->subjects[$subjectId];
            $excess = $count - $subject->maxPeriodsPerWeek;
            if ($excess <= 0) {
                continue;
            }

            for ($d = SchedulerConfig::DAYS_PER_WEEK - 1; $d >= 0 && $excess > 0; $d--) {
                for ($p = SchedulerConfig::PERIODS_PER_DAY - 1; $p >= 0 && $excess > 0; $p--) {
                    if ($timetable->slots[$section->id][$d][$p]->subjectId !== $subjectId) {
                        continue;
                    }
                    if ($isProtected($subjectId, $p)) {
                        continue;
                    }

                    // Prefer under-minimum subjects, then general best replacement
                    $newSubjectId = $findUnderMinSubject($d)
                        ?? $this->findBestReplacementSubject($timetable, $section, $d);
                    $timetable->slots[$section->id][$d][$p]->subjectId = $newSubjectId;
                    $timetable->slots[$section->id][$d][$p]->teacherId = $this->assignTeacher($newSubjectId, $d, $p);
                    $excess--;
                    $changed = true;
                }
            }
        }

        // Pass 2: Fix subjects below min weekly periods (swap over-allocated slots)
        $weeklyCounts = $countWeekly();
        $allSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects,
            $section->coCurricularSubjects ?? []
        );
        foreach ($allSubjects as $subjectId) {
            if (! isset($this->subjects[$subjectId])) {
                continue;
            }
            $subject = $this->subjects[$subjectId];
            $weeklyCount = $weeklyCounts[$subjectId] ?? 0;
            $deficit = $subject->minPeriodsPerWeek - $weeklyCount;
            if ($deficit <= 0) {
                continue;
            }

            // Find slots of over-allocated subjects to replace
            $weeklyCounts = $countWeekly(); // Recount fresh
            $isCoCurricular = ($subject->type === SchedulerConfig::TYPE_CO_CURRICULAR);
            for ($d = 0; $d < SchedulerConfig::DAYS_PER_WEEK && $deficit > 0; $d++) {
                // Check daily count of target subject on this day
                $dailyTarget = 0;
                $hasDifferentCoCurricular = false;
                for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                    $slotSid = $timetable->slots[$section->id][$d][$p]->subjectId;
                    if ($slotSid === $subjectId) {
                        $dailyTarget++;
                    }
                    // Check if day already has a different co-curricular subject
                    if ($isCoCurricular && $slotSid && $slotSid !== $subjectId
                        && isset($this->subjects[$slotSid])
                        && $this->subjects[$slotSid]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        $hasDifferentCoCurricular = true;
                    }
                }
                if ($dailyTarget >= SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                    continue;
                }
                // Don't place co-curricular on a day with another co-curricular
                if ($isCoCurricular && $hasDifferentCoCurricular) {
                    continue;
                }

                for ($p = SchedulerConfig::PERIODS_PER_DAY - 1; $p >= 0 && $deficit > 0; $p--) {
                    $existingSid = $timetable->slots[$section->id][$d][$p]->subjectId;
                    if (! $existingSid || $existingSid === $subjectId) {
                        continue;
                    }
                    if ($isProtected($existingSid, $p)) {
                        continue;
                    }
                    if (! isset($this->subjects[$existingSid])) {
                        continue;
                    }

                    // Only replace if existing subject is above its minimum
                    $existingWeekly = $weeklyCounts[$existingSid] ?? 0;
                    if ($existingWeekly <= $this->subjects[$existingSid]->minPeriodsPerWeek) {
                        continue;
                    }

                    $timetable->slots[$section->id][$d][$p]->subjectId = $subjectId;
                    $timetable->slots[$section->id][$d][$p]->teacherId = $this->assignTeacher($subjectId, $d, $p);
                    $deficit--;
                    $dailyTarget++;
                    $weeklyCounts[$subjectId] = ($weeklyCounts[$subjectId] ?? 0) + 1;
                    $weeklyCounts[$existingSid]--;
                    $changed = true;
                }
            }
        }

        // Pass 3: Fix subjects appearing >2 per day
        for ($d = 0; $d < SchedulerConfig::DAYS_PER_WEEK; $d++) {
            $dailyCounts = [];
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $sid = $timetable->slots[$section->id][$d][$p]->subjectId;
                if ($sid) {
                    $dailyCounts[$sid] = ($dailyCounts[$sid] ?? 0) + 1;
                }
            }

            foreach ($dailyCounts as $subjectId => $count) {
                if ($count <= 2 || ! isset($this->subjects[$subjectId])) {
                    continue;
                }
                if ($isProtected($subjectId, -1)) {
                    // co-curricular; skip
                    continue;
                }

                $excess = $count - 2;
                for ($p = SchedulerConfig::PERIODS_PER_DAY - 1; $p >= 0 && $excess > 0; $p--) {
                    if ($timetable->slots[$section->id][$d][$p]->subjectId !== $subjectId) {
                        continue;
                    }
                    if ($isProtected($subjectId, $p)) {
                        continue;
                    }

                    $newSubjectId = $findUnderMinSubject($d)
                        ?? $this->findBestReplacementSubject($timetable, $section, $d);
                    if ($newSubjectId === $subjectId) {
                        continue; // Don't replace with same subject
                    }
                    $timetable->slots[$section->id][$d][$p]->subjectId = $newSubjectId;
                    $timetable->slots[$section->id][$d][$p]->teacherId = $this->assignTeacher($newSubjectId, $d, $p);
                    $excess--;
                    $changed = true;
                }
            }
        }

            if (! $changed) {
                break; // No more fixes possible
            }
        } // end iteration loop
    }

    /**
     * Fix non-co-curricular subjects appearing 2+ times on the same day.
     * Moves one occurrence to a day where the subject is absent or appears less.
     */
    private function fixSubjectDailyBalance($timetable, $section)
    {
        $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];

        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            $subjectPositions = [];
            for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY; $period++) {
                $sid = $timetable->slots[$section->id][$day][$period]->subjectId;
                if ($sid) {
                    $subjectPositions[$sid][] = $period;
                }
            }

            foreach ($subjectPositions as $subjectId => $positions) {
                if (count($positions) < 2) {
                    continue;
                }
                if (! isset($this->subjects[$subjectId])) {
                    continue;
                }
                // Skip co-curricular (allowed 2/day)
                if ($this->subjects[$subjectId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                    continue;
                }

                // Try to swap excess occurrences with a slot on another day
                for ($i = 1; $i < count($positions); $i++) {
                    $sourcePeriod = $positions[$i];
                    $swapped = false;

                    for ($otherDay = 0; $otherDay < SchedulerConfig::DAYS_PER_WEEK && ! $swapped; $otherDay++) {
                        if ($otherDay === $day) {
                            continue;
                        }

                        // Count how many times this subject already appears on otherDay
                        $countOnOther = 0;
                        for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                            if ($timetable->slots[$section->id][$otherDay][$p]->subjectId === $subjectId) {
                                $countOnOther++;
                            }
                        }
                        if ($countOnOther >= 1) {
                            continue; // otherDay already has this subject
                        }

                        // Try all periods on otherDay for a swap candidate
                        for ($targetPeriod = 0; $targetPeriod < SchedulerConfig::PERIODS_PER_DAY && ! $swapped; $targetPeriod++) {
                            $targetSid = $timetable->slots[$section->id][$otherDay][$targetPeriod]->subjectId;

                            // Don't swap co-curricular
                            if ($targetSid && isset($this->subjects[$targetSid]) &&
                                $this->subjects[$targetSid]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                                continue;
                            }

                            // Don't swap physical subjects INTO period 5
                            if ($targetPeriod === SchedulerConfig::BREAK_PERIOD) {
                                $srcSid = $timetable->slots[$section->id][$day][$sourcePeriod]->subjectId;
                                if ($srcSid && isset($this->subjects[$srcSid])) {
                                    $sLower = strtolower($this->subjects[$srcSid]->name);
                                    foreach ($physicalKeywords as $kw) {
                                        if (strpos($sLower, $kw) !== false) {
                                            continue 2;
                                        }
                                    }
                                }
                            }

                            // Check that the target subject won't create a new balance violation on $day
                            if ($targetSid) {
                                $targetCountOnDay = 0;
                                for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                                    if ($timetable->slots[$section->id][$day][$p]->subjectId === $targetSid) {
                                        $targetCountOnDay++;
                                    }
                                }
                                if ($targetCountOnDay >= 1 && isset($this->subjects[$targetSid]) &&
                                    $this->subjects[$targetSid]->type !== SchedulerConfig::TYPE_CO_CURRICULAR) {
                                    continue; // Would create a new balance violation
                                }
                            }

                            // Swap
                            $temp = clone $timetable->slots[$section->id][$otherDay][$targetPeriod];
                            $timetable->slots[$section->id][$otherDay][$targetPeriod] = clone $timetable->slots[$section->id][$day][$sourcePeriod];
                            $timetable->slots[$section->id][$day][$sourcePeriod] = $temp;

                            // Re-assign teachers for availability
                            $timetable->slots[$section->id][$day][$sourcePeriod]->teacherId =
                                $this->assignTeacher($timetable->slots[$section->id][$day][$sourcePeriod]->subjectId, $day, $sourcePeriod);
                            $timetable->slots[$section->id][$otherDay][$targetPeriod]->teacherId =
                                $this->assignTeacher($timetable->slots[$section->id][$otherDay][$targetPeriod]->subjectId, $otherDay, $targetPeriod);

                            $swapped = true;
                        }
                    }
                }
            }
        }
    }

    /**
     * Break up consecutive heavy subjects (math, science, english) by swapping with non-heavy.
     * Avoids swapping physical subjects out of period 5.
     */
    private function fixConsecutiveHeavySubjects($timetable, $section)
    {
        $heavyKeywords = ['math', 'maths', 'mathematics', 'science', 'english'];
        $physicalKeywords = ['sport', 'sports', 'taekwondo', 'dance', 'physical', 'pe'];

        // Run multiple passes to handle chains of 3+ heavy subjects
        for ($pass = 0; $pass < 2; $pass++) {
            for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
                for ($period = 0; $period < SchedulerConfig::PERIODS_PER_DAY - 1; $period++) {
                    $curId = $timetable->slots[$section->id][$day][$period]->subjectId;
                    $nextId = $timetable->slots[$section->id][$day][$period + 1]->subjectId;

                    if (! $curId || ! $nextId || ! isset($this->subjects[$curId]) || ! isset($this->subjects[$nextId])) {
                        continue;
                    }

                    $curHeavy = $this->isHeavySubject($this->subjects[$curId]->name, $heavyKeywords);
                    $nextHeavy = $this->isHeavySubject($this->subjects[$nextId]->name, $heavyKeywords);

                    if (! $curHeavy || ! $nextHeavy) {
                        continue;
                    }

                    // Find a non-heavy, non-co-curricular slot to swap with the second heavy
                    $swapped = false;

                    // Check all periods in the day (after, then before)
                    $candidates = [];
                    for ($s = $period + 2; $s < SchedulerConfig::PERIODS_PER_DAY; $s++) {
                        $candidates[] = $s;
                    }
                    for ($s = 0; $s < $period; $s++) {
                        $candidates[] = $s;
                    }

                    foreach ($candidates as $swap) {
                        if ($swapped) {
                            break;
                        }
                        $swapId = $timetable->slots[$section->id][$day][$swap]->subjectId;
                        if (! $swapId || ! isset($this->subjects[$swapId])) {
                            continue;
                        }
                        if ($this->isHeavySubject($this->subjects[$swapId]->name, $heavyKeywords)) {
                            continue;
                        }
                        if ($this->subjects[$swapId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                            continue;
                        }
                        // Don't swap physical INTO period 5
                        if ($period + 1 === SchedulerConfig::BREAK_PERIOD || $swap === SchedulerConfig::BREAK_PERIOD) {
                            // Check if a physical subject would end up at period 5
                            $checkId = ($swap === SchedulerConfig::BREAK_PERIOD) ? $timetable->slots[$section->id][$day][$period + 1]->subjectId : $swapId;
                            if ($checkId && isset($this->subjects[$checkId])) {
                                $cLower = strtolower($this->subjects[$checkId]->name);
                                $isPhys = false;
                                foreach ($physicalKeywords as $kw) {
                                    if (strpos($cLower, $kw) !== false) {
                                        $isPhys = true;
                                        break;
                                    }
                                }
                                if ($isPhys) {
                                    continue;
                                }
                            }
                        }

                        // Would the swap create a new consecutive-heavy pair?
                        // Check neighbors of swap position after swapping
                        $wouldCreateNew = false;
                        if ($swap > 0) {
                            $neighborId = $timetable->slots[$section->id][$day][$swap - 1]->subjectId;
                            if ($neighborId && isset($this->subjects[$neighborId]) &&
                                $this->isHeavySubject($this->subjects[$neighborId]->name, $heavyKeywords) &&
                                $neighborId !== $nextId) {
                                $wouldCreateNew = true;
                            }
                        }
                        if (! $wouldCreateNew && $swap < SchedulerConfig::PERIODS_PER_DAY - 1) {
                            $neighborId = $timetable->slots[$section->id][$day][$swap + 1]->subjectId;
                            if ($neighborId && isset($this->subjects[$neighborId]) &&
                                $this->isHeavySubject($this->subjects[$neighborId]->name, $heavyKeywords) &&
                                $neighborId !== $nextId) {
                                $wouldCreateNew = true;
                            }
                        }

                        if ($wouldCreateNew) {
                            continue;
                        }

                        // Swap period+1 with swap
                        $temp = clone $timetable->slots[$section->id][$day][$swap];
                        $timetable->slots[$section->id][$day][$swap] = clone $timetable->slots[$section->id][$day][$period + 1];
                        $timetable->slots[$section->id][$day][$period + 1] = $temp;
                        $swapped = true;
                    }
                }
            }
        }
    }

    /**
     * Move co-curricular subjects from early periods (< 4, 1-indexed) to later periods.
     */
    private function fixCoCurricularPlacement($timetable, $section)
    {
        for ($day = 0; $day < SchedulerConfig::DAYS_PER_WEEK; $day++) {
            for ($period = 0; $period < SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD; $period++) {
                $subjectId = $timetable->slots[$section->id][$day][$period]->subjectId;
                if (! $subjectId || ! isset($this->subjects[$subjectId])) {
                    continue;
                }
                if ($this->subjects[$subjectId]->type !== SchedulerConfig::TYPE_CO_CURRICULAR) {
                    continue;
                }

                // Find a later non-co-curricular slot to swap with
                $swapped = false;
                for ($swap = SchedulerConfig::CO_CURRICULAR_PREFERRED_START_PERIOD; $swap < SchedulerConfig::PERIODS_PER_DAY && ! $swapped; $swap++) {
                    $swapId = $timetable->slots[$section->id][$day][$swap]->subjectId;
                    if ($swapId && isset($this->subjects[$swapId]) &&
                        $this->subjects[$swapId]->type === SchedulerConfig::TYPE_CO_CURRICULAR) {
                        continue; // Don't swap with another co-curricular
                    }

                    $temp = clone $timetable->slots[$section->id][$day][$swap];
                    $timetable->slots[$section->id][$day][$swap] = clone $timetable->slots[$section->id][$day][$period];
                    $timetable->slots[$section->id][$day][$period] = $temp;
                    $swapped = true;
                }
            }
        }
    }

    /**
     * Find the best replacement subject that won't violate weekly max or daily limits.
     */
    private function findBestReplacementSubject($timetable, $section, $day)
    {
        $replacementSubjects = array_merge(
            $section->compulsorySubjects,
            $section->optionalSubjects
        );

        // Count weekly allocations
        $weeklyCounts = [];
        for ($d = 0; $d < SchedulerConfig::DAYS_PER_WEEK; $d++) {
            for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
                $sid = $timetable->slots[$section->id][$d][$p]->subjectId;
                if ($sid) {
                    $weeklyCounts[$sid] = ($weeklyCounts[$sid] ?? 0) + 1;
                }
            }
        }

        // Count daily allocations for this day
        $dailyCounts = [];
        for ($p = 0; $p < SchedulerConfig::PERIODS_PER_DAY; $p++) {
            $sid = $timetable->slots[$section->id][$day][$p]->subjectId;
            if ($sid) {
                $dailyCounts[$sid] = ($dailyCounts[$sid] ?? 0) + 1;
            }
        }

        // Score candidates: prefer subjects under weekly max and under daily limit
        $candidates = [];
        foreach ($replacementSubjects as $candidateId) {
            if (! isset($this->subjects[$candidateId])) {
                continue;
            }
            $subject = $this->subjects[$candidateId];
            $weeklyCount = $weeklyCounts[$candidateId] ?? 0;
            $dailyCount = $dailyCounts[$candidateId] ?? 0;

            // Skip if would exceed weekly max
            if ($weeklyCount >= $subject->maxPeriodsPerWeek) {
                continue;
            }
            // Skip if would exceed daily limit
            if ($dailyCount >= SchedulerConfig::MAX_PERIODS_PER_SUBJECT_PER_DAY) {
                continue;
            }

            // Prefer subjects that are under their minimum (need more periods)
            $score = 0;
            if ($weeklyCount < $subject->minPeriodsPerWeek) {
                $score = 10; // High priority — needs more periods
            } elseif ($dailyCount === 0) {
                $score = 5; // Not on this day yet
            } else {
                $score = 1;
            }
            $candidates[] = ['id' => $candidateId, 'score' => $score];
        }

        if (empty($candidates)) {
            // Fallback to random
            return $replacementSubjects[array_rand($replacementSubjects)];
        }

        // Sort by score descending and pick from top candidates
        usort($candidates, fn ($a, $b) => $b['score'] - $a['score']);
        $topScore = $candidates[0]['score'];
        $topCandidates = array_filter($candidates, fn ($c) => $c['score'] === $topScore);
        $picked = $topCandidates[array_rand($topCandidates)];

        return $picked['id'];
    }

    /**
     * Check if a subject name matches heavy subject keywords.
     */
    private function isHeavySubject(string $name, array $keywords): bool
    {
        $lower = strtolower($name);
        foreach ($keywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a random eligible teacher to a subject
     *
     * @param  string  $subjectId  Subject identifier
     * @return string|null Teacher ID or null
     */
    private function assignTeacher($subjectId, $day = null, $period = null)
    {
        $eligibleTeachers = [];
        $availableTeachers = [];

        foreach ($this->teachers as $teacher) {
            if (in_array($subjectId, $teacher->subjects)) {
                $eligibleTeachers[] = $teacher->id;
                if ($day !== null && $period !== null &&
                    $this->constraintChecker->hasTeacherAvailability($teacher->id, $day, $period)) {
                    $availableTeachers[] = $teacher->id;
                }
            }
        }

        // Prefer available teachers
        if (! empty($availableTeachers)) {
            return $availableTeachers[array_rand($availableTeachers)];
        }

        return ! empty($eligibleTeachers) ? $eligibleTeachers[array_rand($eligibleTeachers)] : null;
    }
}
