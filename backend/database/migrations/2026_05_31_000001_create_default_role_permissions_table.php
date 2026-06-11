<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');
            $table->string('entity_key');
            $table->boolean('can_read')->default(false);
            $table->boolean('can_write')->default(false);
            $table->timestamps();

            $table->unique(['role_name', 'entity_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_role_permissions');
    }
};
