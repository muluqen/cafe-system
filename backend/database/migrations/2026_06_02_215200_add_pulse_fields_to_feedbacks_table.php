<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->text('compliment')->nullable()->after('comment');
            $table->text('complaint')->nullable()->after('compliment');
            $table->text('note')->nullable()->after('complaint');
            $table->json('tags')->nullable()->after('note');
            $table->string('customer_name')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['compliment', 'complaint', 'note', 'tags', 'customer_name']);
        });
    }
};
