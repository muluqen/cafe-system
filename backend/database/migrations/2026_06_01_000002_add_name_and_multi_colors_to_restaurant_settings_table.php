<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('restaurant_id');
            $table->json('brand_colors')->nullable()->comment('Primary, secondary, accent hex colors')->after('brand_color');
            $table->dropColumn('brand_color');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->string('brand_color', 7)->nullable()->after('today_special_id');
            $table->dropColumn(['name', 'brand_colors']);
        });
    }
};
