# TimetableValidationService Usage Guide

## Overview

The `TimetableValidationService` provides comprehensive validation for timetable slot assignments and complete timetables. It enforces both hard constraints (errors) and soft constraints (warnings).

## Methods

### 1. validateSlotAssignment()

Validates a single slot assignment before saving it to the database.

```php
use App\Services\TimetableValidationService;

$validator = new TimetableValidationService();

$result = $validator->validateSlotAssignment(
    classRoomId: 1,      // The class room ID
    termId: 1,           // The academic term ID
    subjectId: 5,        // Subject to assign
    teacherId: 3,        // Teacher to assign (nullable)
    day: 1,              // Day of week (1=Monday, 6=Saturday)
    period: 3            // Period number (1-8)
);

if (!empty($result['errors'])) {
    // Handle validation errors - do not save
    foreach ($result['errors'] as $error) {
        echo $error['message'];
    }
}

if (!empty($result['warnings'])) {
    // Handle warnings - can still save, but inform user
    foreach ($result['warnings'] as $warning) {
        echo $warning['message'];
    }
}
```

### 2. validateCompleteTimetable()

Validates an entire timetable for a class and academic term.

```php
$result = $validator->validateCompleteTimetable(
    classRoomId: 1,
    termId: 1
);

if ($result['has_errors']) {
    // Timetable has validation errors
    foreach ($result['errors'] as $error) {
        echo $error['type'] . ': ' . $error['message'];
    }
}

if ($result['has_warnings']) {
    // Timetable has warnings (soft constraints)
    foreach ($result['warnings'] as $warning) {
        echo $warning['type'] . ': ' . $warning['message'];
    }
}
```

### 3. validate() - Legacy Method

The original validation method that accepts a pre-formatted slots array.

```php
$slots = [
    1 => [ // Monday
        1 => ['subject_id' => 5, 'subject_name' => 'Math', 'teacher_id' => 3],
        2 => ['subject_id' => 6, 'subject_name' => 'English', 'teacher_id' => 4],
        // ... more periods
    ],
    2 => [ // Tuesday
        // ... periods
    ],
    // ... more days
];

$result = $validator->validate($slots, $classRoomId, $termId);
```

## Validation Rules

### Hard Requirements (Errors)

These must be satisfied for a valid timetable:

1. **Subject Daily Limit**: Maximum 2 periods per subject per day
2. **Weekly Requirements**: Subject meets min/max weekly period requirements
3. **Co-curricular Rules**:
   - Only 1 co-curricular subject per day
   - Max 2 periods per co-curricular subject per day
   - Co-curricular periods must be consecutive if 2 periods
4. **Teacher Constraints**:
   - No teacher double-booking (same period, different classes)
   - Maximum 6 periods per day per teacher
5. **Timetable Completeness**: All 48 periods should be filled (warning, not error)

### Soft Requirements (Warnings)

Best practices that should be followed when possible:

1. **Subject Repetition**: Subject should appear only once per day (preferred)
2. **Core Subject Placement**: Core subjects should be in consistent period slots daily
3. **Cognitive Load**: Avoid consecutive demanding subjects
4. **Co-curricular Placement**: Co-curricular subjects should be in middle/end periods (4-8)

## Error/Warning Structure

### Error Object
```php
[
    'type' => 'subject_daily_limit',
    'day' => 1,
    'day_name' => 'Monday',
    'period' => 3,
    'subject_id' => 5,
    'message' => 'Maths already appears 2 times on Monday. Maximum 2 periods per day allowed.'
]
```

### Warning Object
```php
[
    'type' => 'daily_repetition',
    'day' => 1,
    'day_name' => 'Monday',
    'period' => 5,
    'message' => 'English already appears on Monday. Consider spreading across different days.',
    'severity' => 'low' // low, medium, high, or info
]
```

## Error Types

| Type | Description |
|------|-------------|
| `subject_daily_limit` | Subject exceeds 2 periods per day |
| `weekly_minimum` | Subject has fewer periods than required |
| `weekly_maximum` | Subject exceeds maximum periods per week |
| `multiple_cocurricular` | More than 1 co-curricular subject per day |
| `cocurricular_period_limit` | Co-curricular exceeds 2 periods per day |
| `cocurricular_not_consecutive` | Co-curricular periods are not consecutive |
| `teacher_conflict` | Teacher double-booked |
| `teacher_workload` | Teacher exceeds 6 periods per day |
| `invalid_subject` | Subject not found |

