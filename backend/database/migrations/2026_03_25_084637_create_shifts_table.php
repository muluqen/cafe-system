<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 40)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
