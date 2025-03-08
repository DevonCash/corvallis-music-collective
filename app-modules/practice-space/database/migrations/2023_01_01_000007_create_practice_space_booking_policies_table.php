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
        Schema::create('practice_space_booking_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->nullable()->constrained('practice_space_room_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_booking_duration_hours')->nullable();
            $table->integer('min_booking_duration_hours')->nullable();
            $table->integer('max_booking_duration')->nullable()->comment('Alias for max_booking_duration_hours');
            $table->integer('min_booking_duration')->nullable()->comment('Alias for min_booking_duration_hours');
            $table->integer('max_advance_booking_days')->nullable();
            $table->integer('min_advance_booking_hours')->nullable()->comment('Minimum hours in advance a booking must be made');
            $table->text('cancellation_policy')->nullable()->comment('Text description of the cancellation policy');
            $table->integer('cancellation_hours')->nullable()->comment('Hours before start time when cancellation with refund is allowed');
            $table->integer('max_bookings_per_week')->nullable()->comment('Maximum number of bookings a user can make per week');
            $table->decimal('discount_percentage', 5, 2)->nullable()->comment('Default discount percentage for this policy');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_space_booking_policies');
    }
}; 