## Warning Types

| Type | Description |
|------|-------------|
| `empty_slots` | Some timetable slots are empty |
| `daily_repetition` | Subject appears multiple times per day |
| `positional_inconsistency` | Subject appears at varying positions |
| `core_placement_inconsistent` | Core subject not in consistent periods |
| `cognitive_load` | Consecutive demanding subjects |
| `cocurricular_early_placement` | Co-curricular in early periods (1-3) |
| `weekly_minimum_progress` | Subject still below minimum (informational) |

## Usage in Controllers/Livewire

### Example: Livewire Component

```php
use App\Services\TimetableValidationService;

class TimetableDesigner extends Component
{
    protected TimetableValidationService $validator;

    public function boot(TimetableValidationService $validator): void
    {
        $this->validator = $validator;
    }

    public function assignSlot($day, $period, $subjectId, $teacherId): void
    {
        // Validate before saving
        $result = $this->validator->validateSlotAssignment(
            $this->classRoomId,
            $this->termId,
            $subjectId,
            $teacherId,
            $day,
            $period
        );

        if (!empty($result['errors'])) {
            // Show errors to user
            foreach ($result['errors'] as $error) {
                $this->addError('slot', $error['message']);
            }
            return;
        }

        // Save the slot
        TimetableSlot::updateOrCreate([
            'class_room_id' => $this->classRoomId,
            'academic_term_id' => $this->termId,
            'day' => $day,
            'period' => $period,
        ], [
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
        ]);

        // Show warnings if any
        foreach ($result['warnings'] as $warning) {
            session()->flash('warning', $warning['message']);
        }
    }
}
```

### Example: Controller

```php
use App\Services\TimetableValidationService;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableValidationService $validator
    ) {}

    public function validate(Request $request, ClassRoom $classRoom, AcademicTerm $term)
    {
        $result = $this->validator->validateCompleteTimetable(
            $classRoom->id,
            $term->id
        );

        return response()->json($result);
    }

    public function store(Request $request, ClassRoom $classRoom, AcademicTerm $term)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'day' => 'required|integer|min:1|max:6',
            'period' => 'required|integer|min:1|max:8',
        ]);

        $result = $this->validator->validateSlotAssignment(
            $classRoom->id,
            $term->id,
            $validated['subject_id'],
            $validated['teacher_id'],
            $validated['day'],
            $validated['period']
        );

        if (!empty($result['errors'])) {
            return back()->withErrors($result['errors']);
        }

        // Create the slot
        TimetableSlot::create([
            'class_room_id' => $classRoom->id,
            'academic_term_id' => $term->id,
            ...$validated
        ]);

        return back()->with('success', 'Slot assigned successfully');
    }
}
```

## Integration with Frontend

### Example: JavaScript/Alpine.js

```javascript
async function validateSlot(classRoomId, termId, subjectId, teacherId, day, period) {
    const response = await fetch('/api/timetable/validate-slot', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            class_room_id: classRoomId,
            term_id: termId,
            subject_id: subjectId,
            teacher_id: teacherId,
            day: day,
            period: period
        })
    });

    const result = await response.json();

    if (result.errors.length > 0) {
        // Show errors
        result.errors.forEach(error => {
            showError(error.message);
        });
        return false;
    }

    if (result.warnings.length > 0) {
        // Show warnings
        result.warnings.forEach(warning => {
            showWarning(warning.message);
        });
    }

    return true;
}
```

## Testing

The service includes comprehensive tests. Run them with:

```bash
php artisan test --filter=TimetableValidationServiceTest
```

## Dependencies

- Laravel Framework
- Eloquent Models: `Subject`, `Teacher`, `TimetableSlot`, `ClassSubjectSetting`
- Database tables must have proper relationships set up

## Notes

- Days are numbered 1-6 (Monday=1, Saturday=6)
- Periods are numbered 1-8
- Teacher ID can be null if no teacher is assigned yet
- Validation checks existing database records for conflicts
- The service is stateless - create new instance or use dependency injection
