# Enhanced Timetable Generation Algorithm

## Overview
The enhanced timetable generation algorithm has been integrated into the Filament timetable generator page. This algorithm provides sophisticated scheduling capabilities with improved teacher availability checking, subject distribution, and constraint handling.

## Key Features

### 1. **Three-Phase Generation Process**

#### Phase 1: Minimum Period Assignment
- Assigns minimum required periods for all subjects first
- Prioritizes compulsory/core subjects over electives
- Ensures every subject gets its minimum allocation

#### Phase 2: Maximum Period Assignment
- Fills slots up to maximum periods allowed per subject
- Respects daily and weekly limits
- Balances distribution across the week

#### Phase 3: Remaining Slot Filling
- Fills any remaining empty slots with available subjects
- Ensures complete timetable coverage
- Maintains teacher availability constraints

### 2. **Priority-Based Subject Scheduling**

Subjects are scheduled based on priority:
1. **Core/Compulsory Subjects** (Priority 0) - Scheduled first
2. **Elective Subjects** (Priority 1) - Scheduled second
3. **Co-curricular Activities** (Priority 2) - Scheduled third

### 3. **Smart Constraints**

- **Teacher Availability**: Respects teacher unavailable periods
- **Consecutive Period Avoidance**: Prevents same subject in consecutive periods
- **Daily Load Balancing**: Distributes subjects evenly across the week
- **Daily Subject Limit**: Maximum 2 periods per subject per day (configurable)
- **Teacher Daily Limit**: Respects max_periods_per_day for each teacher

### 4. **Subject Period Configuration**

Each subject now supports:
- `weekly_periods`: Target number of periods per week
- `min_periods_per_week`: Minimum periods that must be assigned
- `max_periods_per_week`: Maximum periods allowed per week

## Configuration Options

When generating a timetable, you can configure:

1. **Respect Teacher Availability** (default: true)
   - Skip periods when teachers are marked as unavailable
   
2. **Avoid Consecutive Subjects** (default: true)
   - Prevent scheduling same subject in back-to-back periods
   
3. **Balance Daily Load** (default: true)
   - Distribute subjects evenly across all days of the week
   
4. **Clear Existing Timetables** (default: true)
   - Remove existing timetables before generating new ones

## Database Changes

### New Subject Fields
A migration has been added to include:
- `min_periods_per_week` (nullable, unsigned tiny integer)
- `max_periods_per_week` (nullable, unsigned tiny integer)

### Migration File
Location: `database/migrations/2025_12_29_120000_add_min_max_periods_to_subjects_table.php`

To run the migration:
```bash
php artisan migrate
```

## Usage

### 1. Configure Subjects
Navigate to **Academic Management > Subjects** and set:
- Weekly Periods (Target)
- Minimum Periods (optional - defaults to weekly_periods)
- Maximum Periods (optional - defaults to weekly_periods + 1)

### 2. Generate Timetable
Go to **Timetable Management > Generate Timetable**:
1. Select the academic term
2. Choose classes to generate for
3. Configure generation settings
4. Review and generate

### 3. View Results
The system will show:
- Total slots generated
- Number of classes processed
- Teachers assigned
- Any warnings or errors encountered

## Algorithm Logic

### Teacher Selection
For each period slot, the algorithm:
1. Finds teachers who can teach the subject
2. Checks teacher availability for that day/period
3. Verifies no conflicting assignments
4. Ensures teacher hasn't exceeded daily limit
5. Selects the first available teacher

### Slot Assignment Priority
Slots are assigned with consideration for:
1. Subject priority (core > elective > co-curricular)
2. Current subject distribution (subjects needing more periods)
3. Daily balance (days with fewer periods get priority)
4. Teacher workload distribution

### Conflict Prevention
The algorithm prevents:
- Teacher double-booking (same teacher, different classes, same time)
- Excessive consecutive periods of same subject
- Over-assignment beyond maximum periods
- Under-assignment below minimum periods

## Benefits Over Previous Implementation

1. **Better Subject Distribution**: Ensures all subjects get fair allocation
2. **Improved Teacher Utilization**: More efficient use of teacher availability
3. **Flexible Configuration**: Min/max periods allow fine-tuning
4. **Enhanced Constraints**: Multiple constraint types working together
5. **Balanced Schedules**: Even distribution prevents overloading specific days
6. **Priority Handling**: Important subjects guaranteed placement

## Troubleshooting

