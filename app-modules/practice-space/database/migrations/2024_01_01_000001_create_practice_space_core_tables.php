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
        // Create room categories table
        Schema::create('practice_space_room_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('default_booking_policy')->nullable()->comment('Default booking policy settings for rooms in this category');
            $table->timestamps();
        });

        // Create rooms table
        Schema::create('practice_space_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->nullable()->constrained('practice_space_room_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity');
            $table->decimal('hourly_rate', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('size_sqft')->nullable();
            $table->json('amenities')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->json('photos')->nullable();
            $table->json('specifications')->nullable();
            $table->json('booking_policy')->nullable()->comment('Room-specific booking policy settings');
            $table->timestamps();
        });

        // Create bookings table with all current fields
        Schema::create('practice_space_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('practice_space_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status')->default('reserved');
            $table->string('state')->default('scheduled');
            $table->timestamp('confirmation_requested_at')->nullable();
            $table->timestamp('confirmation_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_pattern')->nullable();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->decimal('total_price', 8, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->boolean('payment_completed')->default(false);
            $table->text('no_show_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Create room equipment table
        Schema::create('practice_space_room_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('practice_space_rooms')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('condition')->nullable();
            $table->string('status')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->timestamps();
        });

        // Create maintenance schedules table
        Schema::create('practice_space_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('practice_space_rooms')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status')->default('scheduled');
            $table->string('technician_name')->nullable();
            $table->string('technician_contact')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create room favorites table
        Schema::create('practice_space_room_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('practice_space_rooms')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'room_id']);
        });

        // Create waitlist entries table
        Schema::create('practice_space_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('practice_space_rooms')->cascadeOnDelete();
            $table->date('preferred_date');
            $table->dateTime('preferred_start_time');
            $table->dateTime('preferred_end_time');
            $table->boolean('is_flexible')->default(false);
            $table->text('notes')->nullable();
            $table->string('status')->default('waiting');
            $table->dateTime('notification_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_space_waitlist_entries');
        Schema::dropIfExists('practice_space_room_favorites');
        Schema::dropIfExists('practice_space_maintenance_schedules');
        Schema::dropIfExists('practice_space_room_equipment');
        Schema::dropIfExists('practice_space_bookings');
        Schema::dropIfExists('practice_space_rooms');
        Schema::dropIfExists('practice_space_room_categories');
    }
};