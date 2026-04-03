<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'restaurant')
            ->whereNull('staff_role')
            ->update(['staff_role' => 'manager']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'restaurant')
            ->where('staff_role', 'manager')
            ->update(['staff_role' => null]);
    }
};