### Warning: "Could only assign X/Y minimum periods"
- **Cause**: Not enough slots or available teachers
- **Solution**: Review teacher availability or increase time slots

### Warning: "No available teacher for subject"
- **Cause**: No teacher can teach subject at that time
- **Solution**: Check teacher subject assignments and availability

### Error: "Teacher has conflicting classes"
- **Cause**: Algorithm detected double-booking
- **Solution**: This should auto-resolve; if persists, clear and regenerate

## Technical Details

### Service Class
`App\Services\TimetableGeneratorService`

### Key Methods
- `generateForClassImproved()`: Main class generation logic
- `assignPeriodsForSubject()`: Subject period assignment
- `findAvailableTeacherImproved()`: Enhanced teacher matching
- `fillRemainingSlots()`: Final slot completion

### Constants
- `PERIODS_PER_DAY`: 8
- `MAX_SUBJECT_CONSECUTIVE`: 1
- `MAX_SUBJECT_PER_DAY`: 2

## Future Enhancements

Potential improvements:
- Lunch break configuration
- Custom period times per class
- Teacher preference weighting
- Subject clustering (related subjects together)
- Room capacity constraints
- Special activity scheduling (assemblies, sports)

## Enhanced Conflict Checker

The conflict checker has been optimized to validate generated timetables against **Class Subject Settings** rules in addition to teacher constraints.

### Validation Categories

#### 1. **Teacher Conflicts** (Critical)
- Detects when a teacher is assigned to multiple classes at the same time
- Shows conflicting classes, subjects, and periods
- Must be resolved for timetable to function

#### 2. **Unavailable Period Violations** (High Priority)
- Identifies when teachers are scheduled during their unavailable times
- Checks against teacher's `available_days` and `available_periods`
- Ensures teacher availability constraints are respected

#### 3. **Overloaded Teachers** (High Priority)
- Detects teachers assigned more periods than their `max_periods_per_week`
- Shows how many periods over the limit each teacher is
- Prevents teacher burnout and excessive workload

#### 4. **Minimum Period Violations** (Class Subject Settings)
- Validates that each subject in each class meets its minimum period requirement
- Compares assigned periods against `min_periods_per_week` in class subject settings
- Shows deficit (how many periods short of minimum)
- Example: "English in Class 1-A: Assigned 3 periods, Required 5 (Deficit: 2)"

#### 5. **Maximum Period Violations** (Class Subject Settings)
- Ensures no subject exceeds its maximum allowed periods per week
- Compares assigned periods against `max_periods_per_week` in class subject settings
- Shows excess (how many periods over maximum)
- Example: "Mathematics in Class 2-B: Assigned 8 periods, Maximum 6 (Excess: 2)"

#### 6. **Combined Period Violations** (Class Subject Settings)
- Validates that combined subjects have adjacent/consecutive periods
- Checks subjects marked as `single_combined = 'combined'`
- Ensures periods are scheduled back-to-back for activities requiring longer time
- Examples: Martial Arts, Lab Work, Project Sessions
- Reports non-adjacent period issues with specific days and period numbers

### How to Use

1. Navigate to **Timetable Management → Conflict Checker**
2. Select an academic term from the dropdown
3. Review the summary statistics showing counts for each violation type
4. Expand each section to see detailed violation information
5. Use the **Recheck** button to refresh after making corrections
6. Export reports when violations need to be shared (coming soon)

### Summary Dashboard

The conflict checker displays a 6-card summary showing:
- Teacher Conflicts (red)
- Unavailable Times (orange)
- Overloaded Teachers (yellow)
- Below Minimum violations (blue)
- Above Maximum violations (purple)
- Combined Period Issues (pink)

### Integration with Class Subject Settings

The enhanced checker uses the **Class Subject Settings Resource** to determine rules:
- Min/Max periods per week per subject per class
- Single vs Combined period requirements
- Active/inactive subject configurations
- Priority levels for scheduling

This ensures the conflict checker validates against the **exact same rules** used during timetable generation, providing comprehensive validation.

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review generation warnings in the UI
3. Verify subject and teacher configuration
4. Ensure database migrations are run
5. Use the Conflict Checker to validate generated timetables

---

**Last Updated**: January 2, 2026
**Version**: 2.1
**Algorithm Source**: Based on `/storage/Algorithm/Algorithm.php`
**Conflict Checker**: `/app/Filament/Pages/ConflictChecker.php`
