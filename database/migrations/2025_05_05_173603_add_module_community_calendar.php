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
        // Create the community_events table
        Schema::create('community_events', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Required for publication
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable(); // Required for publication
            $table->dateTime('end_date')->nullable();
            $table->string('location_name')->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, cancelled, etc.
            $table->boolean('is_online')->default(false);
            $table->string('event_type')->nullable(); // Type of event (e.g., workshop, conference)
            $table->timestamps();
            $table->softDeletes();
        });

        // Add indexes for better performance
        Schema::table('community_events', function (Blueprint $table) {
            $table->index(['user_id']);
            $table->index(['start_date']);
            $table->index(['end_date']);
            $table->index(['status']);
            $table->index(['event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //

        // Drop the community_events table
        Schema::dropIfExists('community_events');
    }
};
