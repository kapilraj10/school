# Timetable Designer Improvements - Implementation Plan

## Overview
Comprehensive improvements to Laravel Livewire timetable designer with validation rules, UI enhancements, and database consistency.

---

## PHASE 1: Simple UI Changes (Can be done in parallel)

### Task 1.1: Hide Saturday Column
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: ~150-160 (weekday loop section)
**Changes**:
- Add condition to skip Saturday (dayOfWeek == 6) in the weekDates loop
- Update grid to show only 6 days instead of 7
**Why**: Requirements specify Sunday-Friday only (6 days)
**Complexity**: Simple
**Depends on**: None

### Task 1.2: Swap Class/Term Selector Positions
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: ~70-90 (top controls section)
**Changes**:
- Move `selectedTermId` select before `selectedClassId` select
- Keep all attributes and classes intact
**Why**: UI preference for better workflow
**Complexity**: Trivial
**Depends on**: None

### Task 1.3: Remove "Today" Button
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: ~103-107
**Changes**:
- Remove the "Today" button and its wire:click="currentWeek"
- Adjust spacing between prev/next week buttons
**Why**: Not needed for recurring weekly view
**Complexity**: Trivial
**Depends on**: None

### Task 1.4: Remove Copy Icons from Table Cells
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: ~207-212
**Changes**:
- Remove the copy icon button (clipboard SVG) from slot display
- Keep only the edit button
**Why**: Simplify UI, copy functionality not needed
**Complexity**: Trivial
**Depends on**: None

### Task 1.5: Add Light/Dark Mode Toggle
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: Add to top controls section (~70)
**Changes**:
- Add Alpine.js component for theme toggle
- Add sun/moon icon button in top-right corner
- Store preference in localStorage
- Toggle 'dark' class on document root
**Why**: User preference for theme switching
**Complexity**: Medium
**Depends on**: None

**Code snippet to add**:
```blade
<div x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggle() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        document.documentElement.classList.toggle('dark', this.darkMode);
    }
}" x-init="document.documentElement.classList.toggle('dark', darkMode)">
    <button @click="toggle()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
        <svg x-show="!darkMode" class="w-5 h-5"><!-- sun icon --></svg>
        <svg x-show="darkMode" class="w-5 h-5"><!-- moon icon --></svg>
    </button>
</div>
```

### Task 1.6: Fit Timetable in Single Window (No Scroll)
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: Multiple - grid container and cell sizing
**Changes**:
- Adjust main container to use `h-[calc(100vh-64px)]` or similar
- Change grid cells from `min-h-[120px]` to flexible height with `flex-1`
- Use `overflow-hidden` instead of `overflow-auto` on timetable container
- Reduce padding/spacing to fit 6 days + 8 periods without scroll
- Use smaller fonts if needed
**Why**: Better UX - see entire timetable at once
**Complexity**: Medium
**Depends on**: Task 1.1 (hiding Saturday helps fit)

### Task 1.7: Show Max Periods/Week in Sidebar
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: ~36-58 (subject card in sidebar)
**Changes**:
- Display subject's `max_periods_per_week` field
- Show format: "Max: X/week" near the slot count
- Add subject type badge (core/elective/co-curricular) with color coding
**Why**: Help users understand constraints while dragging
**Complexity**: Simple
**Depends on**: None

---

## PHASE 2: Database & Data Model Updates

### Task 2.1: Verify Subject Model Fields
**File**: `app/Models/Subject.php`
**Lines**: Already has fields in $fillable
**Changes**:
- Verify `type`, `min_periods_per_week`, `max_periods_per_week` are in fillable array ✓
- Verify casts are correct ✓
- No changes needed - already complete
**Why**: Ensure data consistency for validation
**Complexity**: None (verification only)
**Depends on**: None

### Task 2.2: Verify Teacher Model Fields
**File**: `app/Models/Teacher.php`
**Lines**: Already has fields
**Changes**:
- Verify `max_periods_per_day`, `max_periods_per_week` exist ✓
- No changes needed - already complete
**Why**: Ensure teacher workload tracking works
**Complexity**: None (verification only)
**Depends on**: None

### Task 2.3: Add Helper Methods to Subject Model
**File**: `app/Models/Subject.php`
**Lines**: Add new methods at end
**Changes**:
- Add `isCore()`, `isElective()`, `isCoCurricular()` methods
- Add `getTypeColorClass()` method for UI color coding
- Add `getTypeBadge()` method
**Why**: Clean code, reusable logic for type checking
**Complexity**: Simple
**Depends on**: None

