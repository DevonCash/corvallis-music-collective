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
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->string('rrule_string')->nullable()->after('recurring_pattern');
            $table->dateTime('recurrence_end_date')->nullable()->after('rrule_string');
            $table->foreignId('recurring_booking_id')->nullable()->after('recurrence_end_date')
                  ->references('id')->on('practice_space_bookings')
                  ->onDelete('cascade');
            $table->boolean('is_recurring_parent')->default(false)->after('recurring_booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_space_bookings', function (Blueprint $table) {
            $table->dropColumn(['rrule_string', 'recurrence_end_date', 'is_recurring_parent']);
            $table->dropForeign(['recurring_booking_id']);
            $table->dropColumn('recurring_booking_id');
        });
    }
};
