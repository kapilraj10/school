<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('page_name');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->unique('url');
            $table->index('click_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_clicks');
    }
};
