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
        Schema::table('special_events', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('description');
            $table->string('notice_url')->nullable()->after('venue');
            $table->string('notice_link_text')->nullable()->after('notice_url');
            $table->boolean('show_on_home')->default(true)->after('notice_link_text');
            $table->boolean('show_popup')->default(false)->after('show_on_home');
            $table->string('popup_image')->nullable()->after('show_popup');
            $table->string('popup_cloudinary_public_id')->nullable()->after('popup_image');
            $table->string('popup_cloudinary_folder')->nullable()->after('popup_cloudinary_public_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_events', function (Blueprint $table) {
            $table->dropColumn([
                'venue',
                'notice_url',
                'notice_link_text',
                'show_on_home',
                'show_popup',
                'popup_image',
                'popup_cloudinary_public_id',
                'popup_cloudinary_folder',
            ]);
        });
    }
};
