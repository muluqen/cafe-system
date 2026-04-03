<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 80)->nullable();
            $table->string('status', 40)->default('assigned');
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_shift_assignments');
    }
};
