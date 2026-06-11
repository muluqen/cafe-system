<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('motto')->nullable();
            $table->text('banner_message')->nullable();
            $table->foreignId('today_special_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('brand_color', 7)->nullable()->comment('Hex color like #F97316');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->json('operating_hours')->nullable()->comment('Weekly schedule JSON');
            $table->timestamps();

            $table->unique('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_settings');
    }
};
