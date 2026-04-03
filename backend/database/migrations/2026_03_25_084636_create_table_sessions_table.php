<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedTinyInteger('guest_count')->default(1);
            $table->string('status', 40)->default('open');
            $table->timestamps();

            $table->index(['table_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