```php
public function isCore(): bool { return $this->type === 'core'; }
public function isElective(): bool { return $this->type === 'elective'; }
public function isCoCurricular(): bool { return $this->type === 'co_curricular'; }

public function getTypeColorClass(): string
{
    return match($this->type) {
        'core' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        'elective' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'co_curricular' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    };
}
```

---

## PHASE 3: Validation Service (Complex - Sequential)

### Task 3.1: Create Validation Service Class
**File**: `app/Services/TimetableValidationService.php` (already exists)
**Changes**:
- Review existing service structure
- Add new method: `validateSlotAssignment()`
- Return structure: `['valid' => bool, 'errors' => [], 'warnings' => []]`
**Why**: Centralize all validation logic
**Complexity**: Medium
**Depends on**: None

### Task 3.2: Implement Hard Validation Rules
**File**: `app/Services/TimetableValidationService.php`
**Method**: `validateSlotAssignment()`
**Changes**:
Implement checks for:
1. **Empty periods check**: Ensure no empty slots if timetable has started
2. **Subject type validation**: Verify subject has correct type field
3. **Max 2 periods/day per subject**: Count existing periods for same subject on same day
4. **Subject weekly min/max**: Count total periods for subject across all days
5. **Co-curricular daily limit**: Only 1 co-curricular subject per day
6. **Co-curricular consecutive**: If co-curricular, max 2 consecutive periods of same subject
7. **Teacher max 6 periods/day**: Count teacher's periods on that day
8. **Teacher conflict**: No same teacher in 2 places at same time
**Why**: Enforce business rules
**Complexity**: High
**Depends on**: Task 3.1

**Method signature**:
```php
public function validateSlotAssignment(
    int $classRoomId,
    int $academicTermId,
    int $subjectId,
    int $teacherId,
    int $day,
    int $period,
    ?int $excludeSlotId = null
): array
```

### Task 3.3: Implement Soft Validation Rules (Warnings)
**File**: `app/Services/TimetableValidationService.php`
**Method**: Add to `validateSlotAssignment()`
**Changes**:
Implement warning checks for:
1. **Subject order consistency**: Check if subject placement breaks pattern across days
2. **Prefer 1 occurrence/day**: Warn if subject already scheduled that day
3. **Core subjects same slots**: Warn if core subject not in consistent time slot
4. **Avoid consecutive demanding**: Check if adjacent periods have demanding subjects
5. **Co-curricular middle/end periods**: Warn if co-curricular in early periods (1-3)
**Why**: Guide users toward better timetable quality
**Complexity**: High
**Depends on**: Task 3.2

### Task 3.4: Add Helper Methods for Validation
**File**: `app/Services/TimetableValidationService.php`
**Changes**:
Add protected methods:
- `getSubjectDailyCount($subjectId, $day, ...)`
- `getSubjectWeeklyCount($subjectId, ...)`
- `getCoCurricularSubjectsOnDay($day, ...)`
- `getTeacherDailyPeriods($teacherId, $day, ...)`
- `getAdjacentPeriods($day, $period, ...)`
- `isConsecutivePeriod($period1, $period2)`
**Why**: Break down complex logic into testable units
**Complexity**: Medium
**Depends on**: Task 3.2

---

## PHASE 4: Integration with Livewire Component

### Task 4.1: Inject Validation Service in TimetableDesigner
**File**: `app/Livewire/TimetableDesigner.php`
**Lines**: Add to top of class
**Changes**:
- Add constructor with TimetableValidationService dependency injection
- Store validation errors in public property: `$validationErrors = []`
- Store validation warnings in public property: `$validationWarnings = []`
**Why**: Use validation service in component
**Complexity**: Simple
**Depends on**: Task 3.1

```php
public $validationErrors = [];
public $validationWarnings = [];

public function __construct()
{
    parent::__construct();
}
```

### Task 4.2: Add Validation to assignPeriod Method
**File**: `app/Livewire/TimetableDesigner.php`
**Method**: `assignPeriod()`
**Lines**: ~147-170
**Changes**:
- Call validation service before saving
- If validation fails, set `$validationErrors` and return without saving
- If has warnings, set `$validationWarnings` but still save
- Dispatch browser event to show validation modal
**Why**: Prevent invalid assignments
**Complexity**: Medium
**Depends on**: Task 4.1, 3.2

