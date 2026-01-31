<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('timetable_settings')->whereIn('key', [
            'break_after_period',
            'break_duration_minutes',
            'period_duration_minutes',
        ])->delete();
    }

    public function down(): void
    {
        DB::table('timetable_settings')->insert([
            [
                'key' => 'period_duration_minutes',
                'value' => '40',
                'type' => 'integer',
                'group' => 'periods',
                'description' => 'Duration of each period in minutes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'break_after_period',
                'value' => '4',
                'type' => 'integer',
                'group' => 'periods',
                'description' => 'Break after this period number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'break_duration_minutes',
                'value' => '20',
                'type' => 'integer',
                'group' => 'periods',
                'description' => 'Duration of break in minutes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
