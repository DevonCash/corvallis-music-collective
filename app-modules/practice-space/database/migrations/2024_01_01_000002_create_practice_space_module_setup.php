<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration handles the module setup and any additional configuration
     * that was needed for the practice-space module initialization.
     */
    public function up(): void
    {
        // The practice-space module setup migration was primarily for
        // service provider registration and module configuration.
        // Since we're consolidating migrations, we'll handle any
        // database-specific setup here if needed.
        
        // Add any indexes or additional constraints that improve performance
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->index(['room_id', 'start_time', 'end_time'], 'idx_bookings_room_time');
            $table->index(['user_id', 'start_time'], 'idx_bookings_user_time');
            $table->index(['status', 'state'], 'idx_bookings_status_state');
        });

        Schema::table('practice_space_rooms', function (Blueprint $table) {
            $table->index(['room_category_id', 'is_active'], 'idx_rooms_category_active');
            $table->index(['is_active', 'hourly_rate'], 'idx_rooms_active_rate');
        });

        Schema::table('practice_space_waitlist_entries', function (Blueprint $table) {
            $table->index(['room_id', 'preferred_date', 'status'], 'idx_waitlist_room_date_status');
        });

        Schema::table('practice_space_maintenance_schedules', function (Blueprint $table) {
            $table->index(['room_id', 'start_time', 'end_time'], 'idx_maintenance_room_time');
            $table->index(['status', 'start_time'], 'idx_maintenance_status_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_room_time');
            $table->dropIndex('idx_bookings_user_time');
            $table->dropIndex('idx_bookings_status_state');
        });

        Schema::table('practice_space_rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_category_active');
            $table->dropIndex('idx_rooms_active_rate');
        });

        Schema::table('practice_space_waitlist_entries', function (Blueprint $table) {
            $table->dropIndex('idx_waitlist_room_date_status');
        });

        Schema::table('practice_space_maintenance_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_maintenance_room_time');
            $table->dropIndex('idx_maintenance_status_time');
        });
    }
};