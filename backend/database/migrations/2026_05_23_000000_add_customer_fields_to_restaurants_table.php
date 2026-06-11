<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('cuisine_type', 100)->nullable()->after('slug');
            $table->string('location', 255)->nullable()->after('address');
            $table->decimal('rating', 3, 2)->default(0)->after('location');
            $table->time('opening_time')->nullable()->after('rating');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->string('image_url', 500)->nullable()->after('closing_time');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['cuisine_type', 'location', 'rating', 'opening_time', 'closing_time', 'image_url']);
        });
    }
};
