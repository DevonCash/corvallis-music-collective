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
        // Add booking_policy to rooms
        Schema::table('practice_space_rooms', function (Blueprint $table) {
            $table->json('booking_policy')->nullable()->comment('Room-specific booking policy settings');
        });
        
        // Add default_booking_policy to room categories
        Schema::table('practice_space_room_categories', function (Blueprint $table) {
            $table->json('default_booking_policy')->nullable()->comment('Default booking policy settings for rooms in this category');
        });
        
        // Drop the old booking policy tables
        Schema::dropIfExists('practice_space_booking_policy_overrides');
        Schema::dropIfExists('practice_space_booking_policies');
        
        // Drop the booking status history table
        Schema::dropIfExists('practice_space_booking_status_histories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the new columns
        Schema::table('practice_space_rooms', function (Blueprint $table) {
            $table->dropColumn('booking_policy');
        });
        
        Schema::table('practice_space_room_categories', function (Blueprint $table) {
            $table->dropColumn('default_booking_policy');
        });
        
        // Recreate the old tables
        Schema::create('practice_space_booking_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->nullable()->constrained('practice_space_room_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_booking_duration_hours', 5, 2)->nullable();
            $table->decimal('min_booking_duration_hours', 5, 2)->nullable();
            $table->integer('max_advance_booking_days')->nullable();
            $table->decimal('min_advance_booking_hours', 5, 2)->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->integer('cancellation_hours')->nullable();
            $table->integer('max_bookings_per_week')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('practice_space_booking_policy_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_policy_id')->constrained('practice_space_booking_policies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('overrides')->nullable();
            $table->timestamps();
        });
        
        // Recreate the booking status history table
        Schema::create('practice_space_booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('status');
            $table->string('previous_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
}; 