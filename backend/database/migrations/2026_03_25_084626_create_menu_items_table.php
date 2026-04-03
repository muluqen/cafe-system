<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('preparation_time_minutes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
