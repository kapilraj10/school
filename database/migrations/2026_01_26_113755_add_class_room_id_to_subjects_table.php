<?php

use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('class_room_id')->nullable()->after('code')->constrained('class_rooms')->cascadeOnDelete();
        });

        $this->migrateClassRangeData();

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('class_range');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('class_range')->nullable()->after('code');
        });

        // Use database-agnostic approach for updating class_range
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('UPDATE subjects SET class_range = (SELECT "Class " || CAST(REPLACE(name, "Class ", "") AS INTEGER) FROM class_rooms WHERE class_rooms.id = subjects.class_room_id) WHERE class_room_id IS NOT NULL');
        } else {
            DB::statement('UPDATE subjects SET class_range = (SELECT CONCAT("Class ", CAST(REPLACE(name, "Class ", "") AS INTEGER)) FROM class_rooms WHERE class_rooms.id = subjects.class_room_id) WHERE class_room_id IS NOT NULL');
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['class_room_id']);
            $table->dropColumn('class_room_id');
        });
    }

    private function migrateClassRangeData(): void
    {
        $existingSubjects = Subject::whereNotNull('class_range')->get();

        foreach ($existingSubjects as $subject) {
            $classRanges = $this->parseClassRange($subject->class_range);
            $classRooms = ClassRoom::whereBetween(
                DB::raw('CAST(REPLACE(name, "Class ", "") AS INTEGER)'),
                [$classRanges['start'], $classRanges['end']]
            )->get();

            if ($classRooms->isEmpty()) {
                continue;
            }

            $firstClassRoom = $classRooms->first();
            $subject->class_room_id = $firstClassRoom->id;
            $subject->save();

            foreach ($classRooms->skip(1) as $classRoom) {
                Subject::create([
                    'name' => $subject->name,
                    'code' => $this->generateUniqueCode($subject->code, $classRoom),
                    'class_room_id' => $classRoom->id,
                    'type' => $subject->type,
                    'level' => $subject->level,
                    'status' => $subject->status,
                ]);
            }
        }
    }

    private function parseClassRange(string $classRange): array
    {
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $classRange, $matches)) {
            return ['start' => (int) $matches[1], 'end' => (int) $matches[2]];
        }

        if (preg_match('/(\d+)/', $classRange, $matches)) {
            $class = (int) $matches[1];

            return ['start' => $class, 'end' => $class];
        }

        return ['start' => 1, 'end' => 1];
    }

    private function generateUniqueCode(string $baseCode, ClassRoom $classRoom): string
    {
        $classNumber = preg_replace('/[^0-9]/', '', $classRoom->name);
        $section = $classRoom->section;
        $newCode = preg_replace('/-[\d-]+$/', '', $baseCode)."-{$classNumber}{$section}";

        $counter = 1;
        $originalCode = $newCode;
        while (Subject::where('code', $newCode)->exists()) {
            $newCode = "{$originalCode}-{$counter}";
            $counter++;
        }

        return $newCode;
    }
};
