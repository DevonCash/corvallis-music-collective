<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This is a helper migration that can be copied and modified to add state columns to existing tables.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Example: Add state column to bookings table
        // Schema::table('bookings', function (Blueprint $table) {
        //     $table->string('state')->default('scheduled')->after('end_time');
        //     
        //     // Optional: Add timestamp columns for each state if needed
        //     $table->timestamp('scheduled_at')->nullable();
        //     $table->timestamp('confirmed_at')->nullable();
        //     $table->timestamp('checked_in_at')->nullable();
        //     $table->timestamp('completed_at')->nullable();
        //     $table->timestamp('cancelled_at')->nullable();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Example: Remove state column from bookings table
        // Schema::table('bookings', function (Blueprint $table) {
        //     $table->dropColumn([
        //         'state',
        //         'scheduled_at',
        //         'confirmed_at',
        //         'checked_in_at',
        //         'completed_at',
        //         'cancelled_at',
        //     ]);
        // });
    }
}; 