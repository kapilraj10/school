<?php

namespace App\Console\Commands;

use App\Services\ConflictResolverService;
use Illuminate\Console\Command;

class ResolveConflictsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timetable:resolve-conflicts {academic_term_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically resolve timetable conflicts for a given academic term';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $academicTermId = (int) $this->argument('academic_term_id');

        $this->info("Starting conflict resolution for Academic Term ID: {$academicTermId}");

        try {
            $resolver = new ConflictResolverService($academicTermId);

            $this->info('Analyzing conflicts...');
            $result = $resolver->resolveAllConflicts();

            $this->newLine();
            $this->info('=== Conflict Resolution Summary ===');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Started At', $result['started_at']],
                    ['Completed At', $result['completed_at']],
                ]
            );

            $this->newLine();
            $this->info('=== Initial Conflicts ===');
            $this->table(
                ['Type', 'Count'],
                [
                    ['Teacher Conflicts', $result['initial_conflicts']['teacher_conflicts']],
                    ['Unavailable Violations', $result['initial_conflicts']['unavailable_violations']],
                    ['Overloaded Teachers', $result['initial_conflicts']['overloaded_teachers']],
                    ['Combined Period Violations', $result['initial_conflicts']['combined_period_violations']],
                    ['Total', $result['initial_conflicts']['total']],
                ]
            );

            $this->newLine();
            $this->info('=== Final Conflicts ===');
            $this->table(
                ['Type', 'Count'],
                [
                    ['Teacher Conflicts', $result['final_conflicts']['teacher_conflicts']],
                    ['Unavailable Violations', $result['final_conflicts']['unavailable_violations']],
                    ['Overloaded Teachers', $result['final_conflicts']['overloaded_teachers']],
                    ['Combined Period Violations', $result['final_conflicts']['combined_period_violations']],
                    ['Total', $result['final_conflicts']['total']],
                ]
            );

            $improved = $result['initial_conflicts']['total'] - $result['final_conflicts']['total'];
            $percentageImproved = $result['initial_conflicts']['total'] > 0
                ? round(($improved / $result['initial_conflicts']['total']) * 100, 2)
                : 0;

            $this->newLine();
            if ($result['final_conflicts']['total'] === 0) {
                $this->info("✓ All conflicts resolved successfully! ({$improved} conflicts fixed)");
            } elseif ($improved > 0) {
                $this->info("✓ Improved: {$improved} conflicts resolved ({$percentageImproved}%)");
                $this->warn("⚠ Remaining conflicts: {$result['final_conflicts']['total']}");
            } else {
                $this->error('✗ No conflicts could be resolved automatically.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error resolving conflicts: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
