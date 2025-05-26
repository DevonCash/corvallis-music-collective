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
        Schema::table('productions', function (Blueprint $table) {
            $table->timestamp('planning_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('active_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn([
                'planning_at',
                'published_at',
                'active_at',
                'finished_at',
                'archived_at',
                'rescheduled_at',
                'cancelled_at',
            ]);
        });
    }
}; 