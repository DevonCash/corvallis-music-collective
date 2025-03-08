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
        Schema::create('practice_space_booking_policy_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_policy_id')->constrained('practice_space_booking_policies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('overrides')->comment('JSON object containing policy override values');
            $table->text('notes')->nullable()->comment('Reason for the override');
            $table->timestamp('expires_at')->nullable()->comment('When the override expires');
            $table->timestamps();
            
            // Ensure each user only has one override per policy
            $table->unique(['booking_policy_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_space_booking_policy_overrides');
    }
};
