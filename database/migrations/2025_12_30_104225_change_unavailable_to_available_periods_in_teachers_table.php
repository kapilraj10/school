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
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('unavailable_periods');
            $table->json('available_periods')->nullable()->after('max_periods_per_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('available_periods');
            $table->json('unavailable_periods')->nullable()->after('max_periods_per_week');
        });
    }
};
