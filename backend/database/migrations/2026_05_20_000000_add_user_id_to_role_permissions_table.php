<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            // 1. Drop the old unique constraint
            $table->dropUnique(['restaurant_id', 'staff_role', 'entity_key']);

            // 2. Make staff_role nullable because user-specific permissions don't require a staff_role
            $table->string('staff_role')->nullable()->change();

            // 3. Add the nullable user_id column
            $table->foreignId('user_id')->nullable()->after('restaurant_id')->constrained()->cascadeOnDelete();

            // 4. Create separate unique indexes for role-based and user-based rules
            // We use indexes instead of strict unique constraints for nullable fields to handle SQLite compatibility gracefully
            $table->index(['restaurant_id', 'staff_role', 'entity_key']);
            $table->unique(['restaurant_id', 'user_id', 'entity_key']);
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'user_id', 'entity_key']);
            $table->dropIndex(['restaurant_id', 'staff_role', 'entity_key']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('staff_role')->nullable(false)->change();
            $table->unique(['restaurant_id', 'staff_role', 'entity_key']);
        });
    }
};