```php
public function assignPeriod($subjectId, $teacherId, $date, $period): void
{
    $dayOfWeek = Carbon::parse($date)->dayOfWeek;
    
    $validationService = app(TimetableValidationService::class);
    $result = $validationService->validateSlotAssignment(
        $this->selectedClassId,
        $this->selectedTermId,
        $subjectId,
        $teacherId,
        $dayOfWeek,
        $period
    );
    
    if (!$result['valid']) {
        $this->validationErrors = $result['errors'];
        $this->dispatch('showValidationModal');
        return;
    }
    
    $this->validationWarnings = $result['warnings'];
    
    // ... existing save logic
}
```

### Task 4.3: Add Client-Side Validation on Drag
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: Alpine.js data section (~2-13)
**Changes**:
- Add method to call Livewire validation before drop
- Show visual feedback if invalid drop target
- Only allow drop if validation passes
**Why**: Real-time feedback before save attempt
**Complexity**: Medium
**Depends on**: Task 4.2

**Alpine.js addition**:
```javascript
x-data="{
    draggedSubject: null,
    draggedTeacher: null,
    validatingDrop: false,
    async validateDrop(day, period) {
        this.validatingDrop = true;
        // Call Livewire to validate
        await $wire.validateDrop(this.draggedSubject, this.draggedTeacher, day, period);
        this.validatingDrop = false;
    }
}"
```

---

## PHASE 5: Error Handling & UI Feedback

### Task 5.1: Create Validation Error Modal Component
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: Add new modal section after edit modal (~320)
**Changes**:
- Create modal to display validation errors
- Show list of errors with icon indicators
- Show warnings separately with different color
- Include "Override" option for warnings (if applicable)
- Close button and dismiss on background click
**Why**: User-friendly error display
**Complexity**: Medium
**Depends on**: Task 4.2

**Modal structure**:
```blade
@if (!empty($validationErrors) || !empty($validationWarnings))
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Validation Issues</h3>
                <button wire:click="closeValidationModal">×</button>
            </div>
            
            @if (!empty($validationErrors))
                <div class="mb-4">
                    <h4 class="font-semibold text-red-600 mb-2">Errors (Must Fix)</h4>
                    <ul class="space-y-2">
                        @foreach ($validationErrors as $error)
                            <li class="text-sm text-red-700">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if (!empty($validationWarnings))
                <div>
                    <h4 class="font-semibold text-yellow-600 mb-2">Warnings (Recommendations)</h4>
                    <ul class="space-y-2">
                        @foreach ($validationWarnings as $warning)
                            <li class="text-sm text-yellow-700">• {{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
```

### Task 5.2: Add Visual Drop Zone Indicators
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Lines**: Drop zone cells (~188-240)
**Changes**:
- Add classes to show valid/invalid drop zones on drag
- Green border for valid drops
- Red border for invalid drops
- Use Alpine.js to dynamically check validation
**Why**: Visual guidance during drag operation
**Complexity**: Medium
**Depends on**: Task 4.3

### Task 5.3: Add Method to Close Validation Modal
**File**: `app/Livewire/TimetableDesigner.php`
**Lines**: Add new method
**Changes**:
- Add `closeValidationModal()` method
- Clear `$validationErrors` and `$validationWarnings`
**Why**: Allow users to dismiss modal
**Complexity**: Trivial
**Depends on**: Task 5.1

```php
public function closeValidationModal(): void
{
    $this->validationErrors = [];
    $this->validationWarnings = [];
}
```

---

## PHASE 6: Testing & Polish

### Task 6.1: Create Unit Tests for Validation Service
**File**: `tests/Unit/TimetableValidationServiceTest.php` (new)
**Changes**:
- Test each validation rule independently
- Test edge cases (empty timetable, full timetable, etc.)
- Test warning generation
**Why**: Ensure validation logic is correct
**Complexity**: High
**Depends on**: Task 3.2, 3.3

### Task 6.2: Create Feature Tests for Timetable Designer
**File**: `tests/Feature/TimetableDesignerTest.php` (new)
**Changes**:
- Test drag-drop assignment with valid data
- Test validation error scenarios
- Test that invalid assignments are rejected
- Test that warnings are shown but allow assignment
**Why**: Ensure end-to-end functionality works
**Complexity**: High
**Depends on**: Task 4.2, 5.1

### Task 6.3: Performance Optimization
**File**: `app/Services/TimetableValidationService.php`
**Changes**:
- Cache query results within single validation call
- Use eager loading for relationships
- Consider adding indexes to frequently queried columns
**Why**: Prevent N+1 queries, improve drag-drop responsiveness
**Complexity**: Medium
**Depends on**: Task 3.2

