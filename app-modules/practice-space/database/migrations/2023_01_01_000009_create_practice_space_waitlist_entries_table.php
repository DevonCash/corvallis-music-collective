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
    }
}; 