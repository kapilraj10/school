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
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->after('background_image');
            $table->string('cloudinary_folder')->nullable()->after('cloudinary_public_id');
        });

        Schema::table('text_carousel_items', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->after('author_image');
            $table->string('cloudinary_folder')->nullable()->after('cloudinary_public_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_public_id', 'cloudinary_folder']);
        });

        Schema::table('text_carousel_items', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_public_id', 'cloudinary_folder']);
        });
    }
};