### Task 6.4: Add Loading States
**File**: `resources/views/livewire/timetable-designer.blade.php`
**Changes**:
- Add wire:loading indicators on drag drop
- Show spinner when validating
- Disable interactions during save
**Why**: Better UX, clear feedback
**Complexity**: Simple
**Depends on**: None

---

## PHASE 7: Documentation & Cleanup

### Task 7.1: Update Component Documentation
**File**: Add comments to `app/Livewire/TimetableDesigner.php`
**Changes**:
- Add PHPDoc blocks for all methods
- Document validation flow
- Add example usage comments
**Why**: Maintainability
**Complexity**: Simple
**Depends on**: All previous tasks

### Task 7.2: Run Laravel Pint
**File**: All modified files
**Changes**:
- Run `vendor/bin/pint` to format code
- Fix any style issues
**Why**: Code consistency
**Complexity**: Trivial
**Depends on**: All previous tasks

---

## Summary by Category

### UI Tweaks (Quick Wins - Can Parallelize)
- Tasks 1.1-1.7: Hide Saturday, swap selectors, remove buttons, theme toggle, fit in window, show max periods

### Data Model (Verification + Small Additions)
- Tasks 2.1-2.3: Verify existing fields, add helper methods to models

### Validation Logic (Core Complex Work - Sequential)
- Tasks 3.1-3.4: Create service, implement hard rules, soft rules, helper methods

### Integration (Connect Pieces)
- Tasks 4.1-4.3: Inject service, add validation calls, client-side validation

### Error Handling & Feedback (User Experience)
- Tasks 5.1-5.3: Error modal, visual indicators, dismiss handlers

### Testing & Polish (Quality Assurance)
- Tasks 6.1-6.4: Unit tests, feature tests, performance, loading states

### Documentation (Maintenance)
- Tasks 7.1-7.2: Comments, code formatting

---

## Recommended Execution Order

### Sprint 1 (Quick UI Wins):
1. Tasks 1.1, 1.2, 1.3, 1.4 (trivial removals/swaps) - 1 hour
2. Task 1.7 (show max periods in sidebar) - 1 hour
3. Task 1.5 (dark mode toggle) - 2 hours
4. Task 1.6 (fit without scroll) - 2 hours
**Total: ~6 hours**

### Sprint 2 (Data Model):
1. Tasks 2.1, 2.2 (verification) - 30 mins
2. Task 2.3 (helper methods) - 1 hour
**Total: ~1.5 hours**

### Sprint 3 (Validation Core):
1. Task 3.1 (create service structure) - 2 hours
2. Task 3.4 (helper methods) - 3 hours
3. Task 3.2 (hard rules) - 6 hours
4. Task 3.3 (soft rules) - 4 hours
**Total: ~15 hours**

### Sprint 4 (Integration):
1. Task 4.1 (inject service) - 1 hour
2. Task 4.2 (validation in assignPeriod) - 2 hours
3. Task 4.3 (client-side validation) - 3 hours
**Total: ~6 hours**

### Sprint 5 (Error Handling):
1. Task 5.1 (validation modal) - 3 hours
2. Task 5.2 (visual indicators) - 2 hours
3. Task 5.3 (dismiss handler) - 30 mins
**Total: ~5.5 hours**

### Sprint 6 (Testing & Polish):
1. Task 6.1 (unit tests) - 4 hours
2. Task 6.2 (feature tests) - 4 hours
3. Task 6.3 (performance) - 2 hours
4. Task 6.4 (loading states) - 1 hour
**Total: ~11 hours**

### Sprint 7 (Documentation):
1. Tasks 7.1, 7.2 (docs + formatting) - 1 hour
**Total: ~1 hour**

---

## Total Estimated Effort: ~46 hours

## Critical Path Dependencies:
1. **Validation Service** (3.1-3.4) must be completed before Integration (4.1-4.3)
2. **Integration** (4.1-4.3) must be completed before Error Handling (5.1-5.3)
3. **Error Handling** (5.1-5.3) should be completed before Testing (6.1-6.2)
4. **UI Tasks** (Phase 1) are independent and can be done anytime

## Risk Factors:
- **Validation complexity**: Rules have many edge cases
- **Performance**: Validation on every drag could be slow
- **Testing coverage**: Complex rules need thorough test cases
- **UI responsiveness**: Fitting 6x8 grid without scroll may need careful sizing

## Success Criteria:
✓ All 10 requirements implemented
✓ No invalid timetable slots can be saved
✓ Users see clear error messages
✓ UI fits in single window without scroll
✓ Dark mode works correctly
✓ All tests pass
✓ Code formatted with Pint
