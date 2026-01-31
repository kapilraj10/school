<?php

namespace App\Console\Commands;

use App\Models\PageClick;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPageClicks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-clicks:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup and consolidate duplicate page click entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting page clicks cleanup...');

        DB::beginTransaction();

        try {
            // Mapping of old names to correct sidebar names
            $nameMapping = [
                'Class Rooms' => 'Classes',
                'Class Timetable Designer' => 'Timetable Designer',
                'Timetable Settings' => 'General Settings',
                'General Settings' => 'General Settings',
            ];

            // URL corrections
            $urlMapping = [
                '/admin/class-rooms/class-rooms' => '/admin/class-rooms',
                '/admin/teachers/teachers' => '/admin/teachers',
                '/admin/subjects/subjects' => '/admin/subjects',
            ];

            // Get all page clicks
            $allClicks = PageClick::all();

            // Group by normalized URL
            $grouped = $allClicks->groupBy(function ($click) use ($urlMapping) {
                $parsedUrl = parse_url($click->url);
                $normalizedUrl = $parsedUrl['path'] ?? $click->url;

                // Apply URL mapping if exists
                return $urlMapping[$normalizedUrl] ?? $normalizedUrl;
            });

            $merged = 0;
            $deleted = 0;
            $renamed = 0;

            foreach ($grouped as $normalizedUrl => $clicks) {
                if ($clicks->count() > 1) {
                    // Sort by click count descending
                    $sorted = $clicks->sortByDesc('click_count');
                    $totalClicks = $sorted->sum('click_count');

                    // Find the one that already has the normalized URL, or pick the first
                    $primary = $sorted->firstWhere('url', $normalizedUrl) ?? $sorted->first();

                    // Delete all except the primary first
                    $duplicates = $sorted->reject(fn ($c) => $c->id === $primary->id);
                    foreach ($duplicates as $duplicate) {
                        $duplicate->delete();
                        $deleted++;
                    }

                    // Determine correct page name
                    $pageName = $primary->page_name;
                    if (isset($nameMapping[$pageName])) {
                        $pageName = $nameMapping[$pageName];
                        $renamed++;
                    }

                    // Update the primary record with normalized URL and total clicks
                    $primary->update([
                        'page_name' => $pageName,
                        'url' => $normalizedUrl,
                        'click_count' => $totalClicks,
                    ]);

                    $merged++;
                    $this->info("Merged {$clicks->count()} entries for: {$pageName} ({$normalizedUrl}) - total: {$totalClicks} clicks");
                } else {
                    // Just normalize the URL and name if needed
                    $click = $clicks->first();
                    $pageName = $click->page_name;
                    $urlChanged = false;
                    $nameChanged = false;

                    if (isset($nameMapping[$pageName])) {
                        $pageName = $nameMapping[$pageName];
                        $nameChanged = true;
                        $renamed++;
                    }

                    if ($click->url !== $normalizedUrl) {
                        $urlChanged = true;
                    }

                    if ($urlChanged || $nameChanged) {
                        $click->update([
                            'page_name' => $pageName,
                            'url' => $normalizedUrl,
                        ]);
                        $this->info("Normalized: {$pageName} ({$normalizedUrl})");
                    }
                }
            }

            DB::commit();

            $this->info('');
            $this->info('Cleanup completed!');
            $this->info("Merged groups: {$merged}");
            $this->info("Deleted duplicates: {$deleted}");
            $this->info("Renamed entries: {$renamed}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Cleanup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
