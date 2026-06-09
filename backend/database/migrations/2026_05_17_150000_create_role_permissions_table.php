<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('staff_role'); 
            $table->string('entity_key'); 
            $table->boolean('can_read')->default(true);
            $table->boolean('can_write')->default(false);
            $table->timestamps();

            $table->unique(['restaurant_id', 'staff_role', 'entity_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
