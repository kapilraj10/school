<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->integer('min_periods_per_week')->default(1)->after('weekly_periods');
            $table->integer('max_periods_per_week')->default(1)->after('min_periods_per_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['min_periods_per_week', 'max_periods_per_week']);
        });
    }
};
