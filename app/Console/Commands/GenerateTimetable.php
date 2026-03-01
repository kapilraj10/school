<?php

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Services\GeneticAlgorithmTimetableService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateTimetable extends Command
{
    protected $signature = 'timetable:generate {progressKey} {paramsKey}';

    protected $description = 'Generate timetables for selected classes in the background';

    public function handle(): int
    {
        $progressKey = $this->argument('progressKey');
        $paramsKey = $this->argument('paramsKey');

        $params = Cache::get($paramsKey);

        if (! is_array($params)) {
            $this->error('No generation parameters found for key: '.$paramsKey);

            return self::FAILURE;
        }

        $classIds = $params['class_ids'] ?? [];
        $termId = (int) ($params['academic_term_id'] ?? 0);
        $clearExisting = (bool) ($params['clear_existing'] ?? true);

        $term = AcademicTerm::find($termId);

        if (! $term) {
            $this->writeProgress($progressKey, [
                'status' => 'failed',
                'error' => 'Academic term not found.',
            ]);

            return self::FAILURE;
        }

        $classes = ClassRoom::whereIn('id', $classIds)
            ->get()
            ->sortBy(fn ($c) => (int) filter_var($c->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($c->section ?? 'A'))
            ->values();

        if ($classes->isEmpty()) {
            $this->writeProgress($progressKey, [
                'status' => 'failed',
                'error' => 'No classes found.',
            ]);

            return self::FAILURE;
        }

        $total = $classes->count();
        $classStatuses = [];

        foreach ($classes as $class) {
            $classStatuses[] = [
                'id' => (int) $class->id,
                'name' => $class->full_name,
                'status' => 'pending',
                'message' => 'Waiting...',
                'slots' => 0,
            ];
        }

        $this->writeProgress($progressKey, [
            'status' => 'running',
            'total' => $total,
            'completed' => 0,
            'success_count' => 0,
            'total_slots' => 0,
            'class_statuses' => $classStatuses,
            'current_class' => null,
            'term_id' => $termId,
            'first_class_id' => $classIds[0] ?? null,
            'warnings' => [],
            'errors' => [],
        ]);

        $successCount = 0;
        $totalSlots = 0;
        $allWarnings = [];
        $allErrors = [];

        foreach ($classes as $index => $class) {
            // Mark as running
            $classStatuses[$index]['status'] = 'running';
            $classStatuses[$index]['message'] = 'Generating...';

            $this->writeProgress($progressKey, [
                'status' => 'running',
                'total' => $total,
                'completed' => $index,
                'success_count' => $successCount,
                'total_slots' => $totalSlots,
                'class_statuses' => $classStatuses,
                'current_class' => $class->full_name,
                'term_id' => $termId,
                'first_class_id' => $classIds[0] ?? null,
                'warnings' => $allWarnings,
                'errors' => $allErrors,
            ]);

            try {
                $result = (new GeneticAlgorithmTimetableService)
                    ->setClearExisting($clearExisting)
                    ->generateTimetable($class, $term, 20, 150);

                if ($result['success']) {
                    $successCount++;
                    $slots = (int) ($result['slots'] ?? 0);
                    $totalSlots += $slots;
                    $classStatuses[$index]['status'] = 'completed';
                    $classStatuses[$index]['message'] = $slots.' slots generated';
                    $classStatuses[$index]['slots'] = $slots;

                    if (! empty($result['warnings'])) {
                        $allWarnings = array_merge($allWarnings, $result['warnings']);
                    }
                } else {
                    $classStatuses[$index]['status'] = 'failed';
                    $classStatuses[$index]['message'] = $result['message'] ?? 'Generation failed';

                    if (! empty($result['errors'])) {
                        $allErrors = array_merge($allErrors, $result['errors']);
                    }
                    if (! empty($result['message'])) {
                        $allErrors[] = $result['message'];
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Timetable generation error for '.$class->full_name.': '.$e->getMessage());
                $classStatuses[$index]['status'] = 'failed';
                $classStatuses[$index]['message'] = 'Error: '.$e->getMessage();
                $allErrors[] = $class->full_name.': '.$e->getMessage();
            }

            // Write progress after each class completes
            $this->writeProgress($progressKey, [
                'status' => 'running',
                'total' => $total,
                'completed' => $index + 1,
                'success_count' => $successCount,
                'total_slots' => $totalSlots,
                'class_statuses' => $classStatuses,
                'current_class' => $class->full_name,
                'term_id' => $termId,
                'first_class_id' => $classIds[0] ?? null,
                'warnings' => $allWarnings,
                'errors' => $allErrors,
            ]);
        }

        // Write final completed status
        $this->writeProgress($progressKey, [
            'status' => 'completed',
            'total' => $total,
            'completed' => $total,
            'success_count' => $successCount,
            'total_slots' => $totalSlots,
            'class_statuses' => $classStatuses,
            'current_class' => null,
            'term_id' => $termId,
            'first_class_id' => $classIds[0] ?? null,
            'warnings' => $allWarnings,
            'errors' => $allErrors,
        ]);

        Log::info("Timetable generation complete: {$successCount}/{$total} classes, {$totalSlots} slots");

        return self::SUCCESS;
    }

    private function writeProgress(string $key, array $data): void
    {
        Cache::put($key, $data, now()->addHours(2));
    }
}
